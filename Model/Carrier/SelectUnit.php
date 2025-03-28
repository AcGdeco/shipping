<?php

namespace Deco\Shipping\Model\Carrier;

use Magento\Framework\Data\OptionSourceInterface;

class SelectUnit implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'metric', 'label' => __('Quilômetro')],
            ['value' => 'imperial', 'label' => __('Milha')],
        ];
    }
}