<?php
// Autoload Composer dependencies

use \Illuminate\Support\Carbon as Date;
use Illuminate\Support\Facades\Facade;
use Webit\Wrapper\BcMath\BcMathNumber;

require_once __DIR__ . '/../vendor/autoload.php';

// Set up your application configuration
// Initialize slim application
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Crea un'istanza del gestore del database (Capsule)
$capsule = new \Illuminate\Database\Capsule\Manager();

// Aggiungi la configurazione del database al Capsule
$connections = require_once __DIR__.'/../config/database.php';
$capsule->addConnection($connections['mysql']);

// Esegui il boot del Capsule
$capsule->bootEloquent();
$capsule->setAsGlobal();

// Set up the dependency injection container
require_once __DIR__ . '/../config/container-di.php';

// Set up the logger
require_once __DIR__ . '/../config/logger.php';

// Set up the Cryptable service
require_once __DIR__ . '/../config/cryptable.php';

// Set up the Elasticsearch client
require_once __DIR__ . '/../config/elastic-search.php';

// Set up the Facade application
Facade::setFacadeApplication([
    'log' => $logger,
    'date' => new Date(),
    'crypt' => $crypt,
    'bc-math' => new BcMathNumber(0),
    'elasticsearch' => $elasticsearch,
    'search-service' => new \Budgetcontrol\Stats\Services\SearchService($elasticsearch),
]);
