<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services;

use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use BudgetcontrolLibs\ElasticSearch\Services\Transactions\Search;

class SearchService extends Search
{

    /**
     * Get financial summary for workspace
     */
    public function getFinancialSummary(int $workspaceId, ?ElasticFilter $additionalFilters = null): array
    {
        $filter = ElasticFilter::create()->setWorkspaceId($workspaceId);
        
        if ($additionalFilters) {
            $filter->hydrate($additionalFilters->toArray());
        }

        $aggregator = ElasticAggregator::create($filter)
            ->financialSummary();

        return $this->aggregate($aggregator);
    }

    /**
     * Get category analysis for workspace
     */
    public function getCategoryAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array
    {
        $filter = ElasticFilter::create()->setWorkspaceId($workspaceId);
        
        if ($additionalFilters) {
            $filter->hydrate($additionalFilters->toArray());
        }

        $aggregator = ElasticAggregator::create($filter)
            ->categoryAnalysis();

        return $this->aggregate($aggregator);
    }

    /**
     * Get payment analysis for workspace
     */
    public function getPaymentAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array
    {
        $filter = ElasticFilter::create()->setWorkspaceId($workspaceId);
        
        if ($additionalFilters) {
            $filter->hydrate($additionalFilters->toArray());
        }

        $aggregator = ElasticAggregator::create($filter)
            ->paymentAnalysis();

        return $this->aggregate($aggregator);
    }

    /**
     * Get time-based analysis for workspace
     */
    public function getTimeAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array
    {
        $filter = ElasticFilter::create()->setWorkspaceId($workspaceId);
        
        if ($additionalFilters) {
            $filter->hydrate($additionalFilters->toArray());
        }

        $aggregator = ElasticAggregator::create($filter)
            ->timeAnalysis();

        return $this->aggregate($aggregator);
    }

    /**
     * Get behavior analysis for workspace
     */
    public function getBehaviorAnalysis(int $workspaceId, ?ElasticFilter $additionalFilters = null): array
    {
        $filter = ElasticFilter::create()->setWorkspaceId($workspaceId);
        
        if ($additionalFilters) {
            $filter->hydrate($additionalFilters->toArray());
        }

        $aggregator = ElasticAggregator::create($filter)
            ->behaviorAnalysis();

        return $this->aggregate($aggregator);
    }

    /**
     * Quick search by category
     */
    public function searchByCategory(int $categoryId, int $workspaceId, int $from = 0, int $size = 50): array
    {
        $filter = ElasticFilter::create()
            ->setCategories([$categoryId])
            ->setWorkspaceId($workspaceId);

        return $this->search($filter, $from, $size);
    }

    /**
     * Search transactions by amount range
     */
    public function searchByAmountRange(float $min, float $max, int $workspaceId, int $from = 0, int $size = 50): array
    {
        $filter = ElasticFilter::create()
            ->setAmountRange($min, $max)
            ->setWorkspaceId($workspaceId);

        return $this->search($filter, $from, $size);
    }

    /**
     * Search transactions by date range
     */
    public function searchByDateRange(string $startDate, string $endDate, int $workspaceId, int $from = 0, int $size = 50): array
    {
        $filter = ElasticFilter::create()
            ->setDateRange($startDate, $endDate)
            ->setWorkspaceId($workspaceId);

        return $this->search($filter, $from, $size);
    }

    /**
     * Search transactions by month and year
     */
    public function searchByMonth(int $month, int $year, int $workspaceId, int $from = 0, int $size = 50): array
    {
        $filter = ElasticFilter::create()
            ->setMonthYear($month, $year)
            ->setWorkspaceId($workspaceId);

        return $this->search($filter, $from, $size);
    }
}