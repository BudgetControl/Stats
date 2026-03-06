<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\SavingRepoInterface;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class SavingRepository extends IncomingRepository implements SavingRepoInterface {


    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array
    {
        return $this->statsSevings();
    }

    public function getByCategory(?int $categoryId = null): array
    {
        return [];
    }

    public function getTransactionType(): string
    {
        return Entry::saving->value;
    }

    public function isPositiveAmount(): bool
    {
        return false;
    }

    public function getDefaultFilters(): ElasticFilter
    {
        return ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setType(Entry::saving->value)
            ->setStartDate($this->startDate->toDateString())
            ->setEndDate($this->endDate->toDateString());
    }
}
