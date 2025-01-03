<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use DateTime;
use Illuminate\Database\Capsule\Manager as DB;

class IncomingRepository extends StatsRepository {
    
    public function statsIncoming() {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            WHERE e.type IN ('incoming')
            AND e.amount > 0
            AND e.exclude_from_stats = false
            AND e.deleted_at IS NULL
            AND e.confirmed = true
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

    public function incomingByCategory()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT 
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            JOIN 
                wallets AS a ON e.account_id = a.id
            JOIN 
                sub_categories AS c ON e.category_id = c.id
            WHERE 
                e.type IN ('incoming')
                AND e.amount > 0
                AND a.installement = false
                AND e.exclude_from_stats = false
                AND a.exclude_from_stats = false
                AND a.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND e.confirmed = true
                AND e.planned = false
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND a.workspace_id = $wsId
            GROUP BY 
                c.id, c.name, c.slug;

        ";

        $result = DB::select($query);

        return $result;
    }

    public function incomingByLabels()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT 
                l.id AS label_id,
                l.name AS label_name,
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            JOIN 
                wallets AS a ON e.account_id = a.id
            LEFT JOIN 
                entry_labels AS el ON e.id = el.entry_id
            LEFT JOIN 
                labels AS l ON el.labels_id = l.id
            WHERE 
                e.type IN ('incoming')
                AND e.amount > 0
                AND a.installement = false
                AND e.exclude_from_stats = false
                AND a.exclude_from_stats = false
                AND a.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND e.confirmed = true
                AND e.planned = false
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND a.workspace_id = $wsId
            GROUP BY 
                l.id, l.name;

        ";

        $result = DB::select($query);

        return $result;
    }
}