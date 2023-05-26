<?php

namespace MultiVersion\network\proto\v361;

use Closure;
use http\Env\Request;
use MultiVersion\Main;
use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\proto\compressor\v9ZlibCompressor;
use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\utils\NetItemConverter;
use MultiVersion\network\proto\v361\packets\types\inventory\v361NormalTransactionData;
use MultiVersion\network\proto\v361\packets\v361AddActorPacket;
use MultiVersion\network\proto\v361\packets\v361AddPlayerPacket;
use MultiVersion\network\proto\v361\packets\v361AdventureSettingsPacket;
use MultiVersion\network\proto\v361\packets\v361AvailableActorIdentifiersPacket;
use MultiVersion\network\proto\v361\packets\v361AvailableCommandsPacket;
use MultiVersion\network\proto\v361\packets\v361BiomeDefinitionListPacket;
use MultiVersion\network\proto\v361\packets\v361ContainerClosePacket;
use MultiVersion\network\proto\v361\packets\v361CraftingDataPacket;
use MultiVersion\network\proto\v361\packets\v361HurtArmorPacket;
use MultiVersion\network\proto\v361\packets\v361InventoryTransactionPacket;
use MultiVersion\network\proto\v361\packets\v361MovePlayerPacket;
use MultiVersion\network\proto\v361\packets\v361NetworkChunkPublisherUpdatePacket;
use MultiVersion\network\proto\v361\packets\v361PacketPool;
use MultiVersion\network\proto\v361\packets\v361PhotoTransferPacket;
use MultiVersion\network\proto\v361\packets\v361PlayerActionPacket;
use MultiVersion\network\proto\v361\packets\v361PlayerListPacket;
use MultiVersion\network\proto\v361\packets\v361PlayerSkinPacket;
use MultiVersion\network\proto\v361\packets\v361RequestChunkRadiusPacket;
use MultiVersion\network\proto\v361\packets\v361ResourcePackChunkDataPacket;
use MultiVersion\network\proto\v361\packets\v361ResourcePacksInfoPacket;
use MultiVersion\network\proto\v361\packets\v361ResourcePackStackPacket;
use MultiVersion\network\proto\v361\packets\v361RespawnPacket;
use MultiVersion\network\proto\v361\packets\v361SetActorDataPacket;
use MultiVersion\network\proto\v361\packets\v361SetSpawnPositionPacket;
use MultiVersion\network\proto\v361\packets\v361SetTitlePacket;
use MultiVersion\network\proto\v361\packets\v361SpawnParticleEffectPacket;
use MultiVersion\network\proto\v361\packets\v361StartGamePacket;
use MultiVersion\network\proto\v361\packets\v361UpdateAttributesPacket;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\HurtArmorPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\StructureTemplateDataResponsePacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use Webmozart\PathUtil\Path;

class v361PacketTranslator extends PacketTranslator{
	public const PROTOCOL_VERSION = 361;

	private v361PacketPool $packetPool;
	private v361CraftingDataPacket $craftingDP;
	private InventoryContentPacket $creativeContent;
	private v361RuntimeBlockMapping $blockMapping;
	private array $itemMapping;

