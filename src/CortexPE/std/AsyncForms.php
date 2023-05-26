<?php

namespace CortexPE\std;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\ModalForm;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

// huge thanks to SOF3!!
class AsyncForms
{
    public static function custom(Player $player, string $title, array $elements): \Generator
    {
        $f = yield Await::RESOLVE;
        $player->sendForm(new CustomForm(
            $title, $elements,
            function (Player $player, CustomFormResponse $result) use ($f): void {
                $f($result);
            },
            function (Player $player) use ($f): void {
                $f(null);
            }
        ));
        return yield Await::ONCE;
    }

    public static function menu(Player $player, string $title, string $text, array $options): \Generator
    {
        $f = yield Await::RESOLVE;
        $player->sendForm(new MenuForm(
            $title, $text, $options,
            function (Player $player, int $selectedOption) use ($f): void {
                $f($selectedOption);
            },
            function (Player $player) use ($f): void {
                $f(null);
            }
        ));
        return yield Await::ONCE;
    }

    public static function modal(Player $player, string $title, string $text, string $yesButtonText = "gui.yes", string $noButtonText = "gui.no"): \Generator
    {
        $f = yield Await::RESOLVE;
        $player->sendForm(new ModalForm(
            $title, $text,
            function (Player $player, bool $choice) use ($f): void {
                $f($choice);
            },
            $yesButtonText, $noButtonText
        ));
        return yield Await::ONCE;
    }
}