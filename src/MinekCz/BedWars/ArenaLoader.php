<?php

namespace MinekCz\BedWars;

use pocketmine\utils\Config;

class ArenaLoader 
{




    public static function GetBasicData(string $id) :array
    {
        $final = [];
        $final["id"]          = $id;
        $final["name"]        = "";
        $final["slots"]       = 0;    
        $final["world_lobby"] = "";
        $final["world_game"]  = "";
        $final["lobby"]       = "";
        $final["spectator"]   = "";
        $final["savelobby"]   = "false";

        $final["teams"] = [];
        $final["teambed"] = [];
        $final["teamspawn"] = [];
        $final["generators"] = [];
        $final["shops"] = [];

        $final["enabled"]     = "false";

        return $final;
    }

    public static function LoadArenas() :array
    {
        $files = glob(BedWars::$bedwars->getDataFolder() . "data\\" . "*.yml");
        $final = [];
        foreach ($files as $file) 
        {
            $config = new Config($file, Config::YAML);
            $data = $config->getAll();
            if($data["enabled"] == "false") continue;
            $final[$data["id"]] = new Arena(BedWars::$bedwars, $data);
        }

        return $final;
    }
}