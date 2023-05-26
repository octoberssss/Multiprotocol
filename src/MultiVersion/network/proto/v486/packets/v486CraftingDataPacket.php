<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipeOutput;
use pocketmine\network\mcpe\protocol\types\recipe\MultiRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use function count;

class v486CraftingDataPacket extends CraftingDataPacket{

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
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$inputId = $in->getVarInt();
			$inputMeta = $in->getVarInt();
			$ingredientId = $in->getVarInt();
			$ingredientMeta = $in->getVarInt();
			$outputId = $in->getVarInt();
			$outputMeta = $in->getVarInt();
			$this->potionTypeRecipes[] = new PotionTypeRecipe($inputId, $inputMeta ?? 0, $ingredientId, $ingredientMeta ?? 0, $outputId, $outputMeta ?? 0);
		}
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$input = $in->getVarInt();
			$ingredient = $in->getVarInt();
			$output = $in->getVarInt();
			$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$inputIdAndData = $in->getVarInt();
			[$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
			$outputs = [];
			for($j = 0, $outputCount = $in->getUnsignedVarInt(); $j < $outputCount; ++$j){
				$outputItemId = $in->getVarInt();
				$outputItemCount = $in->getVarInt();
				$outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
			}
			$this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
		}
		$this->cleanRecipes = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->recipesWithTypeIds));
		foreach($this->recipesWithTypeIds as $d){
			$out->putVarInt($d->getTypeId());
			$d->encode($out);
		}
		$out->putUnsignedVarInt(count($this->potionTypeRecipes));
		foreach($this->potionTypeRecipes as $recipe){
			$out->putVarInt($recipe->getInputItemId());
			$out->putVarInt($recipe->getInputItemMeta());
			$out->putVarInt($recipe->getIngredientItemId());
			$out->putVarInt($recipe->getIngredientItemMeta());
			$out->putVarInt($recipe->getOutputItemId());
			$out->putVarInt($recipe->getOutputItemMeta());
		}
		$out->putUnsignedVarInt(count($this->potionContainerRecipes));
		foreach($this->potionContainerRecipes as $recipe){
			$out->putVarInt($recipe->getInputItemId());
			$out->putVarInt($recipe->getIngredientItemId());
			$out->putVarInt($recipe->getOutputItemId());
		}
		$out->putUnsignedVarInt(count($this->materialReducerRecipes));
		foreach($this->materialReducerRecipes as $recipe){
			$out->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
			$out->putUnsignedVarInt(count($recipe->getOutputs()));
			foreach($recipe->getOutputs() as $output){
				$out->putVarInt($output->getItemId());
				$out->putVarInt($output->getCount());
			}
		}
		$out->putBool($this->cleanRecipes);
	}
}