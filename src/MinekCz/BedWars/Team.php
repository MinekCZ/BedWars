<?php

namespace MinekCz\BedWars;

use pocketmine\player\Player;

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
