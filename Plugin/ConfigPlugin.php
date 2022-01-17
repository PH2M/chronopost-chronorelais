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

use Magento\Config\Model\Config;
use Magento\Framework\App\Request\Http;
use Chronopost\Chronorelais\Helper\Data;

/**
 * Class ConfigPlugin
 *
 * @package Chronopost\Chronorelais\Plugin
 */
class ConfigPlugin
{

    /**
     * @var Http
     */
    protected $request;

    /**
     * ConfigPlugin constructor.
     *
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    /**
     * Transform virtual form data to JSON string
     *
     * @param Config   $subject
     * @param \Closure $proceed
     *
     * @return mixed
     */
    public function aroundSave(Config $subject, \Closure $proceed)
    {
        if ($subject->getSection() === Data::CHRONO_RELAIS_CODE) {
            $this->request->getParams();
        }

        return $proceed();
    }
}
