<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\MVPacketBroadcaster;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\Server;

abstract class PacketTranslator{

	public const PROTOCOL_VERSION = null;
	protected PacketSerializerFactory $pkSerializerFactory;
	protected MVPacketBroadcaster $broadcaster;

	public function __construct(Server $server){
		$this->broadcaster = new MVPacketBroadcaster($this, $server);
	}

	public function getBroadcaster() : MVPacketBroadcaster{
		return $this->broadcaster;
	}

	abstract public function setup(MVNetworkSession $session) : void;

	abstract public function handleIncoming(ServerboundPacket $pk) : ?ServerboundPacket;

	abstract public function handleOutgoing(ClientboundPacket $pk) : ?ClientboundPacket;

	abstract public function handleInGame(NetworkSession $session) : ?InGamePacketHandler;

	public function getPacketSerializerFactory() : PacketSerializerFactory{
		return $this->pkSerializerFactory;
	}

	abstract public function injectClientData(array &$data) : void;
}