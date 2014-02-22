# Arya

Arya is a minimalist PHP web SAPI framework providing URI routing, dependency injection and
middleware hooks. The framework leverages HTTP protocol adherence and SOLID design principles to
maximize flexibility while maintaining simplicity and performance.

> **WARNING:** Arya is still under development and there is very little unit-testing present at
> the moment. The project is moving towards an official v0.1.0 release but has not yet reached
> that point. Code may change at any time and without warning. Use at your own risk.

![Travis build https://api.travis-ci.org/rdlowrey/Arya.png?branch=master](https://api.travis-ci.org/rdlowrey/Arya.png?branch=master)

#### Basic Example

```php
<?php
$app = (new Arya\Application)
    ->before(function($request) {...}) // <-- middleware before request handled
    ->route('GET', '/', 'anyFunction')
    ->route('GET', '/lambda', $anyClosure)
    ->route('GET', '/static', 'AnyClass::staticMethod')
    ->route('GET', '/instance', 'AnyClass::instanceMethod') // <-- auto dependency injection
    ->route('GET', '/args/{arg1:\d+}/{arg2:\d+}', 'numericArgsFunction')
    ->after(function($request, $response) { ... }) // <-- middleware before response sent
    ->run()
;
```

#### Project Goals

- Model code on the HTTP/1.1 protocol as outlined in RFC 2616;
- Build components using SOLID, readable and well-tested code;
- Prevent vendor lock-in and static coupling;
- Minimize performance overhead.

## Requirements and Installation

#### Basic Requirements

- [PHP](http://php.net) (5.4+)
- [FastRoute](https://github.com/nikic/FastRoute) | high-performance routing
- [Auryn](https://github.com/rdlowrey/Auryn) | automated dependency injection

#### Testing Requirements

- [PHPUnit](http://phpunit.de/) | running automated tests
- [Artax](https://github.com/rdlowrey/Artax) | running automated tests

### Download

#### Github

You can clone the latest Arya iteration at anytime from the github repository. By using the
`--recursive` option git will automatically retrieve dependency submodules for us.

```bash
$ git clone --recursive git://github.com/rdlowrey/Arya.git
```

#### Composer

```bash
$ php composer.phar create-project rdlowrey/arya /path/to/later/location/Arya dev-master
```

#### Manual Download

Archived tagged release versions are also available (or will be) for manual download on the project
[tags page](https://github.com/rdlowrey/Arya/tags).

### Server Setup

Arya acts as a front-controller to route all requests. To make this work you must configure your
front-facing server to direct all requests to a single file.

#### Apache

With Apache 2.2.16 or higher, you can use the FallbackResource directive in your configuration
file (.htaccess/httpd.conf/vhost.conf):

```
FallbackResource /front_controller.php
```

If you have an older version of Apache you should instead add this block to your config file:

```
<IfModule mod_rewrite.c>
    Options -MultiViews

    RewriteEngine On
    RewriteBase /path/to/app
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ front_controller.php [QSA,L]
</IfModule>
```

#### Nginx
@TODO

#### PHP Server

As of PHP 5.4 you can use the built-in development server to quickly run your application:

```bash
$ php -S localhost:8080 front_controller.php
```

## Contributions

Contributions in the form of pull requests (PRs) are always appreciated! Before submitting a PR
please read the [CONTRIBUTORS][contributors] file.

[contributors]: https://github.com/rdlowrey/Arya/blob/master/CONTRIBUTORS.md "CONTRIBUTORS"

## The Guide

**Routing**

* [Standard Route Targets](#standard-route-targets)
* [Extended Route Targets](#extended-route-targets)
* [Route Arguments](#route-arguments)
* [Route Execution Paths](#route-execution-paths)

**Middleware**

* [Before](#before)
* [After](#after)
* [Finalize](#finalize)

**Dependency Injection**

@TODO

**HTTP Protocol**

* [The HTTP Request](#the-http-request)
* [Simple Responses](#simple-responses)
* [The HTTP Response](#the-http-response)
* [Callable Response Bodies](#callable-response-bodies)

**Other**

* [App Options](#app-options)
* [Debug Mode](#debug-mode)
* [Custom Error Handlers](#custom-error-handlers)

**Server Setup**

* [Apache](#apache)
* [Nginx](#nginx)
* [PHP Server (5.4+)](#php-server)


## Routing

### Standard Route Targets

An Arya route definition consists of exactly three elements:

- HTTP method verb
- URI path
- Target callable

Arya supports any valid [PHP callable](http://www.php.net/manual/en/language.types.callable.php) as
a route target. This means that you can specify function names, lambdas, static class methods and
array instance method constructions. Consider:

```php
<?php
$app = (new Arya\Application)
    ->route('GET', '/', 'myFunctionName')
    ->route('GET', '/lambda-hello', function() { return 'Hello world'; })
    ->route('POST', '/static-method', 'MyClass::myStaticPostHandler')
    ->route('GET', '/array-callback', [$myObject, 'someMethod'])
    ->route('GET', '/instance-method', 'MyController::get') // <-- IMPORTANT
    ->run()
;
```

Routes are matched in the order in which they are assigned with the caveat that routes *without*
arguments always take precedence over routes *with* arguments (see (Route Arguments)[#route-arguments]).

### Extended Route Targets

You'll notice that in the above example code there's special attention paid to the final route,
`SomeClass::someMethod`. We differentiate this route because it doesn't reference a static method.
How is this possible? Well ...

Arya recursively instantiates and provisions classes for you using the include `Auryn` dependency
injection library. Consider the following simple example in which our `MyController` class is
automatically instantiated and invoked for requests to the `/` index resource:

```php
<?php
class Templater {
    function render($uri) {
        return "<html><body><p>Hello from {$uri}!</p></body></html>";
    }
}
class MyController {
    private $templater;
    function __construct(Templater $tpl) {
        $this->templater = $tpl;
    }
    function get(Request $request) {
        return $this->templater->render($request['REQUEST_URI']);
    }
}

$app = (new Arya\Application)->route('GET', '/', 'MyClass::get')->run();
```

> **IMPORTANT:** Arya also recursively injects any dependencies you typehint in your controller
> method signatures. In the above example we use constructor injection to provide the `Templater`
> object that renders our HTML response. However, we could have alternatively typehinted the
> `Templater` in our `MyClass::get` method signature and injected it at call time.

### Route Arguments

Arya uses [FastRoute](https://github.com/nikic/FastRoute) for routing.

```
/lol-cats/{catType}/{catId:\d+}
/widgets/{widgetId:\d+}
/kumqats/{kumqatId}
```

When named URI arguments are matched they are available to route targets in two different ways:

1. As associative array keys in the `$request['ROUTE_ARGS']` array
2. As paramaters with matching names in the route target's method/function signature

So, let's consider a match for our `/lol-cats` route from above:

```php
<?php

use Arya\Application;

function lolcatsFunction($request, $catType, $catId) {
    assert($catType === $request['ROUTE_ARGS']['catType']);
    assert($catId === $request['ROUTE_ARGS']['catId']);

    return '<html><body>woot!</body></html>';
}

$app = (new Application)
    ->route('GET', '/lol-cats/$catType/$#catId', 'lolcatsFunction')
    ->run();
```

The takeway here is that you can accept your URI route arguments as parameters with matching names
in your route target signature if you like but they'll always be available in the
`$request['ROUTE_ARGS']` array as well.

### Route Execution Paths

Every client request follows one of three paths through the routing system:

1. No request URI match is found: `404 Not Found`
2. A request URI is matched, but the HTTP verb does not match: `405 Method Not Allowed`
3. The request URI and HTTP method match a route and the associated target is invoked

## Middleware

One of Arya's goals is to retain PHP's ease of use while still exposing a standard API for
middleware modification before and after each request.

**Middleware Quick and Dirty**

- Arya uses universal `Arya\Request` and `Arya\Response` instances for each page load;
- Middleware may modify the universal request/response instances before or after route handlers;
- Middleware MUST return `TRUE/FALSE/NULL` and may only modify the request/response directly;
- If a middleware returns `TRUE` then no more middleware of the same type (before/after/final) will
  execute;
- "Before" middleware should *only* return `TRUE` if it assigns a response as Arya will treat this
  result as an indication that the route handler should not be invoked;
- "After" middleware will always execute after "before" middleware and/or the routed application
  handler;
- @TODO Mention "Final" middleware (not yet operational)


### Before

@TODO Add URL rewriting example


### After

The middleware concept is just as powerful for modifying responses after they're generated as it is
for altering requests before they reach the application. Let's consider a standard use-case for an
"after" middleware ...

##### Custom Error Pages

Arya provides no *built-in* abstraction for custom error pages because the middleware system exposes
all the necessary tools for a manual implementation. Let's say we want to capture all error
responses and replace them with our own custom error page. All we need to do is specify an "after"
middleware callable to capture and modify such responses before they're sent to the client ...

```php
<?php
use Arya\Response, Arya\Application;

function myErrorPageMiddleware(Response $response) {
    switch ($response->getStatus()) {
        case 404:
            $response->setBody('my custom 404 html');
            break;
        case 500:
            $response->setBody('my custom 500 html');
            break;
        default:
            // don't bother to modify any others
    }
}

(new Application)
    ->route('GET', '/', function() { return 'Hello World'; })
    ->after('myErrorPageMiddleware')
    ->run();
```

What's going on in this example? All we've done is register a middleware class method to be invoked
after the application executes. Our `myErrorPageMiddleware` function simply checks to see if
specific status codes were assigned and if so replaces the response entity body with our own custom
HTML. Note that middleware callables are provisioned in the same way as applications; all we
need to do is typehint our dependencies and they'll be provided automatically either in the
constructor or callable signature (wherever the middleware asks for them).


### Finalize
@TODO "Final" middleware not yet operational






## Dependency Injection
@TODO



## HTTP Protocol

### The HTTP Request

Arya represents every request as an `Arya\Request` instance. This class provides an object-oriented
alternative to the superglobal anti-pattern present by default in PHP web SAPI environments.

**Request Mutability**

`Request` instances implement `ArrayAccess` to provide mutability for middleware callables. In this
way it's possible for middleware components to alter the request to perform actions such as URI
rewriting.

**Request Provisioning**

Because route targets are automatically provisioned they must typehint the `Arya\Request` class in
their method signatures (extended instance method targets may also typehint in `__construct`). For
example, the following route targets demonstrate equivalent ways to ask for the request instance in
your application:

```php
<?php
use Arya\Application, Arya\Request;

function myFunctionTarget(Request $request) {
    return "Hello from " . $request['REQUEST_URI'];
}

class MyCtorRouteClass {
    private $request;
    function __construct(Request $request) {
        $this->request = $request;
    }
    function myTarget() {
        return "Hello from " . $this->request['REQUEST_URI'];
    }
}

class MyMethodRouteClass {
    function myTarget(Request $request) {
        return "Hello from " . $request['REQUEST_URI'];
    }
}

$app = (new Application)
    ->route('GET', '/function', 'myFunctionTarget')
    ->route('GET', '/my-ctor-route', 'MyCtorRouteClass::myTarget')
    ->route('GET', '/my-method-route', 'MyMethodRouteClass::myTarget')
    ->run()
;
```

### Simple Responses

One of the most user-friendly aspects of the PHP web SAPI is the ability to simply `echo` output as
your HTTP response entity body. This approach, however, is suboptimal in terms of testability and
enterprise sustainability. In the spirit of simplifying HTTP Arya allows application callables to
return strings directly to generate a standard 200 response. Consider:

```php
<?php
function helloWorld(Arya\Request $request) {
    return "Hello from " . $request['REQUEST_URI'];
}

$app = new Arya\Application;
$app->route('GET', '/', function(Arya\Request $request) {
    return "Hello from " . $request['REQUEST_URI'];
});
$app->run();
```

Arya actively prevents the manual output of response data via output buffering. If your application
generates any output (including error output) the result is a `500 Internal Server Error` response.
When in DEBUG mode you'll receive a helpful print-out containing the output data. In production
environments a generic 500 error message is displayed. This behavior is designed to funnel all
output through your route target return values so that "after" middleware callables have an
opportunity to inspect/modify output. Note that when in DEBUG mode this behavior also simplifies
n00b-core browser output debugging at runtime.

### The HTTP Response

When your application needs fine-grained control over the HTTP response a simple string is not enough.
In these cases route targets may return an instance of the `Arya\Response` class.

@TODO Talk about status code

@TODO Talk about headers

@TODO Talk about entity body

### Callable Response Bodies
@TODO




## Other
@TODO Intro

### App Options
@TODO

### Debug Mode
@TODO
