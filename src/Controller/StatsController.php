<?php
namespace Budgetcontrol\Stats\Controller;

use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Brick\Math\Internal\Calculator\BcMathCalculator;
use DateTime;
use Webit\Wrapper\BcMath\BcMathNumber;
use Illuminate\Support\Carbon;

class StatsController {

    public function incomingOfCurrentMonth(Request $request, Response $response, $arg) {
        
        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new IncomingRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->statsIncoming();

        return response($result,200);
    }

    public function expensesOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = new ExpensesRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->statsExpenses();

        return response($result,200);
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
        $results = $repository->totalWithPlannedOfCurrentMonth();
        $math = new BcMathNumber();
        $total = 0;
        foreach($results as $key => $result) {
            $total = $math->add($result);
        }


        return response(['balance' => $total],200);
    }
}