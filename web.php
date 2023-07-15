<?php

namespace web;

use http;

// TODO(art): something for REST api?

// TODO(art): add support for placeholders in uri

class App
{
    public $router;
    public $middleware = [];
    public $ctx = [];

    function __construct(string $ns = "")
    {
        $this->router = new Router($ns);
    }

    function add_router(Router $r, string $namespace = ""): void
    {
        $namespace = $this->router->namespace . $namespace;

        if ($namespace)
        {
            foreach (array_keys($r->routes) as $uri)
            {
                $ns_uri = prepend_namespace($namespace, $uri);
                $this->router->routes[$ns_uri] = $r->routes[$uri];
            }
        }
        else
        {
            $this->router->routes = [...$this->router->routes, ...$r->routes];
        }
    }

    function handle_request(): void
    {
        // TODO(art): should these be passed as arguments?
        $uri = parse_url($_SERVER["REQUEST_URI"])["path"];
        $method = $_SERVER["REQUEST_METHOD"];

        if ($method === "POST" && isset($_POST["_method"]))
        {
            // TODO(art): validate method name?
            $method = strtoupper($_POST["_method"]);
        }

        $route = $this->router->routes[$uri][$method] ?? null;

        if (!$route)
        {
            throw new http\Not_Found("route '$method $uri' does not exist");
        }

        foreach ($this->middleware as $fn) $fn($this->ctx);
        foreach ($route["middleware"] as $fn) $fn($this->ctx);

        $route["handler"]($this->ctx);
    }
}

class Router
{
    public $namespace = "";
    public $routes = [];

    function __construct(string $ns = "")
    {
        $this->namespace = $ns;
    }

    function add(string $method, string $uri, callable $handler,
                 array $middleware = []): void
    {
        $ns_uri = $uri;

        if ($this->namespace)
        {
            $ns_uri = prepend_namespace($this->namespace, $uri);
        }

        $this->routes[$ns_uri][$method] = [
            "handler" => $handler,
            "middleware" => $middleware
        ];
    }

    function get(string $uri, callable $handler, array $middleware = []): void
    {
        $this->add("GET", $uri, $handler, $middleware);
    }

    function post(string $uri, callable $handler, array $middleware = []): void
    {
        $this->add("POST", $uri, $handler, $middleware);
    }

    function put(string $uri, callable $handler, array $middleware = []): void
    {
        $this->add("PUT", $uri, $handler, $middleware);
    }

    function patch(string $uri, callable $handler,
                   array $middleware = []): void
    {
        $this->add("PATCH", $uri, $handler, $middleware);
    }

    function delete(string $uri, callable $handler,
                    array $middleware = []): void
    {
        $this->add("DELETE", $uri, $handler, $middleware);
    }
}

function prepend_namespace(string $ns, string $uri): string
{
    if ($uri === "/") return $ns;
    return $ns . $uri;
}
