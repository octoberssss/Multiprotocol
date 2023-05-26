<?php


namespace CortexPE\std;


use muqsit\simplepackethandler\utils\ClosureSignatureParser;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\event\RegisteredListener;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class SingleUseEventListener
{
    use SingletonTrait;

    /** @var RegisteredListener[] */
    private $listeners = [];

    /**
     * @param Plugin $registrant
     * @param \Closure $closure Provide a listener that returns true (to terminate the listener) and false (to continue listening)
     * @param int $priority
     * @param bool $handleCancelled
     * @return RegisteredListener
     * @throws \ReflectionException
     */
    public function waitEvent(Plugin $registrant, \Closure $closure, int $priority = EventPriority::NORMAL, bool $handleCancelled = false): RegisteredListener
    {
        [$evName] = ClosureSignatureParser::parse($closure, [Event::class], "bool");

        $handlerName = Utils::getNiceClosureName($closure);
        if (!$registrant->isEnabled()) {
            throw new PluginException("Plugin attempted to register event handler " . $handlerName . "() to event " . $evName . " while not enabled");
        }
        $timings = new TimingsHandler("Plugin: " . $registrant->getDescription()->getFullName() . " Event: " . $handlerName . "(" . (new \ReflectionClass($evName))->getShortName() . ")");

        $k = microtime(true) . "-" . uniqid();
        $regListener = new RegisteredListener(function (Event $ev) use ($closure, $evName, $handlerName, $k): void {
            if (!($closure)($ev)) return;
            HandlerListManager::global()->getListFor($evName)->unregister($this->listeners[$handlerName . $k]);
            unset($this->listeners[$handlerName . $k]);
        }, $priority, $registrant, $handleCancelled, $timings);

        $this->listeners[$handlerName . $k] = $regListener;

        HandlerListManager::global()->getListFor($evName)->register($regListener);

        return $regListener;
    }

    public function unregister(RegisteredListener $listener): void
    {
        $k = array_search($listener, $this->listeners, true);
        if ($k === false) return;
        [$evName] = ClosureSignatureParser::parse($listener->getHandler(), [Event::class], "bool");
        HandlerListManager::global()->getListFor($evName)->unregister($this->listeners[$k]);
        unset($this->listeners[$k]);
    }
}