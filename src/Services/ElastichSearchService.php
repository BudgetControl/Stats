<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Services;

use Budgetcontrol\Stats\Facade\ElastichSearch;
use Elastic\Elasticsearch\Client;

class ElastichSearchService
{
    protected readonly Client $client;
    protected readonly string $index;

    public function __construct() {
        $this->client = ElastichSearch::client();
        $this->index = ElastichSearch::indexName();
    }
}