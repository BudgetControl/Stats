<?php

namespace Budgetcontrol\Stats\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableChart;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableRowChart;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Illuminate\Support\Carbon;

class TableChartController extends ChartController
{

    public function expensesCategoryByDate(Request $request, Response $response, $arg): Response
    {
        $params = $request->getQueryParams();
        $tableChart = new TableChart();

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);
             // count days between start and end
            $days = $startDate->diffInDays($endDate);

            $startDatePrev = Carbon::rawParse($value['start'])->subDays($days);
            $endDatePrev = Carbon::rawParse($value['end'])->subDays($days);

            $expensesResults = ExpensesRepository::setup($arg['wsid'], $startDate, $endDate)->expensesByCategories();
            $expensesPrevResults = ExpensesRepository::setup($arg['wsid'], $startDatePrev, $endDatePrev)->expensesByCategories();

            /** @var \Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory $expenses */
            foreach ($expensesResults as $expenses) {

                $tableChart->addRows(
                    new TableRowChart(
                        $expenses->total,
                        $expensesPrevResults[$expenses->categorySlug]->total ?? 0,
                        $expenses->categorySlug,
                        'expenses',
                    )
                );
            }
        }

        return response($tableChart->toArray(), 200);
    }
}
