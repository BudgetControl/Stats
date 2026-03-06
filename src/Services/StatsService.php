<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Services;

use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;
use Budgetcontrol\Stats\Domain\Repository\PlannedEntryRepository;
use Illuminate\Support\Carbon;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
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

    public function create(string $wsId, Carbon $startDate, Carbon $endDate)
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
}