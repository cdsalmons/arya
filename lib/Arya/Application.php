<?php

namespace Arya;

use Auryn\Injector,
    Auryn\Provider,
    Auryn\InjectionException,
    Arya\Status,
    Arya\Reason,
    Arya\Routing\Router,
    Arya\Routing\NotFoundException,
    Arya\Routing\MethodNotAllowedException,
    Arya\Routing\CompositeRegexRouter,
    Arya\Sessions\Session;

class Application {

    private $injector;
    private $router;
    private $request;
    private $response;
    private $session;

    private $befores = array();
    private $afters = array();
    private $finalizers = array();

    private $options = array(
        'app.debug' => TRUE,
        'app.auto_reason' => TRUE,
        'app.normalize_method_case' => TRUE,
        'app.allow_empty_response' => FALSE,
        'app.auto_urldecode' => TRUE,
        'session.class' => 'Arya\Sessions\FileSessionHandler',
        'session.strict' => TRUE,
        'session.cookie_name' => 'ARYASESSID',
        'session.cookie_domain' => '',
        'session.cookie_path' => '',
        'session.cookie_secure' => FALSE,
        'session.cookie_httponly' => TRUE,
        'session.check_referer' => '',
        'session.entropy_length' => 1024,
        'session.entropy_file' => NULL,
        'session.hash_function' => NULL,
        'session.cache_limiter' => Session::CACHE_NOCACHE,
        'session.cache_expire' => 180,
        'session.gc_probability' => 1,
        'session.gc_divisor' => 100,
        'session.gc_max_lifetime' => -100,
        'session.middleware_priority' => 20,
        'session.save_path' => NULL
    );

    public function __construct(Injector $injector = NULL, Router $router = NULL) {
        $this->injector = $injector ?: new Provider;
        $this->router = $router ?: new CompositeRegexRouter;

        $self = $this;
    }

    /**
     * Add a route handler
     *
     * @param string $httpMethod
     * @param string $route
     * @param mixed $handler
     * @return AppRouteProxy
     */
    public function route($httpMethod, $uri, $handler) {
        if ($this->options['app.normalize_method_case']) {
            $httpMethod = strtoupper($httpMethod);
        }

        $this->router->addRoute($httpMethod, $uri, $handler);

        return new AppRouteProxy($this, $httpMethod, $uri);
    }

    /**
     * Attach a "before" middleware
     *
     * @param mixed $middleware
     * @param array $options
     * @return Application Returns the current object instance
     */
    public function before($middleware, array $options = array()) {
        $this->befores[] = $this->generateMiddlewareComponents($middleware, $options);

        return $this;
    }

    private function generateMiddlewareComponents($middleware, array $options) {
        $methodFilter = empty($options['method']) ? NULL : $options['method'];
        $uriFilter = empty($options['uri']) ? NULL : $options['uri'];
        $priority = isset($options['priority']) ? @intval($options['priority']) : 50;

        return array($middleware, $methodFilter, $uriFilter, $priority);
    }

    /**
     * Attach an "after" middleware
     *
     * @param mixed $middleware
     * @param array $options
     * @return Application Returns the current object instance
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
     * @return Application Returns the current object instance
     */
    public function finalize($middleware, array $options = array()) {
        $this->finalizers[] = $this->generateMiddlewareComponents($middleware, $options);

        return $this;
    }

    /**
     * Respond to client requests
     *
     * The run method allows users to inject their own Arya\Request instance (which can be useful
     * for testing). Most use-cases should leave the parameter unassigned as Arya will auto-generate
     * the request automatically if not specified.
     *
     * @param array $request The request environment
     * @return void
     */
    public function run(Request $request = NULL) {
        $request = $request ?: $this->generateRequest();
        $response = new Response;

        if ($this->options['app.auto_urldecode']) {
            $request['REQUEST_URI'] = urldecode($request['REQUEST_URI']);
            $request['REQUEST_URI_PATH'] = urldecode($request['REQUEST_URI_PATH']);
        }

        $this->request = $request;
        $this->response = $response;

        $this->injector->share($request);
        $this->injector->share($response);
        $this->injector->share('Arya\Sessions\SessionMiddlewareProxy');
        $this->injector->alias('Arya\Sessions\Session', 'Arya\Sessions\SessionMiddlewareProxy');
        $this->injector->define('Arya\Sessions\SessionMiddlewareProxy', array(
            ':app' => $this,
            ':request' => $request,
            ':priority' => $this->options['session.middleware_priority'],
            'handler' => $this->options['session.class']
        ));

        $middlewareSort = [$this, 'middlewareSort'];
        usort($this->befores, $middlewareSort);

        ob_start();

        if (!$this->doMiddleware($this->befores)) {
            $this->routeRequest();
        }

        // We specifically sort these after handler invocation so that session middleware
        // added during session instantiation can be dynamically prioritized. This isn't
        // strictly necessary but if we sorted these before the request it wouldn't be possible
        // to let users change session middleware priority.
        usort($this->afters, $middlewareSort);
        usort($this->finalizers, $middlewareSort);

        $this->doMiddleware($this->afters);

        $bufferedOutput = ob_get_clean();

        if (isset($bufferedOutput[0])) {
            $this->applyOutputErrorResponse($bufferedOutput);
        }

        $this->sendResponse();
    }

