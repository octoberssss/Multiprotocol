<?php

namespace MultiVersion\network\proto\v486;

use CortexPE\std\ReflectionUtils;
use MultiVersion\Main;
use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\static_resources\GenericItemTranslator;
use MultiVersion\network\proto\static_resources\GenericRuntimeBlockMapping;
use MultiVersion\network\proto\utils\NetItemConverter;
use MultiVersion\network\proto\v486\packets\v486AddActorPacket;
use MultiVersion\network\proto\v486\packets\v486AddPlayerPacket;
use MultiVersion\network\proto\v486\packets\v486AddVolumeEntityPacket;
use MultiVersion\network\proto\v486\packets\v486AdventureSettingsPacket;
use MultiVersion\network\proto\v486\packets\v486AvailableCommandsPacket;
use MultiVersion\network\proto\v486\packets\v486ClientboundMapItemDataPacket;
use MultiVersion\network\proto\v486\packets\v486CraftingDataPacket;
use MultiVersion\network\proto\v486\packets\v486NetworkChunkPublisherUpdatePacket;
use MultiVersion\network\proto\v486\packets\v486NetworkSettingsPacket;
use MultiVersion\network\proto\v486\packets\v486PacketPool;
use MultiVersion\network\proto\v486\packets\v486RemoveVolumeEntityPacket;
use MultiVersion\network\proto\v486\packets\v486RequestChunkRadiusPacket;
use MultiVersion\network\proto\v486\packets\v486SetActorDataPacket;
use MultiVersion\network\proto\v486\packets\v486SpawnParticleEffectPacket;
use MultiVersion\network\proto\v486\packets\v486StartGamePacket;
use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\crafting\FurnaceType;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\RemoveVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeContentEntry;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\recipe\CraftingRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe as ProtocolFurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe as ProtocolPotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe as ProtocolPotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe as ProtocolShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe as ProtocolShapelessRecipe;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use RuntimeException;
use Webmozart\PathUtil\Path;

class v486PacketTranslator extends PacketTranslator{

	public const PROTOCOL_VERSION = 486;

	private v486PacketPool $packetPool;

	private v486CraftingDataPacket $craftingDP;
	private CreativeContentPacket $creativeContent;

	private GenericItemTranslator $itemTranslator;

	private GenericRuntimeBlockMapping $blockMapping;

	private BiomeDefinitionListPacket $biomeDefs;

	private AvailableActorIdentifiersPacket $availableActorIdentifiers;

	public function __construct(Server $server){
		parent::__construct($server);
		$this->packetPool = new v486PacketPool();
		$this->craftingDP = new v486CraftingDataPacket();
		$this->craftingDP->cleanRecipes = true;
		$this->itemTranslator = new GenericItemTranslator(
			Path::join(Main::$resourcePath, "v486", "r16_to_current_item_map.json")
		);
		$this->blockMapping = new GenericRuntimeBlockMapping(
			Path::join(Main::$resourcePath, "v486", "canonical_block_states.nbt"),
			Path::join(Main::$resourcePath, "v486", "r12_to_current_block_map.bin"),
		);
		$nextEntryId = 1;
		$this->creativeContent = CreativeContentPacket::create(array_map(function(Item $item) use (&$nextEntryId){
			return new CreativeContentEntry($nextEntryId++, NetItemConverter::convertItemToProtocol($item, $this->blockMapping, $this->itemTranslator));
		}, CreativeInventory::getInstance()->getAll()));
		$this->pkSerializerFactory = new v486PacketSerializerFactory(v486ItemTypeDictionary::getInstance()->getDictionary(), $this->blockMapping);

		$this->biomeDefs = BiomeDefinitionListPacket::create(self::loadCompoundFromFile(Path::join(Main::$resourcePath, "v486", "biome_definitions.nbt")));
		$this->availableActorIdentifiers = AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(Path::join(Main::$resourcePath, "v486", "entity_identifiers.nbt")));

