<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Budgetcontrol\Stats\Controller\StatsController;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\DebitRepository;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Budgetcontrol\Stats\Domain\Repository\SavingRepository;
use Budgetcontrol\Stats\Domain\Repository\PlannedEntryRepository;
use Budgetcontrol\Stats\Services\StatsService;
use Budgetcontrol\Stats\Domain\ValueObjects\StatsCalculator;
use Illuminate\Support\Carbon;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Testable subclass that allows repository injection via factory method override.
 */
class TestableStatsController extends StatsController
{
    private ?IncomingRepository $incomingRepository = null;
    private ?ExpensesRepository $expensesRepository = null;
    private ?DebitRepository $debitRepository = null;
    private ?StatsRepository $statsRepository = null;
    private ?SavingRepository $savingRepository = null;
    private ?PlannedEntryRepository $plannedEntryRepository = null;
    private ?StatsService $statsService = null;

    public function setIncomingRepository(IncomingRepository $repo): void
    {
        $this->incomingRepository = $repo;
    }

    public function setExpensesRepository(ExpensesRepository $repo): void
    {
        $this->expensesRepository = $repo;
    }

    public function setDebitRepository(DebitRepository $repo): void
    {
        $this->debitRepository = $repo;
    }

    public function setStatsRepository(StatsRepository $repo): void
    {
        $this->statsRepository = $repo;
    }

    public function setSavingRepository(SavingRepository $repo): void
    {
        $this->savingRepository = $repo;
    }

    public function setPlannedEntryRepository(PlannedEntryRepository $repo): void
    {
        $this->plannedEntryRepository = $repo;
    }

    public function setStatsService(StatsService $service): void
    {
        $this->statsService = $service;
    }

    protected function createIncomingRepository(string $wsid, Carbon $startDate, Carbon $endDate): IncomingRepository
    {
        return $this->incomingRepository ?? parent::createIncomingRepository($wsid, $startDate, $endDate);
    }

    protected function createExpensesRepository(string $wsid, Carbon $startDate, Carbon $endDate): ExpensesRepository
    {
        return $this->expensesRepository ?? parent::createExpensesRepository($wsid, $startDate, $endDate);
    }

    protected function createDebitRepository(string $wsid, Carbon $startDate, Carbon $endDate): DebitRepository
    {
        return $this->debitRepository ?? parent::createDebitRepository($wsid, $startDate, $endDate);
    }

    protected function createStatsRepository(string $wsid, Carbon $startDate, Carbon $endDate): StatsRepository
    {
        return $this->statsRepository ?? parent::createStatsRepository($wsid, $startDate, $endDate);
    }

    protected function createSavingRepository(string $wsid, Carbon $startDate, Carbon $endDate): SavingRepository
    {
        return $this->savingRepository ?? parent::createSavingRepository($wsid, $startDate, $endDate);
    }

    protected function createPlannedEntryRepository(string $wsid, Carbon $startDate, Carbon $endDate): PlannedEntryRepository
    {
        return $this->plannedEntryRepository ?? parent::createPlannedEntryRepository($wsid, $startDate, $endDate);
    }

    protected function createStatsService(string $wsid, Carbon $startDate, Carbon $endDate): StatsService
    {
        return $this->statsService ?? parent::createStatsService($wsid, $startDate, $endDate);
    }
}

/**
 * Unit tests for StatsController.
 * Verifies that repositories return statistics data and controllers process it correctly.
 */
class StatsControllerTest extends TestCase
{
    private TestableStatsController $controller;
    private array $defaultArg = ['wsid' => 'test-workspace-uuid'];

    protected function setUp(): void
    {
        $this->controller = new TestableStatsController();
    }

