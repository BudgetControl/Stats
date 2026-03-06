<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Test;

use PHPUnit\Framework\TestCase;
use Budgetcontrol\Stats\Controller\LineChartController;
use Budgetcontrol\Stats\Controller\BarChartController;
use Budgetcontrol\Stats\Controller\ApplePieChartController;
use Budgetcontrol\Stats\Controller\TableChartController;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\ExpensesCategory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ChartControllerTest extends TestCase
{
    private array $defaultArg = ['wsid' => 'test-workspace-uuid'];

    private function mock(?array $payload = null, string $method = 'getQueryParams'): array
    {
        $request = $this->createMock(ServerRequestInterface::class);
        if ($payload) {
            $request->method($method)->willReturn($payload);
        }
        $response = $this->createMock(ResponseInterface::class);
        return [$request, $response];
    }

    private function repository(): StatsRepositoryInterface
    {
        $mock = $this->getMockBuilder(StatsRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('setup')->willReturnSelf();
        $mock->method('statsIncoming')->willReturn(['total' => 0.0]);
        $mock->method('statsExpenses')->willReturn(['total' => 0.0]);
        $mock->method('statsDebits')->willReturn(['total' => 0.0]);
        $mock->method('statsSevings')->willReturn(['total' => 0.0]);
        $mock->method('expensesByCategories')->willReturn([]);
        $mock->method('expensesByLabels')->willReturn([]);

        return $mock;
    }

    private function decodeResult(\Psr\Http\Message\ResponseInterface $result): array
    {
        return json_decode((string) $result->getBody(), true);
    }

    // ============ LineChartController Tests ============

    public function testLineChartIncomingExpensesByDateReturnsStatsWithSeries(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
                ['start' => '2024/02/01', 'end' => '2024/02/28'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new LineChartController($this->repository());
        $result = $controller->incomingExpensesByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('series', $body);
        $this->assertIsArray($body['series']);
        $this->assertNotEmpty($body['series']);
    }

    public function testLineChartReturnsAllFourSeries(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/03/01', 'end' => '2024/03/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new LineChartController($this->repository());
        $result = $controller->incomingExpensesByDate($request, $response, $this->defaultArg);

        $body = $this->decodeResult($result);
        $this->assertCount(4, $body['series']);
    }

    public function testLineChartWithZeroTotalsReturnsSeries(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new LineChartController($this->repository());
        $result = $controller->incomingExpensesByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('series', $body);
    }

    // ============ BarChartController Tests ============

    public function testBarChartExpensesCategoryByDateReturnsEmptySeries(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
        $this->assertEmpty($body['bar']);
    }

    public function testBarChartExpensesLabelsByDateReturnsEmptySeries(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
        $this->assertEmpty($body['bar']);
    }

    public function testBarChartExpensesCategoryCallsRepositoryForEachDateRange(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
                ['start' => '2024/02/01', 'end' => '2024/02/28'],
            ],
        ];

        $repo = $this->repository();
        $repo->expects($this->exactly(2))->method('expensesByCategories')->willReturn([]);

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($repo);
        $controller->expensesCategoryByDate($request, $response, $this->defaultArg);
    }

    // ============ ApplePieChartController Tests ============

    public function testApplePieChartExpensesLabelsByDateReturnsArray(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new ApplePieChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertIsArray($body);
    }

    public function testApplePieChartWithLabelDataReturnsResult(): void
    {
        $labelObj = new \stdClass();
        $labelObj->label_name = 'food';
        $labelObj->total = 150.0;
        $labelObj->name = 'Food';
        $labelObj->id = 1;

        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
            'labels' => ['food'],
        ];

        $repo = $this->getMockBuilder(StatsRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('setup')->willReturnSelf();
        $repo->method('expensesByLabels')->willReturn([$labelObj]);

        list($request, $response) = $this->mock($payload);
        $controller = new ApplePieChartController($repo);
        $result = $controller->expensesLabelsByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ============ TableChartController Tests ============

    public function testTableChartExpensesCategoryByDateReturnsEmptyRows(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('rows', $body);
        $this->assertIsArray($body['rows']);
        $this->assertEmpty($body['rows']);
    }

    public function testTableChartExpensesCategoryWithDataReturnsRows(): void
    {
        $category = new ExpensesCategory(-200.0, 'food', 1, 'Food');

        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $repo = $this->getMockBuilder(StatsRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('setup')->willReturnSelf();
        $repo->method('expensesByCategories')->willReturn(['food' => $category]);

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($repo);
        $result = $controller->expensesCategoryByDate($request, $response, $this->defaultArg);

        $this->assertEquals(200, $result->getStatusCode());
        $body = $this->decodeResult($result);
        $this->assertArrayHasKey('rows', $body);
        $this->assertNotEmpty($body['rows']);
    }

    public function testTableChartCallsRepositorySetupForCurrentAndPreviousPeriod(): void
    {
        $payload = [
            'date_time' => [
                ['start' => '2024/01/01', 'end' => '2024/01/31'],
            ],
        ];

        $repo = $this->repository();
        $repo->expects($this->exactly(2))->method('setup')->willReturnSelf();
        $repo->method('expensesByCategories')->willReturn([]);

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($repo);
        $controller->expensesCategoryByDate($request, $response, $this->defaultArg);
    }
}
