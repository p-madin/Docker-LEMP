<?php
class Router {
    protected $routes = [];
    protected $middlewares = [];
    protected $globalVariables = [];

    public function use(MiddlewareInterface $middleware) {
        $this->middlewares[] = $middleware;
    }

    public function setGlobal(string $key, $value) {
        $this->globalVariables[$key] = $value;
    }

    public function getGlobal(string $key) {
        return $this->globalVariables[$key] ?? null;
    }

    public function get($path, $controller) {
        $this->routes['GET'][$path] = $controller;
    }

    public function post($path, $controller) {
        $this->routes['POST'][$path] = $controller;
    }

    public function dispatch(Request $request) {
        $method = $request->getMethod();
        $path = $request->getPath();

        $controller = $this->routes[$method][$path] ?? null;
        
        if ($controller === null) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        $coreAction = function($req) use ($controller) {
            if ($controller instanceof ControllerInterface) {
                return $controller->execute($req);
            } elseif (is_callable($controller)) {
                return call_user_func($controller, $req);
            } elseif (is_string($controller) && class_exists($controller)) {
                $instance = new $controller();
                if ($instance instanceof ControllerInterface) {
                    return $instance->execute($req);
                }
            }
            throw new Exception("Invalid route controller format.");
        };

        // Build the middleware chain
        $chain = array_reduce(
            array_reverse($this->middlewares),
            function($next, $middleware) {
                return function($req) use ($middleware, $next) {
                    return $middleware->handle($req, $next);
                };
            },
            $coreAction
        );

        $chain($request);
    }
}
?>
