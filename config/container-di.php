<?php

// Slim Container configuration for dependency injection

$container = new \DI\Container();

$controllers = [
    \Budgetcontrol\Stats\Controller\ApplePieChartController::class => \Budgetcontrol\Stats\Domain\Repository\ExpensesRepository::class,
    \Budgetcontrol\Stats\Controller\BarChartController::class => \Budgetcontrol\Stats\Domain\Repository\ExpensesRepository::class,
    \Budgetcontrol\Stats\Controller\LineChartController::class => \Budgetcontrol\Stats\Domain\Repository\StatsRepository::class,
    \Budgetcontrol\Stats\Controller\StatsController::class => \Budgetcontrol\Stats\Domain\Repository\StatsRepository::class,
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