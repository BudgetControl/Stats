<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class IncomingRepository extends StatsRepository implements TransactionRepositoryInterface {
    
    public static function setup(string $wsId, Carbon $startDate, Carbon $endDate): self
    {
        return new self($wsId, $startDate, $endDate);
    }

    public function statsIncoming(): array {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
            ->setType(type: Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->totalAmount();

        $results = SearchService::aggregate($agregator);

        return [
            'total' => $results->total_amount ?? 0.0
        ];
    }

    public function incomingByCategory(?int $categoryId = null): array
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

        if(empty($results)) {
            return [];
        }

        return $results;
    }

    public function incomingByLabels()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();


        $filters = ElasticFilter::create()
            ->setWorkspaceId($wsId)
            ->setDateRange( $startDate, $endDate)
            ->setType(Entry::expenses->value);

        $agregator = ElasticAggregator::create($filters)
            ->groupByTag();

        $results = SearchService::aggregate($agregator);

        if(empty($results)) {
            return [];
        }

        return $results;
    }
}