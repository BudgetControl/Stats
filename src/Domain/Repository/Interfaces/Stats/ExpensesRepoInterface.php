<?php

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

use Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;

interface ExpensesRepoInterface
{
    public function setup(string $wsId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): self;

    /**
     * Retrieves the expenses associated with a specific category.
     *
     * @param int $categoryId The ID of the category for which to retrieve expenses.
     * @return ExpensesCategory The expenses related to the specified category.
     */
    public function expensesByCategory(int $categoryId): ExpensesCategory;

    /**
     * Retrieves expenses categorized by all categories.
     *
     * @return array<String:ExpensesCategory of ExpensesCategory Returns an instance of ExpensesCategory containing the categorized expenses.
     */
    public function expensesByCategories(): array;

    public function expensesByLabels(array $labels = []): array;

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array;

    public function getByCategory(?int $categoryId = null): array;

    public function getTransactionType(): string;

    public function isPositiveAmount(): bool;

    public function getDefaultFilters(): ElasticFilter;
}
