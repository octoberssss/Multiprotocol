<?php

namespace MultiVersion\network;

use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\v361\packets\v361RequestChunkRadiusPacket;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryStream;

class MVPacketBroadcaster implements PacketBroadcaster{

	public function __construct(
		private PacketTranslator $translator,
		private Server $server
	){

	}

	/**
	 * @param MVNetworkSession[]  $recipients
	 * @param ClientboundPacket[] $packets
	 */
	public function broadcastPackets(array $recipients, array $packets) : void{
		$translatedBuffers = [];
		foreach($packets as $packet){
			$pk = $this->translator->handleOutgoing(clone $packet);
			if($pk !== null){
				$translatedBuffers[] = MVPacketBatch::rawFromPackets($this->translator->getPacketSerializerFactory(), $pk)->getBuffer();
			}
		}

		$packetBufferTotalLengths = [];
		$packetBuffers = [];
		$compressors = [];
		/** @var MVNetworkSession[][][] $targetMap */
		$targetMap = [];
		foreach($recipients as $recipient){
			$serializerContext = $recipient->getPacketSerializerContext();
			$bufferId = spl_object_id($serializerContext);
			if(!isset($packetBuffers[$bufferId])){
				$packetBufferTotalLengths[$bufferId] = 0;
				$packetBuffers[$bufferId] = [];
				foreach($translatedBuffers as $buffer){
					$packetBufferTotalLengths[$bufferId] += strlen($buffer);
					$packetBuffers[$bufferId][] = $buffer;
				}
			}

			//TODO: different compressors might be compatible, it might not be necessary to split them up by object
			$compressor = $recipient->getCompressor();
			$compressors[spl_object_id($compressor)] = $compressor;

			$targetMap[$bufferId][spl_object_id($compressor)][] = $recipient;
		}

		foreach($targetMap as $bufferId => $compressorMap){
			foreach($compressorMap as $compressorId => $compressorTargets){
				$compressor = $compressors[$compressorId];

				$threshold = $compressor->getCompressionThreshold();
				if(count($compressorTargets) > 1 && $threshold !== null && $packetBufferTotalLengths[$bufferId] >= $threshold){
					//do not prepare shared batch unless we're sure it will be compressed
					$stream = new BinaryStream();
					PacketBatch::encodeRaw($stream, $packetBuffers[$bufferId]);
					$batchBuffer = $stream->getBuffer();

					$promise = $this->server->prepareBatch(new PacketBatch($batchBuffer), $compressor, timings: Timings::$playerNetworkSendCompressBroadcast);
					foreach($compressorTargets as $target){
						$target->queueCompressed($promise);
					}
				}else{
					foreach($compressorTargets as $target){
						foreach($packetBuffers[$bufferId] as $packetBuffer){
							$target->addToSendBuffer($packetBuffer);
						}
					}
				}
			}
		}
	}
}