<?php


namespace MinekCz\BedWars;

use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class ArenaListener implements Listener
{

    public Arena       $arena;
    public BedWars $BedWars;


    public function __construct(BedWars $BedWars, Arena $arena)
    {
        $this->arena       = $arena;
        $this->BedWars = $BedWars;
    }


    public function HitByEntity(EntityDamageByEntityEvent $event) 
    {
        $damager = $event->getDamager();
        $player  = $event->getEntity();

        if(!$player instanceof Player) return;
        if(!$this->arena->IsInArena($player)) return;


        if($damager instanceof Player) 
        {
            //Murder::
            if($damager == $this->arena->murder) 
            {
                if($damager->getInventory()->getItemInHand()->getId() != ItemIds::IRON_SWORD) 
                {
                    $event->cancel();
                    return;
                }

                $this->arena->KillPlayer($player, $damager);
                return;
            }

            if($damager != $this->arena->murder) 
            {
                if($event->getCause() != EntityDamageByEntityEvent::CAUSE_PROJECTILE) 
                {
                    $event->cancel();
                    return;
                }
                $role = $this->arena->GetRole($player);

                
                $this->arena->KillPlayer($player, $damager);

                if($role != "Murder") 
                {
                    $this->arena->KillPlayer($damager, null);
                }
                $this->arena->CheckPlayers();
                return;
            }
            
        }

        $event->cancel();
    }

    public function EntityDamage(EntityDamageEvent $event) 
    {
        if(!$event->getEntity() instanceof Player) return;
        if($this->arena->IsInArena($event->getEntity())) $event->cancel();
    }

    public function ProjectileHitBlock(ProjectileHitBlockEvent $event) 
    {
        if($this->arena->IsInArena($event->getEntity()->getOwningEntity())) $event->getEntity()->flagForDespawn();
    }

    public function ProjectileHit(ProjectileHitEvent $event) 
    {
        if($this->arena->IsInArena($event->getEntity()->getOwningEntity())) $event->getEntity()->flagForDespawn();
    }

    public function ProjectileCreate(ProjectileLaunchEvent $event) 
    {
        $entity = $event->getEntity()->getOwningEntity();

        if($this->arena->IsInArena($entity) && $entity == $this->arena->sherif) 
        {
            $this->arena->sherifBow = 8;
        }
    }

    public function BlockBreak(BlockBreakEvent $event)  
    {
        if($this->arena->IsInArena($event->getPlayer())) $event->cancel();
    }

    public function BlockPlace(BlockPlaceEvent $event) 
    {
        if($this->arena->IsInArena($event->getPlayer())) $event->cancel();
    }

    public function Hunger(PlayerExhaustEvent $event) 
    {
        if($this->arena->IsInArena($event->getPlayer())) $event->cancel();
    }

    public function OnDrop(PlayerDropItemEvent $event) 
    {
        if($this->arena->IsInArena($event->getPlayer())) $event->cancel();
    }

    public function OnPickUp(EntityItemPickupEvent $event) 
    {
        $entity = $event->getEntity();
        $item =   $event->getItem();

        if(!$entity instanceof Player) return;
        if(!$this->arena->IsInArena($entity)) return;

        if($entity == $this->arena->murder || $entity == $this->arena->sherif) 
        {
            $event->cancel();
            return;
        }

        if($item->getId() != ItemIds::BOW) 
        {
            $event->cancel();
            return;
        }

        if($this->arena->sherif != null) 
        {
            $event->cancel();
            return;
        }

        foreach($this->arena->game_world->getEntities() as $ii) 
        {
            if($ii instanceof ItemEntity) 
            {
                $ii->flagForDespawn();
            }
        }


        $this->arena->sherif = $entity;
        $this->arena->sherifBow = 1;
        $event->cancel();

    }


    //Chest interact::
    public function OnInteract(PlayerInteractEvent $event) 
    {
        if(!$this->arena->IsInArena($event->getPlayer())) return;
        $player = $event->getPlayer();

        if($event->getBlock()->getId() == BlockLegacyIds::CHEST) 
        {
            $event->cancel();
            $player->getInventory()->addItem($this->arena->GetItem(ItemIds::GOLD_INGOT, 0, 1, Lang::get("item_ingot")));
            $block = $this->arena->GetBlock(BlockLegacyIds::AIR, 0);
            $this->arena->game_world->setBlock($event->getBlock()->getPosition(), $block, true);
            return;
        }

        if($event->getItem()->getId() == ItemIds::GOLD_INGOT) 
        {
            $item = $event->getItem();

            if($item->getCount() >= $this->arena->data["arrow_price"]) 
            {

                if($player == $this->arena->murder) 
                {
                    $player->sendMessage(Lang::get("item_cannot_use"));
                    $event->cancel();
                    return;
                }


                $item->setCount($item->getCount() - $this->arena->data["arrow_price"]);
                if($item->getCount() > 0) 
                {
                    $player->getInventory()->setItemInHand($item);
                } else 
                {
                    $player->getInventory()->setItemInHand(ItemFactory::air()); 
                }

                
                $event->cancel();

                $player->getInventory()->addItem($this->arena->GetItem(ItemIds::ARROW, 0, 1, Lang::get("item_arrow")));

                $player->sendMessage(Lang::get("item_bought_arrow"));

                return;
            }
        }
    }
}