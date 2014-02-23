# ASGI

**The ASGI Application**

- Another Server Gateway Interface

**The ASGI Response**

- [Status](#status)
- [Reason](#reason)
- [Headers](#headers)
- [Body](#body)
- [Simple Response](#simple-response)
- [Non-Standard Response Keys](#non-standard-response-keys)

**The ASGI Request**

- [CGI-Style Request Keys](#cgi-style-request-keys)
- [ASGI Request Keys](#asgi-request-keys)
- [Header Request Keys](#header-request-keys)
- [ASGI Request Example](#asgi-request-example)


## The ASGI Application

An ASGI application (*the Application*) is a reference to a PHP callable. This callable accepts
exactly one argument -- [the Request](#the-asgi-request) -- and **SHOULD** return one of the
following dynamic types:

- An associative array or array-like object mapping at least one of the keys
  specified in the [ASGI Response](#the-asgi-response) section.
- A string entity body

While ASGI-compliant servers **MAY** support other response types, those specified in this section
are the only types guaranteed to be supported.

Simple application callables are shown here for reference:

```php
<?php
// Standard app response
$asgiApplication = function($request) {
    return [
        'status' => 200,
        'reason' => 'OK',
        'headers' => [
            'Content-Type' => 'text/plain; charset=utf-8',
            'My-Header: some value',
            'My-Header: another value'
        ],
        'body' => "Hello World"
    ];
};

// Alternative "simple response" application:
$simpleAsgiApplication = function($request) {
    return '<html><body>Hello, World.</body></html>';
};
```





## The ASGI Response

An ASGI Response is an associative array or array-like object mapping at least one of the keys in
this section. Application response keys are *case-sensitive* ...

#### Status

An HTTP status code. This **MUST** be a scalar value that, when cast as an integer, has a value
greater than or equal to 100, less than or equal to 599. Status codes **SHOULD** be used to reflect
the semantic meaning of the HTTP status codes documented in RFC 2616 section 10. Applications
**MUST NOT** return reason phrase information as part of the "status" element:

```php
<?php
$status = 404; // Valid
$status = '404 Not Found'; // <--- Wrong! Do not do this!
```

#### Reason

An optional reason phrase string elaborating on the status code. The specification of the reason
phrase is explicitly separated from the numeric status code to simplify response manipulation.

#### Headers

An indexed array of raw string headers. Applications **SHOULD** endeavor to populate `Content-Type`
key and, if known, the `Content-Length` key. Servers **MAY** (but are not required to) normalize
and/or correct invalid header values.

#### Body

A string, seekable stream resource or `Generator` instance representing the response entity body

#### Simple Response

In addition to array/map responses, servers **MUST** also support application responses that return
the entity body directly. For example:

```php
<?php
$asgiApp = function($request) {
    return '<html><body>Hello, World.</body></html>';
};
```

Servers accepting simple responses **SHOULD** extrapolate any necessary headers from the returned
entity body. The normalization of entity bodies is not required and may not be portable across
ASGI-compliant servers. In the event of a simple response servers **MUST** assume a response status
of `200`.

#### Non-Standard Response Keys

ASGI-compliant applications may optionally return other keys as part of the response map. However,
such extended information is not guaranteed to be supported across ASGI-compliant platforms. Your
mileage may vary.








## The ASGI Request

The ASGI request **MUST** be an associative array or array-like map object specifying CGI-like keys
as detailed below. ASGI applications (and middleware) are free to modify the request but **MUST**
include at least those keys documented in this section unless they would normally be empty. For
example, an *Environment* describing an HTTP request without an entity body **MUST NOT** specify
`CONTENT_LENGTH` or `CONTENT_TYPE` keys.

When an environment key is described as a boolean, its value **MUST** conform to PHP's concept of
"truthy-ness". This means that NULL, an empty string and integer `0` are all valid "falsy"
values. If a boolean key is not present, an application **MAY** treat this as boolean false.

### CGI-Style Request Keys

###### SERVER_NAME

The host/domain name specified by the client request stripped of any trailing port numbers. Hosts
without a DNS name should specify the server's IP address. Servers **MUST** ensure that this value
is sanitized and free from potential malicious influence from the client-controlled `Host` header.

###### SERVER_PORT

The public facing port on which the request was received.

###### SERVER_PROTOCOL

The HTTP protocol agreed upon for the current request, e.g. 1.0 or 1.1. This key consists only of
the numeric protocol version and **MUST NOT** include any prefixing such as "HTTP" or "HTTP/".

###### REMOTE_ADDR

The IP address of the remote client responsible for the current request. Applications should be
equipped to deal with both IPv4 and IPv6 addresses.

###### REMOTE_PORT

The numeric port number in use by the remote client when making the current request.

###### REQUEST_METHOD

The HTTP request method used in the current request, e.g. GET/HEAD/POST.

###### REQUEST_URI

The undecoded raw URI parsed from the HTTP request start line. This value corresponds to the *full*
URI shown here inside curly braces:

```
GET {http://mysite.com/path/to/resource} HTTP/1.1
```

This value is dependent upon the raw request submitted by the client. It may be a full absolute URI
as shown above but it may also contain only the URI path and query components.

###### REQUEST_URI_PATH

Contains *only* the undecoded raw path component from the request URI. This value differs
from the `REQUEST_URI` key in that it **MUST** only represent the URI path and query submitted in
the request even if the raw request start line specified a full absolute URI.

###### HTTPS

This value **MUST** be `TRUE` if the request was submitted over an encrypted connection and `FALSE`
if not. Servers **MUST** assign this value appropriately given the state of encryption on the client
connections used to issue the request regardless of any URI scheme specified in the request line to
avoid spoofing.

###### QUERY_STRING

The portion of the request URL that follows the ?, if any. This key **MAY** be empty, but **MUST**
always be present, even when empty.

###### CONTENT_TYPE

The request's MIME type, as specified by the client. The presence or absence of this key **MUST**
correspond to the presence or absence of an HTTP Content-Type header in the request.

###### CONTENT_LENGTH

The length of the request entity body in bytes. The presence or absence of this key **MUST**
correspond to the presence or absence of HTTP Content-Length header in the request.

### ASGI Request Keys

###### ASGI_VERSION

The ASGI protocol version adhered to by the server generating the request environment

###### ASGI_INPUT

An open stream resource referencing to the request entity body (if present in the request).

###### ASGI_ERROR

An open stream resource referencing the server's error stream. This makes it possible for applications
to centralize error logging in a single location.

###### ASGI_NON_BLOCKING

`TRUE` if the server is invoking the application inside a non-blocking event loop.

### Header Request Keys

These keys correspond to the client-supplied HTTP request headers. The presence or absence of these
keys should correspond to the presence or absence of the appropriate HTTP header in the request. The
key is obtained converting the HTTP header field name to upper case, replacing all occurrences of
hyphens `(-)` with underscores `(_)` and prepending `HTTP_`, as in RFC 3875.

If a client sends multiple header lines with the same key, the server **SHOULD** treat them as if
they were sent in one line and combine them using commas `(,)` as specified in RFC 2616. Alternative
implementations **MAY** represent multiple headers for the same field as a single-dimensional array,
though this behavior is discouraged.

### ASGI Request Example

An example of a typical *Environment* array follows:

```php
$asgiRequest = [
    'SERVER_NAME'        => 'mysite.com',
    'SERVER_PORT'        => '80',
    'SERVER_PROTOCOL'    => '1.1',
    'REMOTE_ADDR'        => '123.456.789.123',
    'REMOTE_PORT'        => '9382',
    'REQUEST_METHOD'     => 'GET',
    'REQUEST_URI'        => '/hello_world.php?foo=bar',
    'REQUEST_URI_PATH'   => '/hello_world.php',
    'HTTPS'              => FALSE,
    'QUERY_STRING'       => '?foo=bar',
    'CONTENT_TYPE'       => 'text/plain',
    'CONTENT_LENGTH'     => '42',

    // --- HTTP_* KEYS --- //

    'HTTP_HOST'             => 'mysite.com',
    'HTTP_CONNECTION'       => 'keep-alive',
    'HTTP_ACCEPT'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'HTTP_USER_AGENT'       => 'Mozilla/5.0 (X11; Linux x86_64) ...',
    'HTTP_ACCEPT_ENCODING'  => 'gzip,deflate,sdch',
    'HTTP_ACCEPT_LANGUAGE'  => 'en-US,en;q=0.8',
    'HTTP_ACCEPT_CHARSET'   => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
    'HTTP_COOKIE'           => 'var1=value1&var2=value2',

    // --- ASGI_* KEYS --- //

    'ASGI_VERSION'          => '0.1',
    'ASGI_INPUT'            => NULL,
    'ASGI_ERROR'            => $resource,
    'ASGI_NON_BLOCKING'     => TRUE
];
```