	public function __construct(Server $server){
		parent::__construct($server);
		$this->packetPool = new v361PacketPool();
		$this->craftingDP = new v361CraftingDataPacket();
		$this->craftingDP->cleanRecipes = true;
		$this->creativeContent = InventoryContentPacket::create(121, array_map(function(Item $item){
			return ItemStackWrapper::legacy(NetItemConverter::item2ItemStack($item));
		}, CreativeInventory::getInstance()->getAll()));
		$this->blockMapping = new v361RuntimeBlockMapping();
		$this->pkSerializerFactory = new v361PacketSerializerFactory(GlobalItemTypeDictionary::getInstance()->getDictionary(), $this->blockMapping);
		$this->itemMapping = json_decode(file_get_contents(Path::join(Main::$resourcePath, "v361", "item_id_map.json")), true);

		$itemDeserializerFunc = Closure::fromCallable([Item::class, "jsonDeserialize"]);
		$recipes = json_decode(file_get_contents(Path::join(Main::$resourcePath, "v361", "recipes.json")), true);
		foreach($recipes as $k => $recipe){
			switch($recipe["type"]){
				case "shapeless":
					if($recipe["block"] !== "crafting_table"){
						break;
					}
					$this->craftingDP->recipesWithTypeIds[] = new ShapelessRecipe(
						CraftingDataPacket::ENTRY_SHAPELESS,
						pack("N", $k),
						array_map([NetItemConverter::class, "item2RecipeIngredient"], array_map($itemDeserializerFunc, $recipe["input"])),
						array_map([NetItemConverter::class, "item2ItemStack"], array_map($itemDeserializerFunc, $recipe["output"])),
						Uuid::fromBytes(random_bytes(16)),
						"crafting_table",
						50,
						0
					);
					break;
				case "shaped":
					if($recipe["block"] !== "crafting_table"){
						break;
					}
					$dec = new \pocketmine\crafting\ShapedRecipe(
						$recipe["shape"],
						array_map($itemDeserializerFunc, $recipe["input"]),
						array_map($itemDeserializerFunc, $recipe["output"])
					);
					$inputs = [];

					for($row = 0, $height = $dec->getHeight(); $row < $height; ++$row){
						for($column = 0, $width = $dec->getWidth(); $column < $width; ++$column){
							$inputs[$row][$column] = NetItemConverter::item2RecipeIngredient($dec->getIngredient($column, $row));
						}
					}
					$this->craftingDP->recipesWithTypeIds[] = new ShapedRecipe(
						CraftingDataPacket::ENTRY_SHAPED,
						pack("N", $k),
						$inputs,
						array_map([NetItemConverter::class, "item2ItemStack"], $dec->getResults()),
						Uuid::fromBytes(random_bytes(16)),
						"crafting_table",
						50,
						0
					);
					break;
				case "smelting":
					if($recipe["block"] !== "furnace"){
						break;
					}
					$in = Item::jsonDeserialize($recipe["input"]);
					$out = Item::jsonDeserialize($recipe["output"]);
					$this->craftingDP->recipesWithTypeIds[] = new FurnaceRecipe(
						$in->hasAnyDamageValue() ? CraftingDataPacket::ENTRY_FURNACE : CraftingDataPacket::ENTRY_FURNACE_DATA,
						$in->getId(), $in->getMeta(),
						NetItemConverter::item2ItemStack($out),
						"furnace"
					);
					break;
			}
		}
	}

	public function setup(MVNetworkSession $session) : void{
		$session->setCompressor(v9ZlibCompressor::getInstance());
		$session->setPacketPool($this->packetPool);
		EncryptionContext::$ENABLED = false; //TODO: Implement encryption
	}

	//Client => Server
	public function handleIncoming(ServerboundPacket $pk) : ?ServerboundPacket{

        if($pk instanceof v361ContainerClosePacket){
			if($pk->windowId === 255) $pk->windowId = WindowTypes::WORKBENCH;
			return $pk;
		}
		if($pk instanceof v361PlayerActionPacket){
			if($pk->action === 13) $pk->action = PlayerAction::RESPAWN;
			return $pk;
		}
        if($pk instanceof InventoryTransactionPacket){
            var_dump("NEW INVV PACK 361");
            return v361InventoryTransactionPacket::create($pk->requestId, $pk->requestChangedSlots, $pk->trData);
        }
		if($pk instanceof v361PlayerSkinPacket){
			$pk->skin = SkinAdapterSingleton::get()->toSkinData($pk->_skin);
			return $pk;
		}
        if($pk instanceof v361RequestChunkRadiusPacket){
            $pk->maxRadius = 32;
            return $pk;
        }
		return $pk;
	}

