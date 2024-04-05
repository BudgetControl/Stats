<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use DateTime;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Carbon\Carbon;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class StatsRepository {
    
    protected int $wsId;
    protected Carbon $startDate;
    protected Carbon $endDate;

    /**
     * StatsRepository constructor.
     *
     * @param string $wsId The ID of the workspace.
     * @param Carbon $startDate The start date for the stats.
     * @param Carbon $endDate The end date for the stats.
     */
    public function __construct(string $wsId, Carbon $startDate, Carbon $endDate)
    {   
        $wsid = @Workspace::where('uuid', $wsId)->first()->id;

        if(is_null($wsid)) {
            throw new NotFoundResourceException('Workspace not found', 404);
        }

        $this->wsId = $wsid;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function statsTotal() {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            JOIN accounts AS a ON e.account_id = a.id
            WHERE e.type in ('expenses', 'incoming', 'debit')
            AND a.installement = 0
            AND e.exclude_from_stats = 0
            AND a.exclude_from_stats = 0
            AND a.deleted_at is null
            AND e.deleted_at is null
            AND e.confirmed = 1
            AND e.planned = 0
            AND e.date_time >= '$startDate'
            AND e.date_time < '$endDate'
            AND a.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    public function total() {
        $wsId = $this->wsId;

        $query = "
            SELECT COALESCE(SUM(balance), 0) AS total_balance
            FROM accounts
            WHERE workspace_id = $wsId
            AND installement = 0
            AND deleted_at is null
            AND exclude_from_stats = 0;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total_balance
        ];
    }

    public function wallets() {
        $wsId = $this->wsId;

        $query = "
            SELECT * FROM budgetV2.accounts WHERE workspace_id = $wsId AND deleted_at is null;
        ";

        $result = DB::select($query);

        return $result;
    }

    public function health() {

        $wsId = $this->wsId;

        $query = "
            SELECT COALESCE(SUM(balance), 0) AS total_balance
            FROM accounts
            WHERE workspace_id = $wsId AND deleted_at is null AND exclude_from_stats = 0;
        ";

        $result = DB::select($query);

        return [
            'health' => $result[0]->total_balance
        ];
    }

    public function totalWithPlannedOfCurrentMonth() {
        $wsId = $this->wsId;

        $query = "
        SELECT 
        COALESCE(SUM(CASE WHEN a.installement = 1  and a.balance < 0 THEN a.installementValue END), 0) AS installement_balance,
        COALESCE(SUM(CASE WHEN a.installement = 0 THEN a.balance END), 0) AS balance_without_installement,
        COALESCE(SUM(CASE WHEN e.planned = 1 THEN e.amount END), 0) AS planned_amount_total
        FROM 
            accounts AS a
        LEFT JOIN (
            SELECT 
                account_id,
                planned,
                SUM(amount) AS amount
            FROM 
                entries
            WHERE 
                planned = 1
                AND MONTH(date_time) = MONTH(CURRENT_DATE())
                AND YEAR(date_time) = YEAR(CURRENT_DATE())
                AND confirmed = 1
                AND deleted_at IS NULL
                AND exclude_from_stats = 0
                AND workspace_id = $wsId
            GROUP BY 
                account_id
        ) AS e ON a.id = e.account_id
        WHERE 
            a.deleted_at IS NULL
            AND a.exclude_from_stats = 0
            AND a.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return $result[0];
    }

}