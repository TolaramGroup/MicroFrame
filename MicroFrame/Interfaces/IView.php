<?php
/**
 * App view interface
 *
 * PHP Version 7
 *
 * @category  Interfaces
 * @package   MicroFrame\Interfaces
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

namespace MicroFrame\Interfaces;

defined('BASE_PATH') or exit('No direct script access allowed');

/**
 * Interface IView
 * @package MicroFrame\Interfaces
 */
interface IView
{

    /**
     * @param $name
     * @return mixed
     */
    public function config($name);

    /**
     * @param null $source
     * @return IModel
     */
    public function model($source = null);

    /**
     * @param null $source
     * @return mixed
     */
    public function loader($source = null);
}
