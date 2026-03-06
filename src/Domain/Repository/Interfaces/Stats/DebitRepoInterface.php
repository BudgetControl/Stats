<?php
namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

interface DebitRepoInterface {

    public function setup(string $wsId, Carbon $startDate, Carbon $endDate): self;

    /**
     * Calculate the total amount of negative debits.
     *
     * This method aggregates all the negative debit entries and returns their total sum.
     *
     * @return array The total sum of negative debits.
     */
    public function totalNegativeStatsDebits(): array;

    /**
     * Calculate the total amount of positive debits.
     *
     * This method aggregates all the positive debit entries and returns their total sum.
     *
     * @return array The total sum of positive debits.
     */
    public function totalPositiveStatsDebits(): array;

    /**
     * Retrieves the debit of credit cards.
     */
    public function debitOfCreditCards(): array;

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array;

    public function getByCategory(?int $categoryId = null): array;

    public function getTransactionType(): string;

    public function isPositiveAmount(): bool;
    public function getDefaultFilters(): ElasticFilter;
}
