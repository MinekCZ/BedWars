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

                $this->arena->CalculatePercents();

                
                $this->arena->lobbyTime--;

                foreach($this->arena->players as $player) 
                {
                    $player->sendActionBarMessage(Lang::format("lobby_starting_tip", 
                    ["{murder_percent}", "{sherif_percent}", "{time}"], 
                    [
                    $this->arena->percents_final[$player->getName()]["murder"], 
                    $this->arena->percents_final[$player->getName()]["sherif"], 
                    $this->arena->lobbyTime
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
                    $this->arena->preGameTime
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
                        ["{role}", "{time}"], 
                        [
                        $this->arena->GetRolePretty($player), 
                        $this->arena->gameTime
                    ]));
                }

                if($this->arena->sherifBow > 0 && $this->arena->sherif != null) 
                {
                    $this->arena->sherifBow--;
                    

                    if($this->arena->sherif->getInventory()->getItemInHand()->getId() == ItemIds::BOW) 
                    {
                        // 385 max
                        $p = ($this->arena->sherifBow / 8 * 100);
                        $d = (385 / 100) * $p;

                        $this->arena->sherif->getInventory()->setItemInHand($this->arena->GetItem(ItemIds::BOW, $d, 1, Lang::get("item_sherif_bow")));
                    }

                    if($this->arena->sherifBow == 0) 
                    {
                        $this->arena->sherif->getInventory()->addItem($this->arena->GetItem(ItemIds::ARROW, 0, 1, Lang::get("item_arrow")));
                    }
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