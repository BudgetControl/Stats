<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Controller\Elastic;

use Budgetcontrol\Library\Model\Wallet;
use BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use BudgetcontrolLibs\ElasticSearch\Services\Transactions\Indexer;

class WalletIndexingController
{
    private const TRANSACTIONS_NUMBER = 1000;
    private ElasticSearchClient $elasticSearchClient;

    public function __construct(ElasticSearchClient $client)
    {
        $this->elasticSearchClient = $client;
    }

    public function bulkIndexWallets(Request $request, $response, $args): Response
    {
        try {
                Wallet::chunk(self::TRANSACTIONS_NUMBER, function ($wallet) {
                    $elastic = new Indexer($this->elasticSearchClient);
                    $elastic->bulkIndexWallets($wallet);
                });

        } catch (\Exception $e) {
            Log::error('Failed to bulk index wallets', ['error' => $e->getMessage()]);
            return response(['error' => 'Failed to bulk index wallets'], 500);
        }

        return response(["message" => "Wallets indexed successfully"], 201);
    }

}