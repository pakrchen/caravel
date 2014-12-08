<?php

namespace Caravel\Console;

class Command extends App
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

    public function help()
    {
        // \033[33m \033[0m  yellow
        // \033[32m \033[0m  green
        // \033[31m \033[0m  red
        $help .= $this->yellow("Usage:") . "\n";
        $help .= "  --command=\"CommandName\" [other options]\n";
        $help .= "\n";
        $help .= "    " . $this->green("php run --command=\"CommandName\" --argument=\"ArgumentValue\"") . "\n";
        $help .= "  \n";
        $help .= "  This example will run DemoCommand and all options can be received by DemoCommand\n";
        $help .= "\n";

        return $help;
    }

    protected function yellow($string)
    {
        return "\033[33m{$string}\033[0m";
    }

    protected function green($string)
    {
        return "\033[32m{$string}\033[0m";
    }

    protected function red($string)
    {
        return "\033[31m{$string}\033[0m";
    }
}
