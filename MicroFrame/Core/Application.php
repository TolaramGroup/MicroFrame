<?php

/**
 * Core Application class
 *
 * PHP Version 7
 *
 * @category  Core
 * @package   MicroFrame\Core
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

namespace MicroFrame\Core;

defined('BASE_PATH') or exit('No direct script access allowed');

use MicroFrame\Handlers\Route;
use MicroFrame\Library\Config;
use MicroFrame\Library\Utils;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as whoopsRun;

/**
 * Application class
 *
 * @category Core
 * @package  MicroFrame\Core
 * @author   Godwin peter .O <me@godwin.dev>
 * @license  MIT License
 * @link     https://godwin.dev
 */
class Application extends Core
{
    /**
     * Contains retrieved config for the app bootstrap.
     *
     * @var array|mixed|null
     */
    private $_config;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->_config = Config::fetch();
    }


    /**
     * Check debug settings and set error checking.
     *
     * @return bool
     */
    public function environment()
    {
        return $this->_config['system']['debug'];
    }

    /**
     * Boot up application
     *
     * @return void
     */
    public function start()
    {

        /**
         * Implement pretty error display.
         */
        if ($this->_config['system']['debug']) {
            $whoops = new whoopsRun;
            $page = new PrettyPageHandler();
            $protectArray = array('_ENV', '_SERVER');

            foreach ($protectArray as $pat) {
                foreach ($GLOBALS[$pat] as $offKey => $offValue) {
                    $page->blacklist($pat, $offKey);
                }
            }
            $whoops->pushHandler($page)->register();
        }

        if ($this->_config['console']) {
            Console::init()->execute();
        } else {
            /**
             * Load defined routes to request
             */
            Utils::get()->injectRoutes();

            /**
             * Load only request route
             */
            Route::set()->boot();
        }
    }
}