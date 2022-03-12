<?php

namespace MinekCz\BedWars;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class Shop 
{

    /** @var string[] */
    public static array $playerData = [];

    public const iron_ing = ItemIds::IRON_INGOT, bronze_ing = ItemIds::BRICK, gold_ing = ItemIds::GOLD_INGOT;

    public static array $ings = ["bronze" => self::bronze_ing, "iron" => self::iron_ing, "gold" => self::gold_ing];

    public static array $data = [];


    public static function GetData() : array 
    {

        $data = [];

        $b = "blocks\\";
        $i = "items\\";


        $blocks[0]              = "Planks x16|{$b}planks_oak|16|bronze|4|" . ItemIds::PLANKS;
        $blocks[count($blocks)] = "Planks x64|{$b}planks_oak|64|bronze|16|". ItemIds::PLANKS;

        $blocks[count($blocks)] = "Stone x16|{$b}stone|16|bronze|8|". ItemIds::STONE;
        $blocks[count($blocks)] = "Stone x64|{$b}stone|64|bronze|32|".  ItemIds::STONE;

        $blocks[count($blocks)] = "Endstone x16|{$b}end_stone|16|bronze|16|". ItemIds::END_STONE;
        $blocks[count($blocks)] = "Endstone x64|{$b}end_stone|64|bronze|64|". ItemIds::END_STONE;

        $blocks[count($blocks)] = "Obsidian x1|{$b}obsidian|1|iron|4|" . ItemIds::OBSIDIAN;
        $blocks[count($blocks)] = "Obsidian x5|{$b}obsidian|5|iron|20|". ItemIds::OBSIDIAN;
        $blocks[count($blocks)] = "Obsidian x10|{$b}obsidian|10|iron|40|". ItemIds::OBSIDIAN;




        $tools[0] = "Wooden Pickaxe|{$i}wood_pickaxe|1|bronze|10|"             . ItemIds::WOODEN_PICKAXE;
        $tools[count($tools)] = "Wooden Axe|{$i}wood_axe|1|bronze|10|"         . ItemIds::WOODEN_AXE;
        $tools[count($tools)] = "Wooden Shovel|{$i}wood_shovel|1|bronze|6|"    . ItemIds::WOODEN_SHOVEL;

        $tools[count($tools)] = "Stone Pickaxe|{$i}stone_pickaxe|1|bronze|20|" . ItemIds::STONE_PICKAXE;
        $tools[count($tools)] = "Stone Axe|{$i}stone_axe|1|bronze|20|"         . ItemIds::STONE_AXE;
        $tools[count($tools)] = "Stone Shovel|{$i}stone_shovel|1|bronze|16|"   . ItemIds::STONE_SHOVEL;

        $tools[count($tools)] = "Iron Pickaxe|{$i}iron_pickaxe|1|iron|10|" . ItemIds::IRON_PICKAXE;
        $tools[count($tools)] = "Iron Axe|{$i}iron_axe|1|iron|10|"         . ItemIds::IRON_AXE;
        $tools[count($tools)] = "Iron Shovel|{$i}iron_shovel|1|iron|6|"    . ItemIds::IRON_SHOVEL;

        $tools[count($tools)] = "Diamond Pickaxe|{$i}diamond_pickaxe|1|gold|6|" . ItemIds::DIAMOND_PICKAXE;
        $tools[count($tools)] = "Diamond Axe|{$i}diamond_axe|1|gold|6|"         . ItemIds::DIAMOND_AXE;
        $tools[count($tools)] = "Diamond Shovel|{$i}diamond_shovel|1|gold|5|"    . ItemIds::DIAMOND_SHOVEL;

        $weapons[0]               = "Wooden Sword|{$i}wood_sword|1|bronze|6|"       . ItemIds::WOODEN_SWORD;
        $weapons[count($weapons)] = "Stone Sword|{$i}stone_sword|1|bronze|15|"      . ItemIds::STONE_SWORD;
        $weapons[count($weapons)] = "Iron Sword|{$i}iron_sword|1|iron|6|"           . ItemIds::IRON_SWORD;
        $weapons[count($weapons)] = "Diamond Sword|{$i}diamond_sword|1|gold|8|"     . ItemIds::DIAMOND_SWORD;

        $food[0] = "Cooked Chicken x16|{$i}chicken_cooked|1|bronze|4|" . ItemIds::COOKED_CHICKEN;
        $food[count($food)] = "Cake x1|{$i}cake|1|iron|2|" . ItemIds::CAKE;

        $bow[0] = "Bow|{$i}bow_standby|1|gold|3|" . ItemIds::BOW;
        $bow[count($bow)] = "Arrow x16|{$i}arrow|1|iron|2|" . ItemIds::ARROW;


        $armor["Chainmail Armor|{$i}chainmail_chestplate|bronze|15"] = [ 
            "1|" . ItemIds::CHAINMAIL_HELMET,
            "1|" . ItemIds::CHAINMAIL_CHESTPLATE,
            "1|" . ItemIds::CHAINMAIL_LEGGINGS,
            "1|" . ItemIds::CHAINMAIL_BOOTS
        ];

        $armor["Iron Armor|{$i}iron_chestplate|iron|10"] = [ 
            "1|" . ItemIds::IRON_HELMET,
            "1|" . ItemIds::IRON_CHESTPLATE,
            "1|" . ItemIds::IRON_LEGGINGS,
            "1|" . ItemIds::IRON_BOOTS
        ];

        $armor["Diamond Armor|{$i}diamond_chestplate|gold|6"] = [ 
            "1|" . ItemIds::DIAMOND_HELMET,
            "1|" . ItemIds::DIAMOND_CHESTPLATE,
            "1|" . ItemIds::DIAMOND_LEGGINGS,
            "1|" . ItemIds::DIAMOND_BOOTS
        ];






        $data["Building|{$b}stone"] = $blocks;
        $data["Tools|{$i}diamond_pickaxe"] = $tools;
        $data["Swords|{$i}diamond_sword"] = $weapons;
        $data["Armor|{$i}diamond_chestplate"] = $armor;
        $data["Food|{$i}cake"] = $food;
        $data["Bow|{$i}bow_standby"] = $bow;

        
        
        return $data;
    }


