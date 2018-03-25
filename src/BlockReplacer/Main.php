<?php
/**
 * Created by PhpStorm.
 * User: angel
 * Date: 3/24/18
 * Time: 10:34 PM
 */

namespace BlockReplacer;


use pocketmine\block\Block;
use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\scheduler\Task;


/**
 * Class Main
 * @package BlockReplacer
 */
class Main extends PluginBase implements Listener {

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(is_file($this->getDataFolder() . 'resources/' . 'config.yml') == false){
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

		if($found == true && $seconds !== null){

			$this->getServer()->getScheduler()->scheduleDelayedTask(new class($event->getBlock()) extends Task{

				/** @var Block  */
				private $block;

				/**
				 *  constructor.
				 * @param Block $block
				 */
				public function __construct(Block $block) {
					$this->block = $block;
				}

				/**
				 * @param int $currentTick
				 */
				public function onRun(int $currentTick) {
					$this->block->getLevel()->setBlock($this->block->asVector3(), $this->block);
				}

			}, $seconds * 20);
		}
	}
}
