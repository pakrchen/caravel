<?php

namespace Caravel\Http;

class Input extends Request
{
    public function get($key)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }
}
