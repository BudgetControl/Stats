<?php

// Slim Container configuration for dependency injection

$container = new \DI\Container();

$controllers = [
    \Budgetcontrol\Stats\Controller\ApplePieChartController::class => \Budgetcontrol\Stats\Domain\Repository\ExpensesRepository::class,
    \Budgetcontrol\Stats\Controller\BarChartController::class => \Budgetcontrol\Stats\Domain\Repository\ExpensesRepository::class,
    \Budgetcontrol\Stats\Controller\LineChartController::class => \Budgetcontrol\Stats\Domain\Repository\StatsRepository::class,
    \Budgetcontrol\Stats\Controller\TableChartController::class => \Budgetcontrol\Stats\Domain\Repository\StatsRepository::class,
];

// Register the Controllers with its dependencies
foreach ($controllers as $controller => $repository) {
    $container->set($controller, function ($c) use ($controller,$repository) {
        // Here you can resolve the dependencies for ChartController
        // For example, if ChartController depends on a StatsRepositoryInterface, you can resolve it
        $statsRepository = $c->get($repository);
        return new $controller($statsRepository);
    });
}

$container->set(\Budgetcontrol\Stats\Services\StatsService::class, function ($c) {
    $repository = $c->get(\Budgetcontrol\Stats\Domain\Repository\StatsRepository::class);
    return new \Budgetcontrol\Stats\Services\StatsService($repository);
});

$container->set(\Budgetcontrol\Stats\Controller\StatsController::class, function ($c) {
    $service = $c->get(\Budgetcontrol\Stats\Services\StatsService::class);
    return new \Budgetcontrol\Stats\Controller\StatsController($service);
});

// ElasticSearch Client
$container->set(\BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::class, function ($c) {

foreach($_ELASTIC_INDEXS as $value) {
        if(empty($value)) {
            throw new \Exception("No index enviromet exist in env configuration");
        }
    }

    return \BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::create(
        env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
        env('ELASTICSEARCH_USERNAME', 'elastic'),
        env('ELASTICSEARCH_PASSWORD', 'changeme')
    );
});

$container->set(\Budgetcontrol\Stats\Controller\Elastic\InternalIndexingController::class, function($c) {
    $client = $c->get(\BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::class);
    $response = $client->client()->ping();
    if(!$response->asBool()) {
        throw new \Exception('Elasticsearch ping failed');
    }

    // create index if not exists
    $client->setIndexName($_ELASTIC_INDEXS['entry'])->createIndexIfNotExists();

    return new \Budgetcontrol\Stats\Controller\Elastic\InternalIndexingController($client);
});

$container->set(\Budgetcontrol\Stats\Controller\Elastic\WalletIndexingController::class, function($c) {
    $client = $c->get(\BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient::class);
    $response = $client->client()->ping();
    if(!$response->asBool()) {
        throw new \Exception('Elasticsearch ping failed');
    }

    // create index if not exists
    $client->setIndexName($_ELASTIC_INDEXS['wallet'])->createIndexIfNotExists();

    return new \Budgetcontrol\Stats\Controller\Elastic\WalletIndexingController($client);
});