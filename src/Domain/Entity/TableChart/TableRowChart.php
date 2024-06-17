<?php
namespace Budgetcontrol\Stats\Domain\Entity\TableChart;
use DivisionByZeroError;

final class TableRowChart
{
    private float $amount;
    private float $prevAmount;
    private string $label;
    private float $bounceRate;
    private string $type;

    public function __construct(float $amount, float $prevAmount, string $label, string $type)
    {
        $this->amount = $amount;
        $this->prevAmount = $prevAmount;
        $this->label = $label;
        $this->bounceRate = $this->bounceRate();
        $this->type = $type;
    }

    private function bounceRate()
    {
        try {
            $difference = abs($this->amount - $this->prevAmount);
            $segno = ($this->amount > $this->prevAmount) ? 1 : -1;
            $percentage = ($difference / $this->amount) * 100 * $segno;
        } catch(DivisionByZeroError $e) {
            $percentage = 0;
        }


        return $percentage;
    }

    /**
     * Get the value of amount
     */ 
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get the value of prevAmount
     */ 
    public function getPrevAmount()
    {
        return $this->prevAmount;
    }

    /**
     * Get the value of label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the value of bounceRate
     */ 
    public function getBounceRate()
    {
        return $this->bounceRate;
    }

    /**
     * Get the value of type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
