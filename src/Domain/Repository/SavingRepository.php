<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class SavingRepository extends StatsRepository implements TransactionRepositoryInterface {

    public static function setup(string $wsId, Carbon $startDate, Carbon $endDate): self
    {
        return new self($wsId, $startDate, $endDate);
    }

    /**
     * Retrieves statistics for savings.
     *
     * @return array An array containing the statistics for savings.
     */
    public function statsSevings(): array
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange($startDate, $endDate)
            ->setType(Entry::saving->value)
            ->setConfirmed(true)
            ->setPlanned(false);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        if (empty($results)) {
            return ['total' => 0.0];
        }

        return [
            'total' => $results[0]->aggregations()->total ?? 0.0
        ];
    }

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
