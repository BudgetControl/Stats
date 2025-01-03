<?php

namespace Budgetcontrol\Stats\Controller;

use Budgetcontrol\Library\Model\Category;
use Budgetcontrol\Library\Model\Label;
use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Entity\BarChart\BarChart;
use Budgetcontrol\Stats\Domain\Entity\BarChart\BarChartBar;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Illuminate\Support\Carbon;
use Budgetcontrol\Library\Model\SubCategory;

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

            /** @var \Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory $expenses */
            foreach($expensesRepository->expensesByCategories() as $expenses) {
                $subCategory = SubCategory::with('category')->where('id', $expenses->categoryId)->first();

                $barChart->addBar(
                    new BarChartBar(
                        $expenses->total,
                        $subCategory,
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

            $startDate = Carbon::rawParse($value['start']);
            $endDate = Carbon::rawParse($value['end']);

            $incomingRepository = new ExpensesRepository(
                $arg['wsid'],
                $startDate,
                $endDate
            );

            foreach($incomingRepository->expensesByLabels() as $label) {
                if ($labels && !in_array($label->label_name, $labels)) {
                    continue;
                }

                $labelObj = Label::where('id', $label->label_id)->first();

                $barChart->addBar(
                    new BarChartBar(
                        $label->total,
                        $labelObj,
                    )
                );

            }

        }

        return response($barChart->toArray(), 200);

    }
}
