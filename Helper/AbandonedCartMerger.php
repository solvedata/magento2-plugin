<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Processor as ItemProcessor;

/**
 * Solve's customisation of Magento\Quote\Model\Quote's quote merging that unions carts to
 *  avoid duplicating items when a cart is reclaimed.
 * 
 * See https://github.com/magento/magento2/blob/2.3.5/app/code/Magento/Quote/Model/Quote.php#L2381
 */
class AbandonedCartMerger
{
    private $eventManager;
    private $itemProcessor;

    public function __construct(
        EventManager $eventManager,
        ItemProcessor $itemProcessor
    ) {
        $this->eventManager = $eventManager;
        $this->itemProcessor = $itemProcessor;
    }

    /**
     * Merge an abanadoned cart into the destination quote.
     *
     * The result is the union of the two carts.
     *
     * @param Quote $quote
     * @return $this
     */
    public function merge(Quote $dest, Quote $source)
    {
        $this->eventManager->dispatch(
            'sales_quote_merge_before',
            ['quote' => $dest, 'source' => $source]
        );

        foreach ($source->getAllVisibleItems() as $item) {
            $found = false;
            foreach ($dest->getAllItems() as $quoteItem) {
                if ($quoteItem->compare($item)) {
                    // Customisation: Set the merge quantity to be the maximum of the two cart's quantities instead of their sum.
                    // This effectively unions the two carts. 
                    $mergedQty = max($quoteItem->getQty(), $item->getQty());
                    // End of Customisation

                    $quoteItem->setQty($mergedQty);
                    $this->itemProcessor->merge($item, $quoteItem);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newItem = clone $item;
                $dest->addItem($newItem);
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setParentItem($newItem);
                        $dest->addItem($newChild);
                    }
                }
            }
        }

        /**
         * Init shipping and billing address if quote is new
         */
        if (!$dest->getId()) {
            $dest->getShippingAddress();
            $dest->getBillingAddress();
        }

        if ($source->getCouponCode()) {
            $dest->setCouponCode($source->getCouponCode());
        }

        $this->eventManager->dispatch(
            'sales_quote_merge_after',
            ['quote' => $dest, 'source' => $source]
        );

        return $dest;
    }
}
