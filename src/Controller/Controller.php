<?php
namespace Budgetcontrol\Stats\Controller;

use Illuminate\Support\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller {

    public function monitor(Request $request, Response $response)
    {
        return response([
            'success' => true,
            'message' => 'Stats service is up and running'
        ]);
        
    }

    /**
     * Calculate the number of weeks in a given month and year.
     *
     * @param int $month The month (1-12).
     * @param int $year The year.
     * @return int The number of weeks in the given month and year.
     */
    protected function weeksInMonth($month, $year) {
        return Carbon::createFromDate($year, $month, 1)->weeksInMonth;
    }

    /**
     * Calculates the number of days in a given month and year.
     *
     * @param int $month The month (1-12).
     * @param int $year The year.
     * @return int The number of days in the specified month and year.
     */
    protected function daysInMonth($month, $year) {
        return Carbon::createFromDate($year, $month, 1)->daysInMonth;
    }
}