    private function createRequest(string $method = 'GET', array $parsedBody = [], array $queryParams = []): \Psr\Http\Message\ServerRequestInterface
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, '/');

        if (!empty($parsedBody)) {
            $request = $request->withParsedBody($parsedBody);
        }
        if (!empty($queryParams)) {
            $request = $request->withQueryParams($queryParams);
        }

        return $request;
    }

    private function createResponse(): \Psr\Http\Message\ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }

    private function decodeResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }

    // ============ IncomingRepository Tests ============

    public function testIncomingOfCurrentMonthReturnsStatsWithTotal(): void
    {
        /** @var IncomingRepository&MockObject $repo */
        $repo = $this->createMock(IncomingRepository::class);
        $repo->method('statsIncoming')->willReturn(['total' => 1500.0]);
        $this->controller->setIncomingRepository($repo);

        $response = $this->controller->incomingOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertArrayHasKey('percentage', $body);
        $this->assertArrayHasKey('total_passed', $body);
        $this->assertIsNumeric($body['total']);
        $this->assertGreaterThanOrEqual(0, $body['total']);
    }

    public function testIncomingOfCurrentMonthWithZeroReturnsStatsData(): void
    {
        /** @var IncomingRepository&MockObject $repo */
        $repo = $this->createMock(IncomingRepository::class);
        $repo->method('statsIncoming')->willReturn(['total' => 0.0]);
        $this->controller->setIncomingRepository($repo);

        $response = $this->controller->incomingOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $body = $this->decodeResponse($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(0, $body['total']);
    }

    // ============ ExpensesRepository Tests ============

    public function testExpensesOfCurrentMonthReturnsStatsWithTotal(): void
    {
        /** @var ExpensesRepository&MockObject $repo */
        $repo = $this->createMock(ExpensesRepository::class);
        $repo->method('statsExpenses')->willReturn(['total' => -500.0]);
        $this->controller->setExpensesRepository($repo);

        $response = $this->controller->expensesOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertArrayHasKey('percentage', $body);
        $this->assertArrayHasKey('total_passed', $body);
    }

    public function testAverageExpensesReturnsStatsWithTotal(): void
    {
        /** @var ExpensesRepository&MockObject $repo */
        $repo = $this->createMock(ExpensesRepository::class);
        $repo->method('statsExpenses')->willReturn(['total' => -1200.0]);
        $this->controller->setExpensesRepository($repo);

        $response = $this->controller->averageExpenses(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
    }

    // ============ DebitRepository Tests ============

    public function testDebitsOfCurrentMonthReturnsStatsWithTotal(): void
    {
        /** @var DebitRepository&MockObject $repo */
        $repo = $this->createMock(DebitRepository::class);
        $repo->method('statsDebits')->willReturn(['total' => 200.0]);
        $this->controller->setDebitRepository($repo);

        $response = $this->controller->debitsOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertArrayHasKey('percentage', $body);
        $this->assertArrayHasKey('total_passed', $body);
    }

    public function testTotalNegativeDebitsReturnsStatsWithTotal(): void
    {
        /** @var DebitRepository&MockObject $repo */
        $repo = $this->createMock(DebitRepository::class);
        $repo->method('totalNegativeStatsDebits')->willReturn(['total' => -300.0]);
        $this->controller->setDebitRepository($repo);

        $response = $this->controller->totalNegativeDebits(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
    }

    public function testTotalPositiveDebitsReturnsStatsWithTotal(): void
    {
        /** @var DebitRepository&MockObject $repo */
        $repo = $this->createMock(DebitRepository::class);
        $repo->method('totalPositiveStatsDebits')->willReturn(['total' => 150.0]);
        $this->controller->setDebitRepository($repo);

        $response = $this->controller->totalPositiveDebits(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertEquals(150.0, $body['total']);
    }

    // ============ StatsRepository Tests ============

    public function testTotalOfCurrentMonthReturnsStatsWithTotal(): void
    {
        /** @var StatsRepository&MockObject $repo */
        $repo = $this->createMock(StatsRepository::class);
        $repo->method('total')->willReturn(['total' => 5000.0]);
        $this->controller->setStatsRepository($repo);

        $response = $this->controller->totalOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertEquals(5000.0, $body['total']);
    }

    public function testWalletsReturnsStatsArray(): void
    {
        $walletsData = [
            ['id' => 1, 'name' => 'Cash', 'balance' => 100.0],
            ['id' => 2, 'name' => 'Bank', 'balance' => 2000.0],
        ];

        /** @var StatsRepository&MockObject $repo */
        $repo = $this->createMock(StatsRepository::class);
        $repo->method('wallets')->willReturn($walletsData);
        $this->controller->setStatsRepository($repo);

        $response = $this->controller->wallets(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertIsArray($body);
        $this->assertCount(2, $body);
    }

    public function testHealthReturnsStatsWithTotal(): void
    {
        /** @var StatsRepository&MockObject $repo */
        $repo = $this->createMock(StatsRepository::class);
        $repo->method('health')->willReturn(['total' => 4500.0]);
        $this->controller->setStatsRepository($repo);

        $response = $this->controller->health(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertEquals(4500.0, $body['total']);
    }

    public function testEntriesReturnsTableChartData(): void
    {
        $statsResult = [
            (object)['total' => -100.0, 'category_slug' => 'food', 'category_type' => 'expenses'],
            (object)['total' => -50.0, 'category_slug' => 'transport', 'category_type' => 'expenses'],
        ];

        /** @var StatsRepository&MockObject $repo */
        $repo = $this->createMock(StatsRepository::class);
        $repo->method('statsByFilters')->willReturn($statsResult);
        $this->controller->setStatsRepository($repo);

        $body = [
            'date' => ['start' => '2024/01/01', 'end' => '2024/01/31'],
            'type' => 'expenses',
        ];

        $response = $this->controller->entries(
            $this->createRequest('POST', $body),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $decoded = $this->decodeResponse($response);
        $this->assertArrayHasKey('rows', $decoded);
        $this->assertIsArray($decoded['rows']);
    }

    public function testTotalPlannedRemainingOfCurrentMonthReturnsStatsWithTotal(): void
    {
        $plannedResult = new \stdClass();
        $plannedResult->total = 350.0;

        /** @var StatsRepository&MockObject $repo */
        $repo = $this->createMock(StatsRepository::class);
        $repo->method('plannedExpenses')->willReturn($plannedResult);
        $this->controller->setStatsRepository($repo);

        $response = $this->controller->totalPlannedRemainingOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertEquals(350.0, $body['total']);
    }

    // ============ SavingRepository Tests ============

    public function testAverageSavingsReturnsStatsWithTotal(): void
    {
        /** @var SavingRepository&MockObject $repo */
        $repo = $this->createMock(SavingRepository::class);
        $repo->method('statsSevings')->willReturn(['total' => 600.0]);
        $this->controller->setSavingRepository($repo);

        $response = $this->controller->averageSavings(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
    }

    // ============ IncomingRepository (average) Tests ============

    public function testAverageIncomingReturnsStatsWithTotal(): void
    {
        /** @var IncomingRepository&MockObject $repo */
        $repo = $this->createMock(IncomingRepository::class);
        $repo->method('statsIncoming')->willReturn(['total' => 3000.0]);
        $this->controller->setIncomingRepository($repo);

        $response = $this->controller->averageIncoming(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
    }

    // ============ PlannedEntryRepository Tests ============

    public function testTotalLoanInstallmentsOfCurrentMonthReturnsStatsWithTotal(): void
    {
        /** @var PlannedEntryRepository&MockObject $repo */
        $repo = $this->createMock(PlannedEntryRepository::class);
        $repo->method('getPlanedMonthlyExpenses')->willReturn(['total' => 400.0]);
        $repo->method('loanOfCreditCards')->willReturn([]);
        $this->controller->setPlannedEntryRepository($repo);

        $response = $this->controller->totalLoanInstallmentsOfCurrentMonth(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
    }

    public function testTotalPlannedMonthlyEntryReturnsStatsWithTotal(): void
    {
        /** @var PlannedEntryRepository&MockObject $repo */
        $repo = $this->createMock(PlannedEntryRepository::class);
        $repo->method('getPlanedMonthlyExpenses')->willReturn(['total' => 200.0]);
        $repo->method('getPlanedWeeklyExpenses')->willReturn(['total' => 50.0]);
        $repo->method('getPlanedDailyExpenses')->willReturn(['total' => 5.0]);
        $this->controller->setPlannedEntryRepository($repo);

        $response = $this->controller->totalPlannedMonthlyEntry(
            $this->createRequest(),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('total', $body);
        $this->assertIsNumeric($body['total']);
        // Total should include monthly + (weekly * weeks) + (daily * days)
        $this->assertGreaterThan(0.0, $body['total']);
    }
}
