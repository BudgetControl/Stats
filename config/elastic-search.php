<?php

$elasticsearch = \BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::create(
    env('ELASTICSEARCH_INDEX', 'transactions'),
    env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    env('ELASTICSEARCH_USERNAME', 'elastic'),
    env('ELASTICSEARCH_PASSWORD', 'changeme')
);

//check connection to elasticsearch
$response = $elasticsearch->client()->ping();
if(!$response->asBool()) {
    throw new \Exception('Elasticsearch ping failed');
}

// create index if not exists
$elasticsearch->createIndexIfNotExists();