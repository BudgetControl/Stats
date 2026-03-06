<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;

interface PlannedEntryRepoInterface {

    public function setup(string $wsId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): self;
    
    /**
     * Retrieves the planned expenses.
     *
     * @return array The planned expenses.
     */
    public function getPlanedMonthlyExpenses(): array;

    /**
     * Retrieves an array of planned weekly expenses.
     *
     * @return array An array containing the planned weekly expenses.
     */
    public function getPlanedWeeklyExpenses(): array;

    /**
     * Retrieves an array of planned daily expenses.
     *
     * @return array An array containing the planned daily expenses.
     */
    public function getPlanedDailyExpenses(): array;

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array;

    public function getByCategory(?int $categoryId = null): array;

    public function getTransactionType(): string;

    public function isPositiveAmount(): bool;

    public function getDefaultFilters(): ElasticFilter;
}
