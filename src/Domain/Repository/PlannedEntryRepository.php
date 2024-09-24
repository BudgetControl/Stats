<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Definition\Period;
use Budgetcontrol\Library\Entity\Entry;
use Illuminate\Database\Capsule\Manager as DB;
use stdClass;

class PlannedEntryRepository extends StatsRepository {
    
    /**
     * Retrieves the planned expenses.
     *
     * @return array The planned expenses.
     */
    public function getPlanedExpenses(): array {
        $wsId = $this->wsId;
        $expensesLabel = Entry::expenses->value;
        $plannedtypeLabelMonthly = Period::monthly->value;
        $plannedtypeLabelDaily = Period::daily->value;
        $plannedtypeLabelWeekly = Period::weekly->value;

        $query = "
            SELECT 
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                planned_entries AS e
            WHERE 
                e.type in ('$expensesLabel')
                AND e.deleted_at is null
                AND e.planned in ('$plannedtypeLabelMonthly', '$plannedtypeLabelDaily', '$plannedtypeLabelWeekly')
                AND e.end_date_time >= CURDATE()
                AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }
}
