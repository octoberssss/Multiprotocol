<?php

declare(strict_types=1);

namespace MultiVersion;

use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\MVRakLibInterface;
use MultiVersion\network\MVRakNetProtocolAcceptor;
use MultiVersion\network\proto\latest\LatestProtocol;
use MultiVersion\network\proto\v361\v361PacketTranslator;
use MultiVersion\network\proto\v486\v486PacketTranslator;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\event\EventPriority;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Webmozart\PathUtil\Path;

class Main extends PluginBase{

	private const PACKET_VIOLATION_WARNING_TYPE = [
		PacketViolationWarningPacket::TYPE_MALFORMED => "MALFORMED",
	];
	private const PACKET_VIOLATION_WARNING_SEVERITY = [
		PacketViolationWarningPacket::SEVERITY_WARNING => "WARNING",
		PacketViolationWarningPacket::SEVERITY_FINAL_WARNING => "FINAL WARNING",
		PacketViolationWarningPacket::SEVERITY_TERMINATING_CONNECTION => "TERMINATION",
	];

	public static string $resourcePath;

	public function onEnable() : void{
		self::$resourcePath = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, Path::join($this->getFile(), "resources")));

		$net = ($server = $this->getServer())->getNetwork();

        $translators = [
			new v361PacketTranslator($server),
			new v486PacketTranslator($server),
			new LatestProtocol($server),
		];

		$regInterface = function(string $ip, int $port, bool $ipv6) use ($server, $translators, $net){
            $rakNetAcceptor = new MVRakNetProtocolAcceptor([9, 10, 11]);
            $interface = new MVRakLibInterface($server, $rakNetAcceptor, $ip, $port, $ipv6);
			foreach($translators as $translator) $interface->registerTranslator($translator);
			$net->registerInterface($interface);
		};
		($regInterface)($server->getIp(), $server->getPort(), false);
		if($server->getConfigGroup()->getConfigBool("enable-ipv6", true)){
			($regInterface)($server->getIpV6(), $server->getPortV6(), true);
		}

		SimplePacketHandler::createMonitor($this)
			->monitorIncoming(function(PacketViolationWarningPacket $pk, NetworkSession $src) : void{
				$severity = self::PACKET_VIOLATION_WARNING_SEVERITY[$pk->getSeverity()];
				$type = self::PACKET_VIOLATION_WARNING_TYPE[$pk->getType()] ?? "UNKNOWN [{$pk->getType()}]";
				$pkID = str_pad(dechex($pk->getPacketId()), 2, "0", STR_PAD_LEFT);
				$this->getLogger()->warning("Received $type Packet Violation ($severity) from {$src->getIp()} message: '{$pk->getMessage()}' Packet ID: 0x$pkID");
			});

		$this->getServer()->getPluginManager()->registerEvent(NetworkInterfaceRegisterEvent::class, function(NetworkInterfaceRegisterEvent $event) : void{
			$interface = $event->getInterface();
			if($interface instanceof MVRakLibInterface || (!$interface instanceof RakLibInterface && !$interface instanceof DedicatedQueryNetworkInterface)){
				return;
			}

			$cls = get_class($interface);
			$this->getLogger()->debug("Prevented network interface $cls from being registered");
			$event->cancel();
		}, EventPriority::NORMAL, $this);
	}

    public function onDisable(): void{
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if(!$this->isLegacyPlayer($player)) continue;

            $ip = $this->getServer()->getIp();
            $port = $this->getServer()->getPort();

            $player->transfer($ip, $port); //prevent 1.12 clients from crashing
        }
    }

    private function isLegacyPlayer(Player $player): bool{
        $ntwrkSession = $player->getNetworkSession();
        if(!$ntwrkSession instanceof MVNetworkSession) return  false;

        $pkTranslator = $ntwrkSession->getPacketTranslator();
        if(($protocol = $pkTranslator::PROTOCOL_VERSION) === ProtocolInfo::CURRENT_PROTOCOL) return false;

        return true;
    }
}
