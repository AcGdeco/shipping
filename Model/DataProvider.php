<?php
namespace Deco\Shipping\Model;

use Deco\Shipping\Model\ResourceModel\Job\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    // @codingStandardsIgnoreStart
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $JobCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $JobCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
    // @codingStandardsIgnoreEnd

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $Job) {
            $this->loadedData[$Job->getId()] = $Job->getData();
        }
        return $this->loadedData;
    }
}
