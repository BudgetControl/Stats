<?php

namespace Tests\Feature;

use Budgetcontrol\Stats\Controller\TableChartController;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TableChartControllerTest extends TestCase
{
    public function test_table_expenses_category_by_date_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_table_expenses_category_response_has_rows_key(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('rows', $body);
        $this->assertIsArray($body['rows']);
    }

    public function test_table_expenses_category_empty_result_has_empty_rows(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEmpty($body['rows']);
    }

    public function test_table_expenses_category_calls_setup_twice_per_date_range(): void
    {
        $repo = $this->getMockBuilder(StatsRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->exactly(2))->method('setup')->willReturnSelf();
        $repo->method('expensesByCategories')->willReturn([]);

        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($repo);
        $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);
    }

    public function test_table_expenses_category_with_multiple_date_ranges_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31'],
                ['start' => date('Y') . '/02/01', 'end' => date('Y') . '/02/28'],
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_table_response_has_type_key(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('type', $body);
        $this->assertEquals('Table', $body['type']);
    }

    private function mock(?array $payload = null, string $method = 'getQueryParams'): array
    {
        $request = $this->createMock(ServerRequestInterface::class);
        if ($payload) {
            $request->method($method)->willReturn($payload);
        }

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['assertJsonStructure'])
            ->getMockForAbstractClass();

        return [$request, $response];
    }

    private function repository(): StatsRepositoryInterface
    {
        $mock = $this->getMockBuilder(StatsRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('setup')->willReturnSelf();
        $mock->method('expensesByCategories')->willReturn([]);

        return $mock;
    }
}
