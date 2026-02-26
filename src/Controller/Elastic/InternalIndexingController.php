<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Controller\Elastic;

use Budgetcontrol\Stats\Services\Transactions\Indexer;
use Budgetcontrol\Library\Model\Entry;
use Log;

class InternalIndexingController
{

    public function indexTransaction($request, $response, $args)
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
    }

    public function bulkIndexTransactions($request, $response, $args)
    {
        // update transaction from date time request body, and wsid on args
        $body = $request->getParsedBody();
        $startDate = $body['start_date'] ?? null;
        $endDate = $body['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            return response(['error' => 'Missing wsid, start_date or end_date'], 400);
        }

        $entries = Entry::where('date_time', '>=', $startDate)
            ->where('date_time', '<=', $endDate)
            ->get();

        try {
            $elastic = new Indexer();
            $elastic->bulkIndexTransactions($entries);
        } catch (\Exception $e) {
            Log::error('Failed to bulk index transactions', ['error' => $e->getMessage(), 'start_date' => $startDate, 'end_date' => $endDate]);
            return response(['error' => 'Failed to bulk index transactions'], 500);
        }

    }

}