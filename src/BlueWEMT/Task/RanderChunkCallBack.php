<?php
namespace BlueWEMT\Task;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\player;
use BlueWEMT\main;
class RanderChunkCallBack extends Task
{
    protected $plugin;
    protected $level;
    protected $Player;
    protected $Chunk;
    public function __construct(main $plugin, Level $level,Chunk $Chunk,Player $player)
    {
        $this->plugin = $plugin;
        $this->level = $level;
        $this->Chunk = $Chunk;
        $this->Player = $player;
    }

    /**
     * @param $currentTicks
     */
    public function onRun($currentTicks)
    {
        if(isset($this->plugin)){
            $this->plugin->API->RerenderChunkCallBack($this->level,$this->Chunk,$this->Player);
        }
    }
}