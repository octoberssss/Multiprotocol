<?php


namespace CortexPE\std;


use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

final class ChunkLoadPromise implements ChunkLoader, ChunkListener
{
    /** @var callable[][] */
    private static $beingGenerated = [];
    /** @var World */
    private $world;
    /** @var int */
    private $chunkX;
    /** @var int */
    private $chunkZ;
    /** @var callable */
    private $callback;

    private function __construct(World $world, int $chunkX, int $chunkZ, callable $callback)
    {
        $this->world = $world;
        $this->chunkX = $chunkX;
        $this->chunkZ = $chunkZ;
        $this->callback = $callback;
    }

    public static function create(World $world, int $chunkX, int $chunkZ, callable $callback): void
    {
        if ($world->isChunkPopulated($chunkX, $chunkZ)) {
            ($callback)();
            return;
        }
        $hash = World::chunkHash($chunkX, $chunkZ);
        if (!isset(self::$beingGenerated[$hash])) {
            self::$beingGenerated[$hash] = [];
            $instance = new self($world, $chunkX, $chunkZ, $callback);
            $world->registerChunkLoader($instance, $chunkX, $chunkZ, true);
            $world->registerChunkListener($instance, $chunkX, $chunkZ);
            $world->orderChunkPopulation($chunkX, $chunkZ, $instance);
        } else {
            self::$beingGenerated[$hash][] = $callback;
        }
    }

    public function getX()
    {
        return $this->chunkX;
    }

    public function getZ()
    {
        return $this->chunkZ;
    }

    public function onChunkLoaded(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
        $this->onComplete();
    }

    public function onComplete(): void
    {
        ($this->callback)();
        foreach ((self::$beingGenerated[World::chunkHash($this->chunkX, $this->chunkZ)] ?? []) as $cb) {
            ($cb)();
        }
        $this->world->unregisterChunkLoader($this, $this->chunkX, $this->chunkZ);
        $this->world->unregisterChunkListenerFromAll($this);
    }

    public function onChunkPopulated(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
        $this->onComplete();
    }

    public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
    }

    public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
    }

    public function onBlockChanged(Vector3 $block): void
    {
    }
}