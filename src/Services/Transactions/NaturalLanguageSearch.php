<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Transactions;

use Budgetcontrol\Stats\Services\ElasticSearchService;

class NaturalLanguageSearch extends ElasticSearchService
{
    /**
     * Interpreta query in linguaggio naturale tipo:
     * "quanto ho speso al supermercato a marzo"
     * "cene costose > 100 euro"
     * "entrate di gennaio 2024"
     */
    public function parseAndSearch(string $query): array
    {
        $filters = [];
        
        // Pattern per date
        if (preg_match('/\b(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\b/i', $query, $matches)) {
            $months = [
                'gennaio' => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4,
                'maggio' => 5, 'giugno' => 6, 'luglio' => 7, 'agosto' => 8,
                'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12,
            ];
            $filters['month'] = $months[strtolower($matches[1])];
            
            // Cerca l'anno nel testo
            if (preg_match('/\b(20\d{2})\b/', $query, $yearMatch)) {
                $filters['year'] = (int) $yearMatch[1];
            } else {
                $filters['year'] = (int) date('Y');
            }
        }
        
        // Pattern per tipi di transazione
        if (preg_match('/\b(entrate|entrata|stipendio|accrediti)\b/i', $query)) {
            $filters['type'] = 'income';
        }
        
        if (preg_match('/\b(spese|speso|pagato|costo|costi)\b/i', $query)) {
            $filters['type'] = 'expense';
        }
        
        // Pattern per importi
        if (preg_match('/(?:>|pi[ùu] di|superiore a)\s*(\d+(?:[.,]\d+)?)/i', $query, $matches)) {
            $filters['min_amount'] = (float) str_replace(',', '.', $matches[1]);
        }
        
        if (preg_match('/(?:<|meno di|inferiore a)\s*(\d+(?:[.,]\d+)?)/i', $query, $matches)) {
            $filters['max_amount'] = (float) str_replace(',', '.', $matches[1]);
        }
        
        // Aggiungi la query testuale pulita
        $cleanQuery = preg_replace('/\b(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre|entrate|entrata|spese|speso|pi[ùu]|meno)\b/i', '', $query);
        $cleanQuery = preg_replace('/[<>]/', '', $cleanQuery);
        $filters['query'] = trim($cleanQuery);
        
        $result = $this->client->search($filters);
        return $result['hits']['hits'] ?? [];
    }
}