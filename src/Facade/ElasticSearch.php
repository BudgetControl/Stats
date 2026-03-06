<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Facade;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Elastic\Elasticsearch\Client client()
 * @method static string indexName()
 * @method static void changeIndexName(string $indexName)
 * 
 * @see \BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient
 */

final class ElasticSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'elasticsearch';
    }

    public static function getInstance(): \BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient
    {
        return Facade::getFacadeApplication()['elasticsearch'];
    }
}