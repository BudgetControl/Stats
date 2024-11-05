<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use DateTime;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ExpensesRepository extends StatsRepository{
    
    public function statsExpenses() {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            WHERE e.type in ('expenses')
            AND e.amount < 0
            AND e.exclude_from_stats = 0
            AND e.deleted_at is null
            AND e.confirmed = 1
            AND e.planned = 0
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    public function expensesByCategory(array $categories = [])
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $addConditions = '';
        if(!empty($categories)) {
            $addConditions .= "AND c.id IN ('" . implode("','", $categories) . "')";
        }

        $query = "
            SELECT 
            c.id AS category_id,
            c.name AS category_name,
            c.slug AS category_slug,
            COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            JOIN 
                sub_categories AS c ON e.category_id = c.id
            WHERE 
                e.type IN ('expenses')
                AND e.amount < 0
                AND e.exclude_from_stats = 0
                AND e.deleted_at IS NULL
                AND e.confirmed = 1
                AND e.planned = 0
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND e.workspace_id = $wsId
                $addConditions
            GROUP BY
                c.id, c.name, c.slug;
        ";

        $result = DB::select($query);

        return $result;
    }

    public function expensesByLabels(array $labels = [])
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $addConditions = '';
        if(!empty($labels)) {
            $addConditions .= "AND l.id IN ('" . implode("','", $labels) . "')";
        }

        $query = "
            SELECT 
                l.id AS label_id,
                l.name AS label_name,
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            LEFT JOIN 
                entry_labels AS el ON e.id = el.entry_id
            LEFT JOIN 
                labels AS l ON el.labels_id = l.id
            WHERE 
                e.type IN ('expenses')
                AND e.amount < 0
                AND e.exclude_from_stats = 0
                AND e.deleted_at IS NULL
                AND e.confirmed = 1
                AND e.planned = 0
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND e.workspace_id = $wsId
                $addConditions
            GROUP BY 
                l.id, l.name;
        ";

        $results = DB::select($query);

        //only labels
        foreach($results as $key => $value) {
            if($value->label_name == null) {
                unset($results[$key]);
            }
        }

        return $results;
    }
}