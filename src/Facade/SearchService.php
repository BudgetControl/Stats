<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Facade;

use Budgetcontrol\Stats\Domain\ValueObjects\Elastic\ElasticFilter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getFinancialSummary(int $workspaceId, ?ElasticFilter $additionalFilters = null)
 * @method static array getCategoryAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters
 * @method static array getPaymentAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null)
 * @method static array getTimeAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters
 * @method static array aggregate(\BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator $aggregator)
 * @method static array search(array $params)
 * @method static array getBehaviorAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null)
 * @method static array searchByCategory(int $workspaceId, ?ElasticFilter $additionalFilters = null, int $from = 0, int $size = 50)
 * @method static array searchByAmountRange(int $workspaceId, float $minAmount, float $maxAmount, ?ElasticFilter $additionalFilters = null, int $from = 0, int $size = 50)
 * @method static array searchByDateRange(int $workspaceId, string $startDate, string $endDate, ?ElasticFilter $additionalFilters = null, int $from = 0, int $size = 50)
 * @method static array searchByMonth(int $workspaceId, int $month, int $year, ?ElasticFilter $additionalFilters = null, int $from = 0, int $size = 50)
 * 
 * @see \Budgetcontrol\Stats\Services\SearchService
 */

final class SearchService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'search-service';
    }
}