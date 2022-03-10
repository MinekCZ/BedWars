<?php

namespace MinekCz\BedWars;

use AttachableLogger;
use Exception;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class Arena 
{
    //consts::
    public const state_none = -1, state_lobby = 0, state_pregame = 1, state_game = 2, state_ending = 3;



    //Base::
    public BedWars $bedwars;
    public array $data;
    public bool $enabled = false;

    public ArenaTask $task;
    public ArenaListener $listener;
    public TeamManager $teams;


    //Worlds::
    public World $lobby_world;
    public World $game_world;


    //State::
    public int $state = self::state_none;

    //Players::

    /** @var Player[] */
    public array $players = [];
    
    /** @var Player[] */
    public array $spectators = [];


    public array $toRespawn = [];


    //Time::
    public int $gameTime;
    public int $lobbyTime;
    public int $preGameTime;
    public int $endTime;





    public function __construct(BedWars $BedWars, array $data)
    {
        $this->bedwars = $BedWars;
        $this->data = $data;

        

        $this->init();

        if(!$this->enabled) return;


        $this->task = new ArenaTask($BedWars, $this);
        $this->listener = new ArenaListener($BedWars, $this);
        $this->teams = new TeamManager($this, $this->data["teams"], $this->data["playersPerTeam"]);

        $this->bedwars->getScheduler()->scheduleRepeatingTask($this->task, 20);
        $this->bedwars->getServer()->getPluginManager()->registerEvents($this->listener, $this->bedwars);
        
    }
    public function init() 
    {
        try 
        {
            if(!count($this->data["teamspawn"]) > 0) return;
            if(!count($this->data["teambed"]) > 0) return;
            if(!count($this->data["generators"]) > 0) return;
            if(!count($this->data["teams"]) > 0) return;
            if(!count($this->data["shops"]) > 0) return;
    
    
            if($this->data["world_game"] == "") return;
            if($this->data["world_lobby"] == "") return;
            if($this->data["lobby"] == "") return;
            if($this->data["spectator"] == "") return;
            if($this->data["slots"] == 0) return;
            if($this->data["playersPerTeam"] == 0) return;
    
            if($this->data["name"] == "") $this->data["name"] = $this->data["id"];
        } catch(Exception $e)
        {
            if(isset($this->data["id"])) 
            {
                $this->getLogger()->error("Cannot load arena {$this->data["id"]}.yml");
                $this->getLogger()->error("Error: {$e->getMessage()}");
            }
        }
        


        

        $this->resetMaps();
        $this->reset();

        if($this->lobby_world != null && $this->game_world != null) 
        {
            $this->enabled = true;
            $this->state = self::state_lobby;

            $this->getLogger()->info("§aArena \"{$this->data["id"]}\" loaded");
        }

        
    }


    //Logic:


    public function JoinPlayer(Player $player) 
    {
        if($this->state == self::state_ending || $this->state == self::state_none) return;

        if(isset($this->players[$player->getName()]) || isset($this->spectators[$player->getName()])) 
        {
            $player->sendMessage("§cAlready in game");
            return;
        }

        if($this->state == self::state_lobby && count($this->players) < $this->data["slots"])
        {
            $this->players[$player->getName()] = $player;
        } else 
        {
            $this->spectators[$player->getName()] = $player;
        }

        if($this->state == self::state_lobby) 
        {

            $vec = BedWars::StringToVec($this->data["lobby"]);
            $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->lobby_world));
            $this->InitPlayer($player, false);

        } else 
        {
            $vec = BedWars::StringToVec($this->data["spawns"][array_rand($this->data["spawns"])]);
            $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->game_world));
            $this->InitPlayer($player, true);
        }
    }

    public function LeavePlayer(Player $player) 
    {
        if(isset($this->players[$player->getName()])) 
        {
            $this->sendMessage("§7[§c-§7] {$player->getName()}");
            unset($this->players[$player->getName()]);

            if($player->isOnline()) 
            {
                $player->setGamemode(GameMode::SURVIVAL());
                $player->setHealth(20);
                $player->getHungerManager()->setFood(20);
                $player->getXpManager()->setXpLevel(0);
                $player->getXpManager()->setXpProgress(0);
                $player->getInventory()->clearAll();
                $player->getArmorInventory()->clearAll();
                $player->getInventory()->clearAll();
                $player->getOffHandInventory()->clearAll();
    
                $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            }
            
            $this->teams->LeaveTeam($player);
        }

        if(isset($this->spectators[$player->getName()])) 
        {
            unset($this->spectators[$player->getName()]);

            if($player->isOnline()) 
            {
                $player->setGamemode(GameMode::SURVIVAL());
                $player->setHealth(20);
                $player->getHungerManager()->setFood(20);
                $player->getXpManager()->setXpLevel(0);
                $player->getXpManager()->setXpProgress(0);
                $player->getInventory()->clearAll();
                $player->getArmorInventory()->clearAll();
                $player->getInventory()->clearAll();
                $player->getOffHandInventory()->clearAll();
    
                $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            }
        }
    }

    public function IsInArena(Player $player) : bool 
    {
        return isset($this->players[$player->getName()]) || isset($this->spectators[$player->getName()]);
    }


    public function InitPlayer(Player $player, bool $spectator) 
    {
        //Set::
        $player->setGamemode($spectator ? GameMode::SPECTATOR() : GameMode::ADVENTURE());
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);
        $player->getXpManager()->setXpLevel(0);
        $player->getXpManager()->setXpProgress(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();

        
        $player->sendMessage(Lang::format("arena_welcome", 
        ["{player}"], 
        [
            $player->getName()
        ]));

        if($spectator) return;
        $this->sendMessage(Lang::format("arena_join", 
            ["{player}"], 
            [
            $player->getName()
        ]));

        $this->teams->JoinTeam($player);
    }

    public function KillPlayer(Player $player, Player $by = null, bool $tp = false) 
    {
        $pteam = $this->teams->GetTeam($player);
        
        if($pteam->bed) 
        {
            if($by != null) 
            {
    
                $player->sendMessage(Lang::format("killed_by", 
                    ["{team}", "{player}"], 
                    [
                    $this->GetTeamPretty($by),
                    $by->getName()
                ]));
    
            } else 
            {
                $player->sendMessage(Lang::get("killed"));
            }

            $this->ToRespawn($player, $by, $tp);
            

        } else 
        {
            if($by != null) 
            {
    
                $player->sendMessage(Lang::format("killed_by_now_spectator", 
                    ["{team}", "{player}"], 
                    [
                    $this->GetTeamPretty($by),
                    $by->getName()
                ]));
    
            } else 
            {
                $player->sendMessage(Lang::get("killed_now_spectator"));
            }

            $team = $this->teams->GetTeam($player);

            if($team != null) 
            {
                $team->alive_p--;

                if($team->alive_p <= 0) {
                    $team->alive = false;
                }
            }

            $this->JoinSpectator($player);
        }



        

        //$this->JoinSpectator($player);


        $this->CheckPlayers();
    }

    public function ToRespawn(Player $player, Player $damager = null, bool $tp = false) 
    {
        $player->setGamemode(GameMode::SPECTATOR());
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);
        $player->getInventory()->clearAll();

        if($tp) 
        {
            if($damager != null) 
            {
                $player->teleport($damager->getPosition());
            } else 
            {
                $vec = BedWars::StringToVec($this->data["spectator"]);
                $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->game_world));
            }
        }

        $this->toRespawn[$player->getName()] = [$player, 10];
    }

    public function Respawn(Player $player) 
    {
        $player->setGamemode(GameMode::SURVIVAL());
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);
        $player->getInventory()->clearAll();


        $team = $this->teams->GetTeam($player);
        $vec = BedWars::StringToVec($this->data["teamspawn"][$team->id]);
        $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->game_world));



        unset($this->toRespawn[$player->getName()]);
    }

    public function CheckPlayers() 
    {
        foreach($this->players as $sender) 
        {
            if(!$sender->isOnline()) 
            {
                $this->LeavePlayer($sender);
                continue;
            }

            if($sender->getWorld() != ($this->state == self::state_lobby ? $this->lobby_world : $this->game_world)) 
            {
                $this->LeavePlayer($sender);
                continue;
            }
        }

        foreach($this->spectators as $sender) 
        {
            if(!$sender->isOnline()) 
            {
                unset($this->spectators[$sender->getName()]);
                continue;
            }

            if($sender->getWorld() != ($this->state == self::state_lobby ? $this->lobby_world : $this->game_world)) 
            {
                unset($this->spectators[$sender->getName()]);
                continue;
            }
            
            if($sender == null) 
            {
                unset($this->spectators[$sender->getName()]);
                continue;
            }
        }

        if($this->state != self::state_lobby && count($this->players) == 0 && $this->state != self::state_ending) 
        {
            
            $this->leaveAll();
            $this->resetMaps();
            $this->reset();
        }

        


        if($this->state != self::state_game) return;

        if(count($this->teams->GetAliveTeams()) <= 1) 
        {
            $this->endGame();
            return;
        }

        if(count($this->players) == 1) 
        {
            $this->endGame();
            return;
        }
        
    }



    public function JoinSpectator(Player $player) 
    {
        $this->spectators[$player->getName()] = $player;

        $player->setGamemode(GameMode::SPECTATOR());
        $player->getInventory()->clearAll();

        if(isset($this->players[$player->getName()])) unset($this->players[$player->getName()]);

        //$this->teams->CheckTeams();
        $this->CheckPlayers();
    }


    public function GetTeamPretty(Player|string $value) :string
    {
        if(is_string($value)) 
        {
            return $this->teams->teams[$value]->display;
        }
        $team = $this->teams->GetTeam($value);

        return $team != null ? $team->display : Lang::get("spectator");
    }

    public function GetTeam(Player $player) :string
    {
        $team = $this->teams->GetTeam($player);

        return $team != null ? $team->id : "spectator";
    }


    public function DestroyBed(Player $player, string $team) :bool
    {
        $pteam = $this->teams->GetTeam($player);

        if($team == $pteam->id) 
        {
            return false;
        }

        $this->teams->teams[$team]->bed = false;

        $this->sendMessage(Lang::format("bed_destroy", ["{team}", "{teamBed}"], [$pteam->display, $this->GetTeamPretty($team)]));


        return true;
    }



    public function startGame() 
    {
        $this->state = self::state_pregame;
        
        foreach($this->players + $this->spectators as $player) 
        {
            $player->getInventory()->clearAll();

            $team = $this->teams->GetTeam($player);

            if($team == null) 
            {
                $vec = BedWars::StringToVec($this->data["spectator"]);
                $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->game_world));
                $player->setGamemode(GameMode::SPECTATOR());
                continue; 
            }

            $player->setGamemode(GameMode::ADVENTURE());

            $vec = BedWars::StringToVec($this->data["teamspawn"][$team->id]);
            $player->teleport(new Position($vec->x, $vec->y, $vec->z, $this->game_world));
        }

        $this->sendMessage(Lang::get("game_starting_soon"));
    }

    public function preGameEnd() 
    {
        $this->state = self::state_game;

        $this->sendMessage(Lang::get("game_started"));

        foreach($this->players as $player) 
        {
            $player->setGamemode(GameMode::SURVIVAL());
            $team = $this->teams->GetTeam($player);
            $player->sendTitle(Lang::format("start_title", ["{team}"], [$team->display]), Lang::format("start_subtitle", ["{players}"], [join(", ", $team->List())]));
            $player->sendMessage(Lang::format("start_team_info", ["{players}"], [join(", ", $team->List())]));
        }
    }

    public function endGame() 
    {
        $this->state = self::state_ending;

        $teams = $this->teams->GetAliveTeams();

        if(count($teams) == 1) 
        {
            $team = $teams[array_key_first($teams)];

            $this->sendTitle(Lang::format("win_title", ["{team}"], [$team->display]));
            $this->sendMessage(Lang::get("prefix"));
            $this->sendMessage(Lang::format("win_info_msg", ["{team}"], [$team->display]));
        } else 
        {
            $this->sendTitle(Lang::get("win_draw_title"));

            $this->sendMessage(Lang::get("prefix"));
            $this->sendMessage(Lang::get("win_draw_msg"));
        }

        foreach($this->teams->teams as $team) 
        {
            $this->sendMessage(Lang::format("win_info_players", ["{team}", "{players}"], [$team->display, join(", ", $team->List())]));
        }

        foreach($this->players as $player) 
        {
            $this->JoinSpectator($player);
        }

    }

    public function finalEnd() 
    {
        $this->leaveAll();
        $this->reset();
        $this->resetMaps();
    }



    public function reset() 
    {
        $this->players = [];
        $this->spectators = [];

        $this->state = self::state_lobby;

        $this->gameTime = 300;
        $this->lobbyTime = 15;
        $this->preGameTime = 10;
        $this->endTime = 10;

        $this->teams = new TeamManager($this, $this->data["teams"], $this->data["playersPerTeam"]);
    }

    public function leaveAll() 
    {
        foreach($this->players + $this->spectators as $player) 
        {
            $this->LeavePlayer($player);
        }
    }

    public function resetMaps() 
    {
        $g = $this->data["world_game"];
        $l = $this->data["world_lobby"];

        if(!$this->bedwars->loadMap($g)) return;
        if($this->data["savelobby"] == "true") 
        {
            if(!$this->bedwars->loadMap($l)) return;
        }

        $this->getServer()->getWorldManager()->loadWorld($g);
        $this->getServer()->getWorldManager()->loadWorld($l);

        $this->lobby_world = $this->getServer()->getWorldManager()->getWorldByName($l);
        $this->game_world  = $this->getServer()->getWorldManager()->getWorldByName($g);
    }

    public function sendMessage(string $msg) 
    {
        foreach($this->players + $this->spectators as $sender) 
        {
            if(!$sender->isOnline()) { $this->LeavePlayer($sender); continue; };
            $sender->sendMessage($msg);
        }
    }

    public function sendTitle(string $msg, string $sub = "") 
    {
        foreach($this->players + $this->spectators as $sender) 
        {
            if(!$sender->isOnline()) { $this->LeavePlayer($sender); continue; };
            $sender->sendTitle($msg, $sub);
        }
    }

    public function sendActionBar(string $msg) 
    {
        foreach($this->players + $this->spectators as $sender) 
        {
            if(!$sender->isOnline()) { $this->LeavePlayer($sender); continue; };
            $sender->sendActionBarMessage($msg);
        }
    }

    public function getServer() :Server
    {
        return $this->bedwars->getServer();
    }

    public function getLogger() :AttachableLogger
    {
        return $this->bedwars->getLogger();
    }

    public function GetItem(int $id, int $meta, int $count, string $name) :Item 
    {
        $item = $this->GetItemFactory()->get($id, $meta, $count);
        $item->setCustomName($name);

        return $item;
    }

    public function GetBlock(int $id, int $meta) :Block 
    {
        return $this->GetBlockFactory()->get($id, $meta);
    }

    public function GetItemFactory() :ItemFactory
    {
        return ItemFactory::getInstance();
    }

    public function GetBlockFactory() :BlockFactory 
    {
        return BlockFactory::getInstance();
    }


}