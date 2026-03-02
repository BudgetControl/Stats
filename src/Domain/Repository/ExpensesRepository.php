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
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ExpensesRepository extends StatsRepository implements TransactionRepositoryInterface
{
    public static function setup(string $wsId, Carbon $startDate, Carbon $endDate): self
    {
        return new self($wsId, $startDate, $endDate);
    }

    public function statsExpenses(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            WHERE e.type IN ('expenses')
            AND e.exclude_from_stats = false
            AND e.deleted_at IS NULL
            AND e.confirmed = true
            AND e.planned = false
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND e.workspace_id = $wsId;

        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

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

        $query = "
            SELECT 
            c.id AS category_id,
            c.name AS category_name,
            c.slug AS category_slug,
            COALESCE(SUM(e.amount), 0) AS total
        FROM 
            entries AS e
        INNER JOIN 
            categories AS c ON e.category_id = c.id
        WHERE 
            e.category_id = $categoryId
            AND e.type = 'expenses'
            AND e.exclude_from_stats = false
            AND e.deleted_at IS NULL
            AND e.confirmed = true
            AND e.planned = false
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND e.workspace_id = $wsId
        GROUP BY 
            c.id, c.name, c.slug;

        ";

        $result = DB::select($query);

        return new ExpensesCategory(
            $result[0]->total,
            $result[0]->category_slug,
            $categoryId,
            $result[0]->category_name
        );
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

    public function expensesByLabels(array $labels = [])
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $addConditions = '';
        if (!empty($labels)) {
            $addConditions .= "AND l.id IN ('" . implode("','", $labels) . "')";
        }

        $query = "
           SELECT 
                l.id AS label_id,
                l.name AS label_name,
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            LEFT JOIN 
                entry_labels AS el ON e.id = el.entry_id
            LEFT JOIN 
                labels AS l ON el.labels_id = l.id
            WHERE 
                e.type IN ('expenses')
                AND e.exclude_from_stats = false
                AND e.deleted_at IS NULL
                AND e.confirmed = true
                AND e.planned = false
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND e.workspace_id = $wsId
                $addConditions
            GROUP BY 
                l.id, l.name;

        ";

        $results = DB::select($query);

        //only labels
        foreach ($results as $key => $value) {
            if ($value->label_name == null) {
                unset($results[$key]);
            }
        }

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
