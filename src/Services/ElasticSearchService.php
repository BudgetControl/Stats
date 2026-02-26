<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Services;

use Budgetcontrol\Stats\Facade\ElasticSearch;
use Elastic\Elasticsearch\Client;

class ElasticSearchService
{
    protected readonly Client $client;
    protected readonly string $index;

    public function __construct() {
        $this->client = ElasticSearch::client();
        $this->index = ElasticSearch::indexName();
    }
}