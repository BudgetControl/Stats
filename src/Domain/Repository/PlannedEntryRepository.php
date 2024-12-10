<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Definition\Period;
use Budgetcontrol\Library\Entity\Entry;
use Illuminate\Database\Capsule\Manager as DB;

class PlannedEntryRepository extends StatsRepository {
    
    /**
     * Retrieves the planned expenses.
     *
     * @return array The planned expenses.
     */
    public function getPlanedMonthlyExpenses(): array {
        $wsId = $this->wsId;
        $expensesLabel = Entry::expenses->value;
        $plannedtypeLabelMonthly = Period::monthly->value;

        $query = "
            SELECT 
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                planned_entries AS e
            WHERE 
                e.type IN ('$expensesLabel')
                AND e.deleted_at IS NULL
                AND e.planning = '$plannedtypeLabelMonthly'
                AND e.end_date_time >= CURRENT_DATE
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    /**
     * Retrieves an array of planned weekly expenses.
     *
     * @return array An array containing the planned weekly expenses.
     */
    public function getPlanedWeeklyExpenses(): array {
        $wsId = $this->wsId;
        $expensesLabel = Entry::expenses->value;
        $plannedtypeLabelWeekly = Period::weekly->value;

        $query = "
            SELECT 
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                planned_entries AS e
            WHERE 
                e.type IN ('$expensesLabel')
                AND e.deleted_at IS NULL
                AND e.planning = '$plannedtypeLabelWeekly'
                AND e.end_date_time >= CURRENT_DATE
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    /**
     * Retrieves an array of planned daily expenses.
     *
     * @return array An array containing the planned daily expenses.
     */
    public function getPlanedDailyExpenses(): array {
        $wsId = $this->wsId;
        $expensesLabel = Entry::expenses->value;
        $plannedtypeLabelDaily = Period::daily->value;

        $query = "
            SELECT 
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                planned_entries AS e
            WHERE 
                e.type IN ('$expensesLabel')
                AND e.deleted_at IS NULL
                AND e.planning = '$plannedtypeLabelDaily'
                AND e.end_date_time >= CURRENT_DATE
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }
}
