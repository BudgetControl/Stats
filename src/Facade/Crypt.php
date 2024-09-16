<?php
declare(strict_types=1);

namespace Budgetcontrol\Wallet\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encrypt(string $value)
 * @method static string decrypt(string $value)
 * 
 * @see \BudgetcontrolLibs\Crypt\Service\CryptableService
 */

final class Crypt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'crypt';
    }
}