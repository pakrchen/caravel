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
        $help  = $this->yellow("Usage:") . "\n";
        $help .= "  --command=\"CommandName\" [other options]\n";
        $help .= "\n";
        $help .= "    " . $this->green("php run --command=\"Demo\" [anything else]") . "\n";
        $help .= "  \n";
        $help .= "  This command will execute DemoCommand::run(). Remember --command is required.\n";
        $help .= "\n";

        return $help;
    }

    public function yellow($string)
    {
        return "\033[33m{$string}\033[0m";
    }

    public function green($string)
    {
        return "\033[32m{$string}\033[0m";
    }

    public function red($string)
    {
        return "\033[31m{$string}\033[0m";
    }
}