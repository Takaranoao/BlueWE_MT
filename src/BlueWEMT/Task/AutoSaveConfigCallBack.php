<?php
namespace BlueWEMT\Task;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\player;
use BlueWEMT\main;
class AutoSaveConfigCallBack extends Task
{
    protected $plugin;
    public function __construct(main $plugin)
    {
        $this->plugin = $plugin;
        $plugin->API->RerenderChunkTaskRunning = true;
    }

    /**
     * @param $currentTicks
     */
    public function onRun($currentTicks)
    {
        if(isset($this->plugin)){
            $this->plugin->API->AutoSaveConfigCallBack();
        }
    }
}