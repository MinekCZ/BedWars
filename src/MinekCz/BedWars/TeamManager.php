<?php

namespace MinekCz\BedWars;

use pocketmine\item\ItemIds;
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
        $this->ppt = $ppt;
        
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
        $team->alive_p++;

        $player->sendMessage(Lang::format("join_team", ["{team}"], [$team->display]));
    }

    /** @return Team[] */
    public function GetAliveTeams() :array
    {
        $final = [];


        foreach($this->teams as $team) 
        {
            
            if($team->alive) 
            {
                //var_dump("{$team->id} : " . count($team->players));
                if(count($team->players) == 0) 
                {
                    $team->alive = false;
                    continue;
                }
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

        $team->alive_p--;

        
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


    public function CheckTeams() 
    {
    }
}