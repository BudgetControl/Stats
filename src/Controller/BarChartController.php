<?php

namespace Budgetcontrol\Stats\Controller;

use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\BarChart\BarChart;
use Budgetcontrol\Stats\Domain\Entity\BarChart\BarChartBar;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Illuminate\Support\Carbon;

class BarChartController extends ChartController
{

    public function expensesCategoryByDate(Request $request, Response $response, $arg): Response
    {
        $params = $request->getQueryParams();
        $categories = empty($params['categories']) ? null : $params['categories'];

        $barChart = new BarChart();

        foreach ($params['date_time'] as $_ => $value) {

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);

            $expensesRepository = new ExpensesRepository(
                $arg['wsid'],
                $startDate,
                $endDate
            );

            foreach($expensesRepository->expensesByCategory() as $category) {
                if ($categories && !in_array($category->category_slug, $categories)) {
                    continue;
                }

                $barChart->addBar(
                    new BarChartBar(
                        $category->total,
                        $category->category_slug,
                        $category->category_id
                    )
                );

            }

        }

        return response($barChart->toArray(), 200);
    }

    public function expensesLabelsByDate(Request $request, Response $response, $arg): Response
    {
        $params = $request->getQueryParams();
        $labels = empty($params['labels']) ? null : $params['labels'];

        $barChart = new BarChart();

        foreach($params['date_time'] as $_ => $value) {

            $startDate = new DateTime($value['start']);
            $endDate = new DateTime($value['end']);

            $incomingRepository = new ExpensesRepository(
                $arg['wsid'],
                $startDate,
                $endDate
            );

            foreach($incomingRepository->expensesByLabels() as $label) {
                if ($labels && !in_array($label->label_name, $labels)) {
                    continue;
                }

                $barChart->addBar(
                    new BarChartBar(
                        $label->total,
                        $label->label_name,
                        $label->label_id
                    )
                );

            }

        }

        return response($barChart->toArray(), 200);

    }
}
