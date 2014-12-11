<?php

namespace Caravel\Console;

use Symfony\Component\Console\Command\Command as SCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SCommand
{
    protected $input;  // InputInterface
    protected $output; // OutputInterface

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName($this->name);
        $this->setDescription($this->description);
        $this->caddArguments();
        $this->caddOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->fire();
    }

    abstract public function fire();

    abstract protected function getArguments();

    abstract protected function getOptions();

    protected function argument()
    {
        return $this->input->getArguments();
    }

    protected function option($name)
    {
        return $this->input->getOption($name);
    }

    protected function caddArguments()
    {
        $arguments = $this->getArguments();
        if (!is_array($arguments)) {
            return false;
        }

        foreach ($arguments as $v) {
            $this->addArgument(
                isset($v[0]) ? $v[0] : null, // name
                isset($v[1]) ? $v[1] : null, // mode
                isset($v[2]) ? $v[2] : null, // description
                isset($v[3]) ? $v[3] : null  // defaultValue
            );
        }
    }

    protected function caddOptions()
    {
        $options = $this->getOptions();
        if (!is_array($options)) {
            return false;
        }

        foreach ($options as $v) {
            $this->addOption(
                isset($v[0]) ? $v[0] : null, // name
                isset($v[1]) ? $v[1] : null, // shortcut
                isset($v[2]) ? $v[2] : null, // mode
                isset($v[3]) ? $v[3] : null, // description
                isset($v[4]) ? $v[4] : null  // defaultValue
            );
        }
    }

    public static function yellow($string)
    {
        return "\033[33m{$string}\033[0m";
    }

    public static function green($string)
    {
        return "\033[32m{$string}\033[0m";
    }

    public static function red($string)
    {
        return "\033[31m{$string}\033[0m";
    }
}
