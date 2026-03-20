<?php
namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;

interface SavingRepoInterface {

    public function setup(string $wsId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): static;

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array;

    public function getByCategory(?int $categoryId = null): array;

    public function getTransactionType(): string;

    public function isPositiveAmount(): bool;

    public function getDefaultFilters(): ElasticFilter;
}
