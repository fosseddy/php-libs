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

function redirect(string $url): void
{
    header("Location: $url");
}
