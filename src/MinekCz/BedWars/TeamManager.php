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
        $need = (int)(count($this->arena->players) / $this->ppt);

        foreach($this->teams as $team) 
        {
            if(count($team->players) <= $need) 
            {
                $team->players[$player->getName()] = $player;
                
                $player->sendMessage(Lang::format("join_team", ["{team}"], [$team->display]));
                return;
            }
        }
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