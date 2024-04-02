<?php
namespace Budgetcontrol\Stats\Domain\Entity\LineChart;

use Budgetcontrol\Stats\Domain\Entity\LineChart\LineChartSeries;
use Budgetcontrol\Stats\Trait\Serializer;

final class LineChart
{
    use Serializer;
    
    public string $type = 'Line';
    private $series = [];

    public function addSeries(LineChartSeries $series)
    {
        $this->series[] = $series;
    }

    public function getSeries()
    {
        return $this->series;
    }

    private function hash(): string
    {       
        $hash = '';
        foreach($this->series as $serie) {
            $points = $serie->getDataPoints();
            $hash .= "{".$serie->getLabel().$serie->getColor();
            foreach($points as $point) {
                $hash .= $point->getXValue().$point->getYValue()."-";
            }
            $hash .= "}";
        }
        return md5("LineChart:$hash");
    }

    public function isEqualsTo(LineChart $chart): bool
    {
        return $this->hash() === $chart->hash();
    }

   
}