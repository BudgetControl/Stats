<?php

namespace Budgetcontrol\Stats\Controller;

use Illuminate\Support\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChart;
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
            $repository = $this->repository->setup(
            $arg['wsid'],
            $startDate,
            $endDate
            );

            $yValue = max(
                $repository->statsIncoming()['total'],
                $repository->statsExpenses()['total'],
                $repository->statsDebits()['total'],
                $repository->statsSevings()['total']
            );

            $incomingSeries->addDataPoint(
            new LineChartPoint(
                $repository->statsIncoming()['total'],
                $yValue, 
                $startDate->format('M')
            )
            );

            $expensesSeries->addDataPoint(
            new LineChartPoint(
                $repository->statsExpenses()['total'],
                $yValue,
                $startDate->format('M')
            )
            );

            $debitSeries->addDataPoint(
            new LineChartPoint(
                $repository->statsDebits()['total'],
                $yValue,
                $startDate->format('M')
            )
            );

            $savingtSeries->addDataPoint(
                new LineChartPoint(
                    $repository->statsSevings()['total'],
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
