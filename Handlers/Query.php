<?php

final class Query {

    const STATE_TAGS = Config::ALLOWED_QUERY_STRINGS;

    public function __construct()
    { }

    public static function Filter()
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

                return $params;
            }
            
        }
        
        return [];

    }

    public static function ModeSwitch($modeType)
    {
        $curQuery = $_SERVER['QUERY_STRING'];
        $firstString = 'mode=';
        $finalQuery = '';
        $locakedStrings = self::STATE_TAGS;

        switch($modeType)
        {
            // Transfer and auth active already - redirect to done.
            case $locakedStrings[1]:
                $finalQuery = str_replace($firstString . $modeType, $firstString . $locakedStrings[3], $curQuery);
            break;
            // Redirect to error view.
            default:
                $finalQuery = str_replace($firstString . $modeType, $firstString . $locakedStrings[4], $curQuery);
            break;
        }

        return $finalQuery;
    }

    public static function PostData($postKey)
    {
        if(in_array($postKey, Config::ALLOWED_POST_KEY))
        {
            return $_POST[$postKey];
        }
        else
        {
            return null;
        }
    }
}