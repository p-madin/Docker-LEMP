<?php

class DataProviderAction implements ControllerInterface {
    public static string $path = '/dataProviders';
    public static string $manage_URI = '/';
    public static string $object_URI = '/';
    public bool $isAction = true;

    public function execute(Request $request) {
        if (isset($request->get['fetch_html'])) {
            $providerName = $request->get['fetch_html'];
            $providerDir = __DIR__ . '/../Data/Providers';
            $filePath = $providerDir . '/' . $providerName . '.php';
            if (file_exists($filePath)) {
                include_once($filePath);
                if (class_exists($providerName)) {
                    $dom = new \xmlDom();
                    $table = new \FlexTableComponent($dom);
                    $table->setDataProvider($providerName);
                    // Just return the inner body if possible, or the whole table.
                    // The client will use outerHTML or replaceWith.
                    header('Content-Type: text/html');
                    echo $dom->dom->saveHTML($table->render());
                    exit;
                }
            }
            http_response_code(404);
            exit;
        }

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
