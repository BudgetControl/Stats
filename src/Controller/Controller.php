<?php
namespace Budgetcontrol\Stats\Controller;

use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller {

    public function monitor(Request $request, Response $response)
    {
        $dbHost = env('DB_HOST');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        // Assuming you are using PDO for database connection
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbPass, $dbUser);
        } catch (PDOException $e) {
            // Connection failed
            Log::error('Database connection failed: ' . $e->getMessage());
            $response->getBody()->write('Database connection failed: ' . $e->getMessage());
            return $response->withStatus(500);
        }

        return response([
            'success' => true,
            'message' => 'Stats service is up and running'
        ]);
        
    }
}