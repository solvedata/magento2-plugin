<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Sales;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class OrderCancel extends EventAbstract
{
    /**
     * Before process event method
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     */
    protected function beforeProcess(Observer $observer): EventAbstract
    {
        parent::beforeProcess($observer);

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        try {
            if (empty($order->getState())) {
                $this->logger->info('order state is empty for order ' . $order->getEntityId());
                throw new \Exception('Order state is empty');
            }
        } catch (\Exception $e) {
            $this->logger->warning($e);
        }

        return $this;
    }

    /**
     * Get observer data
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     *
     * @throws \Exception
     */
    public function prepareData(Observer $observer): EventAbstract
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        // Load addresses if addresses is null
        $order->getAddresses();

        $this->setAffectedEntityId((int)$order->getEntityId())
            ->setAffectedIncrementId($order->getIncrementId())
            ->setPayload([
                'order'                => $order,
                'orderAllVisibleItems' => $order->getAllVisibleItems(),
                'area'                 => $this->eventHelper->getAreaPayloadData($order->getStoreId()),
            ]);

        return $this;
    }
}
