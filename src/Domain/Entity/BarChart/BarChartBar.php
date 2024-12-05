<?php
namespace Budgetcontrol\Stats\Domain\Entity\BarChart;

use Budgetcontrol\Library\Model\Category;
use Illuminate\Database\Eloquent\Model;

final class BarChartBar
{
    private float $value;
    private string $color;
    private Model $data;

    public function __construct(float $value, Model $data)
    {
        $this->value = $value;
        $this->color = $this->color();
        $this->data = $data;
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
     * Get the value of value
     */ 
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of color
     */ 
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the value of data
     *
     * @return Model
     */
    public function getData(): Model
    {
        return $this->data;
    }
}