<?php
namespace Budgetcontrol\Stats\Domain\Entity\LineChart;

use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartPoint;

final class LineChartSeries
{
    private string $label;
    private string $color;
    private array $dataPoints;

    public function __construct($label)
    {
        $this->label = $label;
        $this->color = $this->color();
    }

    public function addDataPoint(LineChartPofloat $dataPoint)
    {
        $this->dataPoints[] = $dataPoint;
    }

    public function getDataPoints(): array
    {
        return $this->dataPoints;
    }


    /**
     * Get the value of label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    private function color()
    {
        $hex = '';
        for ($i = 0; $i < 6; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }
        return '#'.$hex;
    }

    /**
     * Get the value of color
     */ 
    public function getColor()
    {
        return $this->color;
    }
}