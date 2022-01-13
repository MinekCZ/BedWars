<?php

namespace MinekCz\BedWars;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;

class BedWars extends PluginBase 
{

    /** @var BedWars */
    public static $bedwars;

    public $commands;


    /** @var Arena[] */
    public array $arenas;

    
    
    public function onEnable() : void 
    {
        self::$bedwars = $this;
        $this->getLogger()->info("§cLoading BedWars...");

        $this->commands = new Commands($this);
        $this->getServer()->getCommandMap()->register("bedwars", $this->commands, "BedWars");

        $this->saveResource("lang.yml");
        $lang = new Config($this->getDataFolder() . "lang.yml", Config::YAML);
        Lang::$lang = $lang->getAll();

        $this->Load();
    }

    //Api::

    /** 
     * Return Availble Arena (Not full, Lobby state)
     * | If no return NULL
     */
    public static function FindArena() :?Arena 
    {

        $arenas = self::Get()->arenas;

        if(empty($arenas)) return null;

        $final = [null, -1];

        foreach($arenas as $arena) 
        {
            if($arena->state != Arena::state_lobby) continue;

            $count = count($arena->players);

            if($count >= $arena->data["slots"]) continue;



            if($count > $final[1]) 
            {
                $final = [$arena, $count];
            }
        }


        return $final[0];
    }

    /**
     * Return all Availble Arenas (Not full, Lobby state)
     * | If no empty array
     * @return Arena[]
     */
    public static function GetAvailbleArenas() :array 
    {
        $final = [];
        $arenas = self::Get()->arenas;

        foreach($arenas as $arena) 
        {
            if($arena->state != Arena::state_lobby) continue;
            if(count($arena->players) >= $arena->data["slots"]) continue;

            array_push($final, $arena);
        }

        return $final;
    }

