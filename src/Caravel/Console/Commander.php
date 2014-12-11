<?php

namespace Caravel\Console;

use Symfony\Component\Console\Application;

class Commander extends App
{
    public function __construct()
    {
        parent::__construct();

        // In this situation, we need to let Caravel know how to find commands.
        $this->autoload(array(
            self::getAppRoot() . "/commands",
        ));
    }

    public function execute(array $config)
    {
        $application = new Application();

        foreach ($config as $command) {
            if (!class_exists($command)) {
                print_r(Command::red("Command Not Found: [{$command}]\n"));
            } else {
                $application->add(new $command);
            }
        }

        $application->run();
    }
}
