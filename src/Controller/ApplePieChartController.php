<?php

namespace Budgetcontrol\Stats\Controller;

use Illuminate\Support\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Entity\ApplePie\ApplePieChart;
use Budgetcontrol\Stats\Domain\Entity\ApplePie\ApplePieChartField;

/**
 * Represents a controller for generating an Apple Pie Chart.
 * Extends the ChartController class.
 */
class ApplePieChartController extends ChartController
{
    public function expensesLabelsByDate(Request $request, Response $response, $arg): Response
    {
        $params = $request->getQueryParams();
        $labels = empty($params['labels']) ? null : $params['labels'];

        $applePieChart = new ApplePieChart();

        foreach ($params['date_time'] as $_ => $value) {

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

                $applePieChart->addField(
                    new ApplePieChartField(
                        $label->total,
                        $label->label_name,
                        $label->label_id
                    )
                );

            }

        }

        return response($applePieChart->toArray(), 200);
    }
}
