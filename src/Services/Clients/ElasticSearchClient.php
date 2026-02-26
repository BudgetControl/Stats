<?php
declare(strict_types=1);
namespace Budgetcontrol\Stats\Services\Clients;

use Budgetcontrol\Stats\Domain\Entity\ElasticTransaction;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticSearchClient
{
    public readonly string $indexName;
    public readonly Client $client;

    private function __construct(string $indexName, string $host, string $username, string $password)
    {
        $this->indexName = $indexName;

        $this->client = ClientBuilder::create()
            ->setHosts([$host])
            ->setBasicAuthentication($username, $password)
            ->setRetries(2)
            ->build();
    }

    public static function create(string $indexName, string $host, string $username, string $password): self
    {
        return new self($indexName, $host, $username, $password);
    }

    public function indexName(): string
    {
        return $this->indexName;
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Creates an Elasticsearch index if it doesn't already exist.
     *
     * This method checks for the existence of the configured index and creates it
     * if it's not found. The index creation includes any predefined mappings and
     * settings that are required for the application's search functionality.
     *
     * @return bool Returns true if the index was created successfully or already exists,
     *              false if there was an error during index creation
     *
     */
    public function createIndexIfNotExists(): bool
    {
        if ($this->indexExists()) {
            Log::info('Index ' . $this->indexName . ' already exists, skipping creation');
            return true;
        }

        Log::info('Index ' . $this->indexName . ' not found, creating index');

        try {
            $this->createIndex();
            Log::info('Index ' . $this->indexName . ' created successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Error creating index ' . $this->indexName, [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function indexExists(): bool
    {
        try {
            return $this->client()->indices()->exists(['index' => $this->indexName])->asBool();
        } catch (\Exception $e) {
            Log::error('Errore durante la verifica dell\'indice', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Creates an index for transactions data.
     * 
     * This method initializes and creates a new index structure for storing
     * and organizing transaction data, typically used for search and retrieval
     * operations.
     * 
     * @return void
     * @throws \Exception If the index creation fails
     */
    public function createIndex(): void
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'my_analyzer' => [
                                'type' => 'standard',
                                'stopwords' => '_italian_',
                            ],
                        ],
                    ],
                ],
                'mappings' => ElasticTransaction::mapping(),
            ],
        ];

        $this->client->indices()->create($params);
    }
}

