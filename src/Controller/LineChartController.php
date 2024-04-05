<?php

namespace Budgetcontrol\Stats\Controller;

use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChart;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartPoint;
use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartSeries;
use Illuminate\Support\Carbon;

class LineChartController extends ChartController
{

    public function incomingExpensesByDate(Request $request, Response $response, $arg)
    {

        $params = $request->getQueryParams();

        $lineChart = new LineChart();
        $incomingSeries = new LineChartSeries('Incoming');
        $expensesSeries = new LineChartSeries('Expenses');

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);

            $incomingRepository = new IncomingRepository(
                $arg['wsid'],
                $startDate,
                $endDate
            );

            $incomingSeries->addDataPoint(
                new LineChartPoint(
                    $incomingRepository->statsIncoming()['total'],
                    5000,
                    $startDate->format('M')
                )
            );


            $expensesRepository = new ExpensesRepository(
                $arg['wsid'],
                $startDate,
                $endDate
            );

            $expensesSeries->addDataPoint(
                new LineChartPoint(
                    $expensesRepository->statsExpenses()['total'],
                    5000,
                    $startDate->format('M')
                )
            );

        }

        $lineChart->addSeries($incomingSeries);
        $lineChart->addSeries($expensesSeries);
        $results = $lineChart->toArray();

        return response($results, 200);
    }
}
