<?php

require 'vendor/autoload.php';

// Definir número máximo de reintentos
define('MAX_RETRIES', 3);

$className = isset($argv[1]) ? filter_var($argv[1], FILTER_SANITIZE_STRING) : null;
$methodName = isset($argv[2]) ? filter_var($argv[2], FILTER_SANITIZE_STRING) : null;
$params = isset($argv[3]) ? json_decode($argv[3], true) : [];
$retryCount = isset($argv[4]) ? intval($argv[4]) : 0;

try {
    if (!$className || !$methodName) {
        throw new Exception("Clase o método no especificados.");
    }

    // Lista de clases y métodos permitidos por seguridad
    $allowedClasses = [
        'App\Jobs\MyJob',
        'App\Services\BackgroundService',
    ];
    $allowedMethods = [
        'handle',
        'process',
    ];

    if (!in_array($className, $allowedClasses) || !in_array($methodName, $allowedMethods)) {
        throw new Exception("Clase o método no autorizados.");
    }

    if (!class_exists($className) || !method_exists($className, $methodName)) {
        throw new Exception("Clase o método no válidos.");
    }

    $instance = new $className();
    $result = call_user_func_array([$instance, $methodName], $params);

    $logMessage = "Success: {$className}::{$methodName} at " . date('Y-m-d H:i:s') . PHP_EOL;
    file_put_contents('logs/background_jobs.log', $logMessage, FILE_APPEND);

} catch (Exception $e) {
    $errorMessage = "Error: {$e->getMessage()} in {$className}::{$methodName} at " . date('Y-m-d H:i:s') . PHP_EOL;
    file_put_contents('logs/background_jobs_errors.log', $errorMessage, FILE_APPEND);

    if ($retryCount < MAX_RETRIES) {
        $retryCommand = "php " . __FILE__ . " {$className} {$methodName} '" . json_encode($params) . "' " . ($retryCount + 1);
        if (stripos(PHP_OS, 'WIN') === 0) {
            $retryCommand .= " > NUL 2>&1 &";
        } else {
            $retryCommand .= " > /dev/null 2>&1 &";
        }
        exec($retryCommand);
    } else {
        // Registrar que se alcanzó el número máximo de reintentos
        $maxRetriesMessage = "Max retries reached for {$className}::{$methodName} at " . date('Y-m-d H:i:s') . PHP_EOL;
        file_put_contents('logs/background_jobs_errors.log', $maxRetriesMessage, FILE_APPEND);
    }

    exit(1);
}
