<?php
namespace Deco\Shipping\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Deco\Shipping\Model\ResourceModel\Job\CollectionFactory;
use Deco\Shipping\Model\Job;

class MassDelete extends \Magento\Backend\App\Action
{
    protected $filter;
    protected $collectionFactory;
    protected $Jobmodel;

    public function __construct(Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Job $Jobmodel
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->Jobmodel = $Jobmodel;
        parent::__construct($context);
    }

    public function execute()
    {
        $jobData = $this->collectionFactory->create();

        foreach ($jobData as $value) {
            $templateId[]=$value['cep_range_id'];
        }
        $parameterData = $this->getRequest()->getParams('cep_range_id');
        $selectedAppsid = $this->getRequest()->getParams('cep_range_id');
        if (array_key_exists("selected", $parameterData)) {
            $selectedAppsid = $parameterData['selected'];
        }
        if (array_key_exists("excluded", $parameterData)) {
            if ($parameterData['excluded'] == 'false') {
                $selectedAppsid = $templateId;
            } else {
                $selectedAppsid = array_diff($templateId, $parameterData['excluded']);
            }
        }
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('cep_range_id', ['in'=>$selectedAppsid]);
        $delete = 0;
        $model=[];
        foreach ($collection as $item) {
            $this->deleteById($item->getCepRangeId());
            $delete++;
        }
        $this->messageManager->addSuccess(__('A total of %1 Records have been deleted.', $delete));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * [deleteById description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function deleteById($id){
        $item = $this->Jobmodel->load($id);
        $item->delete();
        return;
    }
}
