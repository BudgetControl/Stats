<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces;

use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;


interface TransactionRepositoryInterface
{
    // ============ BASIC TYPE-SPECIFIC STATS ============
    
    /**
     * Get statistics for this transaction type
     */
    public function getStats(): array;

    /**
     * Get transactions by category for this type
     */
    public function getByCategory(?int $categoryId = null): array;

    /**
     * Get transactions by wallet for this type
     */
    public function getByWallet(?int $walletId = null): array;

    /**
     * Get transactions by date range for this type
     */
    public function getByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end): array;

    // ============ AGGREGATION METHODS ============
    
    /**
     * Get total amount for this transaction type
     */
    public function getTotalAmount(): float;

    /**
     * Get average amount for this transaction type
     */
    public function getAverageAmount(): float;

    /**
     * Get count of transactions for this type
     */
    public function getCount(): int;

    /**
     * Get min/max amounts for this transaction type
     */
    public function getMinMaxAmounts(): array;

    // ============ TIME-BASED ANALYSIS ============
    
    /**
     * Get monthly trend for this transaction type
     */
    public function getMonthlyTrend(int $months = 12): array;

    /**
     * Get weekly trend for this transaction type  
     */
    public function getWeeklyTrend(int $weeks = 12): array;

    /**
     * Get daily trend for this transaction type
     */
    public function getDailyTrend(int $days = 30): array;

    // ============ COMPARISON METHODS ============
    
    /**
     * Compare with previous period
     */
    public function compareWithPrevious(\Carbon\Carbon $previousStart, \Carbon\Carbon $previousEnd): array;

    /**
     * Get growth rate compared to previous period
     */
    public function getGrowthRate(\Carbon\Carbon $previousStart, \Carbon\Carbon $previousEnd): float;

    // ============ FILTERING METHODS ============
    
    /**
     * Get transactions with custom filters
     */
    public function getWithFilters(ElasticFilter $filter, int $limit = 100, int $offset = 0): array;

    /**
     * Get aggregated data with custom aggregator
     */
    public function getAggregated(ElasticAggregator $aggregator): array;

    // ============ TOP/BOTTOM ANALYSIS ============
    
    /**
     * Get top transactions by amount
     */
    public function getTopTransactions(int $limit = 10): array;

    /**
     * Get bottom transactions by amount  
     */
    public function getBottomTransactions(int $limit = 10): array;

    /**
     * Get most frequent categories
     */
    public function getTopCategories(int $limit = 10): array;

    /**
     * Get most used payment types
     */
    public function getTopPaymentTypes(int $limit = 10): array;

    // ============ PATTERN ANALYSIS ============
    
    /**
     * Get recurring patterns
     */
    public function getRecurringPatterns(): array;

    /**
     * Get seasonal patterns
     */
    public function getSeasonalPatterns(): array;

    /**
     * Get weekend vs weekday patterns
     */
    public function getWeekendWeekdayPatterns(): array;

    // ============ UTILITY METHODS ============
    
    /**
     * Get the transaction type this repository handles
     */
    public function getTransactionType(): string;

    /**
     * Check if amount is positive or negative for this type
     */
    public function isPositiveAmount(): bool;

    /**
     * Get default filters for this transaction type
     */
    public function getDefaultFilters(): ElasticFilter;
}