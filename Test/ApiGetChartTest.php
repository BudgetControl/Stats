<?php

namespace Tests\Feature;

use Budgetcontrol\Stats\Controller\ApplePieChartController;
use Budgetcontrol\Stats\Controller\BarChartController;
use Budgetcontrol\Stats\Controller\LineChartController;
use Budgetcontrol\Stats\Controller\TableChartController;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiGetChartTest extends TestCase
{
    const BAR = [
        "series" => [
            [
                "label",
                "value",
                "color",
            ],
        ]
    ];

    const LINE = [
        "series" => [
            [
                "label",
                "color",
                "points" => [
                    [
                        "label",
                        "x",
                        "y"
                    ],
                ]
            ],
        ]
    ];

    const TABLE = [
        "series" => [
            [
                "value",
                "value_previus",
                "label",
                "bounce_rate"
            ],
        ]
    ];



    /**
     * A basic feature test example.
     */
    public function test_line_incoming_expenses_data(): void
    {
        $y = date('Y',time());
        $m = date('m',time());

        $payload = [
            'date_time' => [
                [
                    'start' => "$y/$m/01",
                    'end' => "$y/$m/20"
                ]
            ]
        ];

        list($request, $response) = $this->mock($payload);
        $controller = new LineChartController($this->repository());
        $result = $controller->incomingExpensesByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
        $response->assertJsonStructure(self::LINE);

    }

    public function test_table_expenses_category_data(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload, 'getQueryParams');
        $controller = new TableChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_category_data(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload, 'getQueryParams');
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesCategoryByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_bar_expenses_label_data(): void
    {
        $payload = [
            'date_time' => [
                ['start' => date('Y') . '/01/01', 'end' => date('Y') . '/01/31']
            ]
        ];

        list($request, $response) = $this->mock($payload, 'getQueryParams');
        $controller = new BarChartController($this->repository());
        $result = $controller->expensesLabelsByDate($request, $response, ['wsid' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
    }

    private function mock(?array $payload = null, string $method = 'getQueryParams'): array
    {
        $request = $this->createMock(ServerRequestInterface::class);
        if($payload) {
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
        $mock->method('statsIncoming')->willReturn(['total' => 0.0]);
        $mock->method('statsExpenses')->willReturn(['total' => 0.0]);
        $mock->method('statsDebits')->willReturn(['total' => 0.0]);
        $mock->method('statsSevings')->willReturn(['total' => 0.0]);
        $mock->method('expensesByCategories')->willReturn([]);
        $mock->method('expensesByLabels')->willReturn([]);

        return $mock;
    }
}

