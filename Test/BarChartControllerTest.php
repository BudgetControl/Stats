<?php

namespace Tests\Feature;

use Budgetcontrol\Stats\Controller\BarChartController;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BarChartControllerTest extends TestCase
{
    public function test_bar_expenses_category_by_date_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_category_response_has_bar_key(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
    }

    public function test_bar_expenses_category_with_categories_filter_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ],
            'categories' => ['food', 'transport']
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_category_with_multiple_date_ranges_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31'],
                ['start' => date('Y') . '/02/01', 'end' => date('Y') . '/02/28'],
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_labels_by_date_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_labels_response_has_bar_key(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, ['wsid' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('bar', $body);
        $this->assertIsArray($body['bar']);
    }

    public function test_bar_expenses_labels_with_filter_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ],
            'labels' => ['food']
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_labels_with_multiple_date_ranges_returns_200(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31'],
                ['start' => date('Y') . '/02/01', 'end' => date('Y') . '/02/28'],
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
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
        $mock->method('expensesByLabels')->willReturn([]);

        return $mock;
    }
}
