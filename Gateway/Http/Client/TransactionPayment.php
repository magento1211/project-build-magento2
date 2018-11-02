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

namespace Adyen\Payment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;

/**
 * Class TransactionSale
 */
class TransactionPayment implements ClientInterface
{
	/**
	 * PaymentRequest constructor.
	 *
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
	 * @param \Adyen\Payment\Helper\Data $adyenHelper
	 * @param \Adyen\Payment\Model\RecurringType $recurringType
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Adyen\Payment\Helper\Data $adyenHelper,
		\Adyen\Payment\Model\RecurringType $recurringType,
		array $data = []
	) {
		$this->_encryptor = $encryptor;
		$this->_adyenHelper = $adyenHelper;
		$this->_recurringType = $recurringType;
		$this->_appState = $context->getAppState();
	}

	/**
	 * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
	 * @return mixed
	 * @throws ClientException
	 */
	public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
	{
		$request = $transferObject->getBody();

		$client = $this->_adyenHelper->initializeAdyenClient();

		$service = new \Adyen\Service\Checkout($client);

		try {
			$response = $service->payments($request);
		} catch(\Adyen\AdyenException $e) {
			$response['error'] =  $e->getMessage();
		}

		return $response;
	}
}
