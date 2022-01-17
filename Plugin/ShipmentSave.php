<?php
/**
 * Chronopost
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Chronopost
 * @package   Chronopost_Chronorelais
 * @copyright Copyright (c) 2021 Chronopost
 */
declare(strict_types=1);

namespace Chronopost\Chronorelais\Plugin;

use Chronopost\Chronorelais\Helper\Contract as HelperContract;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Chronopost\Chronorelais\Helper\Webservice;
use Chronopost\Chronorelais\Model\ContractsOrdersFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\OrderFactory;

/**
 * Class ShipmentSave
 *
 * @package Chronopost\Chronorelais\Plugin
 */
class ShipmentSave
{
    /**
     * @var HelperShipment
     */
    protected $helperShipment;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var ContractsOrdersFactory
     */
    protected $contractsOrdersFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Webservice
     */
    private $helperWS;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var HelperContract
     */
    private $helperContract;

    /**
     * ShipmentSave constructor.
     *
     * @param HelperShipment         $helperShipment
     * @param ContractsOrdersFactory $contractsOrdersFactory
     * @param ScopeConfigInterface   $scopeConfig
     * @param OrderFactory           $orderFactory
     * @param HelperData             $helperData
     * @param Webservice             $helperWS
     * @param RequestInterface       $request
     * @param HelperContract         $helperContract
     */
    public function __construct(
        HelperShipment $helperShipment,
        ContractsOrdersFactory $contractsOrdersFactory,
        ScopeConfigInterface $scopeConfig,
        OrderFactory $orderFactory,
        HelperData $helperData,
        Webservice $helperWS,
        RequestInterface $request,
        HelperContract $helperContract
    ) {
        $this->helperShipment = $helperShipment;
        $this->contractsOrdersFactory = $contractsOrdersFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->helperData = $helperData;
        $this->helperWS = $helperWS;
        $this->request = $request;
        $this->helperContract = $helperContract;
    }

