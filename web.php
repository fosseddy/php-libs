<?php

namespace web;

use http;

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

            foreach (array_keys($r->dynamic_routes) as $pattern)
            {
                $ns_pattern = "/^" . join("\/", explode("/", $namespace)) . substr($pattern, 2);
                $this->router->dynamic_routes[$ns_pattern] = $r->dynamic_routes[$pattern];
            }
        }
        else
        {
            $this->router->routes = [...$this->router->routes, ...$r->routes];
            $this->router->dynamic_routes = [
                ...$this->router->dynamic_routes,
                ...$r->dynamic_routes
            ];
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

        $route = $this->router->routes[$uri] ?? null;

        if (!$route)
        {
            foreach (array_keys($this->router->dynamic_routes) as $pattern)
            {
                $m = [];
                $match = preg_match($pattern, $uri, $m);

                if ($match)
                {
                    $route = $this->router->dynamic_routes[$pattern];

                    foreach ($m as $k => $v)
                    {
                        if (gettype($k) === "string")
                        {
                            $this->ctx["params"][$k] = $v;
                        }
                    }
                    break;
                }
            }

            if (!$route) throw new http\Error(404, "route does not exist");
        }

        $route = $route[$method] ?? null;
        if (!$route) throw new http\Error(405, "method is not allowed");

        foreach ($this->middleware as $fn) $fn($this->ctx);
        foreach ($route["middleware"] as $fn) $fn($this->ctx);

        $route["handler"]($this->ctx);
    }
}

class Router
{
    public $namespace = "";
    public $routes = [];
    public $dynamic_routes = [];

    function __construct(string $ns = "")
    {
        $this->namespace = $ns;
    }

    function add(string $method, string $uri, callable $handler,
                 array $middleware = []): void
    {
        $is_dynamic = str_contains($uri, "/:");

        if ($this->namespace)
        {
            $uri = prepend_namespace($this->namespace, $uri);
        }

        if ($is_dynamic)
        {
            $parts = explode("/", $uri);

            foreach ($parts as $i => $it)
            {
                if (strlen($it) > 0 && $it[0] === ":")
                {
                    $parts[$i] = "(?P<" . substr($it, 1) . ">[^\/]+)";
                }
            }

            $uri = "/^" . join("\/", $parts) . "$/";

            $this->dynamic_routes[$uri][$method] = [
                "handler" => $handler,
                "middleware" => $middleware
            ];
        }
        else
        {
            $this->routes[$uri][$method] = [
                "handler" => $handler,
                "middleware" => $middleware
            ];
        }
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
