<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Model;

use Budgetcontrol\Library\Model\Wallet as Model;
use BudgetcontrolLibs\Crypt\Traits\Crypt;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class Wallet extends Model {

    use Crypt;

    public function name(): Attribute
    {
        $this->key = env('APP_KEY');
        
        return Attribute::make(
            get: fn (string $value) => $this->decrypt($value),
            set: fn (string $value) => $this->encrypt($value),
        );
    }
    
}