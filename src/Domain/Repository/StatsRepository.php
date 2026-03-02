<?php

namespace Budgetcontrol\Stats\Domain\Repository;

use Brick\Math\BigNumber;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Stats\Domain\Model\Wallet;
use BudgetcontrolLibs\ElasticSearch\Services\Transactions\SearchService;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

abstract class StatsRepository implements StatsRepositoryInterface
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
        $wsid = Workspace::where('uuid', $wsId)->first()->id;
        $wsid = 2;
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
    public function statsTotal(): array
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
    public function total(): array 
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
    public function currentInstallmentValues()
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

        return $wallets;
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

    // ============ StatsRepositoryInterface Implementation ============
    // Note: Many methods are implemented in child classes or need to be implemented

    public function statsExpenses(): array
    {
        throw new \BadMethodCallException('statsExpenses should be implemented in ExpensesRepository');
    }

    public function statsIncoming(): array
    {
        throw new \BadMethodCallException('statsIncoming should be implemented in IncomingRepository');
    }

    public function statsDebits(): array
    {
        throw new \BadMethodCallException('statsDebits should be implemented in DebitRepository');
    }

    public function statsSavings(): array
    {
        throw new \BadMethodCallException('statsSavings should be implemented in SavingRepository');
    }

    public function statsPlannedEntries(): array
    {
        throw new \BadMethodCallException('statsPlannedEntries should be implemented in PlannedEntryRepository');
    }

    public function expensesByCategory(int $categoryId): array|\Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory
    {
        throw new \BadMethodCallException('expensesByCategory should be implemented in ExpensesRepository');
    }

    public function incomingByCategory(?int $categoryId = null): array
    {
        throw new \BadMethodCallException('incomingByCategory should be implemented in IncomingRepository');
    }

    public function debitsByCategory(?int $categoryId = null): array
    {
        throw new \BadMethodCallException('debitsByCategory should be implemented in DebitRepository');
    }

    public function savingsByCategory(?int $categoryId = null): array
    {
        throw new \BadMethodCallException('savingsByCategory should be implemented in SavingRepository');
    }

    public function transactionsByWallet(?int $walletId = null): array
    {
        // TODO: Implement generic wallet analysis
        throw new \BadMethodCallException('transactionsByWallet method not yet implemented');
    }

    public function walletBalances(): array
    {
        // TODO: Implement wallet balances
        throw new \BadMethodCallException('walletBalances method not yet implemented');
    }

    public function monthlyBreakdown(string $type = 'all'): array
    {
        // TODO: Implement monthly breakdown
        throw new \BadMethodCallException('monthlyBreakdown method not yet implemented');
    }

    public function weeklyBreakdown(string $type = 'all'): array
    {
        // TODO: Implement weekly breakdown
        throw new \BadMethodCallException('weeklyBreakdown method not yet implemented');
    }

    public function dailyBreakdown(string $type = 'all'): array
    {
        // TODO: Implement daily breakdown
        throw new \BadMethodCallException('dailyBreakdown method not yet implemented');
    }

    public function yearlyBreakdown(string $type = 'all'): array
    {
        // TODO: Implement yearly breakdown
        throw new \BadMethodCallException('yearlyBreakdown method not yet implemented');
    }

    public function transactionsByPaymentType(string $type = 'all'): array
    {
        // TODO: Implement payment type analysis
        throw new \BadMethodCallException('transactionsByPaymentType method not yet implemented');
    }

    public function transactionsByCurrency(string $type = 'all'): array
    {
        // TODO: Implement currency analysis
        throw new \BadMethodCallException('transactionsByCurrency method not yet implemented');
    }

    public function totalNegativeStatsDebits(): array
    {
        throw new \BadMethodCallException('totalNegativeStatsDebits should be implemented in DebitRepository');
    }

    public function transactionCounts(): array
    {
        // TODO: Implement transaction counts
        throw new \BadMethodCallException('transactionCounts method not yet implemented');
    }

    public function averageAmounts(): array
    {
        // TODO: Implement average amounts
        throw new \BadMethodCallException('averageAmounts method not yet implemented');
    }

    public function minMaxAmounts(): array
    {
        // TODO: Implement min/max amounts
        throw new \BadMethodCallException('minMaxAmounts method not yet implemented');
    }

    public function searchTransactions(ElasticFilter $filter, int $from = 0, int $size = 50): array
    {
        // TODO: Implement Elasticsearch search
        throw new \BadMethodCallException('searchTransactions method not yet implemented');
    }

    public function aggregate(ElasticAggregator $aggregator): array
    {
        // TODO: Implement Elasticsearch aggregation
        throw new \BadMethodCallException('aggregate method not yet implemented');
    }

    public function getFinancialSummary(?ElasticFilter $additionalFilters = null): array
    {
        // TODO: Implement financial summary
        throw new \BadMethodCallException('getFinancialSummary method not yet implemented');
    }

    public function getCategoryAnalysis(?ElasticFilter $additionalFilters = null): array
    {
        // TODO: Implement category analysis
        throw new \BadMethodCallException('getCategoryAnalysis method not yet implemented');
    }

    public function getPaymentAnalysis(?ElasticFilter $additionalFilters = null): array
    {
        // TODO: Implement payment analysis
        throw new \BadMethodCallException('getPaymentAnalysis method not yet implemented');
    }

    public function getTimeAnalysis(?ElasticFilter $additionalFilters = null): array
    {
        // TODO: Implement time analysis
        throw new \BadMethodCallException('getTimeAnalysis method not yet implemented');
    }

    public function getBehaviorAnalysis(?ElasticFilter $additionalFilters = null): array
    {
        // TODO: Implement behavior analysis
        throw new \BadMethodCallException('getBehaviorAnalysis method not yet implemented');
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

    public function transactionExists(string $uuid): bool
    {
        // TODO: Implement transaction existence check
        throw new \BadMethodCallException('transactionExists method not yet implemented');
    }

    public function getTransactionByUuid(string $uuid): ?ElasticTransaction
    {
        // TODO: Implement get transaction by UUID
        throw new \BadMethodCallException('getTransactionByUuid method not yet implemented');
    }

    public function getTransactionsWithFilters(array $filters, int $limit = 100, int $offset = 0): array
    {
        // TODO: Implement filtered transactions
        throw new \BadMethodCallException('getTransactionsWithFilters method not yet implemented');
    }

    public function comparePeriods(Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        // TODO: Implement period comparison
        throw new \BadMethodCallException('comparePeriods method not yet implemented');
    }

    public function getGrowthRates(Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        // TODO: Implement growth rates
        throw new \BadMethodCallException('getGrowthRates method not yet implemented');
    }

    public function getSpendingTrends(int $periods = 12, string $interval = 'month'): array
    {
        // TODO: Implement spending trends
        throw new \BadMethodCallException('getSpendingTrends method not yet implemented');
    }

    public function getIncomeTrends(int $periods = 12, string $interval = 'month'): array
    {
        // TODO: Implement income trends
        throw new \BadMethodCallException('getIncomeTrends method not yet implemented');
    }

    public function getCategoryTrends(int $categoryId, int $periods = 12, string $interval = 'month'): array
    {
        // TODO: Implement category trends
        throw new \BadMethodCallException('getCategoryTrends method not yet implemented');
    }

    public function getBudgetComparison(): array
    {
        // TODO: Implement budget comparison
        throw new \BadMethodCallException('getBudgetComparison method not yet implemented');
    }

    public function getSavingsRate(): array
    {
        // TODO: Implement savings rate
        throw new \BadMethodCallException('getSavingsRate method not yet implemented');
    }

    public function getExpenseRatios(): array
    {
        // TODO: Implement expense ratios
        throw new \BadMethodCallException('getExpenseRatios method not yet implemented');
    }

    public function predictExpenses(int $months = 3): array
    {
        // TODO: Implement expense prediction
        throw new \BadMethodCallException('predictExpenses method not yet implemented');
    }

    public function predictIncome(int $months = 3): array
    {
        // TODO: Implement income prediction
        throw new \BadMethodCallException('predictIncome method not yet implemented');
    }

    public function generateFinancialReport(): array
    {
        // TODO: Implement financial report
        throw new \BadMethodCallException('generateFinancialReport method not yet implemented');
    }

    public function generateCashFlowReport(): array
    {
        // TODO: Implement cash flow report
        throw new \BadMethodCallException('generateCashFlowReport method not yet implemented');
    }

    public function generateCategorySpendingReport(): array
    {
        // TODO: Implement category spending report
        throw new \BadMethodCallException('generateCategorySpendingReport method not yet implemented');
    }

    public function getKPIs(): array
    {
        // TODO: Implement KPIs
        throw new \BadMethodCallException('getKPIs method not yet implemented');
    }

    public function getFinancialHealthScore(): array
    {
        // TODO: Implement financial health score
        throw new \BadMethodCallException('getFinancialHealthScore method not yet implemented');
    }

    public function getSpendingEfficiencyMetrics(): array
    {
        // TODO: Implement spending efficiency metrics
        throw new \BadMethodCallException('getSpendingEfficiencyMetrics method not yet implemented');
    }

    public function getByWallet(?int $walletId = null): array
    {
        // TODO: Implement wallet-specific expenses
        throw new \BadMethodCallException('getByWallet method not yet implemented');
    }

    public function getByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        // TODO: Implement date range expenses
        throw new \BadMethodCallException('getByDateRange method not yet implemented');
    }

    public function getTotalAmount(): float
    {
        $stats = $this->statsExpenses();
        return (float) ($stats['total'] ?? 0.0);
    }

    public function getAverageAmount(): float
    {
        // TODO: Implement average calculation
        throw new \BadMethodCallException('getAverageAmount method not yet implemented');
    }

    public function getCount(): int
    {
        // TODO: Implement count
        throw new \BadMethodCallException('getCount method not yet implemented');
    }

    public function getMinMaxAmounts(): array
    {
        // TODO: Implement min/max calculation
        throw new \BadMethodCallException('getMinMaxAmounts method not yet implemented');
    }

    public function getMonthlyTrend(int $months = 12): array
    {
        // TODO: Implement monthly trend
        throw new \BadMethodCallException('getMonthlyTrend method not yet implemented');
    }

    public function getWeeklyTrend(int $weeks = 12): array
    {
        // TODO: Implement weekly trend
        throw new \BadMethodCallException('getWeeklyTrend method not yet implemented');
    }

    public function getDailyTrend(int $days = 30): array
    {
        // TODO: Implement daily trend
        throw new \BadMethodCallException('getDailyTrend method not yet implemented');
    }

    public function compareWithPrevious(\Carbon\Carbon $previousStart, \Carbon\Carbon $previousEnd): array
    {
        // TODO: Implement period comparison
        throw new \BadMethodCallException('compareWithPrevious method not yet implemented');
    }

    public function getGrowthRate(\Carbon\Carbon $previousStart, \Carbon\Carbon $previousEnd): float
    {
        // TODO: Implement growth rate calculation
        throw new \BadMethodCallException('getGrowthRate method not yet implemented');
    }

    public function getWithFilters(ElasticFilter $filter, int $limit = 100, int $offset = 0): array
    {
        // TODO: Implement Elasticsearch filtering
        throw new \BadMethodCallException('getWithFilters method not yet implemented');
    }

    public function getAggregated(ElasticAggregator $aggregator): array
    {
        // TODO: Implement Elasticsearch aggregation
        throw new \BadMethodCallException('getAggregated method not yet implemented');
    }

    public function getTopTransactions(int $limit = 10): array
    {
        // TODO: Implement top transactions
        throw new \BadMethodCallException('getTopTransactions method not yet implemented');
    }

    public function getBottomTransactions(int $limit = 10): array
    {
        // TODO: Implement bottom transactions
        throw new \BadMethodCallException('getBottomTransactions method not yet implemented');
    }

    public function getTopCategories(int $limit = 10): array
    {
        // TODO: Implement top categories
        throw new \BadMethodCallException('getTopCategories method not yet implemented');
    }

    public function getTopPaymentTypes(int $limit = 10): array
    {
        // TODO: Implement top payment types
        throw new \BadMethodCallException('getTopPaymentTypes method not yet implemented');
    }

    public function getRecurringPatterns(): array
    {
        // TODO: Implement recurring patterns
        throw new \BadMethodCallException('getRecurringPatterns method not yet implemented');
    }

    public function getSeasonalPatterns(): array
    {
        // TODO: Implement seasonal patterns
        throw new \BadMethodCallException('getSeasonalPatterns method not yet implemented');
    }

    public function getWeekendWeekdayPatterns(): array
    {
        // TODO: Implement weekend/weekday patterns
        throw new \BadMethodCallException('getWeekendWeekdayPatterns method not yet implemented');
    }

    function getByCategory(?int $categoryId = null): array
    {
        //TODO: Implement category-specific expenses
        throw new \BadMethodCallException('getByCategory method not yet implemented');
    }

    function getDefaultFilters(): \BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter
    {
        //TODO: Implement default filters
        throw new \BadMethodCallException('getDefaultFilters method not yet implemented');
    }

    function getStats(): array
    {
        //TODO: Implement stats retrieval
        throw new \BadMethodCallException('getStats method not yet implemented');
    }

    function getTransactionType(): string
    {
        //TODO: Implement transaction type retrieval
        throw new \BadMethodCallException('getTransactionType method not yet implemented');
    }

    function isPositiveAmount(): bool
    {
        //TODO: Implement positive amount check
        throw new \BadMethodCallException('isPositiveAmount method not yet implemented');
    }
}
