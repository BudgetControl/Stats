<?php

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class ExpensesRepository extends StatsRepository implements TransactionRepositoryInterface
{

    /**
     * Retrieves the expenses associated with a specific category.
     *
     * @param int $categoryId The ID of the category for which to retrieve expenses.
     * @return ExpensesCategory The expenses related to the specified category.
     */
    public function expensesByCategory(int $categoryId): ExpensesCategory
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->groupByCategory(1000, $categoryId);

        $results = SearchService::aggregate($agregator);

        /** @var \BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator $results */
        foreach ($results as $value) {
            $aggregation = $value->aggregations();
            $data[$aggregation->category_slug] = new ExpensesCategory(
                $aggregation->total,
                $aggregation->category_slug,
                $aggregation->category_id,
                $aggregation->category_name
            );
        }

        return $data;
    }

    /**
     * Retrieves expenses categorized by all categories.
     *
     * @return array<String:ExpensesCategory of ExpensesCategory Returns an instance of ExpensesCategory containing the categorized expenses.
     */
    public function expensesByCategories(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->groupByCategory();

        $results = SearchService::aggregate($agregator);

        if(empty($results)) {
            return [];
        }

        /** @var \BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator $results */
        foreach ($results as $value) {
            $aggregation = $value->aggregations();
            $data[$aggregation->category_slug] = new ExpensesCategory(
                $aggregation->total,
                $aggregation->category_slug,
                $aggregation->category_id,
                $aggregation->category_name
            );
        }

        return $data;
    }

    public function expensesByLabels(array $labels = []): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();
        
        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
            ->setType(Entry::expenses->value);

        if (!empty($labels)) {
            $filters->setTags($labels);
        }

        $agregator = ElasticAggregator::create($filters)
            ->groupByTag();

        $results = SearchService::aggregate($agregator);
        return $results;
    }

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array
    {
        return $this->statsExpenses();
    }

    public function getByCategory(?int $categoryId = null): array
    {
        if ($categoryId !== null) {
            return $this->expensesByCategory($categoryId)->toArray();
        }
        return $this->expensesByCategory(null);
    }

    public function getTransactionType(): string
    {
        return 'expenses';
    }

    public function isPositiveAmount(): bool
    {
        return false; // Expenses are typically negative amounts
    }

    public function getDefaultFilters(): ElasticFilter
    {
        return ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setType('expenses')
            ->setStartDate($this->startDate->toDateString())
            ->setEndDate($this->endDate->toDateString());
    }
}
