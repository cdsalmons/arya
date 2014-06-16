<?php

namespace Arya;

class Routes {

    protected $routes = [];
    protected $canSerializeRoutes = TRUE;

    protected $befores = [];
    protected $afters = [];
    protected $finalizers = [];

    /**
     * Attach a "before" middleware
     *
     * @param mixed $middleware
     * @param array $options
     * @return static Returns the current object instance
     */
    public function before($middleware, array $options = array()) {
        $this->befores[] = $this->generateMiddlewareComponents($middleware, $options);

        return $this;
    }

    /**
     * Attach an "after" middleware
     *
     * @param mixed $middleware
     * @param array $options
     * @return static Returns the current object instance
     */
    public function after($middleware, array $options = array()) {
        $this->afters[] = $this->generateMiddlewareComponents($middleware, $options);

        return $this;
    }

    /**
     * Attach a "finalize" middleware
     *
     * @param mixed $middleware
     * @param array $options
     * @return static Returns the current object instance
     */
    public function finalize($middleware, array $options = array()) {
        $this->finalizers[] = $this->generateMiddlewareComponents($middleware, $options);

        return $this;
    }

    private function generateMiddlewareComponents($middleware, $options) {
        $methodFilter = empty($options['method']) ? NULL : $options['method'];
        $uriFilter = empty($options['uri']) ? NULL : $options['uri'];
        $priority = isset($options['priority']) ? @intval($options['priority']) : 50;

        return array($middleware, $methodFilter, $uriFilter, $priority);
    }

    /**
     * Add a route handler
     *
     * @param string $httpMethod
     * @param string $uri
     * @param mixed $handler
     * @return static Returns the current object instance
     */
    public function route($httpMethod, $uri, $handler) {
        $this->routes[] = [$httpMethod, $uri, $handler];

        if ($handler instanceof \Closure) {
            $this->canSerializeRoutes = FALSE;
        }

        return $this;
    }

    /**
     * Merges route handlers of a specific prefix
     *
     * @param self $routes
     * @param string $prefixPath
     * @throws \UnexpectedValueException
     * @return static Returns the current object instance
     */
    public function addRoutes(self $routes, $prefixPath = "") {
        if (!is_scalar($prefixPath)) {
            throw new \UnexpectedValueException(
                sprintf("Invalid prefix path option type: %s", gettype($prefixPath))
            );
        }

        $this->canSerializeRoutes &= $routes->canSerializeRoutes;

        if ($prefixPath != "") {
            foreach (["befores", "afters", "finalizers"] as $type) {
                foreach ($routes->$type as $middleware) {
                    if ($middleware[2] == NULL) {
                        $middleware[2] = "$prefixPath*";
                    } elseif ($middleware[2][0] != "/") {
                        $middleware[2] = $prefixPath.$middleware[2];
                    }
                    $this->{$type}[] = $middleware;
                }
            }
        }

        foreach ($routes->routes as $route) {
            $route[1] = $prefixPath . $route[1];
            $this->routes[] = $route;
        }

        return $this;
    }

}