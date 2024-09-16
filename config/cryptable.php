<?php

use BudgetcontrolLibs\Crypt\Service\CryptableService;

$crypt = new CryptableService(
    env('APP_KEY')
);