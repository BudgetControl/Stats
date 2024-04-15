<?php
namespace Budgetcontrol\Stats\Domain\Entity\LineChart;

class LineChartPoint
{
    private float $xValue;
    private float $yValue;
    private string $label;

    public function __construct(float $xValue, float $yValue, string $label = '')
    {
        $this->xValue = $xValue;
        $this->yValue = $yValue;
        $this->label = $label;
    }

    public function getXValue()
    {
        return $this->xValue;
    }

    public function getYValue()
    {
        return $this->yValue;
    }

    public function getXYValue()
    {
        return [$this->xValue,$this->yValue];
    }
    
    public function getLabel()
    {
        return $this->label;
    }
}