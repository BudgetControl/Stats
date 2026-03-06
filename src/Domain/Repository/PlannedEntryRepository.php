<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Definition\Period;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\PlannedEntry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class PlannedEntryRepository extends StatsRepository implements TransactionRepositoryInterface {
    
    /**
     * Retrieves the planned expenses.
     *
     * @return array The planned expenses.
     */
    public function getPlanedMonthlyExpenses(): array
    {
        $total = PlannedEntry::where('workspace_id', $this->wsId)
            ->where('type', Entry::expenses->value)
            ->whereNull('deleted_at')
            ->where('planning', Period::monthly->value)
            ->where('end_date_time', '>=', now()->toDateString())
            ->sum('amount');

        return ['total' => (float) $total];
    }

    /**
     * Retrieves an array of planned weekly expenses.
     *
     * @return array An array containing the planned weekly expenses.
     */
    public function getPlanedWeeklyExpenses(): array
    {
        $total = PlannedEntry::where('workspace_id', $this->wsId)
            ->where('type', Entry::expenses->value)
            ->whereNull('deleted_at')
            ->where('planning', Period::weekly->value)
            ->where('end_date_time', '>=', now()->toDateString())
            ->sum('amount');

        return ['total' => (float) $total];
    }

    /**
     * Retrieves an array of planned daily expenses.
     *
     * @return array An array containing the planned daily expenses.
     */
    public function getPlanedDailyExpenses(): array
    {
        $total = PlannedEntry::where('workspace_id', $this->wsId)
            ->where('type', Entry::expenses->value)
            ->whereNull('deleted_at')
            ->where('planning', Period::daily->value)
            ->where('end_date_time', '>=', now()->toDateString())
            ->sum('amount');

        return ['total' => (float) $total];
    }

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array
    {
        return $this->plannedOfPeriod();
    }

    public function getByCategory(?int $categoryId = null): array
    {
        return [];
    }

    public function getTransactionType(): string
    {
        return 'planned';
    }

    public function isPositiveAmount(): bool
    {
        return false;
    }

    public function getDefaultFilters(): ElasticFilter
    {
        return ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setPlanned(true)
            ->setStartDate($this->startDate->toDateString())
            ->setEndDate($this->endDate->toDateString());
    }
}
