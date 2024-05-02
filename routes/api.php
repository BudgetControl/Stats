<?php
/**
 * Stats application apps
 */

$app->get('/{wsid}/incoming', \Budgetcontrol\Stats\Controller\StatsController::class . ':incomingOfCurrentMonth');
$app->get('/{wsid}/expenses', \Budgetcontrol\Stats\Controller\StatsController::class . ':expensesOfCurrentMonth');
$app->get('/{wsid}/total', \Budgetcontrol\Stats\Controller\StatsController::class . ':totalOfCurrentMonth');
$app->get('/{wsid}/wallets', \Budgetcontrol\Stats\Controller\StatsController::class . ':wallets');
$app->get('/{wsid}/health', \Budgetcontrol\Stats\Controller\StatsController::class . ':health');
$app->get('/{wsid}/total/planned', \Budgetcontrol\Stats\Controller\StatsController::class . ':totalPlannedOfCurrentMonth');

$app->get('/{wsid}/chart/line/incoming-expenses', \Budgetcontrol\Stats\Controller\LineChartController::class . ':incomingExpensesByDate');
$app->get('/{wsid}/chart/bar/expenses/category', \Budgetcontrol\Stats\Controller\BarChartController::class . ':expensesCategoryByDate');
$app->get('/{wsid}/chart/table/expenses/category', \Budgetcontrol\Stats\Controller\TableChartController::class . ':expensesCategoryByDate');
$app->get('/{wsid}/chart/bar/expenses/label', \Budgetcontrol\Stats\Controller\BarChartController::class . ':expensesLabelsByDate');
$app->get('/{wsid}/chart/apple-pie/expenses/label', \Budgetcontrol\Stats\Controller\ApplePieChartController::class . ':expensesLabelsByDate');

$app->get('/{wsid}/budgets', \Budgetcontrol\Stats\Controller\BudgetController::class . ':budgets');
$app->post('/{wsid}/budget', \Budgetcontrol\Stats\Controller\BudgetController::class . ':create');
$app->put('/{wsid}/budgets/{budgetId}', \Budgetcontrol\Stats\Controller\BudgetController::class . ':update');
$app->delete('/{wsid}/budgets/{budgetId}', \Budgetcontrol\Stats\Controller\BudgetController::class . ':delete');
$app->get('/{wsid}/budgets/{budgetId}/expired', \Budgetcontrol\Stats\Controller\BudgetController::class . ':expired');
$app->get('/{wsid}/budgets/{budgetId}/exceeded', \Budgetcontrol\Stats\Controller\BudgetController::class . ':exceeded');
$app->get('/{wsid}/budgets/{budgetId}/status', \Budgetcontrol\Stats\Controller\BudgetController::class . ':status');

