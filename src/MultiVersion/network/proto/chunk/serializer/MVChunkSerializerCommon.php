<?php

namespace MultiVersion\network\proto\chunk\serializer;

use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;

trait MVChunkSerializerCommon{

	public function getSubChunkCount(Chunk $chunk) : int{
		for($count = count($chunk->getSubChunks()); $count > 0; --$count){
			if($chunk->getSubChunk($count - 1)->isEmptyFast()){
				continue;
			}
			return $count;
		}

		return 0;
	}

	public function serializeTiles(Chunk $chunk) : string{
		$stream = new BinaryStream();
		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$stream->put($tile->getSerializedSpawnCompound()->getEncodedNbt());
			}
		}

		return $stream->getBuffer();
	}

	public function serializeSubChunk(SubChunk $subChunk, IRuntimeBlockMapping $blockMapper, PacketSerializer $stream, bool $persistentBlockStates) : void{
		$layers = $subChunk->getBlockLayers();
		$stream->putByte(8); //version

		$stream->putByte(count($layers));

		foreach($layers as $blocks){
			$bitsPerBlock = $blocks->getBitsPerBlock();
			$words = $blocks->getWordArray();
			$stream->putByte(($bitsPerBlock << 1) | ($persistentBlockStates ? 0 : 1));
			$stream->put($words);
			$palette = $blocks->getPalette();

			if($bitsPerBlock !== 0){
				//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
				//but since we know they are always unsigned, we can avoid the extra fcall overhead of
				//zigzag and just shift directly.
				$stream->putUnsignedVarInt(count($palette) << 1); //yes, this is intentionally zigzag
			}
			if($persistentBlockStates){
				$nbtSerializer = new NetworkNbtSerializer();
				foreach($palette as $p){
					$stream->put($nbtSerializer->write(new TreeRoot($blockMapper->getBedrockKnownStates()[$blockMapper->toRuntimeId($p >> 4, $p & 0xf)])));
				}
			}else{
				foreach($palette as $p){
					$stream->put(Binary::writeUnsignedVarInt($blockMapper->toRuntimeId($p >> 4, $p & 0xf) << 1));
				}
			}
		}
	}
}