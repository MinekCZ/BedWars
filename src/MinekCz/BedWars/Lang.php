<?php

namespace MinekCz\BedWars;

class Lang 
{
    static array $lang;

    public static function get(string $index) :string
    {
        return isset(self::$lang[$index]) ? self::$lang[$index] : "unknown";
    }

    public static function format(string $index, array $replace, array $to) :string
    {
        $text = self::get($index);

        foreach($replace as $k => $rep) 
        {
            $text = str_replace($rep, $to[$k], $text);
        }

        return $text;
    }
}