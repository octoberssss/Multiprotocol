<?php

namespace MultiVersion\network\proto\chunk;

use GlobalLogger;
use InvalidArgumentException;
use MultiVersion\network\proto\chunk\task\MVChunkRequestTask;
use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkListenerNoOpTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;


/**
 * This class is used by the current MCPE protocol system to store cached chunk packets for fast resending.
 *
 * TODO: make MemoryManager aware of this so the cache can be destroyed when memory is low
 * TODO: this needs a hook for world unloading
 */
class MVChunkCache implements ChunkListener{

	/** @var MVChunkCache[][][] */
	private static array $instances = [];
	/** @var CompressBatchPromise[] */
	private array $caches = [];
	private int $hits = 0;
	private int $misses = 0;

	private function __construct(
		private World $world,
		private Compressor $compressor,
		private PacketSerializerFactory $pkSerializerFactory
	){

	}

	/**
	 * Fetches the ChunkCache instance for the given world. This lazily creates cache systems as needed.
	 *
	 * @param World                   $world
	 * @param Compressor              $compressor
	 * @param PacketSerializerFactory $pkSerializerFactory
	 *
	 * @return MVChunkCache
	 */
	public static function getInstance(World $world, Compressor $compressor, PacketSerializerFactory $pkSerializerFactory) : self{
		$worldId = spl_object_id($world);
		$compressorId = spl_object_id($compressor);
		$factoryId = spl_object_id($pkSerializerFactory);
		if(!isset(self::$instances[$factoryId])){
			GlobalLogger::get()->debug("Created new chunk packet cache (world#$worldId, compressor#$compressorId, packetSerializerFactory#$factoryId)");
			self::$instances[$factoryId] = [];
		}
		if(!isset(self::$instances[$factoryId][$worldId])){
			self::$instances[$factoryId][$worldId] = [];
			$world->addOnUnloadCallback(static function() use ($worldId) : void{
				foreach(self::$instances as $factoryId => $worldCaches){
					foreach($worldCaches[$worldId] ?? [] as $compressorCache){
						$compressorCache->caches = [];
					}
					unset(self::$instances[$factoryId][$worldId]);
					GlobalLogger::get()->debug("Destroyed chunk packet caches for world#$worldId");
				}
			});
		}
		if(!isset(self::$instances[$factoryId][$worldId][$compressorId])){
			self::$instances[$factoryId][$worldId][$compressorId] = new self($world, $compressor, $pkSerializerFactory);
		}
		return self::$instances[$factoryId][$worldId][$compressorId];
	}

	/**
	 * Requests asynchronous preparation of the chunk at the given coordinates.
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return CompressBatchPromise a promise of resolution which will contain a compressed chunk packet.
	 */
	public function request(int $chunkX, int $chunkZ) : CompressBatchPromise{
		$this->world->registerChunkListener($this, $chunkX, $chunkZ);
		$chunk = $this->world->getChunk($chunkX, $chunkZ);
		if($chunk === null){
			throw new InvalidArgumentException("Cannot request an unloaded chunk");
		}
		$chunkHash = World::chunkHash($chunkX, $chunkZ);

		if(isset($this->caches[$chunkHash])){
			++$this->hits;
			return $this->caches[$chunkHash];
		}

		++$this->misses;

		$this->world->timings->syncChunkSendPrepare->startTiming();
		try{
			$this->caches[$chunkHash] = new CompressBatchPromise();

			$this->world->getServer()->getAsyncPool()->submitTask(new MVChunkRequestTask(
				$chunkX,
				$chunkZ,
				$chunk,
				$this->caches[$chunkHash],
				$this->compressor,
				spl_object_id($this->pkSerializerFactory),
				function() use ($chunkX, $chunkZ) : void{
					$this->world->getLogger()->error("Failed preparing chunk $chunkX $chunkZ, retrying");

					$this->restartPendingRequest($chunkX, $chunkZ);
				}
			));
			return $this->caches[$chunkHash];
		}finally{
			$this->world->timings->syncChunkSendPrepare->stopTiming();
		}
	}

	/**
	 * Restarts an async request for an unresolved chunk.
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 */
	private function restartPendingRequest(int $chunkX, int $chunkZ) : void{
		$chunkHash = World::chunkHash($chunkX, $chunkZ);
		$existing = $this->caches[$chunkHash] ?? null;
		if($existing === null or $existing->hasResult()){
			throw new InvalidArgumentException("Restart can only be applied to unresolved promises");
		}
		$existing->cancel();
		unset($this->caches[$chunkHash]);

		$this->request($chunkX, $chunkZ)->onResolve(...$existing->getResolveCallbacks());
	}

	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Chunk $chunk
	 *
	 * @see ChunkListener::onChunkChanged()
	 */
	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		$this->destroyOrRestart($chunkX, $chunkZ);
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 */
	private function destroyOrRestart(int $chunkX, int $chunkZ) : void{
		$cache = $this->caches[World::chunkHash($chunkX, $chunkZ)] ?? null;
		if($cache !== null){
			if(!$cache->hasResult()){
				//some requesters are waiting for this chunk, so their request needs to be fulfilled
				$this->restartPendingRequest($chunkX, $chunkZ);
			}else{
				//dump the cache, it'll be regenerated the next time it's requested
				$this->destroy($chunkX, $chunkZ);
			}
		}
	}

	use ChunkListenerNoOpTrait {
		//force overriding of these
		onChunkChanged as private;
		onBlockChanged as private;
		onChunkUnloaded as private;
	}

	private function destroy(int $chunkX, int $chunkZ) : bool{
		$chunkHash = World::chunkHash($chunkX, $chunkZ);
		$existing = $this->caches[$chunkHash] ?? null;
		unset($this->caches[$chunkHash]);

		return $existing !== null;
	}

	/**
	 * @param Vector3 $block
	 *
	 * @see ChunkListener::onBlockChanged()
	 */
	public function onBlockChanged(Vector3 $block) : void{
		//FIXME: requesters will still receive this chunk after it's been dropped, but we can't mark this for a simple
		//sync here because it can spam the worker pool
		$this->destroy($block->getFloorX() >> Chunk::COORD_BIT_SIZE, $block->getFloorZ() >> Chunk::COORD_BIT_SIZE);
	}

	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Chunk $chunk
	 *
	 * @see ChunkListener::onChunkUnloaded()
	 */
	public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		$this->destroy($chunkX, $chunkZ);
		$this->world->unregisterChunkListener($this, $chunkX, $chunkZ);
	}

	/**
	 * Returns the number of bytes occupied by the cache data in this cache. This does not include the size of any
	 * promises referenced by the cache.
	 */
	public function calculateCacheSize() : int{
		$result = 0;
		foreach($this->caches as $cache){
			if($cache->hasResult()){
				$result += strlen($cache->getResult());
			}
		}
		return $result;
	}

	/**
	 * Returns the percentage of requests to the cache which resulted in a cache hit.
	 */
	public function getHitPercentage() : float{
		$total = $this->hits + $this->misses;
		return $total > 0 ? $this->hits / $total : 0.0;
	}
}