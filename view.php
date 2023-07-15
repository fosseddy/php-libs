<?php

namespace view;

use path;

function render(string $path, array $vars = []): void
{
    extract($vars);
    require_once path\from_base("views", "$path.php");
}

function partial(string $path): string
{
    return path\from_base("views", "$path.php");
}
