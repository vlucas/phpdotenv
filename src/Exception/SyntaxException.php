<?php

namespace Dotenv\Exception;

use InvalidArgumentException;

class SyntaxException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Dotenv values containing spaces must be surrounded by quotes.');
    }
}
