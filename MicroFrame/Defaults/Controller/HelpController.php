<?php

/**
 * Default controller class
 *
 * PHP Version 7
 *
 * @category  HelpController
 * @package   MicroFrame\Defaults\Controller
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

namespace MicroFrame\Defaults\Controller;

defined('BASE_PATH') or exit('No direct script access allowed');

use MicroFrame\Core\Controller as Core;
use MicroFrame\Handlers\Markdown;
use MicroFrame\Library\File;
use MicroFrame\Library\Strings;
use MicroFrame\Library\Value;
use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

/**
 * HelpController Class
 *
 * @category Controller
 * @package  MicroFrame\Defaults\Controller
 * @author   Godwin peter .O <me@godwin.dev>
 * @license  MIT License
 * @link     https://godwin.dev
 */
class HelpController extends Core
{
    /**
     * Help controller index method
     *
     * @return void
     */
    public function index()
    {
        $assistRoot = Value::init()->assistPath();
        $currentPath = $this->request->path(false);
        $basePath = Strings::filter($this->request->url())
            ->replace([$currentPath, '//', ':/'], ['', '/', '://'])->value();

        $rootPath = APP_PATH . '/Docs/';
        $reveal = '';
        $markdownString = '';
        $rootUrl = Strings::filter($basePath)
            ->append($assistRoot)
            ->value();
        $rootIterateCheck = false;

        $requestedPath = Strings::filter($this->request->url())
            ->range($assistRoot . "/")
            ->value();

        if (
            empty($requestedPath)
            || Strings::filter($requestedPath)->contains('http')
        ) {
            $requestedFile = CORE_PATH . '/Defaults/Markdown/Default.md';
        } else {
            $requestedFile = Strings::filter($rootPath . $requestedPath . '.md')
                ->replace('-', ' ')->value();
        }

        $mataFile = Strings::filter($requestedFile)
            ->range('/', true)
            ->replace(['.md', '-', '_'], ['', ' ', ' '])
            ->upperCaseWords()
            ->value();

        if (file_exists($requestedFile)) {

            /**
             * Start HTML unordered parsing.
             */

            $folderStructure = '';

            /**
             * Create a structured HTML unordered list.
             *
             * @param $array
             * @param $rootPath
             */
            $getConstr = function (
                $array,
                $rootPath
            ) use (
                &$reveal,
                &$getConstr,
                &$folderStructure,
                $rootUrl,
                $rootIterateCheck
            ) {

                /**
                 * Loop through documentation directory contents.
                 */
                foreach ($array as $key => $value) {

                    /**
                     * Add a list item.
                     */
                    if (
                        gettype($key) === 'integer'
                        && Strings::filter($value)->contains('.md')
                    ) {
                        $currentFile = Strings::filter($value)
                            ->replace(['.md', ' '], ['', '-'])
                            ->upperCaseWords()
                            ->value();

                        /**
                         * Get absolute file path for confirming existence.
                         */
                        if (empty($folderStructure)) {
                            $folderStructure = '/';
                        }
                        $fileInst = Strings::filter(
                            $rootPath . $folderStructure .
                            $value
                        )->replace(['//', '///'], ['/', '/'])->value();

                        /**
                         * Get relative path for url
                         */
                        $fileInstance = '';
                        if (file_exists($fileInst)) {
                            $fileInstance = Strings::filter(
                                $folderStructure .
                                $currentFile
                            )->replace(['//', '///'], ['/', '/'])->value();
                        }
                        /**
                         * A workaround to return to parent folder from
                         * a deep nesting.
                         */
                        else {
                            while (!file_exists($fileInst)) {
                                $subdir = File::init()->relativePath(
                                    $rootPath,
                                    dirname(dirname($fileInst))
                                );
                                $subdir = Strings::filter($subdir)
                                    ->replace('./', '/')->value();

                                /**
                                 * Get absolute file path for confirming existence.
                                 */
                                $fileInst = Strings::filter(
                                    $rootPath . $subdir .
                                    $value
                                )->replace(['//', '///'], ['/', '/'])->value();
                                if (file_exists($fileInst)) {
                                    $folderStructure = $subdir;
                                    $fileInstance = Strings::filter(
                                        $folderStructure
                                        . $currentFile
                                    )->replace(['//', '///'], ['/', '/'])->value();
                                }
                            }
                        }

                        /**
                         * Final relative file path.
                         */
                        if (strpos($fileInstance, '/') === false) {
                            $fileInstance = '/' . $fileInstance;
                        }

                        /**
                         * Add list item to string.
                         */
                        $currentFile = Strings::filter($currentFile)
                            ->replace('-', ' ')->value();

                        if ($rootPath === CORE_PATH . '/Defaults/Markdown') {
                            $rootUrl = '#';
                        } elseif ($rootPath !== APP_PATH . '/Docs/') {
                            /**
                             * Change root path
                             */

                            $loc = Strings::filter($rootPath)
                                ->replace(APP_PATH . '/Docs')->value();
                            $requestLoc = (strpos($rootPath, '/') !== 0)
                                ? '/' . $loc : $loc;
                            if (
                                !Strings::filter($rootUrl)->contains($requestLoc)
                                && !$rootIterateCheck
                            ) {
                                $rootIterateCheck = true;
                                $rootUrl .= $requestLoc;
                            }
                        }

                        $reveal .= <<<HTML
                    <li><div class="divider divider-restyle"></div></li>
                    <li class="list-restyle"><a class="link-restyle" href="{$rootUrl}{$fileInstance}">{$currentFile}</a></li>
HTML;
                    }

                    /**
                     * Add an unordered list item and it's children items.
                     */
                    if (
                        gettype($value) === 'array'
                        && gettype($key) !== 'integer'
                    ) {
                        arsort($value);

                        /**
                         * New folder path.
                         */
                        if (
                            is_dir(
                                $rootPath .
                                $folderStructure . '/' . $key . '/'
                            )
                        ) {
                            $folderStructure = $folderStructure . '/' . $key . '/';
                        } else {
                            $folderStructure = '/' . $key . '/';
                        }
                        $folderStructure = Strings::filter($folderStructure)
                            ->replace(['//', '///'], ['/', '/'])->value();

                        /**
                         * Temp value for injecting items.
                         */
                        $tempKey = $key . rand(0, 100);

                        /**
                         * Start of wrapper.
                         */
                        $reveal .= <<<HTML
        <li><div class="divider divider-restyle"></div></li>
         <li class="no-padding">
                <ul class="collapsible collapsible-accordion">
                    <li class=""><a class="collapsible-header link-restyle">{$key} &nbsp;&nbsp;&nbsp;<span style="color: indianred">&nbsp;></span></a>
                        <div class="collapsible-body">
                            <ul>
                                {$tempKey}
HTML;

                        /**
                         * Add all children items.
                         */
                        $reveal = str_replace(
                            $tempKey,
                            $getConstr($value, $rootPath),
                            $reveal
                        );

                        /**
                         * End of wrapper
                         */
                        $reveal .= <<<HTML
                        </ul>
                    </div>
                </li>
            </ul>
        </li>
HTML;
                    }
                }
            };

            $markdownString = file_get_contents($requestedFile);
        }

        if (
            $requestedPath === $assistRoot
            || $requestedPath === ''
            || Strings::filter($requestedPath)->contains('http')
        ) {
            $pathValue = '';
            $markdownString = file_get_contents(
                CORE_PATH . '/Defaults/Markdown/Default.md'
            );
        } else {
            $pathValue = Strings::filter($requestedPath)
                ->replace($assistRoot . '/', '')
                ->value();
        }

        /**
         * Get options start and end substr positions.
         */
        $startPos = Strings::filter($markdownString)
            ->charPosition($markdownString, '---', 1);

        if ($startPos === 0) {
            $endPos = Strings::filter($markdownString)
                ->charPosition($markdownString, '---', 2);


            $options = substr($markdownString, $startPos, $endPos);
            $options = new Config($options, new Yaml(), true);
            $options = $options->all();
        } else {
            $options = [];
            $endPos = 0;
        }

        $rootSetup = '';
        if (isset($options['root'])) {
            $rootSetup = $options['root'];
            if (strpos($rootSetup, '/') === 0 && $rootSetup !== '/') {
                $rootSetup = Strings::filter($rootSetup)->range('/', false)->value();
            }
        }

        /**
         * Define default options for requested markdown if not defined,
         * they'll be assigned below.
         */

        /**
         * Default generation for page header.
         */
        if (!isset($options['title'])) {
            $options['title'] = 'new';
        }
        /**
         * Default for page title.
         */
        if ($options['title'] === 'none' || $options['title'] === '') {
            $options['title'] = ucfirst($mataFile . ' documentation');
            $options['header'] = '';
        } else {
            if ($options['title'] === 'new') {
                $options['title'] = ucfirst($mataFile . ' documentation');
            }
            $options['header'] = "<h2 align='center' style='font-weight: 300 !important; color: #9e9e9e !important;'>{$options['title']}</h2>";
        }

        $checkIcons = Strings::filter($options['title'])
            ->between(':', ':', false, true, false, 30);
        foreach ($checkIcons as $value) {
            $options['title'] = str_replace(
                ':' . $value . ':',
                '',
                $options['title']
            );
        }
        $options['title'] = preg_replace('/\s\s+/', ' ', $options['title']);

        /**
         * Get
         */
        if (!isset($options['author'])) {
            $rand = rand(0, 5);
            $authorDefaults = array(
                'Rick Sanchez',
                'Monkey D. Dragon',
                'Dr. Manhattan',
                'Cinderella Man',
                'Rorschach Kovacs',
                'A. Aokiji'
            );
            $options['author'] = $authorDefaults[$rand];
        }

        /**
         * Get markdown updated date.
         */
        if (!isset($options['date'])) {
            if (file_exists($requestedFile)) {
                $options['date'] = date("d-M-Y", filectime(__FILE__));
            } else {
                $options['date'] = date("d-M-Y");
            }
        }

        /**
         * Add header image to markdown output.
         */
        if (!isset($options['image'])) {
            $options['image'] = '/resources/images/php.jpg';
        }
        if ($options['image'] === 'none' || $options['image'] === '') {
            $options['image'] = '';
        } else {
            $options['image'] = "<div class='center'><img src='{$options['image']}'
                        alt='Default Images' class='image-state'></div>";
        }

        /**
         * Adds default tags
         */
        if (!isset($options['tags'])) {
            $options['tags'] = ['PHP', 'MVC', 'Developers', 'Clean Architecture'];
        }
        $tempTagString = '';
        foreach ($options['tags'] as $tagValue) {
            $tempTagString .= "<span class='center tags-style'>{$tagValue}</span>";
        }
        if (sizeof($options['tags']) >= 1) {
            $options['tags'] = "<div class='center-align' style='margin-bottom: 0.3em; white-space: nowrap; overflow-x: scroll;'>{$tempTagString}</div>";
        } else {
            $options['tags'] = '';
        }

        if (
            empty($options['tags'])
            && empty($options['image']) && empty($options['header'])
        ) {
            $options['date-author'] = '';
        } else {
            $options['date-author'] = <<<HTML
<div class='center icon-items'>
<span><i style='vertical-align: middle;' class='material-icons'>person</i>&nbsp; {$options['author']}</span> &nbsp;&nbsp;&nbsp;
<span><i style='vertical-align: middle;' class='material-icons'>access_time</i>&nbsp; {$options['date']}</span>
</div>
HTML;
        }


        /**
         * Check for config, if not available use requested file path as menu root.
         */

        if ($rootSetup === '/') {
            $rootPath = $rootPath . '';
        } elseif (!empty($rootSetup) && is_dir($rootPath . $rootSetup)) {
            $rootPath = $rootPath . $rootSetup;
        } else {
            $rootPath = dirname($requestedFile);
        }

        /**
         * Initialize menu builder.
         */
        if (is_dir($rootPath)) {
            $files = File::init()->dirStructure($rootPath);
            asort($files);
            /**
             * Call menu builder function.
             */
            if (isset($getConstr)) {
                $getConstr($files, $rootPath);
            }
        }

        /**
         * Remove options only if configuration details are present.
         */
        $markdownString = $startPos !== 0
            ? $markdownString : str_replace(
                substr(
                    $markdownString,
                    $startPos,
                    $endPos + 3
                ),
                '',
                $markdownString
            );

        /**
         * Add all metadata specified.
         */
        if (!empty($markdownString)) {
            $markdownString = <<<MARKDOWN

{$options['header']}
{$options['date-author']}
{$options['tags']}
{$options['image']}

$markdownString
MARKDOWN;
        }
        /**
         * Parser markdown to HTML
         */
        $html = Markdown::translate($markdownString);

        /**
         * Default variables.
         * resources $root
         * $url
         * $path
         * $base Base url for site.
         */
        $this->response
            ->data(
                [
                    'html' => $html,
                    'menu' => $reveal,
                    'rootUrl' => $rootUrl,
                    'paths' => $pathValue,
                    'options' => $options
                ]
            )
            ->middleware(
                isset($options['middleware']) ?
                $options['middleware'] : 'default'
            )
            ->render('sys.help');
    }
}
