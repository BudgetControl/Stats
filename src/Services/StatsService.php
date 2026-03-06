<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Services;

use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Illuminate\Support\Carbon;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\DataValue;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\TotalInstallementValue;
use Budgetcontrol\Stats\Domain\ValueObjects\StatsCalculator;
use Illuminate\Support\Facades\Log;

class StatsService
{
    private StatsRepositoryInterface $repository;

    public function __construct(StatsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function setup(string $wsId, Carbon $startDate, Carbon $endDate)
    {
        $this->repository->setup($wsId, $startDate, $endDate);

        return $this;
    }

    public function willArriveAtTheEndOfTheMonth(): StatsCalculator
    {
        try {

            $expenses = $this->repository->statsExpenses();
            $incoming = $this->repository->statsIncoming();
            $plannedEntries = $this->repository->plannedOfPeriod();
            $creditCards = $this->repository->currentInstallmentValues();
            $walletsBalance = $this->repository->total();

            $installementValues = new TotalInstallementValue($creditCards);

            $dataValue = new DataValue();
            $dataValue->sum((float) $expenses['total']);
            $dataValue->sum((float) $plannedEntries['total']);
            $dataValue->sum((float) $incoming['total']);
            $dataValue->sum((float) $walletsBalance['total']);

            return StatsCalculator::create([$dataValue, $installementValues]);
        } catch (\Exception $e) {
            // Log the error and rethrow it
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function statsExpenses(): array
    {
        return $this->repository->statsExpenses();
    }

    public function statsIncoming(): array
    {
        return $this->repository->statsIncoming();
    }

    public function statsDebits(): array
    {
        return $this->repository->statsDebits();
    }

    public function statsSevings(): array
    {
        return $this->repository->statsSavings();
    }

    public function totalNegativeStatsDebits(): array
    {
        return $this->repository->totalNegativeStatsDebits();
    }

    public function totalPositiveStatsDebits(): array
    {
        return $this->repository->totalPositiveStatsDebits();
    }

    public function total(): array
    {
        return $this->repository->total();
    }

    public function wallets(): array
    {
        return $this->repository->wallets();
    }

    public function health(): array
    {
        return $this->repository->health();
    }

    public function statsByFilters(array $options): array
    {
        return $this->repository->statsByFilters($options);
    }

    public function getPlanedMonthlyExpenses(): array
    {
        return $this->repository->getPlanedMonthlyExpenses();
    }

    public function loanOfCreditCards(): array
    {
        return $this->repository->loanOfCreditCards();
    }

    public function plannedExpenses(): array
    {
        return $this->repository->plannedExpenses();
    }

    public function getPlanedWeeklyExpenses(): array
    {
        return $this->repository->getPlanedWeeklyExpenses();
    }

    public function getPlanedDailyExpenses(): array
    {
        return $this->repository->getPlanedDailyExpenses();
    }
}