    /**
     * Return true if player is in any Arena
     * | This also return true if player is spectator
     */
    public static function IsInArena(Player $player) :bool 
    {
        $arenas = self::Get()->arenas;

        foreach($arenas as $arena) 
        {
            if(isset($arena->players[$player->getName()]) || isset($arena->spectators[$player->getName()])) 
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Return arena in which the player is
     * | If none return NULL
     */
    public static function GetArenaByPlayer(Player $player) :?Arena 
    {
        $arenas = self::Get()->arenas;

        foreach($arenas as $arena) 
        {
            if(isset($arena->players[$player->getName()]) || isset($arena->spectators[$player->getName()])) 
            {
                return $arena;
            }
        }

        return null;
    }


    /**
     * Set Arena's lobby or preGame time to 3
     */
    public static function StartArena(Arena $arena) 
    {
        if($arena->state == Arena::state_lobby) 
        {
            $arena->lobbyTime = 3;
            return;
        }

        if($arena->state == Arena::state_pregame) 
        {
            $arena->preGameTime = 3;
            return;
        }
    }

    /**
     * Set Arena's game time to 3
     */
    public static function EndArena(Arena $arena) 
    {
        if($arena->state == Arena::state_game) 
        {
            $arena->gameTime = 3;
            return;
        }
    }


    /**
     * Set time of current state
     */
    public static function SetTime(Arena $arena, int $time) 
    {
        switch($arena->state) 
        {
            case Arena::state_lobby:
                $arena->lobbyTime = $time;
                break;
            case Arena::state_pregame:
                $arena->preGameTime = $time;
                break;
            case Arena::state_game:
                $arena->gameTime = $time;
                break;
            case Arena::state_ending:
                $arena->endTime = $time;
                break;
        }
    }

    /**
     * [0 => gameWorld, 1 => lobbyWorld]
     * @return World[]
     */
    public static function GetArenaWorlds(Arena $arena) :array 
    {
        return [$arena->game_world, $arena->lobby_world];
    }

    /**
     * Return all players including spectators
     * @return Player[]
     */
    public static function GetAllPlayer(Arena $arena) :array
    {
        return $arena->players + $arena->spectators;
    }

    /**
     * Return Arena by ID
     * | If none return NULL
     */
    public static function GetArenaByName(string $id) :?Arena
    {
        $arenas = self::Get()->arenas;

        if(isset($arenas[$id])) 
        {
            return $arenas[$id];
        }

        return null;
    }












    public function Load() 
    {
        if(!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }

        if(!is_dir($this->getDataFolder() . "data")) {
            @mkdir($this->getDataFolder() . "data");
        }

        if(!is_dir($this->getDataFolder() . "data\\saves")) {
            @mkdir($this->getDataFolder() . "data\\saves");
        }

        
        $this->arenas = ArenaLoader::LoadArenas();
    }
    
    
    public static function Get() : BedWars 
    {
        return self::$bedwars;
    }

    public static function VecToString(Vector3 $vec) : string
    {
        return "{$vec->x},{$vec->y},{$vec->z}";
    }

    public static function StringToVec(string $str) : Vector3 
    {
        $split = explode(",", $str);

        if(count($split) != 3) return Vector3::zero();

        return new Vector3($split[0], $split[1], $split[2]);
    }


    public function saveMap(World $level) 
    {

        $level->save(true);

        $levelPath = $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $level->getFolderName();
        $target = $this->getDataFolder() . "data\\saves" . DIRECTORY_SEPARATOR . $level->getFolderName();

        $this->getServer()->getWorldManager()->unloadWorld($level);

        
        if(!is_dir($target)) {
            @mkdir($target);
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath($levelPath)), \RecursiveIteratorIterator::LEAVES_ONLY);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) 
        {
            
            if($file->isDir()) 
            {
                $localPath = substr($file->getPath(), strlen($this->getServer()->getDataPath() . "worlds"));


                if(!is_dir($target . "\.." . $localPath)) {
                    @mkdir($target . "\.." . $localPath);
                }
                
            }

            if($file->isFile()) 
            {
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename();
                $localPath = substr($file->getPath(), strlen($this->getServer()->getDataPath() . "worlds\\" . $level->getFolderName()));

                //var_dump($localPath . DIRECTORY_SEPARATOR . $file->getFilename());

                //var_dump($target . $localPath . "\\" . $file->getFilename());
                $ex = $file->getExtension();
                $name = $file->getFilename();
                if($ex == "log" || $name == "LOCK" || $name == "LOG") 
                {
                    continue;
                }
                var_dump($file->getFilename());

                copy($filePath, $target . $localPath . "\\" . $file->getFilename());
            }

        }
        

    }

    public function loadMap(string $folderName) :bool
    {

        if(!$this->getServer()->getWorldManager()->isWorldGenerated($folderName)) return false;

        if($this->getServer()->getWorldManager()->isWorldLoaded($folderName)) 
        {
            $this->getServer()->getWorldManager()->unloadWorld($this->getServer()->getWorldManager()->getWorldByName($folderName));
        }



        $levelpath = $this->getDataFolder() . "data\\saves\\" . $folderName;

        if(!is_dir($levelpath)) 
        {
            $this->getLogger()->error("Could not load map \"$folderName\". File was not found, try save level in setup");
        }

        $target = $this->getServer()->getDataPath() . "worlds\\" . $folderName;

        array_map('unlink', glob("$target\\db/*.*"));
        array_map('unlink', glob("$target\\db/*"));
        rmdir($target . "\\db");

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath($levelpath)), \RecursiveIteratorIterator::LEAVES_ONLY);



        foreach ($files as $file) 
        {
            
            
            if($file->isDir()) 
            {
                $localPath = substr($file->getPath(), strlen($this->getDataFolder() . "data\\saves"));


                if(!is_dir($target . "\.." . $localPath)) {
                    @mkdir($target . "\.." . $localPath);
                }
                
            }

            if($file->isFile()) 
            {
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename();
                $localPath = substr($file->getPath(), strlen($this->getDataFolder() . "data\\saves\\" . $folderName));

                $ex = $file->getExtension();
                $name = $file->getFilename();
                if($ex == "log" || $name == "LOCK" || $name == "LOG") 
                {
                    continue;
                }

                copy($filePath, $target . $localPath . "\\" . $file->getFilename());
            }

        }

        //$this->getServer()->getWorldManager()->loadWorld($folderName);

        return true;
    }
}