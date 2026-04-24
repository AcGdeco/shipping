<?php

namespace Deco\Shipping\Block;

class EstimateRate extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        array $data = []
    ) {
        $this->localeFormat = $localeFormat;
        $this->storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * Returns the price format array for the store's current display currency,
     * matching the structure used by Magento_Catalog/js/price-utils.
     *
     * @return array
     */
    public function getPriceFormat()
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        return $this->localeFormat->getPriceFormat(null, $currencyCode);
    }

    /**
     * Returns the price format as a JSON string for inline JS usage.
     *
     * @return string
     */
    public function getPriceFormatJson()
    {
        return json_encode($this->getPriceFormat());
    }
}
