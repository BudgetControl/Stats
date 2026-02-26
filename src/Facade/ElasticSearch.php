<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Elastic\Elasticsearch\Client client()
 * @method static string indexName()
 * 
 * @see \Budgetcontrol\Stats\Services\Clients\ElasticSearchClient
 */

final class ElasticSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'elasticsearch';
    }
}