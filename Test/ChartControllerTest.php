<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Budgetcontrol\Stats\Controller\LineChartController;
use Budgetcontrol\Stats\Controller\BarChartController;
use Budgetcontrol\Stats\Controller\ApplePieChartController;
use Budgetcontrol\Stats\Controller\TableChartController;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Unit tests for Chart Controllers.
 * Verifies that repositories return statistics data and chart controllers produce valid responses.
 */
class ChartControllerTest extends TestCase
{
    private array $defaultArg = ['wsid' => 'test-workspace-uuid'];

    private function createRequest(string $method = 'GET', array $queryParams = [], array $parsedBody = []): \Psr\Http\Message\ServerRequestInterface
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, '/');

        if (!empty($queryParams)) {
            $request = $request->withQueryParams($queryParams);
        }
        if (!empty($parsedBody)) {
            $request = $request->withParsedBody($parsedBody);
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

    /**
     * Creates a mock of StatsRepositoryInterface where setup() returns itself.
     *
     * @return StatsRepositoryInterface&MockObject
     */
    private function createRepoMock(): StatsRepositoryInterface
    {
        /** @var StatsRepositoryInterface&MockObject $mock */
        $mock = $this->createMock(StatsRepositoryInterface::class);
        $mock->method('setup')->willReturnSelf();
        return $mock;
    }

    // ============ LineChartController Tests ============

    public function testLineChartIncomingExpensesByDateReturnsStatsWithSeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('statsIncoming')->willReturn(['total' => 1000.0]);
        $repo->method('statsExpenses')->willReturn(['total' => -500.0]);
        $repo->method('statsDebits')->willReturn(['total' => 200.0]);
        $repo->method('statsSevings')->willReturn(['total' => 300.0]);

        $controller = new LineChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
                ['start' => '2024/02/01', 'end' => '2024/02/28'],
            ],
        ];

        $response = $controller->incomingExpensesByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('series', $body);
        $this->assertIsArray($body['series']);
        $this->assertNotEmpty($body['series']);
    }

    public function testLineChartReturnsAllFourSeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('statsIncoming')->willReturn(['total' => 800.0]);
        $repo->method('statsExpenses')->willReturn(['total' => -400.0]);
        $repo->method('statsDebits')->willReturn(['total' => 100.0]);
        $repo->method('statsSevings')->willReturn(['total' => 50.0]);

        $controller = new LineChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/03/01', 'end' => '2024/03/31'],
            ],
        ];

        $response = $controller->incomingExpensesByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $body = $this->decodeResponse($response);
        // Should have 4 series: incoming, expenses, debit, savings
        $this->assertCount(4, $body['series']);
    }

    public function testLineChartWithZeroTotalsReturnsSeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('statsIncoming')->willReturn(['total' => 0.0]);
        $repo->method('statsExpenses')->willReturn(['total' => 0.0]);
        $repo->method('statsDebits')->willReturn(['total' => 0.0]);
        $repo->method('statsSevings')->willReturn(['total' => 0.0]);

        $controller = new LineChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->incomingExpensesByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('series', $body);
    }

    // ============ BarChartController Tests ============

    public function testBarChartExpensesCategoryByDateWithEmptyResultsReturnsEmptySeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('expensesByCategories')->willReturn([]);

        $controller = new BarChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->expensesCategoryByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
        $this->assertEmpty($body['bar']);
    }

    public function testBarChartExpensesLabelsByDateWithEmptyResultsReturnsEmptySeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('expensesByLabels')->willReturn([]);

        $controller = new BarChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->expensesLabelsByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
        $this->assertEmpty($body['bar']);
    }

    public function testBarChartExpensesCategoryByDateCallsRepositoryForEachDateRange(): void
    {
        $repo = $this->createRepoMock();
        $repo->expects($this->exactly(2))
            ->method('expensesByCategories')
            ->willReturn([]);

        $controller = new BarChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
                ['start' => '2024/02/01', 'end' => '2024/02/28'],
            ],
        ];

        $controller->expensesCategoryByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );
    }

    // ============ ApplePieChartController Tests ============

    public function testApplePieChartExpensesLabelsByDateWithEmptyResultsReturnsEmptyFields(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('expensesByLabels')->willReturn([]);

        $controller = new ApplePieChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->expensesLabelsByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertIsArray($body);
    }

    public function testApplePieChartFiltersByLabelParam(): void
    {
        $labelObj = new \stdClass();
        $labelObj->label_name = 'food';
        $labelObj->total = 150.0;
        $labelObj->name = 'Food';
        $labelObj->id = 1;

        $repo = $this->createRepoMock();
        $repo->method('expensesByLabels')->willReturn([$labelObj]);

        $controller = new ApplePieChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
            'labels' => ['food'],
        ];

        $response = $controller->expensesLabelsByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    // ============ TableChartController Tests ============

    public function testTableChartExpensesCategoryByDateWithEmptyResultsReturnsEmptySeries(): void
    {
        $repo = $this->createRepoMock();
        $repo->method('expensesByCategories')->willReturn([]);

        $controller = new TableChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->expensesCategoryByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('rows', $body);
        $this->assertIsArray($body['rows']);
        $this->assertEmpty($body['rows']);
    }

    public function testTableChartExpensesCategoryWithDataReturnsSeriesRows(): void
    {
        $category = new ExpensesCategory(-200.0, 'food', 1, 'Food');

        $repo = $this->createRepoMock();
        $repo->method('expensesByCategories')->willReturn(['food' => $category]);

        $controller = new TableChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $response = $controller->expensesCategoryByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decodeResponse($response);
        $this->assertArrayHasKey('rows', $body);
        $this->assertNotEmpty($body['rows']);
    }

    public function testTableChartCallsRepositorySetupForCurrentAndPreviousPeriod(): void
    {
        $repo = $this->createRepoMock();
        // setup() is called twice per date range (current + previous period)
        $repo->expects($this->exactly(2))->method('setup')->willReturnSelf();
        $repo->method('expensesByCategories')->willReturn([]);

        $controller = new TableChartController($repo);

        $queryParams = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $controller->expensesCategoryByDate(
            $this->createRequest('GET', $queryParams),
            $this->createResponse(),
            $this->defaultArg
        );
    }
}
