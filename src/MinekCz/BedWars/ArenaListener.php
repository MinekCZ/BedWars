<?php


namespace MinekCz\BedWars;

use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\Villager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\PlayerCraftingInventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\world\Position;

class ArenaListener implements Listener
{

    public Arena $arena;
    public BedWars $bedwars;

    public array $lastDamage = [];


    public function __construct(BedWars $bedWars, Arena $arena)
    {
        $this->arena       = $arena;
        $this->bedwars     = $bedWars;
    }


    public function HitByEntity(EntityDamageByEntityEvent $event) 
    {
        $damager = $event->getDamager();
        $player  = $event->getEntity();

        if($player instanceof Villager) 
        {
            if(!$damager instanceof Player) { return; }
            if($damager->getWorld() != $this->arena->game_world) { return; }
            if(!$this->arena->IsInArena($damager)) { return; }
            
            Shop::Use($damager);

            $event->cancel();

            return;
        }

        if(!$player instanceof Player) return;
        if(!$this->arena->IsInArena($player)) return;

        if($this->arena->state != Arena::state_game) 
        {
            $event->cancel();
            return;
        }

        

        if($this->arena->teams->GetTeam($player) == $this->arena->teams->GetTeam($damager)) 
        {
            $event->cancel();
            return;
        }

        if($player->getHealth() <= $event->getFinalDamage()) 
        {
            $event->cancel();
            $this->arena->KillPlayer($player, $damager, $event->getCause() == EntityDamageByEntityEvent::CAUSE_PROJECTILE);
            return;
        }

        $this->lastDamage[$player->getName()] = [$damager, $this->arena->gameTime];
    }

    public function EntityDamage(EntityDamageEvent $event) 
    {
        $player  = $event->getEntity();

        if($player instanceof Villager) 
        {
            if($player->getWorld() != $this->arena->game_world) { return; };

            $event->cancel();

            return;
        }

        if(!$player instanceof Player) return;
        if(!$this->arena->IsInArena($player)) return;
        if($this->arena->state != Arena::state_game) 
        {
            $event->cancel();
            if($event->getCause() == EntityDamageEvent::CAUSE_VOID) 
            {
                $this->arena->Respawn($player);
            }
            return;
        }

        
        
        if($player->getHealth() <= $event->getFinalDamage()) 
        {
            $event->cancel();

            if(isset($this->lastDamage[$player->getName()])) 
            {
                $a = $this->lastDamage[$player->getName()];
                /** @var Player */
                $damager = $a[0];
                $time = $a[1];


                if($time - $this->arena->gameTime < 30) 
                {
                    $this->arena->KillPlayer($player, $damager, true);
                    unset($this->lastDamage[$player->getName()]);
                    return;
                }
            }
            $this->arena->KillPlayer($player, null, $event->getCause() == EntityDamageEvent::CAUSE_VOID);
            return;
        }


    }

    public function ProjectileHitBlock(ProjectileHitBlockEvent $event) 
    {
        if($this->arena->IsInArena($event->getEntity()->getOwningEntity())) $event->getEntity()->flagForDespawn();
    }

    public function ProjectileHit(ProjectileHitEvent $event) 
    {
        if($this->arena->IsInArena($event->getEntity()->getOwningEntity())) $event->getEntity()->flagForDespawn();
    }

    public function BlockBreak(BlockBreakEvent $event)  
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;

        $block = BedWars::VecToString($event->getBlock()->getPosition());
        $player = $event->getPlayer();
        
        
        if($event->getBlock()->getId() == BlockLegacyIds::BED_BLOCK) 
        {
            $breakBed = false;
            foreach($this->arena->data["teambed"] as $k => $pos) 
            {
                if($block == $pos) 
                {
                    $bool = $this->arena->DestroyBed($player, $k);

                    if($bool) { $breakBed = true; }
                }

            }

            if(!$breakBed) { $event->cancel(); }
        }
    }

    public function BlockPlace(BlockPlaceEvent $event) 
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;
    }

    public function Hunger(PlayerExhaustEvent $event) 
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;
    }

    public function OnDrop(PlayerDropItemEvent $event) 
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;
    }

    public function OnInteract(PlayerInteractEvent $event) 
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;

        if($event->getBlock()->getId() == BlockLegacyIds::CRAFTING_TABLE) 
        {
            $event->cancel();
        }
    }

    public function OnVillagerMove(EntityMotionEvent $event) 
    {
        return;
        $entity = $event->getEntity();

        if(!$entity instanceof Villager) { return; }
        if($entity->getWorld() != $this->arena->game_world) { return; }
    }
}