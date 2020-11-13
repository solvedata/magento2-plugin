<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class CartSave extends EventAbstract
{
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
        /** @var Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $quote = $cart->getQuote();
        $quoteAllVisibleItems = $quote->getAllVisibleItems();

        // Add final price to payload
        foreach ($quote->getAllVisibleItems() as $item) {
            $item->setData('final_price', $item->getProduct()->getFinalPrice());
        }

        $this->setAffectedEntityId((int)$quote->getEntityId())
            ->setPayload([
                'quote'                => $quote,
                'quoteAllVisibleItems' => $quoteAllVisibleItems,
                'area'                 => $this->eventHelper->getAreaPayloadData($quote->getStoreId()),
            ]);

        return $this;
    }
}
