<?php

namespace Budgetcontrol\Stats\Controller;

use Budgetcontrol\Stats\Domain\Model\Budget;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

/**
 * Represents a controller for displaying an Apple Pie Chart.
 * Extends the ChartController class.
 */
class BudgetController extends ChartController
{
    /**
     * Retrieves budgets data for the Apple Pie Chart.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional parameters passed to the method.
     * @return void
     */
    public function budgets(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('workspace_id',$arg['wsid'])->where('deleted_at', null)->get();
        return response($budgets->toArray());
    }

    /**
     * Update a budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $arg The route arguments.
     * @return Response The updated budget as a JSON response.
     */
    public function update(Request $request, Response $response, $arg)
    {
        try {
            $this->validate($request->getParsedBody());
        } catch (\InvalidArgumentException $e) {
            return response(['message' => $e->getMessage()], 400);
        }
        
        $budgets = Budget::where('uuid',$arg['budgetId'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $budget = $budgets->first();
        $budget->fill($request->getParsedBody());

        return response($budget->toArray(), 201);
    }

    /**
     * Create a new budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $arg The route arguments.
     * @return Response The newly created budget as a JSON response.
     */
    public function create(Request $request, Response $response, $arg)
    {
        try {
            $this->validate($request->getParsedBody());
        } catch (\InvalidArgumentException $e) {
            return response(['message' => $e->getMessage()], 400);
        }

        $data = new Budget($request->getParsedBody());

        $budget = new Budget();
        $budget->workspace_id = $arg['wsid'];
        $budget->uuid = Uuid::uuid4();
        $budget->budgets = $data['budgets'];
        $budget->configurations = json_encode($data['configuration']);
        $budget->notification = $data['notification'] == 'true' ? 1 : 0;
        $budget->save();

        return response($budget->toArray(), 201);
    }

    /**
     * Deletes a budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg The argument passed to the controller.
     * @return Response The updated HTTP response object.
     */
    public function delete(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('uuid',$arg['budgetId'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $budget = $budgets->first();
        $budget->delete();

        return response([]);
    }

    /**
     * Handle the expired budget request.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional arguments.
     * @return Response The HTTP response object.
     */
    public function expired(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('uuid',$arg['budgetId'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $budget = $budgets->first();
        $configuration = json_decode($budget->configurations, true);

        $date_end = Carbon::createFromFormat('Y-m-d h:i:s', $configuration->end_date);
        if($date_end < Carbon::now()->toAtomString()) {
            return response(['expired' => true]);
        }

        $budget->save();

        return response($budget->toArray());
    }

    public function exceeded(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('uuid',$arg['budgetId'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $budget = $budgets->first();
        $configuration = json_decode($budget->configurations, true);

        if($budget->budget < $budget->balance) {
            return response(['exceeded' => true]);
        }

        $budget->save();

        return response($budget->toArray());
    }

    /**
     * Validates the given data.
     *
     * @param array $data The data to be validated.
     * @return void
     */
    private function validate(array $data): void
    {
        $requiredKeys = ['name', 'note', 'type', 'label', 'period', 'account', 'category', 'end_date', 'start_date'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException("Missing required key: $key");
            }
        }

        if (!is_float($data['balance'])) {
            throw new \InvalidArgumentException("Invalid balance value. Must be a float.");
        }
    }
}