	//Server => Client
	public function handleOutgoing(ClientboundPacket $pk) : ?ClientboundPacket{
		if($pk instanceof AddActorPacket) return v361AddActorPacket::fromLatest($pk);
		if($pk instanceof AddItemActorPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping);
			return $pk;
		}
        if($pk instanceof InventoryTransactionPacket){
            var_dump("NEW INVV PACK 361 XDDD");
            return v361InventoryTransactionPacket::create($pk->requestId, $pk->requestChangedSlots, $pk->trData);
        }
		if($pk instanceof AddPlayerPacket) return v361AddPlayerPacket::fromLatest($pk, $this->blockMapping);
		if($pk instanceof AvailableActorIdentifiersPacket) return new v361AvailableActorIdentifiersPacket();
		if($pk instanceof AvailableCommandsPacket) return v361AvailableCommandsPacket::fromLatest($pk);
		if($pk instanceof BiomeDefinitionListPacket) return new v361BiomeDefinitionListPacket();
		if($pk instanceof ContainerClosePacket) return v361ContainerClosePacket::fromLatest($pk);
		if($pk instanceof CraftingDataPacket) return clone $this->craftingDP;
		if($pk instanceof CreativeContentPacket) return $this->creativeContent;
		if($pk instanceof HurtArmorPacket) return v361HurtArmorPacket::fromLatest($pk);
		if($pk instanceof InventoryContentPacket){
			$pk->items = array_map(function(ItemStackWrapper $netItemStack) : ItemStackWrapper{
				return NetItemConverter::convertToProtocol($netItemStack, $this->blockMapping);
			}, $pk->items);
			return $pk;
		}
		if($pk instanceof InventorySlotPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping);
			return $pk;
		}
		if($pk instanceof LevelEventPacket){
			if($pk->eventId === LevelEvent::PARTICLE_DESTROY){
				$pk->eventData = NetItemConverter::convertBlockRuntimeID($pk->eventData, $this->blockMapping);
			}
			return $pk;
		}
		if($pk instanceof LevelSoundEventPacket){
			if(($pk->sound === LevelSoundEvent::BREAK && $pk->extraData !== -1) || $pk->sound === LevelSoundEvent::PLACE || $pk->sound === LevelSoundEvent::HIT || $pk->sound === LevelSoundEvent::LAND || $pk->sound === LevelSoundEvent::ITEM_USE_ON){
				$pk->extraData = NetItemConverter::convertBlockRuntimeID($pk->extraData, $this->blockMapping);
			}
			return $pk;
		}
		if($pk instanceof MobArmorEquipmentPacket){
			$pk->head = NetItemConverter::convertToProtocol($pk->head, $this->blockMapping);
			$pk->chest = NetItemConverter::convertToProtocol($pk->chest, $this->blockMapping);
			$pk->legs = NetItemConverter::convertToProtocol($pk->legs, $this->blockMapping);
			$pk->feet = NetItemConverter::convertToProtocol($pk->feet, $this->blockMapping);
			return $pk;
		}
		if($pk instanceof MobEquipmentPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping);
			return $pk;
		}
		if($pk instanceof MovePlayerPacket) return v361MovePlayerPacket::fromLatest($pk);
		if($pk instanceof NetworkChunkPublisherUpdatePacket) return v361NetworkChunkPublisherUpdatePacket::fromLatest($pk);
		if($pk instanceof PhotoTransferPacket) return v361PhotoTransferPacket::fromLatest($pk);
		if($pk instanceof PlayerListPacket) return v361PlayerListPacket::fromLatest($pk);
		if($pk instanceof PlayerSkinPacket) return v361PlayerSkinPacket::fromLatest($pk);
		if($pk instanceof ResourcePackChunkDataPacket) return v361ResourcePackChunkDataPacket::fromLatest($pk);
		if($pk instanceof ResourcePacksInfoPacket) return v361ResourcePacksInfoPacket::fromLatest($pk);
		if($pk instanceof ResourcePackStackPacket) return v361ResourcePackStackPacket::fromLatest($pk);
		if($pk instanceof RespawnPacket) return v361RespawnPacket::fromLatest($pk);
		if($pk instanceof SetActorDataPacket) return v361SetActorDataPacket::fromLatest($pk);
		if($pk instanceof SetSpawnPositionPacket) return v361SetSpawnPositionPacket::fromLatest($pk);
		if($pk instanceof SetTitlePacket) return v361SetTitlePacket::fromLatest($pk);
		if($pk instanceof SpawnParticleEffectPacket) return v361SpawnParticleEffectPacket::fromLatest($pk);
		if($pk instanceof StartGamePacket) return v361StartGamePacket::fromLatest($pk, $this->blockMapping, $this->itemMapping);
		if($pk instanceof StructureTemplateDataResponsePacket) return $pk;
		if($pk instanceof UpdateAbilitiesPacket){
			foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
				$player = $world->getPlayers()[$pk->getData()->getTargetActorUniqueId()] ?? null;
				if($player === null) continue;
				if($player->getId() === $pk->getData()->getTargetActorUniqueId()){
					$npk = v361AdventureSettingsPacket::create(0, $pk->getData()->getCommandPermission(), -1, $pk->getData()->getPlayerPermission(), 0, $pk->getData()->getTargetActorUniqueId());
					if(isset($pk->getData()->getAbilityLayers()[0])){
						$abilities = $pk->getData()->getAbilityLayers()[0]->getBoolAbilities();
						$npk->setFlag(v361AdventureSettingsPacket::WORLD_IMMUTABLE, $player->isSpectator());
						$npk->setFlag(v361AdventureSettingsPacket::NO_PVP, $player->isSpectator());
						$npk->setFlag(v361AdventureSettingsPacket::AUTO_JUMP, $player->hasAutoJump());
						$npk->setFlag(v361AdventureSettingsPacket::ALLOW_FLIGHT, $abilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT] ?? false);
						$npk->setFlag(v361AdventureSettingsPacket::NO_CLIP, $abilities[AbilitiesLayer::ABILITY_NO_CLIP] ?? false);
						$npk->setFlag(v361AdventureSettingsPacket::FLYING, $abilities[AbilitiesLayer::ABILITY_FLYING] ?? false);
					}
					return $npk;
				}
			}
		}
		if($pk instanceof UpdateAttributesPacket) return v361UpdateAttributesPacket::fromLatest($pk);
		if($pk instanceof UpdateBlockPacket){
			$pk->blockRuntimeId = NetItemConverter::convertBlockRuntimeID($pk->blockRuntimeId, $this->blockMapping);
			return $pk;
		}
		return $pk;
	}

	public function handleInGame(NetworkSession $session) : ?InGamePacketHandler{
		return new v361InGamePacketHandler($session->getPlayer(), $session, $session->getInvManager());
	}

	public function injectClientData(array &$data) : void{
		static $skinSizes = [
			32 * 64 * 4 => [
				"h" => 32,
				"w" => 64,
			],
			64 * 64 * 4 => [
				"h" => 64,
				"w" => 64,
			],
			128 * 128 * 4 => [
				"h" => 128,
				"w" => 128,
			],
		];

		$data["AnimatedImageData"] = [];
		$data["PersonaPieces"] = [];
		$data["PieceTintColors"] = [];
		$data["PlayFabId"] = "";
		$data["ArmSize"] = "";
		$data["CapeId"] = "";
		$data["CapeOnClassicSkin"] = false;
		$data["IsEditorMode"] = false;
		$data["PersonaSkin"] = false;
		$data["SkinAnimationData"] = "";
		$data["SkinColor"] = "#0";
		$data["ThirdPartyNameOnly"] = false;
		$data["TrustedSkin"] = true;
		$data["SkinGeometryDataEngineVersion"] = base64_encode("0.0.0");

		$dims = $skinSizes[strlen(base64_decode($data["SkinData"], true))];
		$data["SkinImageHeight"] = $dims["h"];
		$data["SkinImageWidth"] = $dims["w"];

		$cape = base64_decode($data["CapeData"] ?? "", true);
		$data["CapeImageHeight"] = 0;
		$data["CapeImageWidth"] = 0;
		if(strlen($cape) > 0){
			$dims = $skinSizes[strlen($cape)];
			$data["CapeImageHeight"] = $dims["h"];
			$data["CapeImageWidth"] = $dims["w"];
		}
		$data["SkinResourcePatch"] = base64_encode(json_encode(["geometry" => ["default" => $data["SkinGeometryName"]]]));
		$data["SkinGeometryData"] = $data["SkinGeometry"];
		unset($data["SkinGeometry"]);
		unset($data["SkinGeometryName"]);
	}
}