		$manager = CraftingManagerFromDataHelper::make(Path::join(Main::$resourcePath, "v486", "recipes.json"));
		$converter = TypeConverter::getInstance();
		$counter = 0;
		$nullUUID = Uuid::fromString(Uuid::NIL);
		foreach($manager->getShapelessRecipes() as $list){
			foreach($list as $recipe){
				$typeTag = match ($recipe->getType()->id()) {
					ShapelessRecipeType::CRAFTING()->id() => CraftingRecipeBlockName::CRAFTING_TABLE,
					ShapelessRecipeType::STONECUTTER()->id() => CraftingRecipeBlockName::STONECUTTER,
					default => throw new AssumptionFailedError("Unreachable"),
				};
				$this->craftingDP->recipesWithTypeIds[] = new ProtocolShapelessRecipe(
					CraftingDataPacket::ENTRY_SHAPELESS,
					Binary::writeInt(++$counter),
					array_map(function(Item $item) use ($converter) : RecipeIngredient{
						return $converter->coreItemStackToRecipeIngredient($item);
					}, $recipe->getIngredientList()),
					array_map(function(Item $item) use ($converter) : ItemStack{
						return $converter->coreItemStackToNet($item);
					}, $recipe->getResults()),
					$nullUUID,
					$typeTag,
					50,
					$counter
				);
			}
		}
		foreach($manager->getShapedRecipes() as $list){
			foreach($list as $recipe){
				$inputs = [];

				for($row = 0, $height = $recipe->getHeight(); $row < $height; ++$row){
					for($column = 0, $width = $recipe->getWidth(); $column < $width; ++$column){
						$inputs[$row][$column] = $converter->coreItemStackToRecipeIngredient($recipe->getIngredient($column, $row));
					}
				}
				$this->craftingDP->recipesWithTypeIds[] = $r = new ProtocolShapedRecipe(
					CraftingDataPacket::ENTRY_SHAPED,
					Binary::writeInt(++$counter),
					$inputs,
					array_map(function(Item $item) use ($converter) : ItemStack{
						return $converter->coreItemStackToNet($item);
					}, $recipe->getResults()),
					$nullUUID,
					CraftingRecipeBlockName::CRAFTING_TABLE,
					50,
					$counter
				);
			}
		}

		foreach(FurnaceType::getAll() as $furnaceType){
			$typeTag = match ($furnaceType->id()) {
				FurnaceType::FURNACE()->id() => FurnaceRecipeBlockName::FURNACE,
				FurnaceType::BLAST_FURNACE()->id() => FurnaceRecipeBlockName::BLAST_FURNACE,
				FurnaceType::SMOKER()->id() => FurnaceRecipeBlockName::SMOKER,
				default => throw new AssumptionFailedError("Unreachable"),
			};
			foreach($manager->getFurnaceRecipeManager($furnaceType)->getAll() as $recipe){
				$input = $converter->coreItemStackToNet($recipe->getInput());
				$this->craftingDP->recipesWithTypeIds[] = new ProtocolFurnaceRecipe(
					CraftingDataPacket::ENTRY_FURNACE_DATA,
					$input->getId(),
					$input->getMeta(),
					$converter->coreItemStackToNet($recipe->getResult()),
					$typeTag
				);
			}
		}

		foreach($manager->getPotionTypeRecipes() as $recipes){
			foreach($recipes as $recipe){
				$input = $converter->coreItemStackToNet($recipe->getInput());
				$ingredient = $converter->coreItemStackToNet($recipe->getIngredient());
				$output = $converter->coreItemStackToNet($recipe->getOutput());
				$this->craftingDP->potionTypeRecipes[] = new ProtocolPotionTypeRecipe(
					$input->getId(),
					$input->getMeta(),
					$ingredient->getId(),
					$ingredient->getMeta(),
					$output->getId(),
					$output->getMeta()
				);
			}
		}

