<?php

namespace MultiVersion\network\proto\latest;

use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\v361\packets\v361InventoryTransactionPacket;
use MultiVersion\network\proto\v361\v361InGamePacketHandler;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\Server;

class LatestProtocol extends PacketTranslator{

	public const PROTOCOL_VERSION = ProtocolInfo::CURRENT_PROTOCOL;

	private LatestRuntimeBlockMappingWrapper $blockMapping;

	public function __construct(Server $server){
		parent::__construct($server);

		$this->blockMapping = new LatestRuntimeBlockMappingWrapper();
		$this->pkSerializerFactory = new LatestPacketSerializerFactory($this->blockMapping);
	}

	public function setup(MVNetworkSession $session) : void{
	}

	public function handleIncoming(ServerboundPacket $pk) : ?ServerboundPacket{
		return $pk;
	}

	public function handleOutgoing(ClientboundPacket $pk) : ?ClientboundPacket{
		return $pk;
	}

	public function handleInGame(NetworkSession $session) : ?InGamePacketHandler{
        return new LatestProtocolGameHandler($session->getPlayer(), $session, $session->getInvManager());
	}

	public function injectClientData(array &$data) : void{
		// TODO: Implement injectClientData() method.
	}
}