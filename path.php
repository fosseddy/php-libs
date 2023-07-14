<?php

namespace path;

// art: BASE_DIR must be defined by user
function from_base(string ...$path): string
{
    return join(DIRECTORY_SEPARATOR, [BASE_DIR, ...$path]);
}
