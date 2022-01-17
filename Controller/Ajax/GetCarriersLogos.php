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

namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Shipping\Model\Config;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GetCarriersLogos
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class GetCarriersLogos extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Config
     */
    protected $shipconfig;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * GetCarriersLogos constructor.
     *
     * @param Context               $context
     * @param JsonFactory           $jsonFactory
     * @param Config                $shipConfig
     * @param Repository            $repository
     * @param DirectoryList         $directoryList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $shipConfig,
        Repository $repository,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->shipconfig = $shipConfig;
        $this->assetRepo = $repository;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
    }

    /**
     * Recovery of logos of chronopost delivery methods
     *
     * @return Json
     */
    public function execute()
    {
        $logos = [];
        $paramsImg = ['_secure' => $this->getRequest()->isSecure()];

        $activeCarriers = $this->shipconfig->getActiveCarriers();
        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    if (!$carrierModel->getIsChronoMethod()) {
                        continue;
                    }

                    if ($carrierModel->getConfigData('logo_url')) {
                        // Check if image not overloaded by client in pub/media/chronorelais folder
                        $logoMediaPath = $this->directoryList->getPath("media") . "/chronopost/" .
                            $carrierModel->getConfigData('logo_url');
                        if (file_exists($logoMediaPath)) {
                            $currentStore = $this->storeManager->getStore();
                            $logos[$carrierCode] = $currentStore->getBaseurl('media') . "/chronopost/" .
                                $carrierModel->getConfigData('logo_url');
                        } else {
                            $logos[$carrierCode] = $this->assetRepo->getUrlWithParams(
                                'Chronopost_Chronorelais::images/' . $carrierModel->getConfigData('logo_url'),
                                $paramsImg
                            );
                        }
                    }
                }
            }
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($logos);

        return $result;
    }
}
