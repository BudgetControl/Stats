<?php
namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Library\Model\Payee;
use Budgetcontrol\Stats\Domain\Model\Wallet;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats\DebitRepoInterface;
use BudgetcontrolLibs\ElasticSearch\Entities\Elastic\ElasticFilter;
use Carbon\Carbon;

class DebitRepository extends PlannedEntryRepository implements DebitRepoInterface {

    /**
     * Calculate the total amount of negative debits.
     *
     * This method aggregates all the negative debit entries and returns their total sum.
     *
     * @return array The total sum of negative debits.
     */
    public function totalNegativeStatsDebits(): array
    {
        $total = Payee::where('workspace_id', $this->wsId)
            ->whereNull('deleted_at')
            ->where('balance', '<', 0)
            ->sum('balance');

        return ['total' => (float) $total];
    }

    /**
     * Calculate the total amount of positive debits.
     *
     * This method aggregates all the positive debit entries and returns their total sum.
     *
     * @return array The total sum of positive debits.
     */
    public function totalPositiveStatsDebits(): array
    {
        $total = Payee::where('workspace_id', $this->wsId)
            ->whereNull('deleted_at')
            ->where('balance', '>', 0)
            ->sum('balance');

        return ['total' => (float) $total];
    }

    /**
     * Retrieves the debit of credit cards.
     */
    public function debitOfCreditCards(): array
    {
        $total = Wallet::where('workspace_id', $this->wsId)
            ->whereIn('type', [EntityWallet::creditCardRevolving->value])
            ->where('exclude_from_stats', false)
            ->where('installement', true)
            ->whereNull('deleted_at')
            ->sum('balance');

        return ['total' => (float) $total];
    }

    // ============ TransactionRepositoryInterface Implementation ============

    public function getStats(): array
    {
        return $this->statsDebits();
    }

    public function getByCategory(?int $categoryId = null): array
    {
        return [];
    }

    public function getTransactionType(): string
    {
        return Entry::debit->value;
    }

    public function isPositiveAmount(): bool
    {
        return false;
    }

    public function getDefaultFilters(): ElasticFilter
    {
        return ElasticFilter::create()
            ->setWorkspaceId($this->wsId)
            ->setType(Entry::debit->value)
            ->setStartDate($this->startDate->toDateString())
            ->setEndDate($this->endDate->toDateString());
    }
}
