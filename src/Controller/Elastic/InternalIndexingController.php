<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Controller\Elastic;

use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Stats\Facade\ElasticSearch;
use BudgetcontrolLibs\ElasticSearch\Services\Clients\ElasticSearchClient;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use BudgetcontrolLibs\ElasticSearch\Services\Transactions\Indexer;

class InternalIndexingController
{
    private const TRANSACTIONS_NUMBER = 1000;
    private ElasticSearchClient $elasticSearchClient;

    public function __construct()
    {
        $this->elasticSearchClient = ElasticSearch::getInstance();
    }

    /**
     * Indexes a transaction into the Elasticsearch system
     * 
     * This method handles the indexing of transaction data into Elasticsearch for
     * search and analytics purposes. It processes the incoming request containing
     * transaction information and stores it in the appropriate Elasticsearch index.
     *
     * @param Request $request The HTTP request object containing transaction data
     * @param Response $response The HTTP response object to be returned
     * @param array $args Route arguments passed from the routing system
     * 
     * @return Response Returns the HTTP response with indexing status
     * 
     * @throws \Exception When Elasticsearch indexing fails or invalid data is provided
     */
    public function indexTransaction(Request $request, $response, $args): Response
    {
        // request must be have only uuid transacion on body, and wsid on args
        $wsid = $args['wsid'] ?? null;
        $body = $request->getParsedBody();
        $uuids = $body['uuids'] ?? null;

        if (!$wsid || !$uuids) {
            return response(['error' => 'Missing wsid or uuids'], 400);
        }

        $idsArray = explode(',', $uuids);
        $entries = Entry::whereIn('uuid', $idsArray)->where('workspace_id', $wsid)->get();

        try {
            $elastic = new Indexer();
            $elastic->bulkIndexTransactions($entries);
        } catch (\Exception $e) {
            Log::error('Failed to index transactions', ['error' => $e->getMessage(), 'wsid' => $wsid, 'uuids' => $uuids]);
            return response(['error' => 'Failed to index transactions'], 500);
        }

        return response(["message" => "Transaction indexed successfully"], 201);

    }

    /**
     * Bulk index transactions into Elasticsearch
     *
     * This method handles the bulk indexing of transaction data into the Elasticsearch index.
     * It processes multiple transactions in a single request for improved performance.
     *
     * @param Request $request The HTTP request object containing transaction data
     * @param Response $response The HTTP response object
     * @param array $args Route arguments and parameters
     * @return Response The HTTP response with indexing operation results
     *
     * @throws \Exception When Elasticsearch indexing fails
     * @throws \InvalidArgumentException When request data is malformed
     */
    public function bulkIndexTransactions(Request $request, $response, $args): Response
    {
        // update transaction from date time request body, and wsid on args
        $body = $request->getParsedBody();
        $startDate = $body['start_date'] ?? null;
        $endDate = $body['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            $entries = Entry::withRelations();
        } else {
            $entries = Entry::where('date_time', '>=', $startDate)
                ->where('date_time', '<=', $endDate)
                ->withRelations();
        }

        try {
                $entries->chunk(self::TRANSACTIONS_NUMBER, function ($entries) {
                    // SEND TO ELASTICSEARCH
                    $elastic = new Indexer(ElasticSearch::getInstance());
                    $elastic->bulkIndexTransactions($entries);
                });

        } catch (\Exception $e) {
            Log::error('Failed to bulk index transactions', ['error' => $e->getMessage(), 'start_date' => $startDate, 'end_date' => $endDate]);
            return response(['error' => 'Failed to bulk index transactions'], 500);
        }



        return response(["message" => "Transaction indexed successfully"], 201);

    }

}