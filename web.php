<?php

namespace web;

require_once __DIR__ . "/path.php";
require_once __DIR__ . "/http.php";

use path, http;

// TODO(art): something for REST api?

// TODO(art): add app middleware?
// TODO(art): App should have router insted of routes

class App
{
    public $routes = [];
    public $middleware = [];
    public $ctx = [];

    function add_router(Router $r, string $namespace = ""): void
    {
        if ($namespace)
        {
            foreach (array_keys($r->routes) as $uri)
            {
                $ns_uri = prepend_namespace($namespace, $uri);
                $this->routes[$ns_uri] = $r->routes[$uri];
            }
        }
        else
        {
            $this->routes = [...$this->routes, ...$r->routes];
        }
    }

    function add_route(string $method, string $uri, callable $handler,
                       array $middleware = []): void
    {
        $this->routes[$uri][$method] = [
            "handler" => $handler,
            "middleware" => $middleware
        ];
    }

    function add_middleware(array $ms): void
    {
        foreach ($ms as $m)
        {
            $this->middleware[] = $m;
        }
    }

    function handle_request(): void
    {
        $uri = parse_url($_SERVER["REQUEST_URI"])["path"];
        $method = $_SERVER["REQUEST_METHOD"];

        if ($method === "POST" && isset($_POST["_method"]))
        {
            // TODO(art): validate method name?
            $method = strtoupper($_POST["_method"]);
        }

        $route = $this->routes[$uri][$method] ?? null;

        if (!$route)
        {
            throw new http\Not_Found([
                "message" => "route '$method $uri' does not exist"
            ]);
        }

        foreach ($this->middleware as $m)
        {
            $m($this->ctx);
        }

        foreach ($route["middleware"] as $m)
        {
            $m($this->ctx);
        }

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
        if ($this->namespace) $uri = prepend_namespace($this->namespace, $uri);

        $this->routes[$uri][$method] = [
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

// TODO(art): think about view\render()
function render_view(string $path, array $vars = []): void
{
    extract($vars);
    require_once path\from_base("views", "$path.php");
}

// TODO(art): think about view\partial()
function partial_view(string $path): string
{
    return path\from_base("views", "$path.php");
}

// TODO(art): http\redirect()
function redirect(string $url): void
{
    header("Location: $url");
}

function prepend_namespace(string $ns, string $uri): string
{
    if ($uri === "/") return $ns;
    return $ns . $uri;
}
