<?php
namespace Budgetcontrol\Stats\Domain\Entity\TableChart;

use Budgetcontrol\Library\Entity\Entry;
use DivisionByZeroError;
use Mlab\MathPercentage\Service\PercentCalculator;

final class TableRowChart
{
    private float $amount;
    private ?float $prevAmount;
    private string $label;
    private float $bounceRate;
    private string $type;

    public function __construct(float $amount, ?float $prevAmount, string $label, string $type)
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
            $percentage = PercentCalculator::calculatePercentage(PercentCalculator::MARGIN_PERCENTAGE, $this->amount, $this->prevAmount);
            $percentage = $percentage->toFloat();
        } catch(DivisionByZeroError $e) {
            if($this->amount == 0 && $this->prevAmount == 0) {
                $percentage = 0;
            } else {

                switch($this->type) {
                    case Entry::expenses->value:
                        $percentage = ($this->amount < $this->prevAmount) ? 100 : -100;
                        break;
                    case Entry::incoming->value:
                        $percentage = ($this->amount > $this->prevAmount) ? 100 : -100;
                        break;
                    default:
                        $percentage = ($this->amount < $this->prevAmount) ? 100 : -100;
                }
            }
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
