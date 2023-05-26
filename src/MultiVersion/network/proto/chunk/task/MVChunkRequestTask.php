<?php

namespace MultiVersion\network\proto\chunk\task;

use Closure;
use MultiVersion\network\MVPacketBatch;
use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;

class MVChunkRequestTask extends AsyncTask{

	private const TLS_KEY_PROMISE = "promise";
	private const TLS_KEY_ERROR_HOOK = "errorHook";

	protected string $chunk;
	protected int $chunkX;
	protected int $chunkZ;

	protected Compressor $compressor;
	protected int $pkSzFacID;

	private string $tiles;

	/**
	 * @param int                  $chunkX
	 * @param int                  $chunkZ
	 * @param Chunk                $chunk
	 * @param CompressBatchPromise $promise
	 * @param Compressor           $compressor
	 * @param int                  $pkSzFacID
	 * @param Closure|null         $onError
	 */
	public function __construct(int $chunkX, int $chunkZ, Chunk $chunk, CompressBatchPromise $promise, Compressor $compressor, int $pkSzFacID, ?Closure $onError = null){
		$this->compressor = $compressor;
		$this->pkSzFacID = $pkSzFacID;
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->tiles = ChunkSerializer::serializeTiles($chunk);

		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
		$this->storeLocal(self::TLS_KEY_ERROR_HOOK, $onError);
	}

	public function onRun() : void{
		/** @var PacketSerializerFactory $pksFactory */
		$pksFactory = $this->worker->getFromThreadStore($this->pkSzFacID);
		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
		$chunkSerializer = $pksFactory->getChunkSerializer();
		$subCount = $chunkSerializer->getSubChunkCount($chunk) + $chunkSerializer->getPaddingSize($chunk);
		$payload = $chunkSerializer->serializeFullChunk($chunk, $pksFactory, $this->tiles);

		$this->setResult($this->compressor->compress(MVPacketBatch::fromPackets($pksFactory, LevelChunkPacket::create(new ChunkPosition($this->chunkX, $this->chunkZ), $subCount, false, null, $payload))->getBuffer()));
	}

	public function onError() : void{
		/**
		 * @var Closure|null                    $hook
		 * @phpstan-var (Closure() : void)|null $hook
		 */
		$hook = $this->fetchLocal(self::TLS_KEY_ERROR_HOOK);
		if($hook !== null){
			$hook();
		}
	}

	public function onCompletion() : void{
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}
}
