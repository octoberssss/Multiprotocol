<?php

namespace MultiVersion\network;

use CortexPE\std\ReflectionUtils;
use GlobalLogger;
use MultiVersion\network\proto\PacketTranslator;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\PthreadsChannelWriter;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\raklib\RakLibPacketSender;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\Network;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use raklib\server\ipc\RakLibToUserThreadMessageReceiver;
use raklib\server\ipc\UserToRakLibThreadMessageSender;
use raklib\server\ProtocolAcceptor;
use raklib\utils\InternetAddress;
use RuntimeException;
use Threaded;

final class MVRakLibInterface extends RakLibInterface{

	private static array $addedFactories = [];
	private Server $_server;
	private Network $_network;
	/** @var PacketTranslator[] */
	private array $mcpeTranslators = [];

	// todo: maybe raknet??
	/** @var int[] */
	private array $allowedMCPEProtocols = [];

	public function __construct(Server $server, ProtocolAcceptor $acceptor, string $ip, int $port, bool $ipV6){
		$this->_server = $server;
        $packetSerializerContext = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
        $packetBroadcaster = new StandardPacketBroadcaster($server, $packetSerializerContext);
        $entityEventBroadcaster = new StandardEntityEventBroadcaster($packetBroadcaster);
		parent::__construct(
            $server,
            $ip,
            $port,
            $ipV6,
            $packetBroadcaster,
            $entityEventBroadcaster,
            $packetSerializerContext
        );

		$mainToThreadBuffer = new Threaded;
		$threadToMainBuffer = new Threaded;
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "rakLib", new MVRakLibServer(
			$server->getLogger(),
			$mainToThreadBuffer,
			$threadToMainBuffer,
			new InternetAddress($ip, $port, $ipV6 ? 6 : 4),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "rakServerId"),
			$acceptor,
			(int) $server->getConfigGroup()->getProperty("network.max-mtu-size", 1492),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "sleeper")
		));
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "eventReceiver", new RakLibToUserThreadMessageReceiver(
			new PthreadsChannelReader($threadToMainBuffer)
		));
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "interface", new UserToRakLibThreadMessageSender(
			new PthreadsChannelWriter($mainToThreadBuffer)
		));
	}

	public function registerTranslator(PacketTranslator $translator) : void{
		if($translator::PROTOCOL_VERSION === null){
			throw new RuntimeException("Unknown protocol version for translator " . get_class($translator));
		}
		if(in_array($translator::PROTOCOL_VERSION, $this->allowedMCPEProtocols)){
			throw new RuntimeException("Translator for version " . $translator::PROTOCOL_VERSION . " already exists");
		}
		$this->mcpeTranslators[$translator::PROTOCOL_VERSION] = $translator;
		$this->allowedMCPEProtocols[] = $translator::PROTOCOL_VERSION;

		// todo: make this IPv6 compatible, move this out of here... and perhaps add a "TranslatorManager" kinda crap,
		//  then we just pass references to translators from RakLib interfaces...
		//  for now, we just put a static var and check if we've already added start hooks for this translator's factory ID...
		$aPool = $this->_server->getAsyncPool();
		$factoryId = spl_object_id($pkSerializerFactory = $translator->getPacketSerializerFactory());
		if(isset(self::$addedFactories[$factoryId])) return;
		self::$addedFactories[$factoryId] = true;
		$aPool->addWorkerStartHook(function(int $workerId) use ($aPool, $factoryId, $pkSerializerFactory) : void{
			$aPool->submitTaskToWorker(new class($factoryId, $pkSerializerFactory) extends AsyncTask{
				public function __construct(private $factoryId, private $pkSerializerFactory){
				}

				public function onRun() : void{
					GlobalLogger::get()->debug("Storing thread-local PacketSerializerFactory instance for packetSerializerFactory#$this->factoryId");
					$this->worker->saveToThreadStore($this->factoryId, $this->pkSerializerFactory);
				}
			}, $workerId);
		});
	}

	/**
	 * @return int[]
	 */
	public function getAllowedMCPEProtocols() : array{
		return $this->allowedMCPEProtocols;
	}

	public function getTranslator(int $protocolVersion) : ?PacketTranslator{
		return $this->mcpeTranslators[$protocolVersion] ?? null;
	}

	public function setNetwork(Network $network) : void{
		$this->_network = $network;
		parent::setNetwork($network);
	}

	public function onClientConnect(int $sessionId, string $address, int $port, int $clientID) : void{
        $packetSerializerContext = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
        $packetBroadcaster = new StandardPacketBroadcaster($this->_server, $packetSerializerContext);

		$session = new MVNetworkSession(
			$this->_server,
			$this,
			$this->_network->getSessionManager(),
			new RakLibPacketSender($sessionId, $this),
            $packetBroadcaster,
			$address,
			$port
		);
		$sessions = ReflectionUtils::getProperty(RakLibInterface::class, $this, "sessions");
		$sessions[$sessionId] = $session;
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "sessions", $sessions);
	}
}