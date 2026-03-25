<?php

namespace Budgetcontrol\Stats\Domain\Repository;

use Brick\Math\BigNumber;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Stats\Domain\Model\Wallet;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\DebitRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\ExpensesRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\IncomingRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\PlannedEntryRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Budgetcontrol\Stats\Domain\Repository\SavingRepository;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Carbon\Carbon;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class StatsRepository extends SavingRepository implements StatsRepositoryInterface, IncomingRepoInterface, ExpensesRepoInterface, DebitRepoInterface, PlannedEntryRepoInterface
{

    /**
     * Retrieves the total stats.
     *
     * @return array The total stats.
     * @deprecated this function will be removed to the next release
     */
    public function statsTotal(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        return [
            'total' => $agregator->total_amount ?? 0.0
        ];
    }

    /**
     * Returns the total value.
     *
     * @return array The total value.
     */
    public function total(): array
    {
        $wallets = $this->wallets();
        $walletIds = array_map(function($wallet) {
            return $wallet['id'];
        }, $wallets);

        $query = "
            SELECT COALESCE(SUM(wallet_balance), 0) AS total_balance
            FROM aggregated_balances
            WHERE id IN (" . implode(',', $walletIds) . ");
        ";

        $result = DB::select($query);

        return [
            'total' => (float) $total
        ];
    }

    /**
     * Retrieves the wallets from the repository.
     *
     * @param \Budgetcontrol\Library\Entity\Wallet|null $walletType The type of wallet to filter by.
     * @return array An array of wallets.
     */
    public function wallets(EntityWallet $walletType = null)
    {
        $wsId = $this->wsId;

        $wallets = Wallet::with('currency')->where('workspace_id', $wsId)
            ->where('deleted_at', null)
            ->where('archived', false);

        if (!is_null($walletType)) {
            $wallets = $wallets->where('type', $walletType->value);
        }

        $wallets = $wallets->get();
        return $wallets->toArray();
    }

    /**
     * Checks the health of the repository.
     *
     * @return array
     */
    public function health()
    {

        $walletsBalance = $this->total();
        $totalPlanned = $this->totalPlannedOfCurrentMonth();

        $total = BigNumber::sum($walletsBalance['total'], $totalPlanned['total'])->toFloat();
        return [
            'total' => $total
        ];
    }

    /**
     * Calculates the total with planned value for the current month.
     *
     * @return \stdClass The total value with planned for the current month.
     * TODO: refactor this function with the new agregated view
     */
    public function totalWithPlannedOfCurrentMonth(): \stdClass
    {
        $wallets = Wallet::where('workspace_id', $this->wsId)
            ->whereNull('deleted_at')
            ->where('archived', false)
            ->where('exclude_from_stats', false)
            ->get();

        $installementBalance = 0.0;
        $balanceWithoutInstallement = 0.0;

        foreach ($wallets as $wallet) {
            if ($wallet->installement && $wallet->balance < 0) {
                $installementBalance += (float) ($wallet->installement_value ?? 0);
            } else {
                $balanceWithoutInstallement += (float) $wallet->balance;
            }
        }

        $filters = ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setMonth(now()->month)
            ->setYear(now()->year)
            ->setPlanned(true);

        $agregator = ElasticAggregator::create($filters)->totalAmount();
        $results = $this->client->aggregate($agregator);
        $plannedTotal = !empty($results) ? ($results[0]->aggregations()->total ?? 0.0) : 0.0;

        $result = new \stdClass();
        $result->installement_balance = $installementBalance;
        $result->balance_without_installement = $balanceWithoutInstallement;
        $result->planned_amount_total = $plannedTotal;

        return $result;
    }



    /**
     * Retrieves the planned entries of the current period.
     */
    public function plannedOfPeriod(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
        SELECT
                COALESCE(SUM(
                    CASE 
                        WHEN a.installement = true AND ab.wallet_balance < 0 
                        THEN a.installement_value
                    END
                ), 0) AS installement_balance,
                COALESCE(SUM(
                    CASE 
                        WHEN a.installement = false 
                        THEN ab.wallet_balance
                    END
                ), 0) AS balance_without_installement,
                COALESCE(SUM(e.amount), 0) AS planned_amount_total
            FROM wallets a
            LEFT JOIN aggregated_balances ab ON ab.account_id = a.id
            LEFT JOIN (
                SELECT 
                    account_id,
                    SUM(amount) AS amount
                FROM entries
                WHERE planned = true
                AND EXTRACT(MONTH FROM date_time) = EXTRACT(MONTH FROM CURRENT_DATE)
                AND EXTRACT(YEAR FROM date_time) = EXTRACT(YEAR FROM CURRENT_DATE)
                AND confirmed = true
                AND deleted_at IS NULL
                AND exclude_from_stats = false
                AND workspace_id = ?
                GROUP BY account_id
            ) AS e ON a.id = e.account_id
            WHERE a.deleted_at IS NULL
            AND a.archived = false
            AND a.exclude_from_stats = false
            AND a.workspace_id = ?;
        ";

        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return ['total' => 0.0];
        }

        return [
            'total' => $results[0]->aggregations()->total ?? 0.0
        ];
    }

    /**
     * Retrieves the installment values.
     *
     * @return array The installment values.
     */
    public function currentInstallmentValues(): array|Collection
    {
        $wsId = $this->wsId;

        $walletTypes = [
            EntityWallet::creditCardRevolving->value
        ];

        $wallets = Wallet::where('workspace_id', $wsId)
            ->where('deleted_at', null)
            ->where('archived', false)
            ->whereIn('type', $walletTypes)
            ->get();

        if ($wallets->isEmpty()) {
            return [];
        }

        return $wallets->toArray();
    }


    /**
     * Returns the total planned of the current month.
     *
     * @return array The total planned of the current month.
     */
    public function totalPlannedOfCurrentMonth(): array
    {
        $filters = ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setMonth(now()->month)
            ->setYear(now()->year)
            ->setPlanned(true);

        $agregator = ElasticAggregator::create($filters)->totalAmount();
        $results = SearchService::aggregate($agregator);

        return [
            'total' => !empty($results) ? ($results[0]->aggregations()->total ?? 0.0) : 0.0
        ];
    }

    /**
     * Retrieves statistics based on the provided filters.
     *
     * @param array $options An array of filters to apply.
     * @return array An array containing the statistics data.
     */
    public function statsByFilters(array $options): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate);

        if (!empty($options['categories'])) {
            $filters->setCategories($options['categories']);
        }

        if (!empty($options['accounts'])) {
            $filters->setWalletIds($options['accounts']);
        }

        if (!empty($options['payment_methods'])) {
            $filters->setPaymentType($options['payment_methods'][0]);
        }

        if (!empty($options['currencies'])) {
            $filters->setCurrency($options['currencies'][0]);
        }

        if (!empty($options['tags'])) {
            $filters->setTags($options['tags']);
        }

        $agregator = ElasticAggregator::create($filters)->groupByCategory();
        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return [];
        }

        $entries = [];
        foreach ($results as $value) {
            $aggregation = $value->aggregations();
            $entry = new \stdClass();
            $entry->total = $aggregation->total ?? 0.0;
            $entry->category_slug = $aggregation->category_slug ?? null;
            $entry->category_id = $aggregation->category_id ?? null;
            $entry->category_uuid = null;
            $entry->category_type = null;
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Retrieves entries from the repository based on the given tags.
     *
     * @param array $tags The tags to filter the entries by.
     * @return array The array of entries matching the given tags.
     */
    protected function entriesFromTags(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $filters = ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setTags($tags);

        return SearchService::search($filters);
    }

    /**
     * Retrieves statistics by category slug.
     *
     * @param string $categorySlug The slug of the category.
     * @param bool $isPlanned (optional) Whether the statistics are planned or not. Default is false.
     * @return \stdClass
     */
    public function statsByCategories(string $categorySlug, bool $isPlanned = false): \stdClass
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate);

        if ($isPlanned) {
            $filters->setPlanned(true);
        }

        $agregator = ElasticAggregator::create($filters)->groupByCategory();
        $results = SearchService::aggregate($agregator);

        $matched = array_filter($results, fn($r) => $r->aggregations()->category_slug === $categorySlug);
        $first = !empty($matched) ? array_values($matched)[0]->aggregations() : null;

        $result = new \stdClass();
        $result->category_uuid = null;
        $result->category_type = null;
        $result->category_slug = $categorySlug;
        $result->total = $first->total ?? 0.0;
        $result->category_id = $first->category_id ?? null;

        return $result;
    }

    /**
     * Retrieves the loan of credit cards.
     *
     * @return mixed The loan of credit cards.
     */
    public function loanOfCreditCards()
    {
        $wsId = $this->wsId;

        $walletsCreditCardsRevolving = $this->wallets(EntityWallet::creditCardRevolving);
        $walletCreditCard = $this->wallets(EntityWallet::creditCard);
        $wallets = array_merge($walletsCreditCardsRevolving, $walletCreditCard);

        $query = "
            SELECT 
                ab.invoice_date,
                ab.installement_value,
                ab.wallet_balance
            FROM 
                aggregated_balances AS ab
            WHERE 
                ab.wallet_balance < 0
                AND ab.wallet_id IN (" . implode(',', array_map(function($wallet) {
                    return $wallet['id'];
                }, $wallets)) . ")
        ";

        $result = DB::select($query);

        return $result;
    }

    /**
     * Retrieves the planned entries from the stats repository.
     *
     * @return \stdClass
     */
    public function plannedExpenses(): \stdClass {
        $wsId = $this->wsId;

        $agregator = ElasticAggregator::create($filters)->totalAmount();
        $results = SearchService::aggregate($agregator);

        $result = new \stdClass();
        $result->total = !empty($results) ? ($results[0]->aggregations()->total ?? 0.0) : 0.0;

        return $result;
    }


    /**
     * Retrieves statistics for savings.
     *
     * @return array An array containing the statistics for savings.
     */
    public function statsSevings(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(Entry::saving->value)
            ->setConfirmed(true)
            ->setPlanned(false);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return ['total' => 0.0];
        }

        return [
            'total' => $results[0]->aggregations()->total ?? 0.0
        ];
    }

    // ============ StatsRepositoryInterface Implementation ============
    // Note: Many methods are implemented in child classes or need to be implemented

    public function statsExpenses(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return [];
        }

        return [
            'total' => $results[0]->aggregations()->total ?? 0.0
        ];
    }

    public function statsIncoming(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(type: Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        return [
            'total' => $results->total_amount ?? 0.0
        ];
    }

/**
     * Retrieves statistics for debits.
     *
     * @return array An array containing the statistics for debits.
     */
    public function statsDebits(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(Entry::debit->value);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return ['total' => 0.0];
        }

        return [
            'total' => $results[0]->aggregations()->total ?? 0.0
        ];
    }

    public function getWorkspaceSummary(): array
    {
        return [
            'workspace_id' => $this->wsId,
            'period' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString(),
            ],
            'total' => $this->total(),
            'stats_total' => $this->statsTotal(),
        ];
    }

}
