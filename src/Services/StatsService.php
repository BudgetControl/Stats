<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Services;

use Budgetcontrol\Stats\Domain\Repository\ExpensesRepository;
use Budgetcontrol\Stats\Domain\Repository\IncomingRepository;
use Budgetcontrol\Stats\Domain\Repository\PlannedEntryRepository;
use Illuminate\Support\Carbon;
use Budgetcontrol\Stats\Domain\Repository\StatsRepository;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\DataValue;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\TotalInstallementValue;
use Budgetcontrol\Stats\Domain\ValueObjects\StatsCalculator;

class StatsService
{

    private string $workspaceId;
    private Carbon $startDate;
    private Carbon $endDate;

    public function __construct(string $wsId, Carbon $startDate, Carbon $endDate)
    {
        $this->workspaceId = $wsId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function willArriveAtTheEndOfTheMonth(): StatsCalculator
    {
        try {
            $expenses = $this->fetchRepositoryData(ExpensesRepository::class, 'statsExpenses');
            $incoming = $this->fetchRepositoryData(IncomingRepository::class, 'statsIncoming');
            $plannedEntries = $this->fetchRepositoryData(PlannedEntryRepository::class, 'plannedOfPeriod');
            $creditCards = $this->fetchRepositoryData(StatsRepository::class, 'currentInstallmentValues');

            $installementValues = new TotalInstallementValue($creditCards);

            $dataValue = new DataValue();
            $dataValue->sum((float) $expenses['total']);
            $dataValue->sum((float) $plannedEntries['total']);
            $dataValue->sum((float) $incoming['total']);

            return StatsCalculator::create([$dataValue, $installementValues]);
        } catch (\Exception $e) {
            // Log the error and rethrow it
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper method to fetch data from a repository.
     *
     * @param string $repositoryClass The repository class name.
     * @param string $method The method to call on the repository.
     * @return array The data fetched from the repository.
     * @throws \RuntimeException If the repository or method is invalid.
     */
    private function fetchRepositoryData(string $repositoryClass, string $method): \Illuminate\Database\Eloquent\Collection|array
    {
        if (!class_exists($repositoryClass)) {
            throw new \RuntimeException("Repository class $repositoryClass does not exist.");
        }

        $repository = new $repositoryClass($this->workspaceId, $this->startDate, $this->endDate);

        if (!method_exists($repository, $method)) {
            throw new \RuntimeException("Method $method does not exist in $repositoryClass.");
        }

        return $repository->$method();
    }
}