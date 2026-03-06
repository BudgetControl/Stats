<?php
namespace Budgetcontrol\Stats\Controller;

use Illuminate\Support\Carbon;
use Webit\Wrapper\BcMath\BcMathNumber;
use Budgetcontrol\Stats\Helpers\PercentCalculator;
use Psr\Http\Message\ResponseInterface as Response;
use Brick\Math\Internal\Calculator\BcMathCalculator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableChart;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Entity\TableChart\TableRowChart;
use Budgetcontrol\Stats\Domain\Repository\DebitRepository;
use Budgetcontrol\Stats\Domain\Repository\PlannedEntryRepository;
use Budgetcontrol\Stats\Domain\Repository\SavingRepository;
use Budgetcontrol\Stats\Services\StatsService;

class StatsController extends Controller {

    protected function createIncomingRepository(string $wsid, Carbon $startDate, Carbon $endDate): IncomingRepository
    {
        return new IncomingRepository($wsid, $startDate, $endDate);
    }

    protected function createExpensesRepository(string $wsid, Carbon $startDate, Carbon $endDate): ExpensesRepository
    {
        return new ExpensesRepository($wsid, $startDate, $endDate);
    }

    protected function createDebitRepository(string $wsid, Carbon $startDate, Carbon $endDate): DebitRepository
    {
        return new DebitRepository($wsid, $startDate, $endDate);
    }

    protected function createStatsRepository(string $wsid, Carbon $startDate, Carbon $endDate): StatsRepository
    {
        return new StatsRepository($wsid, $startDate, $endDate);
    }

    protected function createSavingRepository(string $wsid, Carbon $startDate, Carbon $endDate): SavingRepository
    {
        return new SavingRepository($wsid, $startDate, $endDate);
    }

    protected function createPlannedEntryRepository(string $wsid, Carbon $startDate, Carbon $endDate): PlannedEntryRepository
    {
        return new PlannedEntryRepository($wsid, $startDate, $endDate);
    }

    protected function createStatsService(string $wsid, Carbon $startDate, Carbon $endDate): StatsService
    {
        return new StatsService($wsid, $startDate, $endDate);
    }

    public function incomingOfCurrentMonth(Request $request, Response $response, $arg) {
        
        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createIncomingRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = $repository->statsIncoming()['total'];

        $repository = $this->createIncomingRepository($arg['wsid'], $startDate->modify("-1 month"), $endDate->modify("-1 month"));
        $previusAMount = $repository->statsIncoming()['total'];

        return response([
            "percentage" => round(PercentCalculator::calculatePercentage('margin_percentage', $previusAMount, $currentAmount)),
            "total" => (float) $currentAmount,
            "total_passed" => $previusAMount,
        ],200);
    }

    public function expensesOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createExpensesRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = $repository->statsExpenses()['total'];

        $repository = $this->createExpensesRepository($arg['wsid'], $startDate->modify("-1 month"), $endDate->modify("-1 month"));
        $previusAMount = $repository->statsExpenses()['total'];

