<?php

namespace Caravel\Routing;

class ClassLoader
{
    protected $paths;

    const FILE_EXTENSION = ".php";
    const NAMESPACE_SEPARATOR = "\\";

    public function __construct($paths)
    {
        if (is_array($paths)) {
            $this->paths = $paths;
        } else {
            $this->paths = array($paths);
        }
    }

    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $className The name of the class to load.
     * @return void
     */
    public function loadClass($className)
    {
        $fileName = "";
        if (false !== ($lastNsPos = strripos($className, self::NAMESPACE_SEPARATOR))) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace(self::NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace("_", DIRECTORY_SEPARATOR, $className) . self::FILE_EXTENSION;

        foreach ($this->paths as $path) {
            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($filePath)) {
                require_once $filePath;
            }
        }
    }

    public static function addPaths(array $paths)
    {
        return new ClassLoader($paths);
    }
}
