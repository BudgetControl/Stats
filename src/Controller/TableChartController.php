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
        $categories = empty($params['categories']) ? null : $params['categories'];

        $tableChart = new TableChart();

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);
             // count days between start and end
            $days = $startDate->diff($endDate);

            $startDatePrev = Carbon::rawParse($value['start'])->modify($days->days);
            $endDatePrev = Carbon::rawParse($value['end'])->subDays($days->days);

            $expensesRepository = new ExpensesRepository($arg['wsid'], $startDate, $endDate);
            $expensesPrevRepository = new ExpensesRepository($arg['wsid'], $startDatePrev, $endDatePrev);

            $expensesValue = $expensesRepository->expensesByCategories();
            $expensesVluePrev = $expensesPrevRepository->expensesByCategories();

            /** @var \Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory $expenses */
            foreach ($expensesValue as $expenses) {

                $tableChart->addRows(
                    new TableRowChart(
                        $expenses->total,
                        $expensesVluePrev[$expenses->categorySlug]->total ?? 0,
                        $expenses->categorySlug,
                        'expenses',
                    )
                );
            }
        }

        return response($tableChart->toArray(), 200);
    }
}
