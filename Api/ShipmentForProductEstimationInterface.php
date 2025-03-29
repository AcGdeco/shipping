<?php

namespace Deco\Shipping\Api;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Interface ShipmentForProductEstimationInterface
 * @api
 * @since 100.0.7
 */
interface ShipmentForProductEstimationInterface
{
    /**
     * Estimate shipping by address and product, and return list of available shipping methods
     * @param string $sku
     * @param string $qty
     * @param AddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods
     */
    public function estimateByProductAndAddress($sku, AddressInterface $address, $qty = 1);
}