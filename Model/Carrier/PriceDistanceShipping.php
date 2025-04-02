<?php
namespace Deco\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Deco\Shipping\Model\DataProvider;

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

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var DataProvider
     */
    protected $dataProvider;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ProductRepositoryInterface $productRepository,
        DataProvider $dataProvider,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->productRepository = $productRepository;
        $this->dataProvider = $dataProvider;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();
        $products = $request->getData("all_items");
        $maximum_volume = $this->getConfigData('maximum_volume');
        $maximum_weight = $this->getConfigData('maximum_weight');
        $weight_attribute = $this->getConfigData('weight_attribute');
        $height_attribute = $this->getConfigData('height_attribute');
        $length_attribute = $this->getConfigData('length_attribute');
        $width_attribute = $this->getConfigData('width_attribute');

        $totalVolume = 0;
        $totalWeight = 0;
        foreach($products as $product) {
            if($product->getData("product_type") != "configurable"){
                $qtd = $product->getData("qty");
                $product = $this->productRepository->getById($product->getData('product_id'));
                $volume_length = $product->getData($length_attribute);
                $volume_height = $product->getData($height_attribute);
                $volume_width = $product->getData($width_attribute);
                $weight = $product->getData($weight_attribute);

                $totalVolume += $qtd * ($volume_length * $volume_height * $volume_width);
                $totalWeight += $qtd * $weight;
            }
        }

        $totalVolume = $totalVolume / 1000000;
        $shippingQtyVolume = $totalVolume / $maximum_volume;
        $shippingQtyWeight = $totalWeight / $maximum_weight;

        if($shippingQtyWeight > $shippingQtyVolume) {
            $shippingQty = $shippingQtyWeight;
        } else {
            $shippingQty = $shippingQtyVolume;
        }

        $shippingQty = ceil($shippingQty);

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));



        $shippingPrice = $this->calculateShippingPrice($request);

        $shippingPrice *= $shippingQty;

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

        $cepCollection = $this->dataProvider->getData();
        $dest_postcode = $request->getData('dest_postcode') ? preg_replace('/[^0-9]/', '', $request->getData('dest_postcode')): "";

        foreach($cepCollection as $cepRange) {
            if($cepRange["status"] == 1){
                $cepInicial = preg_replace('/[^0-9]/', '',$cepRange["cep_inicial"]);
                $cepFinal = preg_replace('/[^0-9]/', '',$cepRange["cep_final"]);

                if($cepInicial > $cepFinal) {
                    $cepTem = $cepInicial;
                    $cepInicial = $cepFinal;
                    $cepFinal = $cepTem;
                }

                if($dest_postcode >= $cepInicial && $dest_postcode <= $cepFinal){
                    $price = $cepRange["price"];
                    return $price;
                }
            }
        }

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$this->getConfigData('origin_cep').'&destinations='.$dest_postcode.'&units='.$this->getConfigData('unit').'&key='.$this->getConfigData('api');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            $distance = $data["rows"][0]["elements"][0]["distance"]["text"];
            $distance = preg_replace('/[^0-9.]/', '', $distance);
            $this->distance = $distance;
        } else {
            $result = 'Erro na requisição cURL: ' . curl_error($ch);
        }

        $price = $this->getConfigData('kilometer_price') * $distance;

        if($this->getConfigData('minimum_price') != null && $price < $this->getConfigData('minimum_price')){
            $price = $this->getConfigData('minimum_price');
        }

        curl_close($ch);

        return $price;
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}