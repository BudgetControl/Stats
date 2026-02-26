<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Transactions;

use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Stats\Services\ElastichSearchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Elastic\Elasticsearch\Exception\ElasticsearchException;

class Indexer extends ElastichSearchService
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
    public function bulkIndexTransactions(array $entrys): void
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
                    '_id' => $entry->getId(),
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
        return
            [
                'uuid' => $entry->uuid,
                'note' => $entry->note,
                'amount' => (float) $entry->amount,
                'type' => $entry->type,
                'payment_type' => $entry->payment_type,
                'currency' => $entry->currency()->icon,
                'category_id' => $entry->category_id,
                'category_name' => $entry->subCategory()->name,
                'wallet_id' => $entry->account_id,
                'wallet' => $entry->wallet()->toArray(),
                'date' => Carbon::createFromDate($entry->date_time)->format('Y-m-d H:i:s'),
                'timestamp' => Carbon::createFromDate($entry->date_time)->getTimestamp(),
                'year' => (int) Carbon::createFromDate($entry->date_time)->format('Y'),
                'month' => (int) Carbon::createFromDate($entry->date_time)->format('m'),
                'day' => (int) Carbon::createFromDate($entry->date_time)->format('d'),
                'day_of_week' => Carbon::createFromDate($entry->date_time)->format('N'), // 1 (Monday) - 7 (Sunday)
                'week_of_year' => Carbon::createFromDate($entry->date_time)->format('W'),
                'quarter' => Carbon::createFromDate($entry->date_time)->quarter,
                'tags' => $entry->labels()->toArray(), // array di tag
                'have_payee' => $entry->payee_id !== null,
                'payee' => $entry->payee()->toArray(),
                'confirmed' => $entry->confirmed,
                'planned' => $entry->planned,
                'have_warranty' => $entry->waranty,
                'is_transfer' => $entry->transfer,
                'transfer_relation' => [
                    'transfer_from' => $entry->transfer_id,
                    'transfer_to' => $entry->transfer_relation,
                ],
                'geolocalization' => $entry->geolocation,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
            ];
    }
}