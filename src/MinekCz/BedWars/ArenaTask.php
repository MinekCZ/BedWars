<?php

namespace MinekCz\BedWars;

use pocketmine\item\ItemIds;
use pocketmine\scheduler\Task;
use pocketmine\player\Player;

class ArenaTask extends Task
{

    public BedWars $palermoTown;
    public Arena $arena;


    /** @var int[] */
    public array $spawners = [];

    public array $spawner_times = [
        3, 10, 30
    ];


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

                if(count($this->arena->players) <= 1) 
                {
                    $this->arena->lobbyTime = 30;
                    $this->arena->sendActionBar(Lang::get("waiting_player"));
                    break;
                }

                
                $this->arena->lobbyTime--;
                
                
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

                foreach($this->arena->toRespawn as $respawn) 
                {
                    $time = $respawn[1];
                    /** @var Player */
                    $player = $respawn[0];
                    
                    $this->arena->toRespawn[$player->getName()][1]--;

                    if($time - 1 == 0) 
                    {
                        $player->sendTitle(Lang::get("respawned"), "", -1, 10, 5);
                        $this->arena->Respawn($player);
                        continue;
                    }

                    $player->sendTitle(Lang::format("respawn_title", ["{time}"], [$time - 1]));
                }

                if($this->arena->gameTime == 0) 
                {
                    $this->arena->endGame();
                }


                for($x = 0; $x < count($this->spawners); $x++) 
                {
                    $this->spawners[$x]--;

                    if($this->spawners[$x] <= 0) 
                    {
                        $this->spawners[$x] = $this->spawner_times[$x];
                        $this->arena->SpawnIngots($x);
                    }
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

        foreach($this->arena->players + $this->arena->spectators as $player) 
        {
            $this->arena->score->Display($player);
        }
    }

    public static function formatTime(int $time): string 
    {
        return gmdate("i:s", $time); 
    }
}