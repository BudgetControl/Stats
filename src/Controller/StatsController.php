<?php
namespace Budgetcontrol\Stats\Controller;

use Brick\Math\BigNumber;
use Brick\Math\BigInteger;
use Illuminate\Support\Carbon;
use Budgetcontrol\Stats\Helpers\PercentCalculator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableChart;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableRowChart;

class StatsController {

    public function incomingOfCurrentMonth(Request $request, Response $response, $arg) {
        
        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new IncomingRepository($arg['wsid'],$startDate,$endDate);
        $currentAmount = $repository->statsIncoming()['total'];

        $repository = new IncomingRepository($arg['wsid'],$startDate->modify("-1 month"),$endDate->modify("-1 month"));
        $previusAMount = $repository->statsIncoming()['total'];

        return response([
            "percentage" => round(PercentCalculator::calculatePercentage('margin_percentage', $currentAmount, $previusAMount)),
            "total" => $currentAmount,
            "total_passed" => $previusAMount,
        ],200);
    }

    public function expensesOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new ExpensesRepository($arg['wsid'],$startDate,$endDate);
        $currentAmount = $repository->statsExpenses()['total'];

        $repository = new ExpensesRepository($arg['wsid'],$startDate->modify("-1 month"),$endDate->modify("-1 month"));
        $previusAMount = $repository->statsExpenses()['total'];

        return response([
            "percentage" => round(PercentCalculator::calculatePercentage('margin_percentage', $currentAmount, $previusAMount)),
            "total" => $currentAmount,
            "total_passed" => $previusAMount,
        ],200);

    }

    public function totalOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->total();

        return response($result,200);

    }

    public function wallets(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->wallets();

        return response($result,200);

    }

    public function health(Request $request, Response $response, $arg) {

        $repository = new StatsRepository(
            $arg['wsid'],
            Carbon::now()->firstOfMonth(),
            Carbon::now()->lastOfMonth()
        );
        $result = $repository->health();

        return response($result,200);

    }

    public function totalPlannedOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $planned = $repository->totalWithPlannedOfCurrentMonth();
        $installement_values = $repository->installementValues();
        $total = BigNumber::sum($planned->balance_without_installement, $planned->planned_amount_total);
        foreach($installement_values as $value) {
            $total = BigNumber::sum($total, $value->installement_value);
        }
        /** @var BigInteger $total */
        return response(['total' => (float) $total->__toString()],200);
    }

    public function entries(Request $request, Response $response, $arg) {

        $body = $request->getParsedBody();

        $startDate = Carbon::parse($body['date']['start']) ?? Carbon::now()->firstOfMonth();
        $endDate = Carbon::parse($body['date']['end']) ?? Carbon::now()->lastOfMonth();

        $options = [
            'types' => $body['type'] ?? 'expenses',
            'categories' => $body['categories'] ?? [],
            'accounts' => $body['accounts'] ?? [],
            'tags' => $body['tags'] ?? [],
            'payment_methods' => $body['payment_methods'] ?? [],
            'currencies' => $body['currencies'] ?? null,
        ];

        $entriesRepository = new StatsRepository($arg['wsid'], $startDate, $endDate);
        $entries = $entriesRepository->statsByFilters($options);

        $tableChart = new TableChart();

        foreach ($entries as $entry) {
            $tableChart->addRows(
                new TableRowChart(
                    $entry->total,
                    null,
                    $entry->category_slug,
                    $entry->category_type
                )
            );
        }

        return response($tableChart->toArray(),200);

    }

    public function averageExpenses(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = new ExpensesRepository($arg['wsid'],$startDate,$endDate);
        $currentAmount = round($repository->statsExpenses()['total'] / 12);

        return response([
            "total" => $currentAmount,
        ],200);

    }

    public function averageIncoming(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = new IncomingRepository($arg['wsid'],$startDate,$endDate);
        $currentAmount = round($repository->statsIncoming()['total'] / 12);

        return response([
            "total" => $currentAmount,
        ],200);

    }

    public function averageSavings(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->statsByCategories('savings', 1);
        $total = $result->total;
        $currentAmount = round($total / 12);

        return response([
            "total" => $currentAmount,
        ],200);

    }

    /**
     * Calculate the average savings.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional arguments.
     * @return Response
     */
    public function totalLoanInstallmentsOfCurrentMonth(Request $request, Response $response, $arg): Response {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->statsByCategories('loans_interest', 1);
        $total = $result->total;

        //get load on creditCards
        $creditCards = $repository->loanOfCreditCards();
        if($creditCards->invoice_date > Carbon::now() && $creditCards->invoice_date < Carbon::now()->lastOfMonth()) {
            $total = BigNumber::sum($total, $creditCards->total);
        }
        

        return response([
            "total" => $total,
        ],200);

    }

    public function totalPlannedRemainingOfCurrentMonth(Request $request, Response $response, $arg): Response {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new StatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->plannedExpenses();
        $total = $result->total;

        //get load on creditCards
        $creditCards = $repository->loanOfCreditCards();
        if($creditCards->invoice_date > Carbon::now() && $creditCards->invoice_date < Carbon::now()->lastOfMonth()) {
            $total = BigNumber::sum($total, $creditCards->total);
        }

        return response([
            "total" => $total,
        ],200);

    }
    
}
