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
 * Copyright (c) 2015 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */

namespace Adyen\Payment\Model\Method;
use Magento\Framework\Webapi\Exception;

/**
 * Adyen CreditCard payment method
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Oneclick extends \Adyen\Payment\Model\Method\Cc
{
    const METHOD_CODE = 'adyen_oneclick';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = 'Adyen\Payment\Block\Form\Oneclick';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Adyen\Payment\Block\Info\Oneclick';

    /**
     * Payment Method not ready for internal use
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $additionalData = $data->getAdditionalData();
        $infoInstance = $this->getInfoInstance();

        // get from variant magento code for creditcard type and set this in ccType
        $variant = $additionalData['variant'];
        $ccType = $this->_adyenHelper->getMagentoCreditCartType($variant);
        $infoInstance->setCcType($ccType);
        
        // save value remember details checkbox
        $infoInstance->setAdditionalInformation('recurring_detail_reference',
            $additionalData['recurring_detail_reference']);

        $recurringPaymentType = $this->_adyenHelper->getAdyenOneclickConfigData('recurring_payment_type');
        if ($recurringPaymentType == \Adyen\Payment\Model\RecurringType::ONECLICK) {
            $customerInteraction = true;
        } else {
            $customerInteraction = false;
        }

        $infoInstance->setAdditionalInformation('customer_interaction', $customerInteraction);

        // set number of installements
        if (isset($additionalData['number_of_installments'])) {
            $infoInstance->setAdditionalInformation('number_of_installments', $additionalData['number_of_installments']);
        }

        return $this;
    }

    /**
     * @param \Adyen\Payment\Model\Billing\Agreement $agreement
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateBillingAgreementStatus(\Adyen\Payment\Model\Billing\Agreement $agreement)
    {
        $targetStatus = $agreement->getStatus();

        if ($targetStatus == \Magento\Paypal\Model\Billing\Agreement::STATUS_CANCELED) {
            try {
                $this->_paymentRequest->disableRecurringContract(
                    $agreement->getReferenceId(),
                    $agreement->getCustomerReference(),
                    $agreement->getStoreId()
                );
            } catch(Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Failed to disable this contract'));
            }
        }
        return $this;
    }
}