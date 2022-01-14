<?php

namespace MinekCz\BedWars;

use pocketmine\player\Player;

class TeamManager 
{

    public Arena $arena;

    /** @var Team[] */
    public array $teams;

    public int $ppt = 1;

    public function __construct(Arena $arena, array $teams, int $ppt)
    {
        $this->arena = $arena;
        
        foreach($teams as $k => $team) 
        {
            $this->teams[$k] = new Team([], $k, $team);
        }
    }

    public function JoinTeam(Player $player) 
    {
        $count = count($this->teams);
        $keys = array_keys($this->teams);
        $pcount = count($this->arena->players) - 1;

        $offset = $pcount - ($count * (int)($pcount / $count));

        $team = $this->teams[$keys[$offset]];

        $team->players[$player->getName()] = $player;

        $team->bed = true;
        $team->alive = true;
    }

    /** @return Team[] */
    public function GetAliveTeams() :array
    {
        $final = [];


        foreach($this->teams as $team) 
        {
            if($team->alive) 
            {
                $final[$team->id] = $team;
            }
        }

        return $final;
    }

    public function LeaveTeam(Player $player) 
    {
        $team = $this->GetTeam($player);
        if($team == null) return;

        unset($team->players[$player->getName()]);
    }

    public function GetTeam(Player $player) :?Team
    {
        foreach($this->teams as $team) 
        {
            if(isset($team->players[$player->getName()])) 
            {
                return $team;
            }
        }

        return null;
    }
}

class Team 
{
    /** @var Player[] */
    public array $players = [];
    public string $id = "";
    public string $display = "";
    public string $color = "";

    public bool $alive = false;
    public bool $bed = false;

    public function __construct(array $player, string $id, string $display)
    {
        $this->players = $player;
        $this->id = $id;
        $this->display = $display;

        if($display[0] == "§") 
        {
            $this->color = "§" . $display[1];
        }
    }

    public function List() :array
    {
        $final = [];
        foreach($this->players as $player) 
        {
            $final[$player->getName()] = $player->getName();
        }

        return $final;
    }
}