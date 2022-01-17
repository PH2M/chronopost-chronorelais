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

namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class SaturdayOptionGenerated
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class SaturdayOptionGenerated extends Column
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var CarrierFactory
     */
    private $carrierFactory;

    /**
     * SaturdayOptionExport constructor.
     *
     * @param ContextInterface     $context
     * @param UiComponentFactory   $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param HelperData           $helperData
     * @param CarrierFactory       $carrierFactory
     * @param array                $components
     * @param array                $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        HelperData $helperData,
        CarrierFactory $carrierFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->scopeConfig = $scopeConfig;
        $this->helperData = $helperData;
        $this->carrierFactory = $carrierFactory;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $shippingMethod = $this->helperData->getShippingMethodeCode($item['shipping_method']);
                $saturdayGeneratedOption = $item[$this->getData('name')];
                if ($saturdayGeneratedOption === null) {
                    $carrier = $this->carrierFactory->get($shippingMethod);

                    // If no generated value is determined
                    $saturdayGeneratedOption = 'No';
                    $saturdayOption = $item['force_saturday_option'];
                    if (is_object($saturdayOption)) {
                        $saturdayGeneratedOption = $saturdayOption->getText();
                    }

                    if ($saturdayGeneratedOption === 'Yes') {
                        $saturdayDeliveryLabel = sprintf('%s (%s)', __('Yes'), __('customer choice'));
                    } else {
                        $isSendingDay = $this->helperData->isSendingDay();
                        $deliveryOnSaturday = (bool)$this->helperData->getConfig(
                            'carriers/' . $carrier->getCarrierCode() . '/deliver_on_saturday'
                        );

                        $saturdayDeliveryLabel = __('No');
                        if ($isSendingDay === true && $deliveryOnSaturday === true) {
                            $saturdayDeliveryLabel = __('Yes');
                        }

                        $saturdayDeliveryLabel = sprintf(
                            '%s (%s)',
                            $saturdayDeliveryLabel,
                            __('value that is generated')
                        );
                    }

                    // Override variable by export value
                    $saturdayExportStatus = $this->helperData->getShippingSaturdayStatus($item['entity_id']);
                    if ($saturdayExportStatus !== null) {
                        $saturdayDeliveryLabel = sprintf('%s (%s)', __($saturdayExportStatus), __('admin value'));
                    }
                } else {
                    $saturdayDeliveryLabel = $saturdayGeneratedOption === '1' ? __('Yes') : __('No');
                    $saturdayDeliveryLabel = sprintf('%s (%s)', $saturdayDeliveryLabel, __('generated value'));
                }

                $shippingMethodsAllowed = HelperData::SHIPPING_METHODS_SATURDAY_ALLOWED;
                if (in_array($shippingMethod, $shippingMethodsAllowed)) {
                    $msg = __('If the option is not offered to the customer, ' .
                        'the value is calculated from the delivery method and the time slot defined in the ' .
                        'configurations. If an admin value is entered, this will take precedence.');
                    $saturdayDeliveryLabel .= ' <a href="javascript: void(0);" title="' . $msg . '">(?)</a>';
                } else {
                    $saturdayDeliveryLabel = __('No');
                }

                $item[$this->getData('name')] = $saturdayDeliveryLabel;
            }
        }

        return $dataSource;
    }
}
