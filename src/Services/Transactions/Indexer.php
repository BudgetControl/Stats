<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Transactions;

use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use Budgetcontrol\Stats\Services\ElasticSearchService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Elastic\Elasticsearch\Exception\ElasticsearchException;

class Indexer extends ElasticSearchService
{

    /**
     * Indexes a transaction entry into the search engine.
     *
     * This method takes a transaction entry and processes it for indexing,
     * making it searchable within the system. The indexing process typically
     * involves extracting relevant data from the entry and storing it in
     * an optimized format for quick retrieval.
     *
     * @param Entry $entry The transaction entry to be indexed
     * @return void
     * @throws ElasticsearchException If the indexing process fails
     */public function indexTransaction(Entry $entry): void
    {
        $params = [
            'index' => $this->index,
            'uuid' => $entry->uuid,
            'body' => $this->buildEntry($entry),
        ];
        
        $this->client->index($params);
    }
    
    /**
     * Bulk transactions
     * @param array<int:Entry> $entrys
     * @return void
     */
    public function bulkIndexTransactions(Collection $entrys): void
    {
        $params = ['body' => []];
        
        foreach ($entrys as $entry) {
            if(!$entry instanceof Entry) {
                Log::warning("Is not a Entry instance", ['entry' => $entry]);
                continue; // Salta se non è un'istanza di Entry
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                ],
            ];
            
            $params['body'][] = $this->buildEntry($entry);
        }
        
        $this->client->bulk($params);
    }

    /**
     * Builds an array representation of an Entry for indexing purposes.
     *
     * This method transforms an Entry object into a structured array format
     * that can be used for search indexing or data storage operations.
     *
     * @param Entry $entry The entry object to be converted to array format
     * @return array The structured array representation of the entry
     */
    protected function buildEntry(Entry $entry): array
    {
        $transaction = new ElasticTransaction($entry->toArray());
        return $transaction->toArray();
    }
}