    private function generateRequest() {
        $_input = !empty($_SERVER['CONTENT-LENGTH']) ? fopen('php://input', 'r') : NULL;
        $request = new Request($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_input);

        unset($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);

        return $request;
    }

    private function middlewareSort(array $a, array $b) {
        $a = end($a);
        $b = end($b);

        if ($a == $b) {
            $result = 0;
        } else {
            $result = ($a < $b) ? -1 : 1;
        }

        return $result;
    }

    private function doMiddleware(array $middleware) {
        $shouldStop = FALSE;
        foreach ($middleware as $middlewareStruct) {
            if ($this->applyMiddleware($middlewareStruct)) {
                $shouldStop = TRUE;
                break;
            }
        }

        return $shouldStop;
    }

    private function applyMiddleware(array $middlewareStruct) {
        list($executableMiddleware, $methodFilter, $uriFilter) = $middlewareStruct;

        if ($methodFilter && $this->request['REQUEST_METHOD'] !== $methodFilter) {
            $shouldStop = FALSE;
        } elseif ($uriFilter && !$this->matchesUriFilter($uriFilter, $this->request['REQUEST_URI'])) {
            $shouldStop = FALSE;
        } else {
            $shouldStop = $this->tryMiddleware($executableMiddleware);
        }

        return $shouldStop;
    }

    private function matchesUriFilter($uriFilter, $uriPath) {
        if ($uriFilter === $uriPath) {
            $isMatch = TRUE;
        } elseif ($uriFilter[strlen($uriFilter) - 1] === '*'
            && strpos($uriPath, substr($uriFilter, 0, -1)) === 0
        ) {
            $isMatch = TRUE;
        } else {
            $isMatch = FALSE;
        }

        return $isMatch;
    }

    private function tryMiddleware($executableMiddleware) {
        try {
            $shouldStop = $this->injector->execute($executableMiddleware, array(
                ':request' => $this->request,
                ':response' => $this->response,
            ));

            if ($shouldStop && $shouldStop !== TRUE) {
                throw new \RuntimeException(
                    sprintf('Middleware returned invalid type: %s', gettype($shouldStop))
                );
            }

        } catch (InjectionException $error) {
            $shouldStop = TRUE;
            $this->applyExceptionResponse(new \RuntimeException(
                $msg = 'Middleware injection failure',
                $code = 0,
                $error
            ));
        } catch (\Exception $error) {
            $shouldStop = TRUE;
            $this->applyExceptionResponse(new \RuntimeException(
                $msg = 'Middleware execution threw an uncaught exception',
                $code = 0,
                $error
            ));
        }

        return $shouldStop;
    }

    private function applyExceptionResponse(\Exception $e) {
        $this->response->importArray(array(
            'status' => 500,
            'body' => $this->generateExceptionBody($e)
        ));
    }

    private function generateExceptionBody(\Exception $e) {
        $msg = $this->options['app.debug']
            ? "<pre style=\"color:red\">{$e}</pre>"
            : '<p>Something went terribly wrong!</p>';

        return "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
    }

    private function applyOutputErrorResponse($buffer) {
        $msg = $this->options['app.debug']
            ? "<pre style=\"color:red\">{$buffer}</pre>"
            : '<p>Something went terribly wrong!</p>';

        $body = "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";

        $this->response->importArray(array(
            'status' => 500,
            'body' => $body
        ));
    }

    private function routeRequest($forceMethod = NULL) {
        try {
            $request = $this->request;
            $method = $forceMethod ?: $request['REQUEST_METHOD'];
            $uriPath = $request['REQUEST_URI_PATH'];
            list($routeHandler, $routeArgs) = $this->router->route($method, $uriPath);

            $request['ROUTE_ARGS'] = $routeArgs;
            $paramLiterals = array(
                ':request' => $request,
                ':response' => $this->response
            );

            if ($routeArgs) {
                foreach ($routeArgs as $key => $value) {
                    $paramLiterals[":{$key}"] = $value;
                }
            }

            $result = $this->injector->execute($routeHandler, $paramLiterals);

            if ($result instanceof Response) {
                $this->response->import($result);
            } elseif (is_array($result)) {
                $this->response->importArray($result);
            } else {
                $this->response->setBody($result);
            }
        } catch (NotFoundException $e) {
            $this->applyNotFoundResponse();
        } catch (MethodNotAllowedException $e) {
            if ($method === 'HEAD') {
                $this->routeRequest($forceMethod = 'GET');
            } else {
                $allowedMethods = $e->getAllowedMethods();
                $this->applyMethodNotAllowedResponse($allowedMethods);
            }
        } catch (UserInputException $e) {
            $this->applyBadUserInputResponse($e->getMessage());
        } catch (InjectionException $e) {
            $this->applyExceptionResponse(new \RuntimeException(
                $msg = 'Route handler injection failure',
                $code = 0,
                $prev = $e
            ));
        } catch (\Exception $e) {
            $this->applyExceptionResponse($e);
        }
    }

