<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\PacketPool;

class v361PacketPool extends PacketPool{

	public function __construct(){
		parent::__construct();
		// override other packets
		$this->registerPacket(new v361ActorFallPacket());
		$this->registerPacket(new v361ActorPickRequestPacket());
		$this->registerPacket(new v361AdventureSettingsPacket());
		$this->registerPacket(new v361CommandRequestPacket());
		$this->registerPacket(new v361ContainerClosePacket());
		$this->registerPacket(new v361InventoryTransactionPacket());
		$this->registerPacket(new v361MapInfoRequestPacket());
		$this->registerPacket(new v361ModalFormResponsePacket());
		$this->registerPacket(new v361MovePlayerPacket());
		$this->registerPacket(new v361NpcRequestPacket());
		$this->registerPacket(new v361PlayerActionPacket());
		$this->registerPacket(new v361PlayerSkinPacket());
		$this->registerPacket(new v361SetActorDataPacket());
        $this->registerPacket(new v361RequestChunkRadiusPacket());
	}
}