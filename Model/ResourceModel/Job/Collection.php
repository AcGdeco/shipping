<?php
namespace Deco\Shipping\Model\ResourceModel\Job;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Deco\Shipping\Model\Job as JobModel;
use Deco\Shipping\Model\ResourceModel\Job as JobResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            JobModel::class,
            JobResourceModel::class
        );
    }
}
