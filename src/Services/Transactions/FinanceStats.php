<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Transactions;

use Budgetcontrol\Stats\Services\ElasticSearchService;

class FinanceStats extends ElasticSearchService
{
    /**
     * Dashboard mensile con tutte le statistiche principali
     */
    public function getMonthlyDashboard(int $year, int $month, array $accounts = []): array
    {
        $filter = [
            ['term' => ['year' => $year]],
            ['term' => ['month' => $month]],
        ];
        
        if (!empty($accounts)) {
            $filter[] = ['terms' => ['account_id' => $accounts]];
        }
        
        $params = [
            'index' => 'transactions',
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => $filter,
                    ],
                ],
                'aggs' => [
                    'total_income' => [
                        'filter' => ['term' => ['category_type' => 'income']],
                        'aggs' => [
                            'total' => ['sum' => ['field' => 'amount']],
                        ],
                    ],
                    'total_expenses' => [
                        'filter' => ['term' => ['category_type' => 'expense']],
                        'aggs' => [
                            'total' => ['sum' => ['field' => 'amount']],
                        ],
                    ],
                    'balance' => [
                        'sum' => [
                            'script' => [
                                'source' => "doc['category_type'].value == 'income' ? doc['amount'].value : -doc['amount'].value",
                            ],
                        ],
                    ],
                    'expenses_by_category' => [
                        'terms' => [
                            'field' => 'category_id',
                            'size' => 20,
                        ],
                        'aggs' => [
                            'category_name' => [
                                'terms' => ['field' => 'category_name'],
                            ],
                            'total' => [
                                'sum' => ['field' => 'amount'],
                            ],
                            'avg' => [
                                'avg' => ['field' => 'amount'],
                            ],
                            'count' => ['value_count' => ['field' => 'id']],
                        ],
                    ],
                    'daily_totals' => [
                        'date_histogram' => [
                            'field' => 'date',
                            'calendar_interval' => 'day',
                            'format' => 'yyyy-MM-dd',
                        ],
                        'aggs' => [
                            'income' => [
                                'sum' => [
                                    'script' => [
                                        'source' => "doc['category_type'].value == 'income' ? doc['amount'].value : 0",
                                    ],
                                ],
                            ],
                            'expenses' => [
                                'sum' => [
                                    'script' => [
                                        'source' => "doc['category_type'].value == 'expense' ? doc['amount'].value : 0",
                                    ],
                                ],
                            ],
                            'balance' => [
                                'sum' => [
                                    'script' => [
                                        'source' => "doc['category_type'].value == 'income' ? doc['amount'].value : -doc['amount'].value",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        $response = $this->elasticsearch->search($params);
        $aggs = $response['aggregations'];
        
        return [
            'summary' => [
                'total_income' => $aggs['total_income']['total']['value'] ?? 0,
                'total_expenses' => $aggs['total_expenses']['total']['value'] ?? 0,
                'balance' => $aggs['balance']['value'] ?? 0,
            ],
            'by_category' => array_map(function($bucket) {
                return [
                    'category_id' => $bucket['key'],
                    'category_name' => $bucket['category_name']['buckets'][0]['key'] ?? 'Sconosciuto',
                    'total' => $bucket['total']['value'],
                    'avg' => $bucket['avg']['value'],
                    'count' => $bucket['count']['value'],
                ];
            }, $aggs['expenses_by_category']['buckets']),
            'daily' => array_map(function($bucket) {
                return [
                    'date' => $bucket['key_as_string'],
                    'income' => $bucket['income']['value'],
                    'expenses' => $bucket['expenses']['value'],
                    'balance' => $bucket['balance']['value'],
                ];
            }, $aggs['daily_totals']['buckets']),
        ];
    }
    
    /**
     * Analisi trend su più mesi
     */
    public function getTrendAnalysis(int $months = 12): array
    {
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$months} months");
        
        $params = [
            'index' => 'transactions',
            'body' => [
                'size' => 0,
                'query' => [
                    'range' => [
                        'date' => [
                            'gte' => $startDate->format('Y-m-d'),
                            'lte' => $endDate->format('Y-m-d'),
                        ],
                    ],
                ],
                'aggs' => [
                    'monthly_trend' => [
                        'date_histogram' => [
                            'field' => 'date',
                            'calendar_interval' => 'month',
                            'format' => 'yyyy-MM',
                        ],
                        'aggs' => [
                            'income' => [
                                'filter' => ['term' => ['category_type' => 'income']],
                                'aggs' => ['total' => ['sum' => ['field' => 'amount']]],
                            ],
                            'expenses' => [
                                'filter' => ['term' => ['category_type' => 'expense']],
                                'aggs' => ['total' => ['sum' => ['field' => 'amount']]],
                            ],
                            'savings_rate' => [
                                'bucket_script' => [
                                    'buckets_path' => [
                                        'income' => 'income>total',
                                        'expenses' => 'expenses>total',
                                    ],
                                    'script' => '(params.income - params.expenses) / params.income * 100',
                                ],
                            ],
                        ],
                    ],
                    'top_categories_trend' => [
                        'terms' => [
                            'field' => 'category_id',
                            'size' => 5,
                        ],
                        'aggs' => [
                            'name' => [
                                'terms' => ['field' => 'category_name'],
                            ],
                            'monthly' => [
                                'date_histogram' => [
                                    'field' => 'date',
                                    'calendar_interval' => 'month',
                                ],
                                'aggs' => [
                                    'total' => ['sum' => ['field' => 'amount']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        return $this->elasticsearch->search($params)['aggregations'];
    }
}