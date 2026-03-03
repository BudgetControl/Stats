<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces;

use Carbon\Carbon;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory;
use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;

interface StatsRepositoryInterface
{

    // ============ BASIC STATS ============
    
    /**
     * Get total balance across all wallets
     */
    public function total(): array;

    /**
     * Get total stats for all transaction types in date range
     */
    public function statsTotal(): array;

    // ============ TRANSACTION TYPE STATS ============
    
    /**
     * Get expenses statistics
     */
    public function statsExpenses(): array;

    /**
     * Get incoming statistics  
     */
    public function statsIncoming(): array;

    /**
     * Get debits statistics
     */
    public function statsDebits(): array;

    /**
     * Get savings statistics
     */
    public function statsSavings(): array;

    /**
     * Get planned entries statistics
     */
    public function statsPlannedEntries(): array;

    // ============ CATEGORY ANALYSIS ============
    
    /**
     * Get expenses grouped by category
     */
    public function expensesByCategory(int $categoryId): array|ExpensesCategory;

    /**
     * Get incoming grouped by category
     */
    public function incomingByCategory(?int $categoryId = null): array;

    /**
     * Get debits grouped by category
     */
    public function debitsByCategory(?int $categoryId = null): array;

    /**
     * Get savings grouped by category
     */
    public function savingsByCategory(?int $categoryId = null): array;

    // ============ WALLET ANALYSIS ============
    
    /**
     * Get transactions grouped by wallet
     */
    public function transactionsByWallet(?int $walletId = null): array;

    /**
     * Get wallet balances
     */
    public function walletBalances(): array;

    // ============ TIME-BASED ANALYSIS ============
    
    /**
     * Get monthly breakdown of transactions
     */
    public function monthlyBreakdown(string $type = 'all'): array;

    /**
     * Get weekly breakdown of transactions
     */
    public function weeklyBreakdown(string $type = 'all'): array;

    /**
     * Get daily breakdown of transactions
     */
    public function dailyBreakdown(string $type = 'all'): array;

    /**
     * Get yearly breakdown of transactions
     */
    public function yearlyBreakdown(string $type = 'all'): array;

    // ============ PAYMENT TYPE ANALYSIS ============
    
    /**
     * Get transactions grouped by payment type
     */
    public function transactionsByPaymentType(string $type = 'all'): array;

    /**
     * Get transactions grouped by currency
     */
    public function transactionsByCurrency(string $type = 'all'): array;

    // ============ ADVANCED STATS ============
    
    /**
     * Get negative/positive breakdown
     */
    public function totalNegativeStatsDebits(): array;

    /**
     * Get transaction count statistics
     */
    public function transactionCounts(): array;

    /**
     * Get average transaction amounts
     */
    public function averageAmounts(): array;

    /**
     * Get min/max transaction amounts
     */
    public function minMaxAmounts(): array;

    // ============ ELASTICSEARCH METHODS ============
    
    /**
     * Search transactions using filters
     */
    public function searchTransactions(ElasticFilter $filter, int $from = 0, int $size = 50): array;

    /**
     * Execute aggregation queries
     */
    public function aggregate(ElasticAggregator $aggregator): array;

    /**
     * Get financial summary using Elasticsearch
     */
    public function getFinancialSummary(?ElasticFilter $additionalFilters = null): array;

    /**
     * Get category analysis using Elasticsearch
     */
    public function getCategoryAnalysis(?ElasticFilter $additionalFilters = null): array;

    /**
     * Get payment analysis using Elasticsearch
     */
    public function getPaymentAnalysis(?ElasticFilter $additionalFilters = null): array;

    /**
     * Get time analysis using Elasticsearch
     */
    public function getTimeAnalysis(?ElasticFilter $additionalFilters = null): array;

    /**
     * Get behavior analysis using Elasticsearch
     */
    public function getBehaviorAnalysis(?ElasticFilter $additionalFilters = null): array;

    // ============ UTILITY METHODS ============
    
    /**
     * Get workspace statistics summary
     */
    public function getWorkspaceSummary(): array;

    /**
     * Check if transaction exists
     */
    public function transactionExists(string $uuid): bool;

    /**
     * Get transaction by UUID
     */
    public function getTransactionByUuid(string $uuid): ?ElasticTransaction;

    /**
     * Get transactions with filters
     */
    public function getTransactionsWithFilters(array $filters, int $limit = 100, int $offset = 0): array;

    // ============ COMPARISON METHODS ============
    
    /**
     * Compare periods (current vs previous)
     */
    public function comparePeriods(Carbon $previousStartDate, Carbon $previousEndDate): array;

    /**
     * Get growth rates
     */
    public function getGrowthRates(Carbon $previousStartDate, Carbon $previousEndDate): array;

    // ============ TREND ANALYSIS ============
    
    /**
     * Get spending trends
     */
    public function getSpendingTrends(int $periods = 12, string $interval = 'month'): array;

    /**
     * Get income trends
     */
    public function getIncomeTrends(int $periods = 12, string $interval = 'month'): array;

    /**
     * Get category trends
     */
    public function getCategoryTrends(int $categoryId, int $periods = 12, string $interval = 'month'): array;

    // ============ BUDGET ANALYSIS ============
    
    /**
     * Get budget vs actual comparison
     */
    public function getBudgetComparison(): array;

    /**
     * Get savings rate
     */
    public function getSavingsRate(): array;

    /**
     * Get expense ratios by category
     */
    public function getExpenseRatios(): array;

    // ============ FORECASTING ============
    
    /**
     * Predict future expenses based on historical data
     */
    public function predictExpenses(int $months = 3): array;

    /**
     * Predict future income based on historical data
     */
    public function predictIncome(int $months = 3): array;

    // ============ REPORTING METHODS ============
    
    /**
     * Generate comprehensive financial report
     */
    public function generateFinancialReport(): array;

    /**
     * Generate cash flow report
     */
    public function generateCashFlowReport(): array;

    /**
     * Generate category spending report
     */
    public function generateCategorySpendingReport(): array;

    // ============ PERFORMANCE METRICS ============
    
    /**
     * Get key performance indicators (KPIs)
     */
    public function getKPIs(): array;

    /**
     * Get financial health score
     */
    public function getFinancialHealthScore(): array;

    /**
     * Get spending efficiency metrics
     */
    public function getSpendingEfficiencyMetrics(): array;
}