<?php

$Elasticsearch = \Budgetcontrol\Stats\Services\Clients\ElasticSearchClient::getInstance(
    env('ELASTICSEARCH_INDEX', 'transactions'),
    env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    env('ELASTICSEARCH_USERNAME', 'elastic'),
    env('ELASTICSEARCH_PASSWORD', 'changeme')
);

// create index if not exists
$Elasticsearch->createIndexIfNotExists();