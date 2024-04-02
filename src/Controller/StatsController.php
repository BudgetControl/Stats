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

class StatsController {

    public function incoming(Request $request, Response $response, $arg) {
        
        $repository = new IncomingRepository(
            $arg['wsid'],
            new DateTime($request->getQueryParams()['start_date']),
            new DateTime($request->getQueryParams()['end_date'])
        );
        $result = $repository->statsIncoming();

        return response($result,200);
    }

    public function expenses(Request $request, Response $response, $arg) {

        $repository = new ExpensesRepository(
            $arg['wsid'],
            new DateTime($request->getQueryParams()['start_date']),
            new DateTime($request->getQueryParams()['end_date'])
        );
        $result = $repository->statsExpenses();

        return response($result,200);
    }

    public function total(Request $request, Response $response, $arg) {

        $repository = new StatsRepository(
            $arg['wsid'],
            new DateTime($request->getQueryParams()['start_date']),
            new DateTime($request->getQueryParams()['end_date'])
        );
        $result = $repository->total();

        return response($result,200);

    }

    public function wallets(Request $request, Response $response, $arg) {

        $repository = new StatsRepository(
            $arg['wsid'],
            new DateTime('first day of this month'),
            new DateTime('last day of this month')
        );
        $result = $repository->wallets();

        return response($result,200);

    }

    public function health(Request $request, Response $response, $arg) {

        $repository = new StatsRepository(
            $arg['wsid'],
            new DateTime('first day of this month'),
            new DateTime('last day of this month')
        );
        $result = $repository->health();

        return response($result,200);

    }

    public function totalPlanned(Request $request, Response $response, $arg) {

        $repository = new StatsRepository(
            $arg['wsid'],
            new DateTime('first day of this month'),
            new DateTime('last day of this month')
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