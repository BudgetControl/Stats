<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Stats\Domain\Repository\Interfaces\TransactionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class SavingRepository extends StatsRepository implements TransactionRepositoryInterface{
    
    public static function setup(string $wsId, Carbon $startDate, Carbon $endDate): self
    {
        return new self($wsId, $startDate, $endDate);
    }

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
            WHERE e.category_id = 62
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
