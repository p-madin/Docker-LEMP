<?php

class DataProviderAction implements ControllerInterface {
    public static string $path = '/dataProviders';
    public static string $manage_URI = '/';
    public static string $object_URI = '/';
    public bool $isAction = true;

    public function execute(Request $request) {
        $providers = [];
        
        // Scan for DataProvider classes in the dedicated Providers directory
        $providerDir = __DIR__ . '/../Data/Providers';
        if (is_dir($providerDir)) {
            $files = scandir($providerDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) continue;
                
                $filePath = $providerDir . '/' . $file;
                include_once($filePath);
                
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
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'providers' => $providers]);
        exit;
    }
}
