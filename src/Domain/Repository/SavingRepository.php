<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class SavingRepository extends StatsRepository{
    
    /**
     * Retrieves statistics for savings.
     *
     * @return array An array containing the statistics for savings.
     */
    public function statsSevings() 
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            WHERE e.type in ('saving')
            AND e.exclude_from_stats = false
            AND e.deleted_at is null
            AND e.confirmed = true
            AND e.amount < 0
            AND e.planned = false
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

}