        return response([
            "percentage" => round(PercentCalculator::calculatePercentage('margin_percentage', $previusAMount, $currentAmount,) * -1 ),
            "total" => (float) $currentAmount,
            "total_passed" => $previusAMount,
        ],200);

    }

    public function debitsOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createDebitRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = $repository->statsDebits()['total'];

        $repository = $this->createDebitRepository($arg['wsid'], $startDate->modify("-1 month"), $endDate->modify("-1 month"));
        $previusAMount = $repository->statsDebits()['total'];

        return response([
            "percentage" => round(PercentCalculator::calculatePercentage('margin_percentage', $previusAMount, $currentAmount)),
            "total" => (float) $currentAmount,
            "total_passed" => $previusAMount,
        ],200);

    }

    public function totalNegativeDebits(Request $request, Response $response, $arg) {

        $startDate = Carbon::now();
        $endDate = Carbon::now();

        $repository = $this->createDebitRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = $repository->totalNegativeStatsDebits()['total'];

        return response([
            "total" => (float) $currentAmount,
        ],200);
    }

    public function totalPositiveDebits(Request $request, Response $response, $arg) {

        $startDate = Carbon::now();
        $endDate = Carbon::now();

        $repository = $this->createDebitRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = $repository->totalPositiveStatsDebits()['total'];

        return response([
            "total" => (float) $currentAmount,
        ],200);
    }


    public function totalOfCurrentMonth(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createStatsRepository(
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

        $repository = $this->createStatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->wallets();

        return response($result,200);

    }

    public function health(Request $request, Response $response, $arg) {

        $repository = $this->createStatsRepository(
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

        $service = $this->createStatsService(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $values = $service->willArriveAtTheEndOfTheMonth();
        $total = $values->get();

        return response(['total' => $total->toFloat()],200);
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

        $entriesRepository = $this->createStatsRepository($arg['wsid'], $startDate, $endDate);
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

        // get the current month number
        $months = Carbon::now()->month;
        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = $this->createExpensesRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = round($repository->statsExpenses()['total'] / $months);

        return response([
            "total" => (float) $currentAmount,
        ],200);

    }

    public function averageIncoming(Request $request, Response $response, $arg) {

        $months = Carbon::now()->month;
        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = $this->createIncomingRepository($arg['wsid'], $startDate, $endDate);
        $currentAmount = round($repository->statsIncoming()['total'] / $months);

        return response([
            "total" => (float) $currentAmount,
        ],200);

    }

     public function averageSavings(Request $request, Response $response, $arg) {

        $months = Carbon::now()->month;
        $startDate = Carbon::now()->firstOfYear();
        $endDate = Carbon::now()->lastOfYear();

        $repository = $this->createSavingRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->statsSevings('savings');
        $total = $result['total'];
        $currentAmount = round($total / $months);

        return response([
            "total" => (float) $currentAmount,
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

        $repository = $this->createPlannedEntryRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->getPlanedMonthlyExpenses();
        $totalStats = new BcMathCalculator();
        $creditCards = $repository->loanOfCreditCards();
        $total = $result['total'];
        foreach($creditCards as $creditCard) {
            $balance = $creditCard->balance > $creditCard->installement_value ? $creditCard->balance : $creditCard->installement_value;
            $total = $totalStats->add($total, $balance);
        }
        

        return response([
            "total" => (float) $total,
        ],200);

    }

    /**
     * Calculate the total planned remaining amount for the current month.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional arguments.
     * @return Response The HTTP response object.
     */
    public function totalPlannedRemainingOfCurrentMonth(Request $request, Response $response, $arg): Response {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createStatsRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $result = $repository->plannedExpenses();

        return response([
            "total" => (float) $result->total
        ],200);

    }

    /**
     * Calculate the total planned monthly entry.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional arguments.
     * @return void
     */
    public function totalPlannedMonthlyEntry(Request $request, Response $response, $arg) {

        $startDate = Carbon::now()->firstOfMonth();
        $endDate = Carbon::now()->lastOfMonth();

        $repository = $this->createPlannedEntryRepository(
            $arg['wsid'],
            $startDate,
            $endDate
        );
        $monthly = $repository->getPlanedMonthlyExpenses();
        $weekly = $repository->getPlanedWeeklyExpenses();
        $daily = $repository->getPlanedDailyExpenses();

        // Calculate the total planned entry.
        // Moltiplique the weekly and daily planned expenses by the number of weeks and days in the month.
        $totalMonthly = $monthly['total'];

        //check if the month has 4 or 5 weeks
        $weeks = $this->weeksInMonth(date('m'), date('Y'));
        $totalWeekly = $weekly['total'] * $weeks;

        //check if the month has 30 or 31 days
        $days = $this->daysInMonth(date('m'), date('Y'));
        $totalDaily = $daily['total'] * $days;

        $total = new BcMathNumber($totalMonthly);
        $total = $total->add($totalWeekly);
        $total = $total->add($totalDaily);

        return response([
            "total" => $total->toFloat(),
        ],200);

    }
    
}
