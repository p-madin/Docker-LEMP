<?php

class DataProviderAction implements ControllerInterface {
    public static string $path = '/dataProviders';
    public bool $isAction = true;

    public function execute(Request $request) {
        $providers = [];
        
        // Scan for controllers that implement DataProviderInterface
        $controllerDir = __DIR__;
        $files = scandir($controllerDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) continue;
            
            $className = str_replace('.php', '', $file);
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->implementsInterface('DataProviderInterface') && !$reflection->isAbstract()) {
                    $instance = new $className();
                    $providers[] = [
                        'id' => $className,
                        'name' => $instance->getDataSourceName()
                    ];
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'providers' => $providers]);
        exit;
    }
}
