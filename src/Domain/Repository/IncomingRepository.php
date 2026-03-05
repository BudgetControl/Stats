<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Budgetcontrol\Stats\Facade\SearchService;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticAggregator;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class IncomingRepository extends StatsRepository implements TransactionRepositoryInterface {
    

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