<?php

namespace Deco\Shipping\Model\Api;

use Magento\Framework\App\ObjectManager;

class ShipmentForProductEstimation implements \Deco\Shipping\Api\ShipmentForProductEstimationInterface 
{
    /**
     * Shipping method converter
     *
     * @var \Magento\Quote\Model\Cart\ShippingMethodConverter
     */
    protected $converter;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory; // Injeção da Factory de Quote

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository; // Renomeado para clareza e padrão

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $productType; // Injeção para checar o tipo de produto

    /**
     * Construtor
     *
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Product\Type $productType
     */

    public function __construct(
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\Quote\Model\Quote\Item $item,
        \Magento\Catalog\Model\Product\Type $productType
    ) {
        $this->converter = $converter;
        $this->totalsCollector = $totalsCollector;
        $this->quote = $quote;
        $this->product = $product;
        $this->item = $item;
        $this->productType = $productType;
    }

    /**
     * @inheritdoc
     */
    public function estimateByProductAndAddress($id, \Magento\Quote\Api\Data\AddressInterface $address, $qty = 1)
    {
        $output = [];
        $address->setCountryId('BR');
        $product = $this->product->getById($id);

        // Limpa o quote antes de adicionar novos itens
        $this->quote->removeAllItems();

        if ($product->getTypeId() === 'bundle') {
            // Tratamento especial para produtos bundle
            $this->addBundleProductToQuote($product, $qty);
        } else {
            // Tratamento para produtos simples
            $this->item->setProduct($product);
            $this->item->setQty($qty);
            $this->quote->addItem($this->item);
        }

        $shippingAddress = $this->quote->getShippingAddress();
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($this->quote, $shippingAddress);
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $this->quote->getQuoteCurrencyCode());
            }
        }
        
        return $output;
    }

    /**
     * Adiciona produto bundle ao quote com seus itens filhos
     */
    protected function addBundleProductToQuote($product, $qty)
    {
        $typeInstance = $product->getTypeInstance();
        $selectionCollection = $typeInstance->getSelectionsCollection(
            $typeInstance->getOptionsIds($product),
            $product
        );

        $bundleOptions = [];
        $bundleOptionsQty = [];
        
        // Obter todas as seleções (incluindo múltiplas seleções por opção)
        foreach ($selectionCollection as $selection) {
            $optionId = $selection->getOptionId();
            
            if (!isset($bundleOptions[$optionId])) {
                $bundleOptions[$optionId] = [];
                $bundleOptionsQty[$optionId] = [];
            }
            $bundleOptions[$optionId][] = $selection->getSelectionId();
            $bundleOptionsQty[$optionId][] = $selection->getSelectionQty() * $qty;

            // Se a opção permite múltiplas seleções
            /*if ($selection->getOption()->getType() == 'checkbox' || 
                $selection->getOption()->getType() == 'multi') {
                // Adiciona como array para múltiplas seleções
                if (!isset($bundleOptions[$optionId])) {
                    $bundleOptions[$optionId] = [];
                    $bundleOptionsQty[$optionId] = [];
                }
                $bundleOptions[$optionId][] = $selection->getSelectionId();
                $bundleOptionsQty[$optionId][] = $selection->getSelectionQty() * $qty;
            } else {
                // Opção de seleção única
                $bundleOptions[$optionId] = $selection->getSelectionId();
                $bundleOptionsQty[$optionId] = $selection->getSelectionQty() * $qty;
            }*/
        }

        // Verificação de opções obrigatórias
        $options = $typeInstance->getOptionsByIds($typeInstance->getOptionsIds($product), $product);
        foreach ($options as $option) {
            if ($option->getRequired() && !isset($bundleOptions[$option->getOptionId()])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Por favor, selecione todas as opções obrigatórias.')
                );
            }
        }

        $request = new \Magento\Framework\DataObject([
            'product' => $product->getId(),
            'bundle_option' => $bundleOptions,
            'bundle_option_qty' => $bundleOptionsQty,
            'qty' => $qty
        ]);

        $result = $this->quote->addProduct($product, $request);
        if (is_string($result)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($result));
        }
    }

    /**
     * Transforma a interface de endereço em um Array
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface $address
     * @return array
     */
    protected function extractAddressData($address)
    {
        $className = \Magento\Customer\Api\Data\AddressInterface::class;
        if ($address instanceof \Magento\Quote\Api\Data\AddressInterface) {
            $className = \Magento\Quote\Api\Data\AddressInterface::class;
        } elseif ($address instanceof \Magento\Quote\Api\Data\EstimateAddressInterface) {
            $className = \Magento\Quote\Api\Data\EstimateAddressInterface::class;
        }
        return $this->getDataObjectProcessor()->buildOutputDataArray(
            $address,
            $className
        );
    }

    /**
     * Obtém o processador de objeto de dados (DataObjectProcessor)
     *
     * @return \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected function getDataObjectProcessor()
    {
        if ($this->dataProcessor === null) {
            $this->dataProcessor = ObjectManager::getInstance()->get(\Magento\Framework\Reflection\DataObjectProcessor::class);
        }
        return $this->dataProcessor;
    }
}
