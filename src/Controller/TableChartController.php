<?php

namespace Budgetcontrol\Stats\Controller;

use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableChart;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableRowChart;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Illuminate\Support\Carbon;
use Budgetcontrol\Library\Model\SubCategory;

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

            foreach (SubCategory::all() as $category) {
               
                $categoryStats = $expensesRepository->expensesByCategory([$category->id]);
                $expensesVluePrev = $expensesPrevRepository->expensesByCategory([$category->id]);

                if(is_null($categoryStats)) {
                    $categoryStats = (object) ['total' => 0, 'category_slug' => $category->slug];
                }

                if(is_null($expensesVluePrev)) {
                    $expensesVluePrev = (object) ['total' => 0];
                }

                $tableChart->addRows(
                    new TableRowChart(
                        $categoryStats->total,
                        $expensesVluePrev->total ?? 0,
                        $categoryStats->category_slug,
                        'expenses',
                    )
                );
            }
        }

        return response($tableChart->toArray(), 200);
    }
}
