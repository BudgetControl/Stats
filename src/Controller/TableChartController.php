<?php

namespace Budgetcontrol\Stats\Controller;

use DateTime;
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
        $categories = empty($params['categories']) ? null : $params['categories'];

        $tableChart = new TableChart();

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);

            $startDatePrev = Carbon::rawParse($value['start'])->modify('-1 month');
            $endDatePrev = Carbon::rawParse($value['end'])->modify('-1 month');

            $expensesRepository = new ExpensesRepository($arg['wsid'], $startDate, $endDate);
            $expensesPrevRepository = new ExpensesRepository($arg['wsid'], $startDatePrev, $endDatePrev);

            foreach ($expensesRepository->expensesByCategory() as $category) {
                if ($categories && !in_array($category->category_name, $categories)) {
                    continue;
                }

                $expensesVluePrev = $expensesPrevRepository->expensesByCategory([$category->category_id])[0];
                $tableChart->addRows(
                    new TableRowChart(
                        $category->total,
                        $expensesVluePrev->total ?? 0,
                        $category->category_name,
                        'expenses',
                    )
                );
            }
        }

        return response($tableChart->toArray(), 200);
    }
}
