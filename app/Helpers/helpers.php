<?php

if (!function_exists('runBackgroundJob')) {
    /**
     * Ejecuta un trabajo en segundo plano mediante el archivo background_job_runner.php.
     *
     * @param string $class Nombre de la clase que contiene el método a ejecutar.
     * @param string $method Nombre del método a ejecutar dentro de la clase.
     * @param array $parameters Parámetros opcionales que se pasarán al método.
     * @param int $retryCount Número máximo de reintentos en caso de fallo.
     * 
     * @return void
     */
    function runBackgroundJob($class, $method, $parameters = [], $retryCount = 0)
    {
        $runnerPath = base_path('scripts/background_job_runner.php');

        $serializedParameters = base64_encode(json_encode($parameters));

        $command = sprintf(
            'php %s %s %s %s %d > /dev/null 2>&1 &',
            escapeshellarg($runnerPath),  
            escapeshellarg($class),  
            escapeshellarg($method),
            escapeshellarg($serializedParameters),
            $retryCount
        );

        if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
            // Comando para Windows
            $command = sprintf(
                'start /b php %s %s %s %s %d',
                escapeshellarg($runnerPath),
                escapeshellarg($class),
                escapeshellarg($method),
                escapeshellarg($serializedParameters),
                $retryCount
            );
            pclose(popen($command, 'r'));
        } else {
            // Comando para sistemas Unix
            exec($command);
        }
    }
}
