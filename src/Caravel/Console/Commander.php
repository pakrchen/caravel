<?php

namespace Caravel\Console;

class Commander extends App
{
    public function __construct()
    {
        parent::__construct();

        // In this situation, we need to let Caravel know how to find commands.
        $this->autoload(array(
            self::getAppRoot() . "/commands",
        ));

        // import what you've defined
        require_once self::getAppRoot() . "/custom.php";
    }
}
