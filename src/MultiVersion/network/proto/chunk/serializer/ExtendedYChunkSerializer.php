<?php

namespace MultiVersion\network\proto\chunk\serializer;

use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\LegacyBiomeIdToStringIdMap;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\utils\Binary;
use pocketmine\world\format\Chunk;

class ExtendedYChunkSerializer implements MVChunkSerializer{
	use MVChunkSerializerCommon;

	public function serializeFullChunk(Chunk $chunk, PacketSerializerFactory $factory, ?string $tiles = null) : string{
		$stream = $factory->newEncoder($factory->newSerializerContext());

		//TODO: HACK! fill in fake subchunks to make up for the new negative space client-side
		$padding = $this->getPaddingSize($chunk);
		for($y = 0; $y < $padding; $y++){
			$stream->putByte(8); //subchunk version 8
			$stream->putByte(0); //0 layers - client will treat this as all-air
		}

		$subChunkCount = $this->getSubChunkCount($chunk);
		for($y = 0; $y < $subChunkCount; ++$y){
			$this->serializeSubChunk($chunk->getSubChunk($y), $factory->getBlockMapping(), $stream, false);
		}

		//TODO: right now we don't support 3D natively, so we just 3Dify our 2D biomes so they fill the column
		$encodedBiomePalette = self::serializeBiomesAsPalette($chunk);
		$stream->put(str_repeat($encodedBiomePalette, 24));

		$stream->putByte(0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if($tiles !== null){
			$stream->put($tiles);
		}else{
			$stream->put($this->serializeTiles($chunk));
		}
		return $stream->getBuffer();
	}

	public function getPaddingSize(Chunk $chunk) : int{
		return match ($chunk->getBiomeId(7, 7)) {
			BiomeIds::HELL, BiomeIds::THE_END => 0,
			default => ChunkSerializer::LOWER_PADDING_SIZE
		};
	}

	private static function serializeBiomesAsPalette(Chunk $chunk) : string{
//		$biomeIdMap = LegacyBiomeIdToStringIdMap::getInstance();
//		$biomePalette = new PalettedBlockArray($chunk->getBiomeId(0, 0));
//		for($x = 0; $x < 16; ++$x){
//			for($z = 0; $z < 16; ++$z){
//				$biomeId = $chunk->getBiomeId($x, $z);
//				if($biomeIdMap->legacyToString($biomeId) === null){
//					//make sure we aren't sending bogus biomes - the 1.18.0 client crashes if we do this
//					$biomeId = BiomeIds::OCEAN;
//				}
//				for($y = 0; $y < 16; ++$y){
//					$biomePalette->set($x, $y, $z, $biomeId);
//				}
//			}
//		}
//
//		$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
//		$encodedBiomePalette =
//			chr(($biomePaletteBitsPerBlock << 1) | 1) . //the last bit is non-persistence (like for blocks), though it has no effect on biomes since they always use integer IDs
//			$biomePalette->getWordArray();
//
//		//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
//		//but since we know they are always unsigned, we can avoid the extra fcall overhead of
//		//zigzag and just shift directly.
//		$biomePaletteArray = $biomePalette->getPalette();
//		if($biomePaletteBitsPerBlock !== 0){
//			$encodedBiomePalette .= Binary::writeUnsignedVarInt(count($biomePaletteArray) << 1);
//		}
//		foreach($biomePaletteArray as $p){
//			$encodedBiomePalette .= Binary::writeUnsignedVarInt($p << 1);
//		}
//
//		return $encodedBiomePalette;
        return "";
	}
}