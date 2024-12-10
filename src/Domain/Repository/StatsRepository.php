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

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            JOIN wallets AS a ON e.account_id = a.id
            WHERE e.type IN ('expenses', 'incoming')
              AND e.exclude_from_stats = false
              AND a.exclude_from_stats = false
              AND e.confirmed = true
              AND e.planned = false
              AND e.date_time >= ?
              AND e.date_time < ?
              AND a.workspace_id = ?
              AND a.deleted_at IS NULL
              AND e.deleted_at IS NULL;
        ";

        $result = DB::select($query, [
            $this->startDate->toAtomString(),
            $this->endDate->toAtomString(),
            $wsId,
        ]);

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
            WHERE workspace_id = ?
              AND deleted_at IS NULL
              AND exclude_from_stats = false;
        ";

        $result = DB::select($query, [$wsId]);

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
            WHERE workspace_id = ?
              AND deleted_at IS NULL
              AND exclude_from_stats = false;
        ";

        $result = DB::select($query, [$wsId]);

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
        COALESCE(SUM(CASE WHEN a.installement = 1  and a.balance < 0 THEN a.installement_value END), 0) AS installement_balance,
        COALESCE(SUM(CASE WHEN a.installement = 0 THEN a.balance END), 0) AS balance_without_installement,
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
                AND MONTH(date_time) = MONTH(CURRENT_DATE())
                AND YEAR(date_time) = YEAR(CURRENT_DATE())
                AND confirmed = true
                AND deleted_at IS NULL
                AND exclude_from_stats = false
                AND workspace_id = $wsId
            GROUP BY 
                account_id
        ) AS e ON a.id = e.account_id
        WHERE 
            a.deleted_at IS NULL
            AND a.exclude_from_stats = false
            AND a.installement = false
            AND a.workspace_id = $wsId;
        ";

        $result = DB::select($query);

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
        $query = "select installement_value from wallets where installement = true and deleted_at is null and invoice_date >= '$date'  AND MONTH(invoice_date) = MONTH(CURRENT_DATE()) and workspace_id = $wsId and balance < installement_value;";
        $result = DB::select($query);

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
                AND MONTH(e.date_time) = MONTH(CURRENT_DATE())
                AND YEAR(e.date_time) = YEAR(CURRENT_DATE())
                AND e.confirmed = true
                AND e.deleted_at IS NULL
                AND e.exclude_from_stats = false
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

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
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND e.workspace_id = $wsId
                AND e.type IN ('expenses', 'incoming')
                $addJoins
            GROUP BY 
                cc.type, c.name, c.id
                ) as query
            WHERE 
                query.category_type in ('incoming','expenses')
                $addConditions
            ORDER BY
                query.category_type desc;";

        $result = DB::select($query);

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
     * @param int $isPlanned (optional) Whether the statistics are planned or not. Default is 0.
     * @return stdClass
     */
    public function statsByCategories(string $categorySlug, bool $isplanned = false): stdClass
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();
        $isplanned = $isplanned ? true : false;

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
                AND e.planned in (false,$isplanned)
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND e.workspace_id = $wsId
                AND e.type IN ('expenses', 'incoming')
            WHERE 
                c.slug = '$categorySlug'
            GROUP BY 
                cc.type, c.name, c.id
            ORDER BY
                cc.type desc;";

        $result = DB::select($query);

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
                AND MONTH(e.date_time) = MONTH(CURRENT_DATE())
                AND YEAR(e.date_time) = YEAR(CURRENT_DATE())
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
