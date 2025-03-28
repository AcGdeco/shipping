<?php
namespace Deco\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;

class PriceDistanceShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'pricedistanceshipping';

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var MethodFactory
     */
    protected $distance;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shippingPrice = $this->calculateShippingPrice($request);

        if($this->getConfigData('maximum_radius') != null && $this->distance > $this->getConfigData('maximum_radius')) {
            return false;
        }

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        $result->append($method);
        return $result;
    }

    protected function calculateShippingPrice(RateRequest $request)
    {

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$this->getConfigData('origin_cep').'&destinations='.$request->getData('dest_postcode').'&units='.$this->getConfigData('unit').'&key='.$this->getConfigData('api');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            $distance = $data["rows"][0]["elements"][0]["distance"]["text"];
            $distance = preg_replace('/[^0-9]/', '', $distance);
            $this->distance = $distance;
        } else {
            $result = 'Erro na requisição cURL: ' . curl_error($ch);
        }

        $price = $this->getConfigData('kilometer_price') * $distance;

        curl_close($ch);

        return $price;
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}