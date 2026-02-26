<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Transactions;

use Budgetcontrol\Stats\Services\ElastichSearchService;

class Search extends ElastichSearchService
{

    /**
     * Search for transactions based on the provided filters
     * 
     * @param array $filters Array of search criteria to filter transactions
     * @param int $from Starting offset for pagination (default: 0)
     * @param int $size Number of results to return (default: 50)
     * @return array Array of matching transactions
     */
    public function search(array $filters, int $from = 0, int $size = 50): array
    {
        $must = [];
        $filter = [];

        // Filtro per testo (descrizione o note)
        if (!empty($filters['query'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['query'],
                    'fields' => ['note^3', 'category_name', 'tags'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        // Filtro per date
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $range = [];
            if (!empty($filters['start_date'])) {
                $range['gte'] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $range['lte'] = $filters['end_date'];
            }

            $filter[] = [
                'range' => [
                    'date' => $range,
                ],
            ];
        }

        // Filtro per mese/anno specifico
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $filter[] = [
                'bool' => [
                    'must' => [
                        ['term' => ['month' => (int) $filters['month']]],
                        ['term' => ['year' => (int) $filters['year']]],
                    ],
                ],
            ];
        }

        // Filtro per categorie
        if (!empty($filters['categories'])) {
            $filter[] = [
                'terms' => [
                    'category_id' => array_map('intval', (array) $filters['categories']),
                ],
            ];
        }

        // Filtro per tipo (entrata/uscita)
        if (!empty($filters['type'])) {
            $filter[] = [
                'term' => [
                    'category_type' => $filters['type'],
                ],
            ];
        }

        // Filtro per conti
        if (!empty($filters['wallet'])) {
            $filter[] = [
                'terms' => [
                    'wallet_id' => array_map('intval', (array) $filters['wallet_id']),
                ],
            ];
        }

        // Filtro per range di importo
        if (isset($filters['min_amount']) || isset($filters['max_amount'])) {
            $range = [];
            if (isset($filters['min_amount'])) {
                $range['gte'] = (float) $filters['min_amount'];
            }
            if (isset($filters['max_amount'])) {
                $range['lte'] = (float) $filters['max_amount'];
            }

            $filter[] = [
                'range' => [
                    'amount' => $range,
                ],
            ];
        }

        // Filtro per tipo transazione
        if (!empty($filters['type'])) {
            $filter[] = [
                'term' => [
                    'type' => $filters['type'],
                ],
            ];
        }

        // Filtro per tag
        if (!empty($filters['tags'])) {
            $filter[] = [
                'terms' => [
                    'tags' => (array) $filters['tags'],
                ],
            ];
        }

        $params = [
            'index' => 'transactions',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => $filter,
                    ],
                ],
                'sort' => [
                    ['date' => ['order' => 'desc']],
                    ['timestamp' => ['order' => 'desc']],
                ],
                'from' => $from,
                'size' => $size,
            ],
        ];

        $response = $this->client->search($params);

        return [
            'total' => $response['hits']['total']['value'],
            'hits' => array_map(function ($hit) {
                return [
                    'id' => $hit['_id'],
                    'score' => $hit['_score'],
                    ...$hit['_source']
                ];
            }, $response['hits']['hits']),
        ];
    }

    /**
     * Ricerca avanzata per espressioni tipo "cena > 50" o "supermercato marzo"
     */
    public function smartSearch(string $query, int $userId): array
    {
        // Qui puoi implementare un parser NLP semplice
        // Per ora un esempio base
        return $this->search(['query' => $query]);
    }
}