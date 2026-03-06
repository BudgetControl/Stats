<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces;

/**
 * Unified repository interface that combines all stats functionality
 * 
 * This interface provides a complete contract for all stats-related operations,
 * combining traditional database queries with modern Elasticsearch capabilities.
 */
interface UnifiedStatsRepositoryInterface extends 
    StatsRepositoryInterface, 
    TransactionRepositoryInterface, 
    ElasticSearchRepositoryInterface
{
    // ============ HYBRID OPERATIONS ============
    
    /**
     * Get data using both database and Elasticsearch for comparison
     */
    public function getHybridData(array $filters = []): array;

    /**
     * Sync data between database and Elasticsearch
     */
    public function syncData(array $options = []): array;

    /**
     * Validate data consistency between sources
     */
    public function validateDataConsistency(): array;

    // ============ ADVANCED ANALYTICS ============
    
    /**
     * Get machine learning insights
     */
    public function getMlInsights(): array;

    /**
     * Detect anomalies in spending patterns
     */
    public function detectAnomalies(array $options = []): array;

    /**
     * Get predictive analytics
     */
    public function getPredictiveAnalytics(int $periods = 6, string $interval = 'month'): array;

    /**
     * Get personalized recommendations
     */
    public function getRecommendations(): array;

    // ============ REAL-TIME OPERATIONS ============
    
    /**
     * Get real-time statistics
     */
    public function getRealTimeStats(): array;

    /**
     * Stream live data updates
     */
    public function streamUpdates(\Closure $callback): void;

    /**
     * Subscribe to data changes
     */
    public function subscribeToChanges(array $events, \Closure $callback): void;

    // ============ EXPORT/IMPORT ============
    
    /**
     * Export data in various formats
     */
    public function exportData(string $format = 'json', array $filters = []): array;

    /**
     * Import data from external sources
     */
    public function importData(array $data, array $options = []): array;

    /**
     * Backup data
     */
    public function backupData(string $destination): array;

    /**
     * Restore data from backup
     */
    public function restoreData(string $source): array;

    // ============ CACHING OPERATIONS ============
    
    /**
     * Cache frequently accessed data
     */
    public function cacheData(string $key, array $data, int $ttl = 3600): bool;

    /**
     * Get cached data
     */
    public function getCachedData(string $key): ?array;

    /**
     * Invalidate cache
     */
    public function invalidateCache(array $keys = []): bool;

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array;

    // ============ PERFORMANCE OPTIMIZATION ============
    
    /**
     * Optimize query performance
     */
    public function optimizePerformance(array $options = []): array;

    /**
     * Get performance benchmarks
     */
    public function getPerformanceBenchmarks(): array;

    /**
     * Profile query execution
     */
    public function profileQuery(array $query): array;

    /**
     * Get slow query analysis
     */
    public function getSlowQueryAnalysis(): array;

    // ============ MULTI-TENANT OPERATIONS ============
    
    /**
     * Get cross-workspace analytics (for authorized users)
     */
    public function getCrossWorkspaceAnalytics(array $workspaceIds): array;

    /**
     * Compare workspace performance
     */
    public function compareWorkspaces(array $workspaceIds): array;

    /**
     * Get workspace benchmarks
     */
    public function getWorkspaceBenchmarks(int $workspaceId): array;

    // ============ SECURITY & AUDITING ============
    
    /**
     * Get audit trail for data access
     */
    public function getAuditTrail(array $filters = []): array;

    /**
     * Log data access
     */
    public function logDataAccess(string $operation, array $context = []): void;

    /**
     * Get security metrics
     */
    public function getSecurityMetrics(): array;

    /**
     * Validate data access permissions
     */
    public function validatePermissions(string $operation, array $context = []): bool;

    // ============ API INTEGRATION ============
    
    /**
     * Get REST API endpoints data
     */
    public function getApiData(string $endpoint, array $parameters = []): array;

    /**
     * Get GraphQL query results
     */
    public function getGraphqlData(string $query, array $variables = []): array;

    /**
     * Get webhook data
     */
    public function getWebhookData(array $filters = []): array;

    // ============ NOTIFICATION SYSTEM ============
    
    /**
     * Set up alerts for specific conditions
     */
    public function setAlert(array $conditions, array $actions): string;

    /**
     * Get triggered alerts
     */
    public function getAlerts(array $filters = []): array;

    /**
     * Cancel alert
     */
    public function cancelAlert(string $alertId): bool;

    /**
     * Get notification history
     */
    public function getNotificationHistory(array $filters = []): array;
}