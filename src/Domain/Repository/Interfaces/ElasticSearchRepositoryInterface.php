<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces;

use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use Budgetcontrol\Stats\Domain\ValueObjects\Elastic\ElasticFilter;
use Budgetcontrol\Stats\Domain\ValueObjects\Elastic\ElasticAggregator;

interface ElasticSearchRepositoryInterface
{
    // ============ BASIC SEARCH OPERATIONS ============
    
    /**
     * Search transactions with filters
     */
    public function search(ElasticFilter $filter, int $from = 0, int $size = 50): array;

    /**
     * Smart search with natural language query
     */
    public function smartSearch(string $query, int $workspaceId): array;

    /**
     * Custom search with raw Elasticsearch body
     */
    public function customSearch(array $body): array;

    // ============ AGGREGATION OPERATIONS ============
    
    /**
     * Execute aggregation query
     */
    public function aggregate(ElasticAggregator $aggregator): array;

    /**
     * Get financial summary aggregations
     */
    public function getFinancialSummary(int $workspaceId, ?ElasticFilter $additionalFilters = null): array;

    /**
     * Get category analysis aggregations
     */
    public function getCategoryAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array;

    /**
     * Get payment analysis aggregations
     */
    public function getPaymentAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array;

    /**
     * Get time-based analysis aggregations
     */
    public function getTimeAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array;

    /**
     * Get behavior analysis aggregations
     */
    public function getBehaviorAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array;

    // ============ SPECIFIC SEARCH METHODS ============
    
    /**
     * Search by category
     */
    public function searchByCategory(int $categoryId, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by amount range
     */
    public function searchByAmountRange(float $min, float $max, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by date range
     */
    public function searchByDateRange(string $startDate, string $endDate, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by month and year
     */
    public function searchByMonth(int $month, int $year, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by wallet
     */
    public function searchByWallet(int $walletId, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by transaction type
     */
    public function searchByType(string $type, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by payment type
     */
    public function searchByPaymentType(string $paymentType, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by currency
     */
    public function searchByCurrency(string $currency, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search by tags
     */
    public function searchByTags(array $tags, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search confirmed/unconfirmed transactions
     */
    public function searchByConfirmationStatus(bool $confirmed, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search planned/unplanned transactions
     */
    public function searchByPlanningStatus(bool $planned, int $workspaceId, int $from = 0, int $size = 50): array;

    /**
     * Search transfer transactions
     */
    public function searchTransfers(int $workspaceId, int $from = 0, int $size = 50): array;

    // ============ DOCUMENT OPERATIONS ============
    
    /**
     * Get transaction by UUID
     */
    public function getByUuid(string $uuid): ?ElasticTransaction;

    /**
     * Check if transaction exists
     */
    public function exists(string $uuid): bool;

    /**
     * Index a transaction
     */
    public function indexTransaction(ElasticTransaction $transaction): bool;

    /**
     * Update a transaction
     */
    public function updateTransaction(string $uuid, array $updates): bool;

    /**
     * Delete a transaction
     */
    public function deleteTransaction(string $uuid): bool;

    /**
     * Bulk index transactions
     */
    public function bulkIndexTransactions(array $transactions): array;

    // ============ INDEX MANAGEMENT ============
    
    /**
     * Create index with proper mapping
     */
    public function createIndex(): bool;

    /**
     * Delete index
     */
    public function deleteIndex(): bool;

    /**
     * Check if index exists
     */
    public function indexExists(): bool;

    /**
     * Reindex all transactions
     */
    public function reindexAll(): array;

    /**
     * Get index statistics
     */
    public function getIndexStats(): array;

    // ============ UTILITY METHODS ============
    
    /**
     * Get total document count
     */
    public function getTotalCount(int $workspaceId): int;

    /**
     * Get unique values for a field
     */
    public function getUniqueValues(string $field, int $workspaceId): array;

    /**
     * Get field statistics
     */
    public function getFieldStats(string $field, int $workspaceId): array;

    /**
     * Validate query syntax
     */
    public function validateQuery(array $query): bool;

    /**
     * Explain query execution
     */
    public function explainQuery(array $query): array;

    /**
     * Get suggestions for autocomplete
     */
    public function getSuggestions(string $field, string $text, int $workspaceId, int $size = 10): array;

    // ============ HEALTH & MONITORING ============
    
    /**
     * Check Elasticsearch connection health
     */
    public function checkHealth(): array;

    /**
     * Get cluster info
     */
    public function getClusterInfo(): array;

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array;
}