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

namespace Chronopost\Chronorelais\Helper;

use Chronopost\Chronorelais\Model\HistoryLtFactory;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Contract as HelperContract;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment as OrderShipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Shipment
 *
 * @package Chronopost\Chronorelais\Helper
 */
class Shipment extends AbstractHelper
{
    const HISTORY_TYPE_SHIPMENT = 1;
    const HISTORY_TYPE_RETURN = 2;

    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var ConvertOrder
     */
    protected $convertOrder;

    /**
     * @var ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * @var Webservice
     */
    protected $helperWebserviceNotifier;

    /**
     * @var OrderShipment
     */
    protected $shipment;

    /**
     * @var HistoryLtFactory
     */
    protected $ltHistoryFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var null|AdapterInterface
     */
    private $connection = null;

    /**
     * @var array
     */
    private $historyLt = [];

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var Contract
     */
    private $helperContract;

    /**
     * Shipment constructor.
     *
     * @param Context            $context
     * @param TrackFactory       $trackFactory
     * @param ConvertOrder       $convertOrder
     * @param ShipmentNotifier   $shipmentNotifier
     * @param Webservice         $webservice
     * @param OrderShipment      $shipment
     * @param HistoryLtFactory   $historyLtFactory
     * @param ResourceConnection $resource
     * @param HelperData         $helperData
     * @param Contract           $helperContract
     */
    public function __construct(
        Context $context,
        TrackFactory $trackFactory,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        Webservice $webservice,
        OrderShipment $shipment,
        HistoryLtFactory $historyLtFactory,
        ResourceConnection $resource,
        HelperData $helperData,
        Contract $helperContract
    ) {
        parent::__construct($context);
        $this->trackFactory = $trackFactory;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->helperWebserviceNotifier = $webservice;
        $this->shipment = $shipment;
        $this->ltHistoryFactory = $historyLtFactory;
        $this->resource = $resource;
        $this->helperData = $helperData;
        $this->helperContract = $helperContract;
    }