		foreach($manager->getPotionContainerChangeRecipes() as $recipes){
			foreach($recipes as $recipe){
				$input = ItemTranslator::getInstance()->toNetworkId($recipe->getInputItemId(), 0);
				$ingredient = ItemTranslator::getInstance()->toNetworkId($recipe->getIngredient()->getId(), 0);
				$output = ItemTranslator::getInstance()->toNetworkId($recipe->getOutputItemId(), 0);
				$this->craftingDP->potionContainerRecipes[] = new ProtocolPotionContainerChangeRecipe(
					$input[0],
					$ingredient[0],
					$output[0]
				);
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return CacheableNbt
	 */
	private static function loadCompoundFromFile(string $path) : CacheableNbt{
		$rawNbt = @file_get_contents($path);
		if($rawNbt === false){
			throw new RuntimeException("Failed to read file");
		}
		return new CacheableNbt((new NetworkNbtSerializer())->read($rawNbt)->mustGetCompoundTag());
	}

	public function setup(MVNetworkSession $session) : void{
		$session->setPacketPool($this->packetPool);
	}

	public function handleIncoming(ServerboundPacket $pk) : ?ServerboundPacket{
        if ($pk instanceof RequestChunkRadiusPacket) v486RequestChunkRadiusPacket::fromLatest($pk);
		return $pk;
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleOutgoing(ClientboundPacket $pk) : ?ClientboundPacket{
		if($pk instanceof AddActorPacket) return v486AddActorPacket::fromLatest($pk);
		if($pk instanceof AddItemActorPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping, $this->itemTranslator);
			return $pk;
		}
		if($pk instanceof AddPlayerPacket) return v486AddPlayerPacket::fromLatest($pk, $this->blockMapping, $this->itemTranslator);
		if($pk instanceof AddVolumeEntityPacket) return v486AddVolumeEntityPacket::fromLatest($pk);
		if($pk instanceof AvailableActorIdentifiersPacket) return $this->availableActorIdentifiers;
		if($pk instanceof AvailableCommandsPacket) return v486AvailableCommandsPacket::fromLatest($pk);
		if($pk instanceof BiomeDefinitionListPacket) return $this->biomeDefs;
		if($pk instanceof ClientboundMapItemDataPacket) return v486ClientboundMapItemDataPacket::fromLatest($pk);
		if($pk instanceof CraftingDataPacket) return $this->craftingDP;
		if($pk instanceof CreativeContentPacket) return $this->creativeContent;
		if($pk instanceof InventoryContentPacket){
			$pk->items = array_map(function(ItemStackWrapper $netItemStack) : ItemStackWrapper{
				return NetItemConverter::convertToProtocol($netItemStack, $this->blockMapping, $this->itemTranslator);
			}, $pk->items);
			return $pk;
		}
		if($pk instanceof InventorySlotPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping, $this->itemTranslator);
			return $pk;
		}
		if($pk instanceof InventoryTransactionPacket){
			if($pk->trData instanceof UseItemTransactionData || $pk->trData instanceof UseItemOnEntityTransactionData || $pk->trData instanceof ReleaseItemTransactionData){
				ReflectionUtils::setProperty(get_class($pk->trData), $pk->trData, "itemInHand", NetItemConverter::convertToProtocol($pk->trData->getItemInHand(), $this->blockMapping, $this->itemTranslator));
			}
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
			$pk->head = NetItemConverter::convertToProtocol($pk->head, $this->blockMapping, $this->itemTranslator);
			$pk->chest = NetItemConverter::convertToProtocol($pk->chest, $this->blockMapping, $this->itemTranslator);
			$pk->legs = NetItemConverter::convertToProtocol($pk->legs, $this->blockMapping, $this->itemTranslator);
			$pk->feet = NetItemConverter::convertToProtocol($pk->feet, $this->blockMapping, $this->itemTranslator);
			return $pk;
		}
		if($pk instanceof MobEquipmentPacket){
			$pk->item = NetItemConverter::convertToProtocol($pk->item, $this->blockMapping, $this->itemTranslator);
			return $pk;
		}
		if($pk instanceof NetworkChunkPublisherUpdatePacket) return v486NetworkChunkPublisherUpdatePacket::fromLatest($pk);
		if($pk instanceof NetworkSettingsPacket) return v486NetworkSettingsPacket::fromLatest($pk);
		if($pk instanceof RemoveVolumeEntityPacket) return v486RemoveVolumeEntityPacket::fromLatest($pk);
		if($pk instanceof SetActorDataPacket) return v486SetActorDataPacket::fromLatest($pk);
		if($pk instanceof SpawnParticleEffectPacket) return v486SpawnParticleEffectPacket::fromLatest($pk);
		if($pk instanceof StartGamePacket) return v486StartGamePacket::fromLatest($pk);
		if($pk instanceof UpdateAbilitiesPacket){
			foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
				$player = $world->getPlayers()[$pk->getData()->getTargetActorUniqueId()] ?? null;
				if($player === null) continue;
				if($player->getId() === $pk->getData()->getTargetActorUniqueId()){
					$npk = v486AdventureSettingsPacket::create(0, $pk->getData()->getCommandPermission(), -1, $pk->getData()->getPlayerPermission(), 0, $pk->getData()->getTargetActorUniqueId());
					if(isset($pk->getData()->getAbilityLayers()[0])){
						$abilities = $pk->getData()->getAbilityLayers()[0]->getBoolAbilities();
						$npk->setFlag(v486AdventureSettingsPacket::WORLD_IMMUTABLE, $player->isSpectator());
						$npk->setFlag(v486AdventureSettingsPacket::NO_PVP, $player->isSpectator());
						$npk->setFlag(v486AdventureSettingsPacket::AUTO_JUMP, $player->hasAutoJump());
						$npk->setFlag(v486AdventureSettingsPacket::ALLOW_FLIGHT, $abilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT] ?? false);
						$npk->setFlag(v486AdventureSettingsPacket::NO_CLIP, $abilities[AbilitiesLayer::ABILITY_NO_CLIP] ?? false);
						$npk->setFlag(v486AdventureSettingsPacket::FLYING, $abilities[AbilitiesLayer::ABILITY_FLYING] ?? false);
					}
					return $npk;
				}
			}
		}
		if($pk instanceof UpdateAdventureSettingsPacket) return null;
		if($pk instanceof UpdateBlockPacket){
			$pk->blockRuntimeId = NetItemConverter::convertBlockRuntimeID($pk->blockRuntimeId, $this->blockMapping);
			return $pk;
		}
		return $pk;
	}

	public function handleInGame(NetworkSession $session) : ?InGamePacketHandler{
		return new v486InGamePacketHandler($session->getPlayer(), $session, $session->getInvManager());
	}

	public function injectClientData(array &$data) : void{
		$data["IsEditorMode"] = false;
		$data["TrustedSkin"] = true;
	}
}
