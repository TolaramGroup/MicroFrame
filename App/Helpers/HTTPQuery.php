<?php

/* 
 * MIT License
 * Copyright 2020 - Godwin peter .O (me@godwin.dev)
 * Tolaram Group Nigeria
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files
 * (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish
 * distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so
 */

namespace App\Helpers;

final class HTTPQuery {

    public function __construct()
    { }

    public static function RequestGetData($getkey = null)
    {
        $query = null;
        if(isset($_SERVER['QUERY_STRING']))
        {
            $query  = explode('&', $_SERVER['QUERY_STRING']);
            if(count($query) > 0 && !empty($query[0]))
            {
                $params = array();

                foreach( $query as $param )
                {
                // prevent notice on explode() if $param has no '='
                if (strpos($param, '=') === false) $param += '=';

                list($name, $value) = explode('=', $param, 2);
                $params[urldecode($name)][] = urldecode($value);
                }

                if($getkey === null)
                {
                    return $params;
                }
                else
                {
                    return $params[$getkey];
                }
            }
        }
        
        return [];

    }

    public static function RequestPostData($postKey = null)
    {
        if($postKey === null)
        {
            return $_POST;
        }
        else
        {
            if(isset($_POST[$postKey]))
            {
                return $_POST[$postKey];
            }
            else
            {
                return null;
            }
        }
        
    }
}