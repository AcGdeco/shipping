<?php
namespace Deco\Shipping\Model;

use Magento\Framework\Model\AbstractModel;
use Deco\Shipping\Model\ResourceModel\Job as JobResourceModel;

class Job extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(JobResourceModel::class);
    }
}
