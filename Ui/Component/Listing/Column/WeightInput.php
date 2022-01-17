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

use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class WeightInput
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class WeightInput extends Column
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * WeightInput constructor.
     *
     * @param ContextInterface     $context
     * @param UiComponentFactory   $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Data                 $helper
     * @param array                $components
     * @param array                $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $entity_id = $item['entity_id'];
                $totalWeight = $this->helper->getWeightOfOrder($entity_id, true);
                $chronoWeightUnit = $this->helper->getChronoWeightUnit($entity_id);
                $weightUnit = $this->helper->getWeightUnit($entity_id);
                if ($weightUnit === 'lbs') {
                    $totalWeight = round($totalWeight / 2.205, 3);
                }

                $render = '<input type="text" name="weight_input"
                 value="' . $totalWeight . '" class="input-text" data-position="1" data-orderid="' .
                    $item['entity_id'] . '" data-shipping-method="' . $item['shipping_method'] . '"/>';

                $render .= '<p class="weight-line">' . __('Store: %1', $weightUnit) . '</p>';
                $render .= '<p class="weight-line">' . __('Chronopost: %1', $chronoWeightUnit) . 's</p>';

                if ($chronoWeightUnit === 'g' || $weightUnit === 'lbs') {
                    $message = __('The conversion is done automatically on generation');
                    $render .= ' <a href="javascript: void(0);" title="' . $message . '">(?)</a>';
                }

                $item[$this->getData('name')] = $render;
            }
        }

        return $dataSource;
    }
}
