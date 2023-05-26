<?php

namespace MultiVersion\network\proto\chunk\serializer;

use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\world\format\Chunk;

class MultiLayeredChunkSerializer implements MVChunkSerializer{
	use MVChunkSerializerCommon;

	public function getPaddingSize(Chunk $chunk) : int{
		return 0; // no padding, we don't support below-bedrock on older versions
	}

	public function serializeFullChunk(Chunk $chunk, PacketSerializerFactory $factory, ?string $tiles = null) : string{
		$stream = $factory->newEncoder($factory->newSerializerContext());
		$subChunkCount = $this->getSubChunkCount($chunk);
		for($y = 0; $y < $subChunkCount; ++$y){
			$this->serializeSubChunk($chunk->getSubChunk($y), $factory->getBlockMapping(), $stream, false);
		}
		$stream->put($chunk->getBiomeIdArray());
		$stream->putByte(0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if($tiles !== null){
			$stream->put($tiles);
		}else{
			$stream->put($this->serializeTiles($chunk));
		}
		return $stream->getBuffer();
	}
}