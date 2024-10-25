<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Model;

use Budgetcontrol\Library\Model\Currency;
use Budgetcontrol\Library\Model\Wallet as Model;

final class Wallet extends Model {

    public function currency()
    {
       return $this->belongsTo(Currency::class, 'currency', 'id');
    }
    
}