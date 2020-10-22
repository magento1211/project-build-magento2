<?php
/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2020 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */

namespace Adyen\Payment\Block\Transparent;

use Adyen\Service\Validator\DataArrayValidator;
use Magento\Framework\View\Element\Template;

class Redirect extends Template
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;
    /**
     * @var \Adyen\Payment\Logger\AdyenLogger
     */
    protected $adyenLogger;
    /**
     * Redirect constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\UrlInterface $url
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\UrlInterface $url,
        \Adyen\Payment\Logger\AdyenLogger $adyenLogger,
        array $data = []
    ) {
        $this->url = $url;
        $this->adyenLogger = $adyenLogger;
        parent::__construct($context, $data);
    }

    /**
     * Returns url for redirect.
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->url->getUrl("adyen/process/redirect"); //TODO this will be replaced by getOrigin() for PWA integrations
    }

    /**
     * Returns params to be redirected.
     * @return array
     */
    public function getPostParams()
    {
        $postParams = (array)$this->_request->getPostValue();
        $allowedPostParams = array('MD', 'PaRes');
        $postParams = DataArrayValidator::getArrayOnlyWithApprovedKeys($postParams, $allowedPostParams);
        $this->adyenLogger->addAdyenDebug(
            'Adyen 3DS1 PostParams forwarded to process redirect endpoint'
        );
        return $postParams;
    }
}
