<?php

namespace MinekCz\BedWars;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

class Shop 
{

    /** @var string[] */
    public static array $playerData = [];


    public static function GetData() : array 
    {

        $data = [];

        $b = "blocks\\";

        $blocks[0]              = "Planks x16|{$b}planks_oak|16|bronze|5";
        $blocks[count($blocks)] = "Planks x64|{$b}planks_oak|64|bronze|20";

        $blocks[count($blocks)] = "Stone x16|{$b}stone|16|bronze|10";
        $blocks[count($blocks)] = "Stone x64|{$b}stone|64|bronze|40";

        $blocks[count($blocks)] = "Endstone x16|{$b}end_stone|16|bronze|20";
        $blocks[count($blocks)] = "Endstone x64|{$b}end_stone|64|bronze|60";

        $blocks[count($blocks)] = "Obsidian x1|{$b}obsidian|1|iron|4";
        $blocks[count($blocks)] = "Obsidian x5|{$b}obsidian|5|iron|20";
        $blocks[count($blocks)] = "Obsidian x10|{$b}obsidian|10|iron|40";





        $data["Blocks|{$b}stone"] = $blocks;

        
        
        return $data;
    }


    public static function Use(Player $player, string $path = "") 
    {
        self::$playerData[$player->getName()] = $path;

        $data = self::GetData();
        $his = "root";
        $ex = explode("/", $path);
        foreach($ex as $k => $p) 
        {
            if($p == "" || $p == " ") continue;
            if(!isset($data[$p])) 
            {
                break;
            }
            $data = $data[$p];
            $his .= "/" . $p;
        }


        if(!is_array($data)) 
        {
            $player->sendMessage("bought: " . $data);
            return;
        }

        $fun = function(Player $player, $result) use($data, $path) 
        {
            $keys = array_keys($data);

            if(isset($keys[$result])) 
            {
                //var_dump("Sending -> " .  $result);
                
                $path .= "/" . $keys[$result];

                var_dump($path);
                self::Use($player, $path);
            }
        };

        $form = new SimpleForm($fun);

        $keys = array_keys($data);


        foreach($keys as $k) 
        {
            $dat = $data[$k];

            $ex = explode("|", str_replace("\\", "/", is_array($dat) ? $k : $dat));

            $form->addButton($ex[0], 0, "textures/" . $ex[1] . ".png");
        }

        $player->sendForm($form);
    }
}