    private function applyNotFoundResponse() {
        $this->response->importArray(array(
            'status' => 404,
            'body' => '<html><body><h1>404 Not Found</h1></body></html>'
        ));
    }

    private function applyMethodNotAllowedResponse(array $allowedMethods) {
        $this->response->importArray(array(
            'status' => 405,
            'headers' => array('Allow: ' . implode(',', $allowedMethods)),
            'body' => '<html><body><h1>405 Method Not Allowed</h1></body></html>'
        ));
    }

    private function applyBadUserInputResponse($errorMessage) {
        $reason = 'Bad Input Parameter';
        $errorMessage = $errorMessage ?: 'Invalid form or query parameter input';
        $this->response->importArray([
            'status' => 400,
            'reason' => $reason,
            'body' => "<html><body><h1>400 {$reason}</h1><p>{$errorMessage}</p></body></html>"
        ]);
    }

    private function sendResponse() {
        // @TODO Decide if headers assigned with header() should
        // actually be retained.
        if ($nativeHeaders = headers_list()) {
            foreach ($nativeHeaders as $line) {
                $this->response->addHeaderLine($line);
            }
        }

        $statusCode = $this->response->getStatus();
        $reason = $this->response->getReasonPhrase();
        if ($this->options['app.auto_reason'] && empty($reason)) {
            $reasonConstant = "Arya\Reason::HTTP_{$statusCode}";
            $reason = defined($reasonConstant) ? constant($reasonConstant) : '';
            $this->response->setReasonPhrase($reason);
        }

        $statusLine = sprintf("HTTP/%s %s", $this->request['SERVER_PROTOCOL'], $statusCode);
        if (isset($reason[0])) {
            $statusLine .= " {$reason}";
        }

        header_remove();
        header($statusLine);

        foreach ($this->response->getAllHeaderLines() as $headerLine) {
            header($headerLine, $replace = FALSE);
        }

        flush(); // Force header output

        $body = $this->response->getBody();

        if (is_string($body)) {
            echo $body;
        } elseif (is_callable($body)) {
            $this->outputCallableBody($body);
        }
    }

    private function outputCallableBody(callable $body) {
        try {
            $body();
        } catch (\Exception $e) {
            $this->outputManualExceptionResponse($e);
        }
    }

    private function outputManualExceptionResponse(\Exception $e) {
        if (!headers_sent()) {
            header_remove();
            $protocol = $this->request['SERVER_PROTOCOL'];
            header("HTTP/{$protocol} 500 Internal Server Error");
            echo $this->generateExceptionBody($e);
        }
    }

    private function applyFinalizers($middleware) {
        try {
            $this->injector->execute($middleware, []);
        } catch (\Exception $e) {
            error_log($e->__toString());
        }
    }

    /**
     * Retrieve an application option setting
     *
     * @param string $option
     * @throws \DomainException
     * @return mixed
     */
    public function getOption($option) {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        } else {
            throw new \DomainException(
                sprintf('Unknown option: %s', $option)
            );
        }
    }

    /**
     * Set multiple application options
     *
     * @param array $options
     * @throws \DomainException
     * @return Application Returns the current object instance
     */
    public function setAllOptions(array $options) {
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }

        return $this;
    }

    /**
     * Set an application option
     *
     * @param string $option
     * @param mixed $value
     * @throws \DomainException
     * @return Application Returns the current object instance
     */
    public function setOption($option, $value) {
        if (isset($this->options[$option])) {
            $this->assignOptionValue($option, $value);
        } else {
            throw new \DomainException(
                sprintf('Unknown option: %s', $option)
            );
        }

        return $this;
    }

    private function assignOptionValue($option, $value) {
        switch ($option) {
            case 'session.class':
                $this->setSessionClass($value);
                break;
            case 'session.save_path':
                $this->setSessionSavePath($value);
                break;
            default:
                $this->options[$option] = $value;
        }
    }

    private function setSessionClass($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                'session.class must be a string'
            );
        } elseif (!class_exists($value)) {
            throw new \LogicException(
                sprintf('session.class does not exist and could not be autoloaded: %s', $value)
            );
        } else {
            $this->options['session.class'] = $value;
            $this->injector->alias('Arya\Sessions\SessionHandler', $value);
        }
    }

    private function setSessionSavePath($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf('session.class requires a string; %s provided', gettype($value))
            );
        } elseif (!(is_dir($value) && is_writable($value))) {
            throw new \InvalidArgumentException(
                sprintf('session.save_path requires a writable directory path: %s', $value)
            );
        } else {
            $this->options['session.save_path'] = $value;
            $this->injector->define('Arya\Sessions\FileSessionHandler', array(
                ':dir' => $value
            ));
        }
    }

}
