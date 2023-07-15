<?php

namespace http;

class Error extends \Exception
{
    public $status_code;
    public $data;

    function __construct(int $status_code, string $message = "",
                         array $data = [])
    {
        parent::__construct($message);

        $this->status_code = $status_code;
        $this->data = $data;
    }

    function getData(): array
    {
        return ["message" => $this->message, ...$this->data];
    }
}

class Bad_Request extends Error
{
    public $status_code = 400;

    function __construct(string $message = "", array $data = [])
    {
        parent::__construct($this->status_code, $message, $data);
    }
}

class Not_Found extends Error
{
    public $status_code = 404;

    function __construct(string $message = "", array $data = [])
    {
        parent::__construct($this->status_code, $message, $data);
    }
}

function redirect(string $url): void
{
    header("Location: $url");
}
