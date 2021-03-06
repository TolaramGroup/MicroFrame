<?php

/**
 * Reflect Library class
 *
 * PHP Version 7
 *
 * @category  Library
 * @package   MicroFrame\Library
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

namespace MicroFrame\Library;

defined('BASE_PATH') or exit('No direct script access allowed');

use MicroFrame\Core\Core;
use MicroFrame\Handlers\Logger;
use ReflectionClass;

/**
 * Reflect Class
 *
 * @category Library
 * @package  MicroFrame\Library
 * @author   Godwin peter .O <me@godwin.dev>
 * @license  MIT License
 * @link     https://godwin.dev
 */
class Reflect extends Core
{
    /**
     * System default content path.
     *
     * @var string
     */
    private static $_sysPath = "MicroFrame\Defaults\\";


    /**
     * Application content path.
     *
     * @var string
     */
    private static $_appPath = "App\\";

    /**
     * Reflect constructor.
     */
    public function __construct()
    {
    }

    /**
     * Statically initialize class object
     *
     * @return self
     */
    public static function check()
    {
        return new self();
    }

    /**
     * Dynamically get classes and method based on filtered strings
     *
     * @param string $path        here
     * @param array  $args        here
     * @param bool   $checkMethod A condition to see if
     *                            there's a matching class index or a class method
     *
     * @return mixed
     */
    public function stateLoader($path, $args = array(), $checkMethod = false)
    {

        /**
         * Get base namespace
         */
        $type = Strings::filter($path)
            ->range(".", 1, true)->value();

        /**
         * Get core class
         */
        $core = Strings::filter($path)
            ->range(".", 2, true)
            ->range(".", 1)
            ->value();

        /**
         * Get requested resource path
         */
        $path = Strings::filter($path)
            ->range(".", 2, false)
            ->replace(".", "\\")
            ->value();

        $pathHandler = function ($base) use ($core, $path) {
            return "$base{$core}\\{$path}";
        };

        if (Strings::filter($type)->contains("sys")) {
            $path = $pathHandler(self::$_sysPath);
        } elseif ((Strings::filter($type)->contains("app"))) {
            $path = $pathHandler(self::$_appPath);
        }

        $classDirect = Strings::filter($path)
            ->append($core)
            ->upperCaseWords()
            ->value();

        $classIndex = Strings::filter($classDirect . "\\\\")
            ->replace($core . "\\\\", "\Index{$core}")
            ->value();

        $classUpper = Strings::filter($path)
            ->range("\\", true, true)
            ->append($core)
            ->upperCaseWords()
            ->value();

        $classMethod = Strings::filter($path)
            ->range("\\", true, false)
            ->upperCaseWords()
            ->value();

        $classIndexMethod = Strings::filter($path)
            ->range('\\', true, true)
            ->append('\\' . 'Index' . $core)
            ->upperCaseWords()
            ->value();

        $classIndexMethodValue = Strings::filter($path)
            ->range("\\", true, false)
            ->upperCaseWords()
            ->value();

        if (class_exists($classDirect)) {
            $path = $classDirect;
        } elseif (class_exists($classIndex)) {
            $path = $classIndex;
        } elseif (class_exists($classUpper)) {
            $path = $classUpper;
        }
        /**
         * Review closely for any issues
         */
        elseif (class_exists($classIndexMethod)) {
            $path = $classIndexMethod;
        }

        switch ($core) {
            case 'Controller':
                /**
                 * $args[2] hold the name method to be called.
                 */
                if (class_exists($classIndexMethod) && gettype($args) === 'array') {
                    $args[2] = $classIndexMethodValue;
                } elseif (class_exists($classUpper) && gettype($args) === 'array') {
                    $args[2] = $classMethod;
                }
                break;
            case 'Model':
                /**
                 * $args[0] hold the name method to be called.
                 */
                if (class_exists($classUpper) && gettype($args) === 'array') {
                    $args[0] = $classMethod;
                }
                break;
            default:
                break;
        }

        if (gettype($args) !== 'array' && class_exists($path)) {
            return true;
        }
        if ($checkMethod && class_exists($path)) {
            return $classMethod;
        }
        if (class_exists($path)) {
            try {
                $classBuilder = new ReflectionClass($path);
            } catch (\ReflectionException $e) {
                Logger::set($e->getMessage())->error();
            }
            /**
             * Reflection instance.
             *
             * @var ReflectionClass $classBuilder
             */
            return $classBuilder->newInstanceArgs($args);
        } else {
            return false;
        }
    }

    /**
     * Method instance loader.
     *
     * @param string $classInstance here
     * @param string $methodName    here
     * @param array  $paramArrays   here
     *
     * @return mixed
     */
    public function methodLoader($classInstance, $methodName, $paramArrays = array())
    {
        return call_user_func_array(
            array($classInstance, $methodName),
            $paramArrays
        );
    }


    /**
     * Get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     *
     * @param string $filePathName here
     *
     * @return string
     */
    public function getClassFullNameFromFile($filePathName)
    {
        return $this->getClassNamespaceFromFile($filePathName) . '\\' . $this->getClassNameFromFile($filePathName);
    }


    /**
     * Build and return an object of a class from its file path
     *
     * @param string $filePathName here
     *
     * @return mixed
     */
    public function getClassObjectFromFile($filePathName)
    {
        $classString = $this->getClassFullNameFromFile($filePathName);
        return new $classString();
    }

    /**
     * Get the class namespace form file path using token
     *
     * @param string $filePathName here
     *
     * @return null|string
     */
    public function getClassNamespaceFromFile($filePathName)
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i])
                        ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (!$namespace_ok) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * Get the class name form file path using token
     *
     * @param string $filePathName here
     *
     * @return mixed
     */
    protected function getClassNameFromFile($filePathName)
    {
        $php_code = file_get_contents($filePathName);

        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if (
                $tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes[0];
    }
}
