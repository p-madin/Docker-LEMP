<?php
class Router {
    /**
     * @var array $routes Maps HTTP method + path to its controller implementation.
     * Structured as: ['GET' => ['/path' => ControllerInstance], 'POST' => [...]]
     */
    protected array $routes = [];

    /**
     * @var array $middlewares Global middleware pipeline executed before the controller.
     */
    protected array $middlewares = [];

    /**
     * @var array $registry The master registry for all paths.
     * Maps path string -> ['controller' => Instance, 'protected' => bool]
     */
    protected array $registry = [];

    /**
     * Registers a global middleware into the execution pipeline.
     */
    public function use(MiddlewareInterface $middleware) {
        $this->middlewares[] = $middleware;
    }

    /**
     * Registers a controller instance in both the master registry and method-specific routing tables.
     * 
     * @param string $path The URL path to register.
     * @param ControllerInterface $controller The controller instance.
     * @param bool $protected Whether this path requires an active session.
     */
    public function registerController(string $path, ControllerInterface $controller, bool $protected = false) {
        // Master registry stores all metadata in one place
        $this->registry[$path] = [
            'controller' => $controller,
            'protected'  => $protected
        ];

        // Optimization: Pre-map the HTTP methods for high-speed dispatch
        if ($controller->isAction) {
            $this->post($path, $controller);
        } else {
            // Pages/Views support both GET (viewing) and POST (filtering/forms)
            $this->get($path, $controller);
            $this->post($path, $controller);
        }
    }

    /**
     * Returns the controller instance mapped to a specific path.
     */
    public function getController(string $path): ?ControllerInterface {
        return $this->registry[$path]['controller'] ?? null;
    }

    /**
     * Determines if a path corresponds to a direct Action controller (isAction = true).
     */
    public function isAction(string $path): bool {
        return isset($this->registry[$path]) && $this->registry[$path]['controller']->isAction;
    }

    /**
     * Determines if a path requires authentication (nbProtected = true).
     */
    public function isProtected(string $path): bool {
        return isset($this->registry[$path]) && $this->registry[$path]['protected'];
    }

    /**
     * Bootstraps the Router's registry from the database navigation configuration.
     */
    public function initializeFromDatabase($sysConfigController) {
        $items = $sysConfigController->getNavbarItems();
        foreach ($items as $item) {
            $className = $item['controller'];
            if ($className && !empty($className)) {
                $filePath = __DIR__ . "/../Controllers/" . $className . ".php";
                if (file_exists($filePath)) {
                    include_once($filePath);
                    if (class_exists($className)) {
                        $controllerInstance = new $className();
                        $this->registerController($item['url'], $controllerInstance, $item['protected']);
                    }
                }
            }
        }
        
        // Finalize special routing aliases (e.g., support index.php and legacy logout paths)
        $index = $this->getController('/');
        if ($index) {
            $this->get('/index.php', $index);
        }
        
        $logout = $this->getController('/logout');
        if ($logout) {
            $this->get('/logout-action.php', $logout);
            // Ensure the alias inherits the protection status
            $this->registry['/logout-action.php'] = [
                'controller' => $logout,
                'protected'  => $this->isProtected('/logout')
            ];
        }
    }

    /**
     * Registers a path for HTTP GET requests.
     */
    public function get($path, $controller) {
        $this->routes['GET'][$path] = $controller;
    }

    /**
     * Registers a path for HTTP POST requests.
     */
    public function post($path, $controller) {
        $this->routes['POST'][$path] = $controller;
    }

    /**
     * The heart of the Front Controller. Dispatches the request through the middleware pipeline
     * and into the target controller's execute() method.
     */
    public function dispatch(Request $request) {
        /**
         * The leaf node of the middleware chain execution.
         * Resolves the controller lazily *after* all previous middlewares have run.
         */
        $coreAction = function($req) {
            $method = $req->getMethod();
            $path = $req->getPath();
            $controller = $this->routes[$method][$path] ?? null;

            if ($controller === null) {
                http_response_code(404);
                echo "404 Not Found";
                return;
            }

            if ($controller instanceof ControllerInterface) {
                return $controller->execute($req);
            }
            throw new Exception("Invalid route controller format.");
        };

        // Reduce the middleware stack into a single executable closure (Onion Architecture)
        $chain = array_reduce(
            array_reverse($this->middlewares),
            function($next, $middleware) {
                return function($req) use ($middleware, $next) {
                    return $middleware->handle($req, $next);
                };
            },
            $coreAction
        );

        $result = $chain($request);

        if ($result instanceof View) {
            echo $result->execute();
        }
    }
}
