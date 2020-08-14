<?php
/**
 * Route Handlers class
 *
 * PHP Version 7
 *
 * @category  Handlers
 * @package   MicroFrame\Handlers
 * @author    Godwin peter .O <me@godwin.dev>
 * @author    Tolaram Group Nigeria <teamerp@tolaram.com>
 * @copyright 2020 Tolaram Group Nigeria
 * @license   MIT License
 * @link      https://github.com/gpproton/microframe
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so
 */

namespace MicroFrame\Handlers;

defined('BASE_PATH') or exit('No direct script access allowed');

/**
 * Default resource references
 */
use MicroFrame\Core\Request;
use MicroFrame\Core\Response;
use MicroFrame\Library\Reflect;
use MicroFrame\Defaults\Middleware\DefaultMiddleware;
use MicroFrame\Library\Strings;
use MicroFrame\Library\Value;

/**
 * Class Route
 * @package MicroFrame\Handlers
 */
class Route
{
    private $request;
    private $response;
    private $proceed;
    const appPath = "app.Controller.";
    const sysPath = "sys.Controller.";

    /**
     * Route constructor.
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->proceed = false;
    }

    /**
     * @return Route
     */
    public static function set()
    {
        return new self();
    }

    /**
     * @param $path
     * @param bool $check
     * @param null $response
     * @param null $request
     * @param bool $auto
     * @return mixed
     */
    private function initialize($path, $check = true, $response = null, $request = null, $auto = true)
    {
        if ($check) {
            return Reflect::check()->stateLoader($path, $check);
        }
        $auto = !$auto ? "static" : $auto;
        Reflect::check()->stateLoader($path, array($response, $request, "", $auto))
            /**
             * Default middleware left here just for extra capability.
             */
            ->middleware(new DefaultMiddleware)
            ->start();
    }

    /**
     * Custom route mapping method for assigning to controller, closure, string & paths
     *
     * @param string $path
     * @param array $methods
     * @param string $functions
     * @param array $middleware
     * @param int $status
     * @return void
     */
    public static function map($path = "index", $methods = array('get'), $functions = "index", $middleware = array(), $status = 200)
    {
        /**
         * Filter out unintended string output
         */
        ob_clean();

        $clazz = new self();
        $wildCard = Strings::filter($path)->contains("*");
        /**
         * Path filtering for illegal chars.
         */
        $path = Strings::filter($path)
            ->replace(["/", "\\", "-", "_", " "], [".", ".", ".", ".", ""])
            ->range("*", false, true)
            ->trim([" ", "."])
            ->value();
        $customScriptsPath = "./../App/Custom/";

        /**
         * Path validation logic
         */
        if ($wildCard && Strings::filter($clazz->request->path())->contains($path)) {
            $clazz->proceed = true;
        } elseif ($path === $clazz->request->path()) {
            $clazz->proceed = true;
        } elseif (empty($path) && empty($clazz->request->path())) {
            /**
             * Extra index check, may be redundant but for assurance.
             */
            $clazz->proceed = true;
        }

        if ($clazz->proceed) {
            /**
             * handle request method mismatch
             */
            $clazz->response->methods($methods, false, true);

            /**
             * Directory and script mapping
             */
            if (Strings::filter($functions)->contains("./")
                || is_file($functions)) {
                $reqPath = $customScriptsPath . Strings::filter($functions)->replace("./")->value();
                /**
                 * Restore cleaned globals
                 */
                Request::overrideGlobals(false);

                if (is_file($functions) && Strings::filter($functions)->contains(".php")) {
                    include_once($functions);
                    die();
                } elseif (is_dir($reqPath)) {
                    chdir($reqPath);
                    $dirContents = scandir("./");
                    if (in_array("index.html", $dirContents)) {
                        /**
                         * HTML index item inclusion.
                         */
                        echo file_get_contents("./index.html");
                    } elseif (in_array("index.php", $dirContents)) {
                        /**
                         * PHP index script inclusion.
                         */
                        include_once("index.php");
                    } else {
                        $clazz->response->notFound();
                    }
                } else {
                    $clazz->response->notFound();
                }
                die();
            }

            /**
             * Firstly check closure and then execute with return.
             */
            if (gettype($functions) === 'object') {
                $clazz->response->data($functions());
            }
            /**
             * Handle System Controller mapping.
             */
            elseif (Strings::filter($functions)->contains(self::sysPath) && $clazz->initialize($functions)) {
                $clazz->initialize($functions, false, $clazz->response, $clazz->request, false);
            }
            /**
             * Handle App Controller mapping.
             */
            elseif ($clazz->initialize(self::appPath . $functions)) {
                $clazz->initialize(self::appPath . $functions, false, $clazz->response, $clazz->request, false);
            } else {
                $clazz->response->data($functions);
            }

            /**
             * TODO: Switch to a dot base middleware call.
             */
            foreach ($middleware as $middleKey) {
                $clazz->response->middleware($middleKey);
            }

            $clazz->response->status($status);
            /**
             * Send structured output.
             */
            $clazz->response->send();
        }
    }

    /**
     *
     * @param string $path
     */
    public function boot($path = null)
    {
        /**
         * Define custom system routes here with $this->map() method
         * E.g General Swagger | Docs | Wiki
         *
         * Find option for sys.Controller
         *
         * NOTE: Do not modify except you know what you're doing!!!!
         *
         */

        /**
         * Swagger 3.0 Doc API for requested path
         */
        self::map("/api/swagger*", ['get', 'post'], self::sysPath . "Swagger", []);

        /**
         * Assist page config value.
         */

        $assistRoot = Value::init()->assistPath();

        /**
         * Swagger frontend for corresponding API Doc
         */
        self::map("/{$assistRoot}/swagger*", ['get'], self::sysPath . "SwaggerUI", []);

        /**
         * MarkDown based help documentation web view.
         */
        self::map("/{$assistRoot}/*", ['get'], self::sysPath . "Help", []);

        /**
         * Resource router for requested resource files.
         */
        self::map("/resources/*", ['get'], self::sysPath . "Resources", []);

        /**
         **** System route path end. ****
         */

        if (is_null($path)) {
            $path = $this->request->path();
        }
        /**
         * Call Index if route is not set.
         */
        if (empty($path) && $this->initialize(self::appPath . "index")) {
            $this->initialize(self::appPath . "index", false, $this->response, $this->request);
        }
        /**
         * Try calling specified route if the controller exist.
         */
        elseif ($this->initialize(self::appPath .  $path)) {
            $this->initialize(self::appPath . $path, false, $this->response, $this->request);
        }
        /**
         * Call default controller if all fail.
         */
        else {
            $this->initialize(self::sysPath . "default", false, $this->response, $this->request);
        }
    }
}
