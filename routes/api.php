<?php
/**
 * Stats application apps
 */

$app->get('/{wsid}/incoming', \Budgetcontrol\Stats\Controller\StatsController::class . ':incoming');
$app->get('/{wsid}/expenses', \Budgetcontrol\Stats\Controller\StatsController::class . ':expenses');
$app->get('/{wsid}/total', \Budgetcontrol\Stats\Controller\StatsController::class . ':total');
$app->get('/{wsid}/wallets', \Budgetcontrol\Stats\Controller\StatsController::class . ':wallets');
$app->get('/{wsid}/health', \Budgetcontrol\Stats\Controller\StatsController::class . ':health');
$app->get('/{wsid}/total-planned', \Budgetcontrol\Stats\Controller\StatsController::class . ':totalPlanned');

$app->get('/{wsid}/chart/line/incoming-expenses', \Budgetcontrol\Stats\Controller\LineChartController::class . ':incomingExpensesByDate');
$app->get('/{wsid}/chart/bar/category', \Budgetcontrol\Stats\Controller\BarChartController::class . ':expensesCategoryByDate');
$app->get('/{wsid}/chart/table/category', \Budgetcontrol\Stats\Controller\TableChartController::class . ':expensesCategoryByDate');
$app->get('/{wsid}/chart/bar/label', \Budgetcontrol\Stats\Controller\BarChartController::class . ':expensesLabelByDate');