    /**
     * Create shipment and labels
     *
     * @param Order       $order
     * @param array       $savedQtys
     * @param array       $trackData
     * @param array       $dimensions
     * @param int         $packageNumber
     * @param bool        $isImport
     * @param int         $contractId
     * @param string|null $expirationDate
     * @param string|null $offer
     *
     * @return OrderShipment
     * @throws LocalizedException
     * @throws MailException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createNewShipment(
        Order $order,
        array $savedQtys,
        array $trackData,
        array $dimensions,
        int $packageNumber = 1,
        bool $isImport = false,
        int $contractId = 1000,
        $expirationDate = null,
        $offer = null
    ) {
        if (!$isImport && !$order->canShip()) {
            throw new LocalizedException(
                __("You can't create a shipment.")
            );
        }

        $shipment = $this->convertOrder->toShipment($order);
        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            if (isset($savedQtys[$orderItem->getId()])) {
                $qtyShipped = min($savedQtys[$orderItem->getId()], $orderItem->getQtyToShip());
            } elseif (!count($savedQtys)) {
                $qtyShipped = $orderItem->getQtyToShip();
            } else {
                continue;
            }

            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        $shipment->setData('dimensions', $dimensions);
        $shipment->setData('nb_colis', $packageNumber);
        $shipment->setData('contract_id', $contractId);
        $shipment->setData('expiration_date', $expirationDate);
        $shipment->setData('offer', $offer);

        // Case of import tracking via the BO
        $shipment->setTrackData($trackData);

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        if ($shipment->getExtensionAttributes()) {
            $shipment->getExtensionAttributes()->setSourceCode('default');
        }

        $shipment->setData('create_track_toshipment', true);

        if ((!isset($trackData['send_mail']) || (isset($trackData['send_mail']) && $trackData['send_mail']))) {
            if (isset($trackData['comment'])) {
                $shipment->addComment($trackData['comment'], true, $trackData['include_comment']);
            }

            $this->shipmentNotifier->notify($shipment);
            $shipment->setData('create_track_toshipment', false);
        }

        $shipment->save();
        $shipment->getOrder()->save();

        return $shipment;
    }

    /**
     * Create track to shipment
     *
     * @param OrderShipment $shipment
     * @param array         $trackData
     * @param array         $dimensions
     * @param int           $packageNumber
     * @param int           $contractId
     * @param float|null    $customeAdValorem
     * @param string|null   $expirationDate
     * @param string|null   $offer
     *
     * @return array
     * @throws \SoapFault|NoSuchEntityException
     */
    public function createTrackToShipment(
        OrderShipment $shipment,
        array $trackData,
        array $dimensions,
        int $packageNumber = 1,
        int $contractId = 1000,
        $customeAdValorem = null,
        $expirationDate = null,
        $offer = null
    ) {
        $trackDatas = [];
        $resultParcelValues = [];

        $order = $shipment->getOrder();

        if (count($trackData) > 0) {
            $trackData = array_merge($trackData, [
                'parent_id' => $shipment->getId(),
                'order_id'  => $order->getId()
            ]);

            $trackDatas[] = $trackData;
        } else {
            $expedition = $this->helperWebserviceNotifier->createLabel(
                $shipment,
                'shipping',
                'returninformation',
                $dimensions,
                (int)$packageNumber,
                (int)$contractId,
                $customeAdValorem,
                $expirationDate,
                $offer
            );

            if ($expedition) {
                if (is_object($expedition->return->resultParcelValue)) {
                    $resultParcelValues[] = $expedition->return->resultParcelValue;
                } else {
                    $resultParcelValues = $expedition->return->resultParcelValue;
                }

                $shippingMethod = explode('_', $order->getShippingMethod());

                $count = count($resultParcelValues);
                for ($ite = 0; $ite < $count; $ite++) {
                    $trackData = [
                        'track_number'              => $resultParcelValues[$ite]->skybillNumber,
                        'parent_id'                 => $shipment->getId(),
                        'order_id'                  => $order->getId(),
                        'chrono_reservation_number' => $expedition->return->reservationNumber,
                        'carrier'                   => ucwords($shippingMethod[1]),
                        'carrier_code'              => $shippingMethod[1],
                        'title'                     => ucwords($shippingMethod[1]),
                        'popup'                     => '1'
                    ];

                    if (!isset($dimensions[$ite])) {
                        $dimensions[$ite] = $dimensions['weight'] ?? 0;
                    }

                    $this->saveLtHistory(
                        (string)$shipment->getId(),
                        (string)$resultParcelValues[$ite]->skybillNumber,
                        (float)$dimensions[$ite]['weight'] ?? 0,
                        $expedition->return->reservationNumber,
                        null,
                        $expirationDate
                    );

                    $trackDatas[] = $trackData;
                }
            }
        }

        foreach ($trackDatas as $trackUnitData) {
            try {
                $track = $this->trackFactory->create();
                $track->addData($trackUnitData);
                $shipment->addTrack($track)->setData('create_track_toshipment', false)->save();
            } catch (\Exception $exception) {
                $this->_logger->error($exception->getMessage());
            }
        }

        return $trackDatas;
    }

    /**
     * Save history
     *
     * @param string      $shipmentId
     * @param string      $ltNumber
     * @param float       $weight
     * @param null|string $reservation
     * @param null|int    $type
     * @param null|string $expirationDate
     *
     * @throws \Exception
     */
    public function saveLtHistory(
        string $shipmentId,
        string $ltNumber,
        float $weight,
        $reservation = null,
        $type = null,
        $expirationDate = null
    ) {
        if (!$type) {
            $type = self::HISTORY_TYPE_SHIPMENT;
        }

        $ltHistory = $this->ltHistoryFactory->create();
        $ltHistory->setData('shipment_id', $shipmentId);
        $ltHistory->setData('lt_number', $ltNumber);
        $ltHistory->setData('weight', $weight);
        $ltHistory->setData('reservation', $reservation);
        $ltHistory->setData('type', $type);
        $ltHistory->setData('expiration_date', $expirationDate);
        $ltHistory->save();
    }

    /**
     * Load shipment by increment id
     *
     * @param string $incrementId
     *
     * @return OrderShipment
     */
    public function getShipmentByIncrementId(string $incrementId): OrderShipment
    {
        return $this->shipment->loadByIncrementId($incrementId);
    }

    /**
     * Get shipment id from increment id
     *
     * @param string $incrementId
     *
     * @return string|null
     */
    public function getShipmentIdFromIncrementId(string $incrementId)
    {
        return $this->getConnection()->fetchOne(
            $this->getConnection()->select()
                ->from($this->getConnection()->getTableName('sales_shipment'), 'entity_id')
                ->where('increment_id = ?', $incrementId)
        );
    }

