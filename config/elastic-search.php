<?php

$elasticsearch = \BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::create(
    env('ELASTICSEARCH_INDEX', 'transactions'),
    env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    env('ELASTICSEARCH_USERNAME', 'elastic'),
    env('ELASTICSEARCH_PASSWORD', 'changeme')
);

// Skip connectivity check and index setup in testing environment
if (env('APP_ENV') !== 'testing') {
    $response = $elasticsearch->client()->ping();
    if(!$response->asBool()) {
        throw new \Exception('Elasticsearch ping failed');
    }

    // create index if not exists
    $elasticsearch->createIndexIfNotExists();
}
