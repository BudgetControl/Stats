<?php

namespace Budgetcontrol\Stats\Controller;

use DateTime;
use Illuminate\Support\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChart;
use Budgetcontrol\Stats\Domain\Repository\DebitRepository;
use Budgetcontrol\Stats\Domain\Repository\SavingRepository;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartPoint;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartSeries;

class LineChartController extends ChartController
{

    public function incomingExpensesByDate(Request $request, Response $response, $arg)
    {

        $params = $request->getQueryParams();

        $lineChart = new LineChart();
        $incomingSeries = new LineChartSeries('incoming');
        $expensesSeries = new LineChartSeries('expenses');
        $debitSeries = new LineChartSeries('debit');
        $savingtSeries = new LineChartSeries('savings');

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);

            // incoming
            $incomingRepository = new IncomingRepository(
            $arg['wsid'],
            $startDate,
            $endDate
            );

            // expenses
            $expensesRepository = new ExpensesRepository(
            $arg['wsid'],
            $startDate,
            $endDate
            );

            // debit
            $debitRepository = new DebitRepository(
            $arg['wsid'],
            $startDate,
            $endDate
            );

            // savings
            $savingRepository = new SavingRepository(
                $arg['wsid'],
                $startDate,
                $endDate
                );

            $yValue = max(
            $incomingRepository->statsIncoming()['total'],
            $expensesRepository->statsExpenses()['total'],
            $debitRepository->statsDebits()['total'],
            $savingRepository->statsSevings()['total']
            );

            $incomingSeries->addDataPoint(
            new LineChartPoint(
                $incomingRepository->statsIncoming()['total'],
                $yValue, 
                $startDate->format('M')
            )
            );

            $expensesSeries->addDataPoint(
            new LineChartPoint(
                $expensesRepository->statsExpenses()['total'],
                $yValue,
                $startDate->format('M')
            )
            );

            $debitSeries->addDataPoint(
            new LineChartPoint(
                $debitRepository->statsDebits()['total'],
                $yValue,
                $startDate->format('M')
            )
            );

            $savingtSeries->addDataPoint(
                new LineChartPoint(
                    $savingRepository->statsSevings()['total'],
                    $yValue,
                    $startDate->format('M')
                )
            );

        }

        $lineChart->addSeries($incomingSeries);
        $lineChart->addSeries($expensesSeries);
        $lineChart->addSeries($debitSeries);
        $lineChart->addSeries($savingtSeries);
        $results = $lineChart->toArray();

        return response($results, 200);
    }
}