    /**
     * Get connection
     *
     * @return AdapterInterface|null
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection();
        }

        return $this->connection;
    }

    /**
     * Get label url
     *
     * @param OrderShipment|string $shipment
     * @param string|null          $trackNumber
     * @param string               $type
     * @param array                $dimensions
     * @param int                  $packageNumber
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws \SoapFault
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getLabelUrl($shipment, $trackNumber, string $type, array $dimensions = [], $packageNumber = 1): array
    {
        $etiquetteUrl = [];

        // Load shipment
        if (!$shipment instanceof OrderShipment) {
            $shipment = $this->getShipmentByIncrementId($shipment);
        }

        $order = $shipment->getOrder();
        $shippingMethod = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
        if ($trackNumber !== null) {
            $etiquetteUrl[] = base64_decode(
                $this->helperWebserviceNotifier->getLabelByReservationNumber(
                    $trackNumber,
                    $shippingMethod,
                    $type,
                    $order->getShippingAddress()
                )
            );
        } elseif ($shipTracks = $shipment->getAllTracks()) {
            $revisionNumbers = [];
            foreach ($shipTracks as $shipTrack) {
                $chronoReservationNumber = $shipTrack->getChronoReservationNumber();
                if (!$chronoReservationNumber || in_array($chronoReservationNumber, $revisionNumbers)) {
                    continue;
                }

                $revisionNumbers[] = $chronoReservationNumber;
                if (strlen($chronoReservationNumber) > 50) {
                    $etiquetteUrl[] = base64_decode($chronoReservationNumber);
                } else {
                    $etiquetteUrl[] = base64_decode(
                        $this->helperWebserviceNotifier->getLabelByReservationNumber(
                            $chronoReservationNumber,
                            $shippingMethod,
                            'shipping',
                            $shipment->getOrder()->getShippingAddress()
                        )
                    );
                }
            }
        } else {
            $contractFromOrder = $this->helperContract->getContractByOrderId((string)$shipment->getOrderId());
            $contract = $this->helperContract->getContractByNumber(
                $contractFromOrder->getContractAccountNumber()
            );

            if ($contract) {
                $trackDatas = $this->createTrackToShipment(
                    $shipment,
                    [],
                    $dimensions,
                    (int)$packageNumber,
                    (int)$contract['contract_id']
                );

                $revisionNumbers = [];
                foreach ($trackDatas as $trackData) {
                    $chronoReservationNumber = $trackData['chrono_reservation_number'];
                    if (in_array($chronoReservationNumber, $revisionNumbers)) {
                        continue;
                    }

                    $revisionNumbers[] = $chronoReservationNumber;
                    $etiquetteUrl[] = base64_decode(
                        $this->helperWebserviceNotifier->getLabelByReservationNumber(
                            $chronoReservationNumber,
                            $shippingMethod,
                            $type,
                            $order->getShippingAddress()
                        )
                    );
                }
            }
        }

        return $etiquetteUrl;
    }

    /**
     * Get return for shipment
     *
     * @param string $shipmentId
     *
     * @return mixed
     */
    public function getReturnsForShipment(string $shipmentId)
    {
        return $this->ltHistoryFactory->create()->getCollection()
            ->addFieldToFilter('shipment_id', $shipmentId)
            ->addFieldToFilter('type', static::HISTORY_TYPE_RETURN);
    }

    /**
     * Get return for shipment
     *
     * @param string $shipmentId
     *
     * @return mixed
     */
    public function getTrackingForShipment(string $shipmentId)
    {
        return $this->ltHistoryFactory->create()->getCollection()
            ->addFieldToFilter('shipment_id', $shipmentId)
            ->addFieldToFilter('type', static::HISTORY_TYPE_SHIPMENT);
    }

    /**
     * Get history lt
     *
     * @param string $field
     * @param string $value
     *
     * @return array
     */
    public function getHistoryLt(string $field, string $value)
    {
        if (!isset($this->historyLt[$field][$value])) {
            $this->historyLt[$field][$value] = $this->ltHistoryFactory->create()->getCollection()
                ->addFieldToFilter($field, $value)
                ->getFirstItem();
        }

        return $this->historyLt[$field][$value];
    }
}
