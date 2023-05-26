<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MultiRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use UnexpectedValueException;

class v361CraftingDataPacket extends CraftingDataPacket{

	public static function fromLatest(CraftingDataPacket $pk) : ?self{
		$npk = new self();
		$npk->recipesWithTypeIds = $pk->recipesWithTypeIds;
		$npk->cleanRecipes = $pk->cleanRecipes;
		if(count($pk->recipesWithTypeIds) < 1) return null;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$recipeCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $recipeCount; ++$i){
			$recipeType = $in->getVarInt();

			$this->recipesWithTypeIds[] = match ($recipeType) {
				self::ENTRY_SHAPELESS, self::ENTRY_SHULKER_BOX, self::ENTRY_SHAPELESS_CHEMISTRY => ShapelessRecipe::decode($recipeType, $in),
				self::ENTRY_SHAPED, self::ENTRY_SHAPED_CHEMISTRY => ShapedRecipe::decode($recipeType, $in),
				self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => FurnaceRecipe::decode($recipeType, $in),
				self::ENTRY_MULTI => MultiRecipe::decode($recipeType, $in),
				default => throw new PacketDecodeException("Unhandled recipe type $recipeType!"),
			};
		}
		$this->cleanRecipes = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->recipesWithTypeIds));
		foreach($this->recipesWithTypeIds as $d){
			$out->putVarInt($d->getTypeId());
			switch(true){
				case ($d instanceof ShapelessRecipe):
					$out->putString($d->getRecipeId());
					$out->putUnsignedVarInt(count($d->getInputs()));
					foreach($d->getInputs() as $item){
						$out->putRecipeIngredient($item);
					}

					$out->putUnsignedVarInt(count($d->getOutputs()));
					foreach($d->getOutputs() as $item){
						$out->putItemStackWithoutStackId($item);
					}

					$out->put(str_repeat("\x00", 16)); //Null UUID
					$out->putString($d->getBlockName());
					$out->putVarInt($d->getPriority());
					break;
				case ($d instanceof ShapedRecipe):
					$out->putString($d->getRecipeId());
					$out->putVarInt($d->getWidth());
					$out->putVarInt($d->getHeight());
					foreach($d->getInput() as $row){
						foreach($row as $ingredient){
							$out->putRecipeIngredient($ingredient);
						}
					}

					$out->putUnsignedVarInt(count($d->getOutput()));
					foreach($d->getOutput() as $item){
						$out->putItemStackWithoutStackId($item);
					}

					$out->put(str_repeat("\x00", 16)); //Null UUID
					$out->putString($d->getBlockName());
					$out->putVarInt($d->getPriority());
					break;
				case ($d instanceof FurnaceRecipe):
					$out->putVarInt($d->getInputId());
					if($d->getTypeId() === self::ENTRY_FURNACE_DATA){
						$out->putVarInt($d->getInputMeta());
					}
					$out->putItemStackWithoutStackId($d->getResult());
					$out->putString($d->getBlockName());
					break;
				case ($d instanceof MultiRecipe):
					$out->putUUID($d->getRecipeId());
					break;
				default:
					throw new UnexpectedValueException("Unhandled recipe type {$d->getTypeId()}!");
			}
		}

		$out->putBool($this->cleanRecipes);
	}
}