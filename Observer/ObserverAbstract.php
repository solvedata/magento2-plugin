<?php

namespace SolveData\Events\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

abstract class ObserverAbstract implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EventAbstract
     */
    protected $handler;

    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param EventAbstract $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        EventAbstract $handler
    ) {
        $this->config = $config;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
        $this->handler = $handler;
    }

    /**
     * Execute event
     *
     * @param Observer $observer
     *
     * @return ObserverAbstract
     */
    public function execute(Observer $observer): ObserverAbstract
    {
        try {
            if (!$this->config->isEnabledEvent($observer->getEvent()->getName())) {
                return $this;
            }

            $this->handler->process($observer);
        } catch (\Throwable $throwable) {
            $this->logger->debug('Failed to process event');
            $this->logger->error($throwable);
        }

        return $this;
    }
}
