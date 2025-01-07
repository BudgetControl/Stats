<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Wallet;
use DateTime;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Stats\Domain\Model\Workspace;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class DebitRepository extends StatsRepository{
    
    /**
     * Retrieves statistics for debits.
     *
     * @return array An array containing the statistics for debits.
     */
    public function statsDebits() {
        $wsId = $this->wsId;
        $startDate = $this->startDate->toAtomString();
        $endDate = $this->endDate->toAtomString();

        $query = "
            SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM entries AS e
            WHERE e.type in ('debit')
            AND e.exclude_from_stats = false
            AND e.deleted_at is null
            AND e.confirmed = true
            AND e.planned = false
            AND e.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }

    /**
     * Retrieves the debit of credit cards.
     *
     * @return void
     */
    public function debitOfCreditCards() {
        $wsId = $this->wsId;
        
        $query = "
            SELECT COALESCE(SUM(w.balance), 0) AS total
            FROM wallets AS w
            WHERE w.type in ('".Wallet::creditCardRevolving->value."')
            AND w.exclude_from_stats = false
            AND w.installement = true
            AND w.deleted_at is null
            AND w.workspace_id = $wsId;
        ";

        $result = DB::select($query);

        return [
            'total' => $result[0]->total
        ];
    }
}