    /**
     * Before save shipment
     *
     * @param Shipment $subject
     *
     * @return Shipment
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function beforeSave(Shipment $subject)
    {
        if ($subject->getData('create_track_to_shipment') === null) {
            $subject->setData('create_track_to_shipment', false);
            if ($subject->getData('entity_id') === null) {
                $subject->setData('create_track_to_shipment', true);
            }
        }

        if (!$subject->getData('create_track_to_shipment')) {
            return $subject;
        }

        $order = $subject->getOrder();
        $subject = $subject->loadByIncrementId($subject->getIncrementId());
        $shippingMethodCode = $this->helperData->getShippingMethodeCode($order->getShippingMethod());

        if ($this->helperData->isChronoMethod($shippingMethodCode)) {
            // Set param to subject if param exist (shipment admin interface)
            $dimensions = $subject->getData('dimensions') ?: [];
            if ($this->request->getParam('dimensions')) {
                $dimensions = json_decode($this->request->getParam('dimensions'), true);
                $subject->setData('dimensions', $dimensions);
            }

            if ($this->request->getParam('nb_colis')) {
                $subject->setData('nb_colis', (int)$this->request->getParam('nb_colis'));
            }

            if ($this->request->getParam('ad_valorem')) {
                $subject->setData('ad_valorem', $this->request->getParam('ad_valorem'));
            }

            if ($this->request->getParam('contract') !== null) {
                $subject->setData('contract_id', $this->request->getParam('contract'));
            }

            if ($this->request->getParam('offers')) {
                $subject->setData('offer', $this->request->getParam('offers'));
            }

            if ($this->request->getParam('expiration_date')) {
                $expirationDate = $this->helperData->validateExpirationDate(
                    $this->request->getParam('expiration_date'),
                    $this->request->getParam('offers')
                );

                if ($expirationDate === false) {
                    throw new LocalizedException(
                        __('You cannot ship merchandise with an expiration date of less than 3 days.')
                    );
                }

                $subject->setData('expiration_date', $expirationDate);
            }

            $weightCoef = $this->helperData->getWeightCoef();

            // Build dimensions data
            $count = count($dimensions);
            for ($iterator = 0; $iterator < $count; $iterator++) {
                $msg = '';
                $error = false;
                $dimensionsLimit = $dimensions[$iterator];
                $weightLimit = $this->helperData->getWeightLimit($shippingMethodCode);
                $dimLimit = $this->helperData->getInputDimensionsLimit($shippingMethodCode);
                $globalLimit = $this->helperData->getGlobalDimensionsLimit($shippingMethodCode);

                if (isset($dimensionsLimit['weight']) && $dimensionsLimit['weight'] > $weightLimit && !$error) {
                    $msg = __('One or several packages are above the weight limit (%1 kg)', $weightLimit / $weightCoef);
                    $error = true;
                }

                if (isset($dimensionsLimit['width']) && $dimensionsLimit['width'] > $dimLimit && !$error) {
                    $msg = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                    $error = true;
                }

                if (isset($dimensionsLimit['height']) && $dimensionsLimit['height'] > $dimLimit && !$error) {
                    $msg = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                    $error = true;
                }

                if (isset($dimensionsLimit['length']) && $dimensionsLimit['length'] > $dimLimit && !$error) {
                    $msg = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                    $error = true;
                }

                if (isset($dimensionsLimit['height'], $dimensionsLimit['width'], $dimensionsLimit['length']) && !$error) {
                    $global = 2 * $dimensionsLimit['height'] + $dimensionsLimit['width'] + 2 * $dimensionsLimit['length'];
                    if ($global > $globalLimit) {
                        $msg = __(
                            'One or several packages are above the total (L+2H+2l) size limit (%1 cm)',
                            $globalLimit
                        );
                        $error = true;
                    }
                }

                if ($error) {
                    throw new LocalizedException($msg);
                }
            }

            $contract = $this->helperContract->getSpecificContract((int)$subject->getData('contract_id'));
            $result = $this->helperWS->checkContract($contract);
            if ($result && $result->return->errorCode === 0) {
                return $subject;
            }

            if ($result === false) {
                $message = __('An error occured during the label creation. ' .
                    'Please check if this contract can edit labels for this carrier.');
            } else {
                $message = __($result->return->errorMessage);
                if ($result->return->errorCode === 33) {
                    $message = __('An error occured during the label creation. ' .
                        'Please check if this contract can edit labels for this carrier.');
                }
            }

            throw new LocalizedException($message);
        }
    }

    /**
     * After save shipment
     *
     * @param Shipment $subject
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @return Shipment
     * @throws \Exception
     */
    public function afterSave(Shipment $subject)
    {
        $contractId = $subject->getData('contract_id');
        $packageNumber = $subject->getData('nb_colis') ?: 1;
        $dimensions = $subject->getData('dimensions') ?: [];
        $adValorem = $subject->getData('ad_valorem') ?: null;
        $expirationDate = $subject->getData('expiration_date') ?: null;
        $offer = $subject->getData('offer') ?: null;

        // Insurance not available for multiple packages
        if ($packageNumber > 1) {
            $adValorem = null;
        }

        // To avoid multiple label creation
        if (!$subject->getData('create_track_to_shipment')) {
            return $subject;
        }

        $order = $subject->getOrder();
        $subject = $subject->loadByIncrementId($subject->getIncrementId());
        $shippingMethodCode = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
        if ($this->helperData->isChronoMethod($shippingMethodCode)) {
            $hasTrack = false;
            $tracks = $subject->getAllTracks();
            foreach ($tracks as $track) {
                if ($track->getData('chrono_reservation_number')) {
                    $hasTrack = true;
                    break;
                }
            }

            // Chronopost order without tracking
            if ($hasTrack === false) {
                $this->helperShipment->createTrackToShipment(
                    $subject,
                    $subject->getTrackData() ?: [],
                    $dimensions,
                    (int)$packageNumber,
                    (int)$contractId,
                    (float)$adValorem,
                    $expirationDate,
                    $offer
                );
            }

            // Add contract to order if not exist
            $contractOrder = $this->contractsOrdersFactory->create()->getCollection()
                ->addFieldToFilter('order_id', $subject->getOrder()->getId());
            if (count($contractOrder) === 0) {
                $this->addContractToOrder($subject, (int)$contractId);
            }
        }

        return $subject;
    }

    /**
     * Link contract to order
     *
     * @param Shipment $subject
     * @param int      $contractId
     *
     * @return void
     * @throws \Exception
     */
    private function addContractToOrder(Shipment $subject, int $contractId)
    {
        $contract = $this->helperContract->getSpecificContract($contractId);
        if ($contract) {
            $contractOrder = $this->contractsOrdersFactory->create();
            $contractOrder->setData('order_id', $subject->getOrder()->getId());
            $contractOrder->setData('contract_name', $contract['name']);
            $contractOrder->setData('contract_account_number', $contract['number']);
            $contractOrder->setData('contract_sub_account_number', $contract['subAccount']);
            $contractOrder->setData('contract_account_password', $contract['pass']);
            $contractOrder->save();
        }
    }
}
