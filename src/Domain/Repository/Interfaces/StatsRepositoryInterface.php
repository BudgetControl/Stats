<?php

namespace Budgetcontrol\Stats\Domain\Repository\Interfaces;

use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\DebitRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\ExpensesRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\IncomingRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\PlannedEntryRepoInterface;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\SavingRepoInterface;
use Illuminate\Database\Eloquent\Collection;

interface StatsRepositoryInterface extends DebitRepoInterface, IncomingRepoInterface, SavingRepoInterface, PlannedEntryRepoInterface, ExpensesRepoInterface
{

    /**
     * Retrieves the total stats.
     *
     * @return array The total stats.
     */
    public function statsTotal(): array;

    /**
     * Returns the total value.
     *
     * @return array The total value.
     */
    public function total(): array;

    /**
     * Retrieves the wallets from the repository.
     *
     * @return array An array of wallets.
     */
    public function wallets();

    /**
     * Checks the health of the repository.
     *
     * @return array
     */
    public function health();

    /**
     * Calculates the total with planned value for the current month.
     *
     * @return \stdClass The total value with planned for the current month.
     */
    public function totalWithPlannedOfCurrentMonth(): \stdClass;

    /**
     * Retrieves the planned entries of the current period.
     */
    public function plannedOfPeriod(): array;

    /**
     * Retrieves the installment values.
     *
     * @return array The installment values.
     */
    public function currentInstallmentValues(): array|Collection;


    /**
     * Returns the total planned of the current month.
     *
     * @return array The total planned of the current month.
     */
    public function totalPlannedOfCurrentMonth(): array;

    /**
     * Retrieves statistics based on the provided filters.
     *
     * @param array $options An array of filters to apply.
     * @return array An array containing the statistics data.
     */
    public function statsByFilters(array $options): array;

    /**
     * Retrieves statistics by category slug.
     *
     * @param string $categorySlug The slug of the category.
     * @param bool $isPlanned (optional) Whether the statistics are planned or not. Default is false.
     * @return \stdClass
     */
    public function statsByCategories(string $categorySlug, bool $isPlanned = false): \stdClass;

    /**
     * Retrieves the loan of credit cards.
     *
     * @return mixed The loan of credit cards.
     */
    public function loanOfCreditCards();

    /**
     * Retrieves the planned entries from the stats repository.
     *
     * @return \stdClass
     */
    public function plannedExpenses(): \stdClass;


    /**
     * Retrieves statistics for savings.
     *
     * @return array An array containing the statistics for savings.
     */
    public function statsSevings(): array;


    // ============ StatsRepositoryInterface Implementation ============
    // Note: Many methods are implemented in child classes or need to be implemented

    public function statsExpenses(): array;

    public function statsIncoming(): array;

    /**
     * Retrieves statistics for debits.
     *
     * @return array An array containing the statistics for debits.
     */
    public function statsDebits(): array;

    public function getWorkspaceSummary(): array;

}
