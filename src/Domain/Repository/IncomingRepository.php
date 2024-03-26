<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use DateTime;
use Illuminate\Database\Capsule\Manager as DB;

class IncomingRepository extends StatsRepository {
    
    public function statsIncoming() {
        $wsId = $this->wsId;
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            JOIN accounts AS a ON e.account_id = a.id
            WHERE e.type in ('incoming', 'debit')
            AND e.amount > 0
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

    public function incomingByCategory()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = "
            SELECT 
            c.id AS category_id,
            c.name AS category_name,
            COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            JOIN 
                accounts AS a ON e.account_id = a.id
            JOIN 
                sub_categories AS c ON e.category_id = c.id
            WHERE 
                e.type IN ('incoming', 'debit')
                AND e.amount > 0
                AND a.installement = 0
                AND e.exclude_from_stats = 0
                AND a.exclude_from_stats = 0
                AND a.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND e.confirmed = 1
                AND e.planned = 0
                AND e.date_time >= '$startDate'
                AND e.date_time < '$endDate'
                AND a.workspace_id = $wsId
            GROUP BY
                c.id, c.name;
        ";

        $result = DB::select($query);

        return $result;
    }

    public function incomingByLabels()
    {
        $wsId = $this->wsId;
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = "
            SELECT 
                l.id AS label_id,
                l.name AS label_name,
                COALESCE(SUM(e.amount), 0) AS total
            FROM 
                entries AS e
            JOIN 
                accounts AS a ON e.account_id = a.id
            LEFT JOIN 
                entry_labels AS el ON e.id = el.entry_id
            LEFT JOIN 
                labels AS l ON el.labels_id = l.id
            WHERE 
                e.type IN ('incoming', 'debit')
                AND e.amount > 0
                AND a.installement = 0
                AND e.exclude_from_stats = 0
                AND a.exclude_from_stats = 0
                AND a.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND e.confirmed = 1
                AND e.planned = 0
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