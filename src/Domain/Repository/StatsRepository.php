<?php

namespace Budgetcontrol\Stats\Domain\Repository;

use Brick\Math\BigNumber;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use Budgetcontrol\Stats\Domain\Model\Wallet;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;
use Budgetcontrol\Stats\Facade\SearchService;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class StatsRepository implements StatsRepositoryInterface
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

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
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
        $total = Wallet::where('workspace_id', $this->wsId)
            ->where('installement', false)
            ->whereNull('deleted_at')
            ->where('archived', false)
            ->where('exclude_from_stats', false)
            ->sum('balance');

        return [
            'total' => (float) $total
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
        $total = Wallet::where('workspace_id', $this->wsId)
            ->whereNull('deleted_at')
            ->where('archived', false)
            ->where('exclude_from_stats', false)
            ->sum('balance');

        $totalPlanned = $this->totalPlannedOfCurrentMonth();

        $total = BigNumber::sum($total, $totalPlanned['total'])->toFloat();
        return [
            'total' => $total
        ];
    }

    /**
     * Calculates the total with planned value for the current month.
     *
     * @return \stdClass The total value with planned for the current month.
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
        $results = SearchService::aggregate($agregator);
        $plannedTotal = !empty($results) ? ($results[0]->aggregations()->total ?? 0.0) : 0.0;

        $result = new \stdClass();
        $result->installement_balance = $installementBalance;
        $result->balance_without_installement = $balanceWithoutInstallement;
        $result->planned_amount_total = $plannedTotal;

        return $result;
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
        $walletsType = [EntityWallet::creditCard->value, EntityWallet::creditCardRevolving->value];

        return Wallet::where('workspace_id', $this->wsId)
            ->whereNull('deleted_at')
            ->where('exclude_from_stats', false)
            ->where('archived', false)
            ->whereIn('type', $walletsType)
            ->where('balance', '<', 0)
            ->get(['invoice_date', 'installement_value', 'balance']);
    }

    /**
     * Retrieves the planned entries from the stats repository.
     *
     * @return \stdClass
     */
    public function plannedExpenses(): \stdClass
    {
        $filters = ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setMonth(now()->month)
            ->setYear(now()->year)
            ->setPlanned(true)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)->totalAmount();
        $results = SearchService::aggregate($agregator);

        $result = new \stdClass();
        $result->total = !empty($results) ? ($results[0]->aggregations()->total ?? 0.0) : 0.0;

        return $result;
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
