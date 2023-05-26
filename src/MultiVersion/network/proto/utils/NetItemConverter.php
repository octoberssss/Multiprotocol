<?php

namespace MultiVersion\network\proto\utils;

use Exception;
use MultiVersion\network\proto\static_resources\GenericItemTranslator;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;

final class NetItemConverter{

	private const DAMAGE_TAG = "Damage"; //TAG_Int
	private const DAMAGE_TAG_CONFLICT_RESOLUTION = "___Damage_ProtocolCollisionResolution___";
	private const PM_ID_TAG = "___Id___";
	private const PM_META_TAG = "___Meta___";

	public static function convertToProtocol(ItemStackWrapper $source, IRuntimeBlockMapping $blockMapping, GenericItemTranslator $itemTranslator = null) : ItemStackWrapper{
		return ItemStackWrapper::legacy(self::convertItemStackToProtocol($source->getItemStack(), $blockMapping, $itemTranslator));
	}

	public static function convertItemStackToProtocol(ItemStack $itemStack, IRuntimeBlockMapping $blockMapping, ?GenericItemTranslator $itemTranslator) : ItemStack{
		return self::convertItemToProtocol(TypeConverter::getInstance()->netItemStackToCore($itemStack), $blockMapping, $itemTranslator);
	}

	public static function convertItemToProtocol(Item $item, IRuntimeBlockMapping $blockMapping, ?GenericItemTranslator $itemTranslator) : ItemStack{
		if($item->isNull()){
			return ItemStack::null();
		}

		$nbt = null;
		if($item->hasNamedTag()){
			$nbt = clone $item->getNamedTag();
		}

		$isBlockItem = ($id = $item->getId()) < 256;
		$meta = $item->getMeta();

		if($itemTranslator !== null){
			$idMeta = $itemTranslator->toNetworkIdQuiet($item->getId(), $item->getMeta());
			if($idMeta === null){
				//Display unmapped items as INFO_UPDATE, but stick something in their NBT to make sure they don't stack with
				//other unmapped items.
				[$id, $meta] = $itemTranslator->toNetworkId(ItemIds::INFO_UPDATE, 0);
				if($nbt === null){
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::PM_ID_TAG, $item->getId());
				$nbt->setInt(self::PM_META_TAG, $item->getMeta());
			}else{
				[$id, $meta] = $idMeta;

				if($item instanceof Durable && $item->getDamage() > 0){
					if($nbt !== null){
						if(($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null){
							$nbt->removeTag(self::DAMAGE_TAG);
							$nbt->setTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION, $existing);
						}
					}else{
						$nbt = new CompoundTag();
					}
					$nbt->setInt(self::DAMAGE_TAG, $item->getDamage());
					$meta = 0;
				}elseif($isBlockItem && $item->getMeta() !== 0){
					//TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
					//client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
					//client-side. Aside from being very annoying, this also breaks various server-side behaviours.
					if($nbt === null){
						$nbt = new CompoundTag();
					}
					$nbt->setInt(self::PM_META_TAG, $item->getMeta());
					$meta = 0;
				}
			}
		}

		$blockRuntimeId = 0;
		if($isBlockItem){
			$block = $item->getBlock();
			if($block->getId() !== BlockLegacyIds::AIR){
				$blockRuntimeId = self::convertBlockRuntimeID(RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId()), $blockMapping);
			}
		}

		return new ItemStack(
			$id,
			$meta,
			$item->getCount(),
			$blockRuntimeId,
			$nbt,
			$item->getCanPlaceOn(),
			$item->getCanDestroy(),
			$item->getId() === ItemIds::SHIELD ? 0 : null
		);
	}

	public static function convertBlockRuntimeID(int $runtimeID, IRuntimeBlockMapping $blockMapping) : int{
		try{
			$fullID = RuntimeBlockMapping::getInstance()->fromRuntimeId($runtimeID);
		}catch(Exception $e){
			$fullID = BlockLegacyIds::INFO_UPDATE << 4;
		}
		return $blockMapping->toRuntimeId($fullID >> 4, $fullID & 0xf);
	}

	public static function item2ItemStack(Item $item) : ItemStack{
		return new ItemStack(
			$item->getId(), $item->getMeta(), $item->getCount(),
			$item->getBlock()->getFullId(), $item->getNamedTag(),
			$item->getCanPlaceOn(), $item->getCanDestroy(), $item->getId() === ItemIds::SHIELD ? 0 : null
		);
	}

	public static function itemStack2Item(ItemStack $item) : Item{
		return ItemFactory::getInstance()->get($item->getId(), $item->getMeta(), $item->getCount(), $item->getNbt());
	}

	public static function item2RecipeIngredient(Item $item) : RecipeIngredient{
		return new RecipeIngredient(new IntIdMetaItemDescriptor($item->getId(), $item->getMeta()), $item->getCount());
	}
}
