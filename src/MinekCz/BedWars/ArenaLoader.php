<?php

namespace MinekCz\BedWars;

use pocketmine\player\Player;
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
        $final["playersPerTeam"] = 1;
        $final["villager"] = [];

        $final["enabled"]     = "false";

        return $final;
    }

    public static function CheckData(Player $player, array $data) 
    {

        $miss = 0;

        foreach(self::GetBasicData("check") as $key => $f) 
        {
            if(!isset($data[$key])) { $player->sendMessage("§7- Key §c\"{$key}\" §7is §ccorrupted/missing§7! Please fix file or create new arena"); $miss++;  }
        }
        
        foreach($data as $key => $d) 
        {
            if(empty($d) || $d == null || $d == "") 
            {

                $player->sendMessage("§c- Key \"{$key}\" is empty! Please setup it's data");

                $miss++;

            }
        }

        if($miss > 0) 
        {

            $player->sendMessage("§7---");
            $player->sendMessage("§7There are §c{$miss} mistakes! §7Fix all of them and \"/pt savedata\" again.");

        }
    }

    public static function LoadArenas() :array
    {
        $files = glob(BedWars::$bedwars->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "*.yml");
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