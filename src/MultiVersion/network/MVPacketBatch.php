<?php

namespace MultiVersion\network;

use Generator;
use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;

class MVPacketBatch{

	public function __construct(
		private string $buffer
	){

	}

	/**
	 * Constructs a packet batch from the given list of packets.
	 *
	 * @param PacketSerializerFactory|null $factory
	 * @param Packet                       ...$packets
	 *
	 * @return PacketBatch
	 */
	public static function rawFromPackets(?PacketSerializerFactory $factory, Packet ...$packets) : PacketBatch{
		if($factory === null){
			$context = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
			$serializer = PacketSerializer::encoder($context);
		}else{
			$context = $factory->newSerializerContext();
			$serializer = $factory->newEncoder($context);
		}

		foreach($packets as $packet){
			/** @var ClientboundPacket $packet */
			$timings = Timings::getEncodeDataPacketTimings($packet);
			$timings->startTiming();
			try{
				$subSerializer = $factory === null ? PacketSerializer::encoder($context) : $factory->newEncoder($context);
				$packet->encode($subSerializer);
				$serializer->put($subSerializer->getBuffer());
			}finally{
				$timings->stopTiming();
			}
		}
		return new PacketBatch($serializer->getBuffer());
	}

	/**
	 * Constructs a packet batch from the given list of packets.
	 *
	 * @param PacketSerializerFactory|null $factory
	 * @param Packet                       ...$packets
	 *
	 * @return PacketBatch
	 */
	public static function fromPackets(?PacketSerializerFactory $factory, Packet ...$packets) : PacketBatch{
		if($factory === null){
			$context = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
			$serializer = PacketSerializer::encoder($context);
		}else{
			$context = $factory->newSerializerContext();
			$serializer = $factory->newEncoder($context);
		}

		foreach($packets as $packet){
			/** @var ClientboundPacket $packet */
			$timings = Timings::getEncodeDataPacketTimings($packet);
			$timings->startTiming();
			try{
				$subSerializer = $factory === null ? PacketSerializer::encoder($context) : $factory->newEncoder($context);
				$packet->encode($subSerializer);
				$serializer->putString($subSerializer->getBuffer());
			}finally{
				$timings->stopTiming();
			}
		}
		return new PacketBatch($serializer->getBuffer());
	}

	/**
	 * @return Generator|Packet[]|null[]
	 * @phpstan-return Generator<int, array{?Packet, string}, void, void>
	 * @throws PacketDecodeException
	 */
	public function getPackets(MVNetworkSession $session, PacketSerializerContext $decoderContext, int $max) : Generator{
		$serializer = self::newDecoder($session, $this->buffer, 0, $decoderContext);
		for($c = 0; $c < $max and !$serializer->feof(); ++$c){
			try{
				$buffer = $serializer->getString();
				yield $c => [$session->getPacketPool()->getPacket($buffer), $buffer];
			}catch(BinaryDataException $e){
				throw new PacketDecodeException("Error decoding packet $c of batch: " . $e->getMessage(), 0, $e);
			}
		}
		if(!$serializer->feof()){
			throw new PacketDecodeException("Reached limit of $max packets in a single batch");
		}
	}

	public function getBuffer() : string{
		return $this->buffer;
	}

	private static function newDecoder(MVNetworkSession $session, string $buffer, int $offset, PacketSerializerContext $context) : PacketSerializer{
		$trans = $session->getPacketTranslator();
		if($trans !== null){
			return $trans->getPacketSerializerFactory()->newDecoder($buffer, $offset, $trans->getPacketSerializerFactory()->newSerializerContext());
		}
		return PacketSerializer::decoder($buffer, $offset, $context);
	}
}