    public static function Use(Player $player, string $path = "") 
    {
        self::$playerData[$player->getName()] = $path;

        $data = self::$data;
        $his = "root";
        $ex = explode("/", $path);
        $last = "";
        foreach($ex as $k => $p) 
        {
            if($p == "" || $p == " ") continue;
            if(!isset($data[$p])) 
            {
                break;
            }
            $data = $data[$p];
            $his .= "/" . $p;
            $last = $p;
        }


        if(!is_array($data)) 
        {

            $ex = explode("|", str_replace("\\", "/", $data));

            $imu = $ex[2];
            $iid = $ex[5];

            $cur = $ex[3];
            $cmu = $ex[4];



            $item = self::GetItem($iid, 0, $imu, "");
            $price = self::GetItem(self::$ings[$cur], 0, $cmu, "");

            if($player->getInventory()->contains($price)) 
            {
                $player->getInventory()->removeItem($price);
                $player->getInventory()->addItem($item);

                $player->sendMessage(Lang::format("shop_buy", ["{item}"], [$item->getName()]));
            } else {
                $player->sendMessage(Lang::get("shop_cannot_buy"));
            }

            $l = explode("/",$path);
            $path = substr($path, 0, strlen($path)-strlen($l[count($l)-1])-1);

            self::Use($player, $path);


            return;
        } else 
        {
            $ex = explode("|", str_replace("\\", "/", $last));
            if(isset($ex[2])) 
            {
                $cur = $ex[2];
                $cmu = $ex[3];
                $price = self::GetItem(self::$ings[$cur], 0, $cmu, "");

                if($player->getInventory()->contains($price)) 
                {
                    $player->getInventory()->removeItem($price);
                    foreach($data as $dat) 
                    {
                        $exx = explode("|", $dat);
                        $imu = $exx[0];
                        $iid = $exx[1];

                        $item = self::GetItem($iid, 0, $imu, "");
                        $player->getInventory()->addItem($item);
                        $player->sendMessage(Lang::format("shop_buy", ["{item}"], [$item->getName()]));

                    }
                } else {
                    $player->sendMessage(Lang::get("shop_cannot_buy"));
                }



                return;
            }
        }

        $fun = function(Player $player, $result) use($data, $path) 
        {
            $keys = array_keys($data);

            if(isset($keys[$result])) 
            {
                //var_dump("Sending -> " .  $result);
                
                $path .= "/" . $keys[$result];

                self::Use($player, $path);
            }
        };

        $form = new SimpleForm($fun);
        $form->setTitle(Lang::get("shop"));

        $keys = array_keys($data);


        foreach($keys as $k) 
        {
            $dat = $data[$k];

            $ex = explode("|", str_replace("\\", "/", is_array($dat) ? $k : $dat));

            if(is_array($dat) && isset($ex[2])) 
            {
                $form->addButton($ex[0] . "\n{$ex[2]} x {$ex[3]}", 0, "textures/" . $ex[1] . ".png");

                continue;
            }

            if(isset($ex[3])) 
            {
                $form->addButton($ex[0] . "\n§o{$ex[3]} x{$ex[4]}", 0, "textures/" . $ex[1] . ".png");
            } else {
                $form->addButton($ex[0], 0, "textures/" . $ex[1] . ".png");
            }
            
        }

        $player->sendForm($form);
    }


    public static function GetItem(int $id, int $meta, int $count, string $name) :Item 
    {
        $item = self::GetItemFactory()->get($id, $meta, $count);
        if($name != "") { $item->setCustomName($name); };

        return $item;
    }

    public static function GetItemFactory() :ItemFactory
    {
        return ItemFactory::getInstance();
    }
}