<?php

namespace MinekCz\BedWars;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;

class Commands extends Command
{
    
    public array $data;

    /** @var Player[] */
    public array $inSetup = [];

    public BedWars $BedWars;


    public function __construct(BedWars $BedWars)
    {
        parent::__construct("BedWars", "BedWars", null, ["pt"]);
        $this->BedWars = $BedWars;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {

        if(!$sender instanceof Player || $sender->getServer()->isOp($sender->getName())) 
        {
            if(count($args) != 0 && $args[0] == "noperm") {
                $this->a_unset($args, 0);
                $this->NoPerm($sender, $args);
                return;
            }
            if(isset($this->inSetup[$sender->getName()])) 
            {
              $this->Editor($this->inSetup[$sender->getName()], $args);
              return;
            }
            $this->Perm($sender, $args);
            return;
        } else {
            $this->NoPerm($sender, $args);
            return;
        }
        
    }

    public function NoPerm(CommandSender $sender, array $args) 
    {
        if(count($args) == 0) {
            $sender->sendMessage(
            
                "BedWars\n" .
                "/pt join | /pt join <arena>\n" .
                "/pt leave\n" .
                ""
            );

            return;
        }

        switch($args[0]) 
        {
            case "join":
                if(!$sender instanceof Player) { $sender->sendMessage("§cNot Available in console!"); return; }

                if(isset($args[1]) && isset($this->BedWars->arenas[$args[1]])) 
                {
                    $this->BedWars->arenas[$args[1]]->JoinPlayer($sender);
                    break;
                }

                $arena = BedWars::FindArena();

                if($arena != null) 
                {
                    $arena->JoinPlayer($sender);
                }
                break;
            case "leave":
                if(!$sender instanceof Player) { $sender->sendMessage("§cNot Available in console!"); return; }

                $arena = BedWars::GetArenaByPlayer($sender);
                if($arena != null) 
                {
                    $arena->LeavePlayer($sender);
                }
                break;
            default:
            $sender->sendMessage("\n§7Unknown argument §c\"{$args[0]}\"§7!\n§7\"/pt\" for help");
                break;
        }
    }

    public function Perm(CommandSender $sender, array $args) 
    {
        if(count($args) == 0) {
            $sender->sendMessage(
            
                "§cPalermo§fTown\n" .
                "§o§7/pt join | /pt join <arena>\n" .
                "§o§7/pt leave\n" . 
                "§o§7/pt list\n" .
                "§o§7/pt admin [help | create | remove | setup (player only) | list]"
            );

            return;
        }

        switch($args[0]) 
        {
            case "join":
                if(!$sender instanceof Player) { $sender->sendMessage("§cNot Available in console!"); return; }

                if(isset($args[1]) && isset($this->BedWars->arenas[$args[1]])) 
                {
                    $this->BedWars->arenas[$args[1]]->JoinPlayer($sender);
                    break;
                }

                $arena = BedWars::FindArena();

                if($arena != null) 
                {
                    $arena->JoinPlayer($sender);
                }
                break;
            case "leave":
                if(!$sender instanceof Player) { $sender->sendMessage("§cNot Available in console!"); return; }

                $arena = BedWars::GetArenaByPlayer($sender);
                if($arena != null) 
                {
                    $arena->LeavePlayer($sender);
                }
                break;
            case "list":

                $sender->sendMessage("\n§7Loaded Arenas: ");

                foreach($this->BedWars->arenas as $arena) 
                {
                    $sender->sendMessage("§o§7- {$arena->data["id"]}");
                }

                break;

            case "admin":
                
                self::a_unset($args, 0);
                $this->Admin($sender, $args);
                break;

            default:
                $sender->sendMessage("§cUnknown arg ({$args[0]})! \"/pt\" for help");
                break;
        }
    }

    public function Admin(CommandSender $sender, array $args) 
    {
        if(count($args) == 0) 
        {
            $sender->sendMessage("§7[help | create | remove | setup | list]");
            return;
        }

        switch($args[0]) 
        {
            case "help":

                $sender->sendMessage(

                    "How to setup: \n" .
                    "\n1. Create arena     : /pt admin create \"arena name\"\n" .
                      "2. Enter setup mode : /pt admin setup \"arena name\"\n"  .
                      "3. For more info    : \"/pt\"  \n"
                );
                break;


            case "create":

                if(!isset($args[1])) { $sender->sendMessage("§7Please enter arena name. /pt admin create §c\"arena name\""); return; }

                $this->data = ArenaLoader::GetBasicData($args[1]);

                $sender->sendMessage("\n§7Arena with name {$args[1]} is created");
                $sender->sendMessage("§7\"/pt admin setup\" to setup arena");

                break;
            case "remove":
                break;
            case "setup":

                if(empty($this->data)) 
                {
                    $sender->sendMessage("§cNo data found! §7\"/pt admin create <arena_name>\"");
                    return;
                }

                if(!$sender instanceof Player) { $sender->sendMessage("§cNot Available in console!"); return; }

                $this->inSetup[$sender->getName()] = $sender;

                $sender->sendMessage("§7You're now in setup mode. \"/pt\" for more info");

                break;
            case "list":
                break;
            case "load":

                if(!isset($args[1])) { $sender->sendMessage("\n§7Please enter arena name. /pt admin load §c\"arena name\""); return; }

                if(!is_file($this->getDataFolder() . "data\\{$args[1]}.yml")) 
                {
                    $sender->sendMessage("\n§7Arena with name §c\"{$args[1]}\" §7doesn't exists");
                    return;
                }

                $config = new Config($this->getDataFolder() . "data\\{$args[1]}.yml", Config::YAML);
                $this->data = $config->getAll();

                $sender->sendMessage("\n§7Arena with name §c\"{$args[1]}\" §7is loaded in memory");
                $sender->sendMessage("§7\"/pt admin setup\" to setup arena");


                break;
            default:
                $sender->sendMessage("\n§7Unknown argument §c\"{$args[0]}\"§7!\n§7\"/pt admin\" for help");
                break;
        }
    }

    public function Editor(Player $sender, array $args) 
    {
        if(count($args) == 0) {
            $sender->sendMessage(
            
                "§cPalermo§7Town Editor\n" .
                "§7§o/pt info\n" .
                "§7§o/pt dump [key]\n" .
                "§7§o/pt reset\n" .
                "§7§o/pt set <data> <arg>\n" .
                ""
            );

            return;
        }

        switch($args[0]) 
        {
            case "info":

                $sender->sendMessage("§7Data:");
                $sender->sendMessage(" ");
                foreach ($this->data as $k => $i) 
                {
                    $sender->sendMessage("§7§o{$k}: §a" . (is_array($i) ? count($i) : $i));
                }


                break;

            case "dump":

                $sender->sendMessage("");
                if(isset($args[1])) 
                {
                    if(isset($this->data[$args[1]])) 
                    {   
                        ob_start();
                        var_dump($this->data[$args[1]]);
                        $sender->sendMessage(ob_get_clean());
                    }

                    return;
                }


                ob_start();
                var_dump($this->data);
                $sender->sendMessage(ob_get_clean());

                break;

            case "reset":

                $this->data = ArenaLoader::GetBasicData($this->data["id"]);
                $sender->sendMessage("§7Data was reset");
                break;

            case "exit":
            case "leave":
                unset($this->inSetup[$sender->getName()]);
                $sender->sendMessage("§7You've exited editor");
                break;
            case "set":
                
                if(!isset($args[1])) 
                {
                    $sender->sendMessage("\n§cIncorrect Format!");

                    $sender->sendMessage("\n§7/pt set <key> <value>");
                    $sender->sendMessage("§7/pt set <key> <index (num)> <value>");

                    $sender->sendMessage("\n§7§oExample: /pt set name ExampleName");
                    $sender->sendMessage("§7§oExample: /pt set spawn 1 player_pos");
                    $sender->sendMessage("§7§oExample: /pt set chests 3 look_block");
                    $sender->sendMessage("§7§oExample: /pt set spawn 3 look_pos");
                    return;
                }

                $key = $args[1];

                if(!isset($this->data[$key])) 
                {
                    $sender->sendMessage("\n§cKey §7\"{$key}\" §cdoesn't exists in data\n§7\"/pt info\" to show Available keys");
                    return;
                }

                if(is_array($this->data[$key])) 
                {

                    $kexists = "\n§7Key §c\"{$key}\"§7 exists.\n";
                    $tformat = "§c> §7Use this format: §7/pt set {$key} §c<index> <value>";

                    if(!isset($args[2])) 
                    {
                        $sender->sendMessage($kexists . $tformat);
                        return;
                    }


                    $index = $args[2];

                    if(!is_numeric($index)) 
                    {
                        $sender->sendMessage($kexists . "§cSecond argument must be number §7(your input: §c{$index}§7)");
                        $sender->sendMessage($tformat);
                        return;
                    }

                    $kexists = "\n§7Key §c\"{$key}\" §7and index §c\"{$index}\"§7 is valid.\n";
                    $tformat = "§c> §7Use this format: §7/pt set {$key} {$index} §c<value>§7";

                    if(!isset($args[3])) 
                    {
                        $sender->sendMessage($kexists . $tformat);
                        $sender->sendMessage("§c! §7Available values in this context are: \"player_pos\", \"look_block\", \"look_pos\"");
                        return;
                    }


                    $value_arg = $args[3];
                    $value = "";
                    /** @var Block|null */
                    $block =  null;

                    switch($value_arg) 
                    {
                        case "pos_player":
                        case "player_pos":

                            $value = BedWars::VecToString($sender->getPosition()->round(0, PHP_ROUND_HALF_DOWN));

                            break;
                        case "block_look":
                        case "look_block":
                            $block = $sender->getTargetBlock(4, []);
                            $value = BedWars::VecToString($block->getPosition());
                            break;
                        case "pos_look":
                        case "look_pos":
                            $value = BedWars::VecToString($sender->getTargetBlock(4, [])->getPosition()->addVector(new Vector3(0, 1, 0)));
                            break;
                        default:

                            $sender->sendMessage($kexists . $tformat);
                            $sender->sendMessage("§c! §7Available values in this context are: \"player_pos\", \"look_block\", \"look_pos\"");
                            return;
                    }

                    if($key == "chests") 
                    {
                        if($block == null) 
                        {
                            $sender->sendMessage("\n§7Chests key require §c\"look_block\" argument\n§c> §7Use this format: /pt set {$key} {$index} §clook_block");
                            return;
                        }

                        $this->data[$key][$index] = [$value, $block->getMeta()];
                        $sender->sendMessage("\n§7Key §a\"{$key}-{$index}\" §7has been successfully §aset §7to §a\"{$value}\"");
                        $sender->sendMessage("§a> §7You can use §a\"/pt info\" or §a\"/pt dump\" §7for detailed info");

                        return;
                    }

                    $this->data[$key][$index] = $value;
                    $sender->sendMessage("\n§7Key §a\"{$key}-{$index}\" §7has been successfully §aset §7to §a\"{$value}\"");
                    $sender->sendMessage("§a> §7You can use §a\"/pt info\" or §a\"/pt dump\" §7for detailed info");
                    return;


                } else 
                {
                    if(!isset($args[2])) 
                    {
                        $sender->sendMessage("\n§7Key §c\"{$key}\"§7 exists.\n§c > §7Use this format: /pt set {$key} §c<value>");
                        $sender->sendMessage("§cOptional value: §7\"player_pos\", \"look_block\", \"look_pos\"");
                        return;
                    }

                    $value = $args[2];

                    switch($args[2]) 
                    {
                        case "pos_player":
                        case "player_pos":

                            $value = BedWars::VecToString($sender->getPosition()->round(0, PHP_ROUND_HALF_DOWN));

                            break;
                        case "block_look":
                        case "look_block":
                            $block = $sender->getTargetBlock(4, []);
                            $value = BedWars::VecToString($block->getPosition());
                            break;
                        case "pos_look":
                        case "look_pos":
                            $value = BedWars::VecToString($sender->getTargetBlock(4, [])->getPosition()->addVector(new Vector3(0, 1, 0)));
                            break;
                        default:
                            break;
                    }

                    $this->data[$key] = $value;

                    $sender->sendMessage("\n§7Key §a\"{$key}\" §7has been successfully §aset §7to §a\"{$value}\"");
                    $sender->sendMessage("§a> §7You can use §a\"/pt info\" or §a\"/pt dump\" §7for detailed info");

                    return;
                }
            case "savedata":

                $config = new Config($this->getDataFolder() . "data\\" . $this->data["id"] . ".yml", Config::YAML);
                $config->setAll($this->data);
                $config->save();

                $sender->sendMessage("§7Arena §adata §7has been successfully §asaved");
                break;

            case "savelevel":
            case "savelevels":

                if(!$this->getServer()->getWorldManager()->isWorldLoaded($this->data["world_lobby"]) && $this->data["world_lobby"] != $this->data["world_game"]) 
                {
                    $sender->sendMessage("§7Lobby level §c\"{$this->data["world_lobby"]}\" §7is §cnot loaded §7or does §cnot exist");
                    return;
                }

                if(!$this->getServer()->getWorldManager()->isWorldLoaded($this->data["world_game"])) 
                {
                    $sender->sendMessage("§7Game level \"{$this->data["world_game"]}\" is not loaded or does not exist");
                    return;
                }

                $lobby = $this->data["world_lobby"];
                $game  = $this->data["world_game"];

                if($lobby == $game) 
                {
                    $sender->sendMessage("\n§7Lobby world and Game world are same, lobby will be on §a\"{$game}\"");
                }

                $sender->sendMessage("\n§7Saving §a\"{$game}\"§7...");

                $this->BedWars->saveMap($this->getServer()->getWorldManager()->getWorldByName($game));

                if($this->data["savelobby"] == "true" && $lobby != $game) 
                {
                    $sender->sendMessage("\n§7Saving §a\"{$lobby}\"§7...");
                    $this->BedWars->saveMap($this->getServer()->getWorldManager()->getWorldByName($lobby));
                } elseif ($lobby != $game)
                {
                    $sender->sendMessage("\n§7To §asave§7 the §alobby §7map, use §a\"/pt set savelobby true\"");
                }





                break;


            default:
                $sender->sendMessage("\n§7Unknown argument §c\"{$args[0]}\"§7!\n§7\"/pt\" for help");
                break;
        }
    }

    public static function a_unset(array &$array, int $index) 
    {
        unset($array[$index]);
        $array = array_values($array);
    }

    public function getDataFolder() :string
    {
        return $this->BedWars->getDataFolder();
    }

    public function getServer() :Server
    {
        return $this->BedWars->getServer();
    }
}