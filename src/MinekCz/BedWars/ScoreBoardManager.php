<?php

namespace MinekCz\BedWars;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;


//Credit: https://forums.pmmp.io/threads/how-to-properly-use-scoreboards-and-especially-updates.10136/
//Msg by: nexTRushh

class ScoreBoardManager 
{

    public BedWars $bedWars;
    public Arena $arena;

    public string $id;
    

    public function __construct(BedWars $bedWars, Arena $arena)
    {
        $this->arena = $arena;
        $this->bedWars = $bedWars;

        $this->id = "bw-" . $this->arena->data["id"];
    }
    



    public function Display(Player $player)
    {
        $this->Update($player);
            

        $this->SetLine($player, 1, Lang::format("sb_map", ["{map}"], [$this->arena->data["name"]]));
        $this->SetLine($player, 2, Lang::format("sb_time", ["{time}"], [$this->arena->GetTime(true)]));
        $this->SetLine($player, 3, Lang::format("sb_team", ["{team}"], [$this->arena->GetTeamPretty($player)]));
        $this->SetLine($player, 4, "   ");
        
        $line = 4;
        foreach($this->arena->teams->teams as $team) 
        {
            $line++;

            //$this->SetLine($player, $line, ($team->display . "§7: ". $team->alive_p . " " . ($team->bed ? " §a✓" : " §c✕")));
            $this->SetLine($player, $line,   Lang::format("sb_teams",   ["{team}", "{count}", "{bedAlive}"], [$team->display, $team->alive_p, ($team->bed ? Lang::get("sb_alive") : Lang::get("sb_not_alive")) ]   )   );
        }

        //$this->SetLine($player, $line+1, "   ");
        $this->SetLine($player, $line+1, Lang::get("sb_description"));
        
    }
    







    public function SetLine(Player $player, int $score, string $text)
    {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $this->id;
        $entry->type = 3;
        $entry->customName = " $text   ";
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $pk = new SetScorePacket();
        $pk->type = 0;
        $pk->entries[$score] = $entry;
        
        //$player->sendDataPacket($pk);
        $player->getNetworkSession()->sendDataPacket($pk);
    }


    public function Update(Player $player) 
    {
        $this->RemoveScoreBoard($player);

        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $this->id;
        $pk->displayName = Lang::get("prefix");
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function RemoveScoreBoard(Player $player) 
    {
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $this->id;
        $player->getNetworkSession()->sendDataPacket($pk);
    }


}