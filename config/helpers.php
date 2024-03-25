<?php
/**
 * This file is a helper file that contains various functions.
 */

if(!function_exists('config')) {
    function confing(string $key, string $value): string {
        return $_ENV[$key] ?? $value;
    }
}

if(!function_exists('response')) {
    function response(array $dataResponse, int $statusCode = 200): \Psr\Http\Message\ResponseInterface {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode($dataResponse));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}

// More functions...
