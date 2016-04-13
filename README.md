caravel
=======

A plain PHP framework. (to quickly build a web app, see <https://github.com/pakrchen/caravel-mvc>)

## How to use Caravel framework

1.  You need to create a file named "composer.json" in your project's root directory. And the content in it is like below.

    <pre>
    {
      "require": {
        "pakrchen/caravel": "dev-master"
      }
    }
    </pre>

2.  Then run "composer install" in the root directory, where Composer will fetch the framework as a package automatically.

    If you have not installed Composer in your server, install it. Composer makes you powerful and you will surely benefit from various other excellent packages.

    Here is an instruction of the installation of Composer: <https://getcomposer.org/doc/00-intro.md#installation-nix>.

3.  You have gotten the core part of the framework. Good job! Now create two folders named "app" and "public" respectively in the root directory.

    In "public" folder, create a file named "index.php", which is the only entrance of your web application. And the content in it is like below.

    <pre>
    &lt;?php

    require_once dirname(__DIR__) . "/vendor/autoload.php";

    $app = new Caravel\App();

    $app->run();
    </pre>

    In "app" folder, create a folder named "controllers" to store your controllers, a folder named "models" to store your models, and a folder named "views" to store your views.

    That's right! This is what we call the "MVC" structure. (see [caravel-mvc](https://github.com/pakrchen/caravel-mvc))

4.  In Caravel framework, we require a "custom.php" file in the app directory to accept your custom configuration.

    For example, the configuration below tells the framework where you store your controllers and models. **This is required**.

    <pre>
    &lt;?php

    use Caravel\App;
    use Caravel\Core\ClassLoader;
    use Caravel\Core\Log;

    /*
    |--------------------------------------------------------------------------
    | Register The Class Loader
    |--------------------------------------------------------------------------
    |
    | You may use the class loader to load your controllers and models.
    | This is useful for keeping all of your classes in the "global" namespace.
    |
    */

    ClassLoader::addPaths(array(

        APP::getAppRoot() . "/controllers",
        APP::getAppRoot() . "/models",

    ))->register();
    </pre>

    This means that you can change the name of the folder where you store your controllers as long as your define it in "custom.php".

    Obviously, you can also add additional folders such as "libraries" and let Caravel know how to find it.

    Furthermore, You can also define where to store application's logs and how to handle an exception which is not caught by appending these codes to "custom.php". However, **this is optional**.

    <pre>
    /*
    |--------------------------------------------------------------------------
    | Application Error Logger
    |--------------------------------------------------------------------------
    |
    | Here we will configure the error logger setup for the application.
    | By default we will build a basic log file setup which creates a single
    | file for logs.
    |
    */

    Log::useFile("/tmp/caravel.log");

    /*
    |--------------------------------------------------------------------------
    | Application Error Handler
    |--------------------------------------------------------------------------
    |
    | Here you may handle any errors that occur in your application, including
    | logging them or displaying custom views for specific errors.
    |
    */

    App::error(function(Exception $e) {

        Log::exception($e);

    });
    </pre>

## An example for you

I believe that a pratical example is better than any word.

A simple demo is offered in [caravel-mvc](https://github.com/pakrchen/caravel-mvc). You can find it in <https://github.com/pakrchen/caravel-mvc> to preview what your project would look like. **It is strongly recommeded**.

## An nginx configuration for you

I hope it helps. But you might have to adjust it for your environment.

<pre>
server {
  listen 80;
  server_name your-domain; # eg. example.com
  root your-project-root-directory/public; # eg. /data/www/example/public
  index index.php;

  access_log where-you-store-access-log; # eg. /data/logs/example_access.log;
  error_log where-you-store-error-log; # eg. /data/logs/example_error.log;

  # Check if file exists
  if (!-e $request_filename)
  {
    rewrite ^/(.*)$ /index.php last;
    break;
  }

  location ~ \..*/.*\.php$
  {
    # I'm pretty sure this stops people trying to traverse your site to get to other PHP files
    return 403;
  }

  location ~* \.php {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_script_name;
    include fastcgi_params;
  }
}
</pre>
