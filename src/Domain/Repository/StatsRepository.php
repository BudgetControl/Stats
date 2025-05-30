<?php

namespace Budgetcontrol\Stats\Domain\Repository;

use Brick\Math\BigNumber;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Stats\Domain\Model\Wallet;
use DateTime;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Carbon\Carbon;
use stdClass;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class StatsRepository
{

    protected int $wsId;
    protected Carbon $startDate;
    protected Carbon $endDate;

    /**
     * StatsRepository constructor.
     *
     * @param string $wsId The ID of the workspace.
     * @param Carbon $startDate The start date for the stats.
     * @param Carbon $endDate The end date for the stats.
     */
    public function __construct(string $wsId, Carbon $startDate, Carbon $endDate)
    {
        $wsid = @Workspace::where('uuid', $wsId)->first()->id;

        if (is_null($wsid)) { 
            throw new NotFoundResourceException('Workspace not found', 404);
        }

        $this->wsId = $wsid;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Retrieves the total stats.
     *
     * @return array The total stats.
     */
    public function statsTotal()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            JOIN wallets AS a ON e.account_id = a.id
            WHERE e.type in ('expenses', 'incoming')
            AND e.exclude_from_stats = false
            AND a.exclude_from_stats = false
            AND a.installement = false
            AND a.deleted_at is null
            AND e.deleted_at is null
            AND e.confirmed = true
            AND a.archived = false
            AND e.planned = false
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND a.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    /**
     * Returns the total value.
     *
     * @return array The total value.
     */
    public function total()
    {
        $wsId = $this->wsId;

        $query = "
            SELECT COALESCE(SUM(balance), 0) AS total_balance
            FROM wallets
            WHERE workspace_id = $wsId
            AND installement = false
            AND deleted_at is null
            AND archived = false
            AND exclude_from_stats = false;
        ";

        $result = DB::select($query);

        return [
            'total' => (float) $result[0]->total_balance
        ];
    }

    /**
     * Retrieves the wallets from the repository.
     *
     * @return array An array of wallets.
     */
    public function wallets()
    {
        $wsId = $this->wsId;

        $wallets = Wallet::with('currency')->where('workspace_id', $wsId)
            ->where('deleted_at', null)
            ->where('archived', false)
            ->get();
        
        return $wallets->toArray();
    }

    /**
     * Checks the health of the repository.
     *
     * @return array
     */
    public function health()
    {

        $wsId = $this->wsId;

        $query = "
            SELECT COALESCE(SUM(balance), 0) AS total_balance
            FROM wallets
            WHERE workspace_id = $wsId AND deleted_at is null AND archived = false AND exclude_from_stats = false;
        ";

        $result = DB::select($query);

        $totalPlanned = $this->totalPlannedOfCurrentMonth();

        $total = BigNumber::sum($result[0]->total_balance, $totalPlanned['total'])->toFloat();
        return [
            'total' => $total
        ];
    }

    /**
     * Calculates the total with planned value for the current month.
     *
     * @return stdClass The total value with planned for the current month.
     */
    public function totalWithPlannedOfCurrentMonth()
    {
        $wsId = $this->wsId;

        $query = "
        SELECT 
        COALESCE(SUM(CASE WHEN a.installement = true  and a.balance < 0 THEN a.installement_value END), 0) AS installement_balance,
        COALESCE(SUM(CASE WHEN a.installement = false THEN a.balance END), 0) AS balance_without_installement,
        COALESCE(SUM(CASE WHEN e.planned = true THEN e.amount END), 0) AS planned_amount_total
        FROM 
            wallets AS a
        LEFT JOIN (
            SELECT 
                account_id,
                planned,
                SUM(amount) AS amount
            FROM 
                entries
            WHERE 
                planned = true
                AND EXTRACT(MONTH FROM date_time) = EXTRACT(MONTH FROM CURRENT_DATE)
                AND EXTRACT(YEAR FROM date_time) = EXTRACT(YEAR FROM CURRENT_DATE)

                AND confirmed = true
                AND deleted_at IS NULL
                AND exclude_from_stats = false
                AND workspace_id = ?
            GROUP BY 
                account_id, planned
        ) AS e ON a.id = e.account_id
        WHERE 
            a.deleted_at IS NULL
            AND a.archived = false
            AND a.exclude_from_stats = false
            AND a.installement = false
            AND a.workspace_id = ?;
        ";

        $result = DB::select($query, [$wsId, $wsId]);

        return $result[0];
    }

    /**
     * Retrieves the installment values.
     *
     * @return array The installment values.
     */
    public function installementValues()
    {
        $wsId = $this->wsId;

        $date = Carbon::now()->toAtomString();
        $query = "
        select installement_value from wallets where installement = true 
        and deleted_at is null and invoice_date >= ? 
        AND archived = false
        AND EXTRACT(MONTH FROM invoice_date) = EXTRACT(MONTH FROM CURRENT_DATE)
        and workspace_id = ? and balance < installement_value;
        ";

        $result = DB::select($query, [$date, $wsId]);

        return $result;
    }

    /**
     * Returns the total planned of the current month.
     *
     * @return int The total planned of the current month.
     */
    public function totalPlannedOfCurrentMonth()
    {
        $wsId = $this->wsId;

        $query = "
            SELECT 
                COALESCE(SUM(CASE WHEN e.planned = true THEN e.amount END), 0) AS planned_amount_total
            FROM 
                entries AS e
            WHERE 
                e.planned = true
                AND EXTRACT(MONTH FROM e.date_time) = EXTRACT(MONTH FROM CURRENT_DATE)
                AND EXTRACT(YEAR FROM e.date_time) = EXTRACT(YEAR FROM CURRENT_DATE)
                AND e.confirmed = true
                AND e.deleted_at IS NULL
                AND e.exclude_from_stats = false
                AND e.workspace_id = ?;
        ";

        $result = DB::select($query, [$wsId]);

        return [
            'total' => $result[0]->planned_amount_total
        ];
    }

    /**
     * Retrieves statistics based on the provided filters.
     *
     * @param array $options An array of filters to apply.
     * @return array An array containing the statistics data.
     */
    public function statsByFilters(array $options)
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $addConditions = '';
        $addJoins = '';
        if (!empty($options['categories'])) {
            $addConditions .= " AND query.category_id IN ('" . implode("','", $options['categories']) . "')";
        }

        if (!empty($options['accounts'])) {
            $addJoins .= " AND e.account_id IN ('" . implode("','", $options['accounts']) . "')";
        }

        if (!empty($options['payment_methods'])) {
            $addJoins .= " AND e.payment_type IN ('" . implode("','", $options['payment_methods']) . "')";
        }

        if (!empty($options['currencies'])) {
            $addJoins .= " AND e.currency_id IN ('" . implode("','", $options['currencies']) . "')";
        }

        if(!empty($options['tags'])) {
            $tags = $this->entriesFromTags($options['tags']);
            $entries = array_map(function($entry) {
                return $entry->id;
            }, $tags);
            $entries = implode(',', $entries);
            $entries = str_replace(',,','',$entries); // Work Around fixme:
            if(!empty($entries)) {
                $addJoins .= " AND e.id in ($entries)";
            }
        }

        $query = "select * from (
            SELECT 
                c.uuid AS category_uuid,
                cc.type AS category_type,
                c.slug AS category_slug,
                COALESCE(SUM(e.amount), 0) AS total,
                c.id AS category_id
            FROM 
                sub_categories AS c
            JOIN 
                categories AS cc ON c.category_id = cc.id
            LEFT JOIN 
                entries AS e ON e.category_id = c.id
                AND e.exclude_from_stats = false
                AND e.deleted_at IS NULL
                AND e.confirmed = true
                AND e.planned = false
                AND e.date_time >= :startDate
                AND e.date_time < :endDate
                AND e.workspace_id = :wsId
                AND e.type IN ('expenses', 'incoming', 'debit')
                $addJoins
            GROUP BY 
                cc.type, c.name, c.id, c.uuid, c.slug
                ) as query
            WHERE 
                query.category_type in ('incoming','expenses', 'debit')
                $addConditions
            ORDER BY
                query.category_type desc;";

        $result = DB::select($query, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'wsId' => $wsId
        ]);

        return $result;
    }

    /**
     * Retrieves entries from the repository based on the given tags.
     *
     * @param array $tags The tags to filter the entries by.
     * @return array The array of entries matching the given tags.
     */
    protected function entriesFromTags(array $tags): array
    {
        $query = "select entries.* from entries
        right join entry_labels on entries.id = entry_labels.entry_id
        right join labels on entry_labels.labels_id = labels.id
        where labels.id in (".implode(',', $tags).") AND entries.deleted_at IS NULL;";
        $results = DB::select($query);

        if(empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Retrieves statistics by category slug.
     *
     * @param string $categorySlug The slug of the category.
     * @param bool $isPlanned (optional) Whether the statistics are planned or not. Default is 0.
     * @return stdClass
     */
    public function statsByCategories(string $categorySlug, bool $isPlanned = false): stdClass
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();
        $andPlanned = '';
        if($isPlanned) {
            $andPlanned = "AND e.planned = true";
        }

        $query = "
            SELECT 
                c.uuid AS category_uuid,
                cc.type AS category_type,
                c.slug AS category_slug,
                COALESCE(SUM(e.amount), 0) AS total,
                c.id AS category_id
            FROM 
                sub_categories AS c
            JOIN 
                categories AS cc ON c.category_id = cc.id
            LEFT JOIN 
                entries AS e ON e.category_id = c.id
                AND e.exclude_from_stats = false
                AND e.deleted_at IS NULL
                AND e.confirmed = true
                $andPlanned
                AND e.date_time >= :startDate
                AND e.date_time < :endDate
                AND e.workspace_id = :wsId
                AND e.type IN ('expenses', 'incoming')
            WHERE 
                c.slug = :categorySlug
            GROUP BY 
                cc.type, c.name, c.id, c.uuid, c.slug
            ORDER BY
                cc.type desc;";

        $result = DB::select($query, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'wsId' => $wsId,
            'categorySlug' => $categorySlug
        ]);

        return $result[0];
    }

    /**
     * Retrieves the loan of credit cards.
     *
     * @return mixed The loan of credit cards.
     */
    public function loanOfCreditCards()
    {
        $wsId = $this->wsId;

        $walletsType = [EntityWallet::creditCard->value, EntityWallet::creditCardRevolving->value];

        $query = "
            SELECT 
                a.invoice_date,
                a.installement_value,
                a.balance
            FROM 
                wallets AS a
            WHERE 
                a.deleted_at IS NULL
                AND a.exclude_from_stats = false
                AND a.archived = false
                AND ( 
                    a.type = '".$walletsType[0]."'
                    OR a.type = '".$walletsType[1]."' 
                )
                AND a.balance < 0
                AND a.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return $result;
    }

    /**
     * Retrieves the planned entries from the stats repository.
     *
     * @return stdClass
     */
    public function plannedExpenses(): stdClass {
        $wsId = $this->wsId;

        $query = "
            SELECT 
                COALESCE(SUM(CASE WHEN e.planned = true THEN e.amount END), 0) AS total
            FROM 
                entries AS e
            WHERE 
                e.planned = true
                AND EXTRACT(MONTH FROM e.date_time) = EXTRACT(MONTH FROM CURRENT_DATE)
                AND EXTRACT(YEAR FROM e.date_time) = EXTRACT(YEAR FROM CURRENT_DATE)
                AND e.confirmed = true
                AND e.deleted_at IS NULL
                AND e.exclude_from_stats = false
                AND e.type IN ('expenses')
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return $result[0];
    }
}
