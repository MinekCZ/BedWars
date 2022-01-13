<?php

namespace MinekCz\BedWars;

use pocketmine\item\ItemIds;
use pocketmine\scheduler\Task;
use pocketmine\player\Player;

class ArenaTask extends Task
{

    public BedWars $palermoTown;
    public Arena $arena;


    public function __construct(BedWars $palermotown, Arena $arena)
    {
        $this->palermoTown = $palermotown;
        $this->arena = $arena;
    }


    public function onRun(): void
    {
        if(!count($this->arena->players + $this->arena->spectators) > 0) return;
        $this->arena->CheckPlayers();

        switch($this->arena->state) 
        {
            case Arena::state_lobby:

                if(count($this->arena->players) == 0) 
                {
                    $this->arena->lobbyTime = 15;
                    $this->arena->sendActionBar(Lang::get("waitig_players"));
                    break;
                }

                
                $this->arena->lobbyTime--;

                foreach($this->arena->players as $player) 
                {
                    $player->sendActionBarMessage(Lang::format("lobby_starting_tip", 
                    ["{team}", "{time}"], 
                    [
                    $this->arena->GetTeamPretty($player),  
                    $this->formatTime($this->arena->lobbyTime)
                    ]));
                }
                
                
                if($this->arena->lobbyTime == 0) 
                {
                    $this->arena->startGame();
                }
                break;
            case Arena::state_pregame:

                $this->arena->preGameTime--;

                $this->arena->sendActionBar(Lang::format("pregame_tip", 
                    ["{time}"], 
                    [
                    $this->formatTime($this->arena->preGameTime)
                ]));
                

                if($this->arena->preGameTime == 0) 
                {
                    $this->arena->preGameEnd();
                }

                break;

            case Arena::state_game:

                $this->arena->gameTime--;

                /** @var Player */
                foreach($this->arena->players + $this->arena->spectators as $player) 
                {
                    $player->sendActionBarMessage(Lang::format("game_tip", 
                        ["{team}", "{time}"], 
                        [
                        $this->arena->GetTeamPretty($player), 
                        $this->formatTime($this->arena->gameTime)
                    ]));
                }

                if($this->arena->gameTime == 0) 
                {
                    $this->arena->endGame();
                }

                break;
            case Arena::state_ending:

                $this->arena->endTime--;
                $this->arena->sendActionBar(Lang::format("endgame_tip", 
                    ["{time}"], 
                    [
                    $this->arena->endTime
                ]));

                if($this->arena->endTime == 0) 
                {
                    $this->arena->finalEnd();
                }

                break;
        }
    }

    public static function formatTime(int $time): string 
    {
        return gmdate("i:s", $time); 
    }
}