<?php
/**
 * Created by PhpStorm.
 * User: angel
 * Date: 3/24/18
 * Time: 10:34 PM
 */

namespace BlockReplacer;


use pocketmine\block\Block;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\scheduler\Task;


/**
 * Class Main
 * @package BlockReplacer
 */
class Main extends PluginBase implements Listener {
	
	/** @var string[] */
	public $blocks = [];
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(is_file($this->getDataFolder() . 'config.yml') == false){
			$data = [
				'blocks-replacing' => [
					'7:0:5'
				]
			];
			$this->getConfig()->setAll($data);
			$this->getConfig()->save();
		}
		$this->getConfig()->save();
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlock(BlockBreakEvent $event){
		if($event->isCancelled()) return;

		$found = false;
		$seconds = null;
		foreach($this->getConfig()->get('blocks-replacing') as $data){
			$param = explode(':', $data);

			if($param[0] == $event->getBlock()->getId()){

				if($param[1] == 0){
					$found = true;
					break;
				}

				if($param[1] == $event->getBlock()->getDamage()) $found = true;
				if($param[2] !== null) $seconds = $param[2];

				break;
			}
		}
		
		$block = $event->getBlock();
		
		$this->blocks[] = $block->getX().':'.$block->getY().':'.$block->getZ();
		
		if($found == true && $seconds !== null){
			
			$plugin = $this;
			
			$this->getServer()->getScheduler()->scheduleDelayedTask(new class($block, $plugin) extends Task{

				/** @var Block  */
				private $block;
				
				private $plugin;
				
				/**
				 *  constructor.
				 * @param Block $block
				 * @param Main $plugin
				 */
				public function __construct(Block $block, Main $plugin) {
					$this->block = $block;
					$this->plugin = $plugin;
				}

				/**
				 * @param int $currentTick
				 */
				public function onRun(int $currentTick) {
					$this->block->getLevel()->setBlock($this->block->asVector3(), $this->block);
					
					foreach($this->plugin->blocks as $key => $value){
						$param = explode(':', $value);
						
						if($param[0] == $this->block->x)
							if($param[1] == $this->block->y)
								if($param[2] == $this->block->z) unset($this->plugin->blocks[$key]);
					}
				}

			}, $seconds * 20);
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event){
		$newTable = [];
		foreach($this->blocks as $key => $value){
			$newTable[$value] = $key;
		}
		
		$block = $event->getBlock();
		$coords = $block->getX().':'.$block->getY().':'.$block->getZ();
		
		if(isset($this->blocks[$newTable[$coords]])){
			$event->getPlayer()->sendMessage("There's currently a block being replaced here!");
			$event->setCancelled();
		}
	}
}
