<?php
/**
 * Created by PhpStorm.
 * User: angel
 * Date: 3/24/18
 * Time: 10:34 PM
 */

namespace BlockReplacer;

use pocketmine\{
	block\Block,  
	utils\Config,
	event\Listener, 
	scheduler\Task, 
	plugin\PluginBase, 
	event\block\BlockPlaceEvent, 
	event\block\BlockBreakEvent,
};

/**
 * Class Main
 * @package BlockReplacer
 */
class Main extends PluginBase implements Listener {

	/** @var string[] */
	public $blocks = [];

	/** @var Config */
	private $cfg;

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(is_dir($this->getDataFolder()) == false) mkdir($this->getDataFolder());

		$this->cfg = new Config($this->getDataFolder() . 'config.yml', Config::YAML, [
			'blocks-replacing' => [
				'7:0:5'
			]
		]);
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlock(BlockBreakEvent $event){
		if($event->isCancelled()) return;

		$block = $event->getBlock();

		$found = false;
		$seconds = null;

		foreach($this->cfg->get('blocks-replacing') as $data){
			$param = explode(':', $data);


			if($param[0] == $block->getId()){
				if($param[1] == 0 or $param[1] == $block->getDamage()){
					$seconds = $param[2] == null ? 5 : $param[2];
					$found = true;
				}
			}

			if($found) break;
		}


		if($found){

			$this->blocks[] = $block->getX().':'.$block->getY().':'.$block->getZ();


			$this->getServer()->getScheduler()->scheduleDelayedTask(new class($block, $this) extends Task{

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

		if(isset($newTable[$coords])){
			$event->getPlayer()->sendMessage("There's currently a block being replaced here!");
			$event->setCancelled();
		}
	}
}
