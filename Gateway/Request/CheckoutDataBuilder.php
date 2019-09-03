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

namespace Adyen\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Adyen\Payment\Observer\AdyenHppDataAssignObserver;

class CheckoutDataBuilder implements BuilderInterface
{
    /**
     * @var \Adyen\Payment\Helper\Data
     */
    private $adyenHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param \Adyen\Payment\Helper\Data                 $adyenHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
	public function __construct(
        \Adyen\Payment\Helper\Data $adyenHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->adyenHelper = $adyenHelper;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
    }

	/**
	 * @param array $buildSubject
	 * @return mixed
	 */
	public function build(array $buildSubject)
	{
		/** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDataObject */
		$paymentDataObject =\Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
		$payment = $paymentDataObject->getPayment();
		$order = $payment->getOrder();
		$storeId = $order->getStoreId();
		$request = [];

        // do not send email
        $order->setCanSendNewEmailFlag(false);

        $request['paymentMethod']['type'] = $payment->getAdditionalInformation(AdyenHppDataAssignObserver::BRAND_CODE);

        // Additional data for payment methods with issuer list
        if ($payment->getAdditionalInformation(AdyenHppDataAssignObserver::ISSUER_ID)) {
            $request['paymentMethod']['issuer'] = $payment->getAdditionalInformation(AdyenHppDataAssignObserver::ISSUER_ID);
        }

        $request['returnUrl'] = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK) . 'adyen/process/result';

        // Additional data for ACH
        if ($payment->getAdditionalInformation("bankAccountNumber")) {
            $request['bankAccount']['bankAccountNumber'] = $payment->getAdditionalInformation("bankAccountNumber");
        }

        if ($payment->getAdditionalInformation("bankLocationId")) {
            $request['bankAccount']['bankLocationId'] = $payment->getAdditionalInformation("bankLocationId");
        }

        if ($payment->getAdditionalInformation("bankAccountOwnerName")) {
            $request['bankAccount']['ownerName'] = $payment->getAdditionalInformation("bankAccountOwnerName");
        }

		// Additional data for open invoice payment
		if ($payment->getAdditionalInformation("gender")) {
			$order->setCustomerGender(\Adyen\Payment\Model\Gender::getMagentoGenderFromAdyenGender(
				$payment->getAdditionalInformation("gender"))
			);
			$request['paymentMethod']['personalDetails']['gender'] = $payment->getAdditionalInformation("gender");
		}

        if ($payment->getAdditionalInformation("dob")) {
            $order->setCustomerDob($payment->getAdditionalInformation("dob"));

			$request['paymentMethod']['personalDetails']['dateOfBirth']= $this->adyenHelper->formatDate($payment->getAdditionalInformation("dob"), 'Y-m-d') ;
		}

		if ($payment->getAdditionalInformation("telephone")) {
			$order->getBillingAddress()->setTelephone($payment->getAdditionalInformation("telephone"));
			$request['paymentMethod']['personalDetails']['telephoneNumber']= $payment->getAdditionalInformation("telephone");
		}

        if ($payment->getAdditionalInformation("ssn")) {
            $request['paymentMethod']['personalDetails']['socialSecurityNumber']= $payment->getAdditionalInformation("ssn");
        }

        // Additional data for sepa direct debit
        if ($payment->getAdditionalInformation("ownerName")) {
            $request['paymentMethod']['sepa.ownerName'] = $payment->getAdditionalInformation("ownerName");
        }

        if ($payment->getAdditionalInformation("ibanNumber")) {
            $request['paymentMethod']['sepa.ibanNumber'] = $payment->getAdditionalInformation("ibanNumber");
        }

		if ($this->adyenHelper->isPaymentMethodOpenInvoiceMethod(
			    $payment->getAdditionalInformation(AdyenHppDataAssignObserver::BRAND_CODE)
		    ) || $this->adyenHelper->isPaymentMethodAfterpayTouchMethod(
				$payment->getAdditionalInformation(AdyenHppDataAssignObserver::BRAND_CODE)
			) || $this->adyenHelper->isPaymentMethodOneyMethod(
                $payment->getAdditionalInformation(AdyenHppDataAssignObserver::BRAND_CODE)
            )
        ) {
			$openInvoiceFields = $this->getOpenInvoiceData($order);
			$request = array_merge($request, $openInvoiceFields);
		}

        // Ratepay specific Fingerprint
        if ($payment->getAdditionalInformation("df_value") && $this->adyenHelper->isPaymentMethodRatepayMethod(
                $payment->getAdditionalInformation(AdyenHppDataAssignObserver::BRAND_CODE)
            )) {
            $request['deviceFingerprint'] = $payment->getAdditionalInformation("df_value");
        }

        //Boleto data
        if ($payment->getAdditionalInformation("social_security_number")) {
            $request['socialSecurityNumber'] = $payment->getAdditionalInformation("social_security_number");
        }

        if ($payment->getAdditionalInformation("firstname")) {
            $request['shopperName']['firstName'] = $payment->getAdditionalInformation("firstname");
        }

        if ($payment->getAdditionalInformation("lastName")) {
            $request['shopperName']['lastName'] = $payment->getAdditionalInformation("lastName");
        }

        if ($payment->getMethod() == \Adyen\Payment\Model\Ui\AdyenBoletoConfigProvider::CODE) {
            $boletoTypes = $this->adyenHelper->getAdyenBoletoConfigData('boletotypes');
            $boletoTypes = explode(',', $boletoTypes);

            if (count($boletoTypes) == 1) {
                $request['selectedBrand'] = $boletoTypes[0];
                $request['paymentMethod']['type'] = $boletoTypes[0];
            } else {
                $request['selectedBrand'] = $payment->getAdditionalInformation("boleto_type");
                $request['paymentMethod']['type'] = $payment->getAdditionalInformation("boleto_type");
            }

            $deliveryDays = (int)$this->adyenHelper->getAdyenBoletoConfigData("delivery_days", $storeId);
            $deliveryDays = (!empty($deliveryDays)) ? $deliveryDays : 5;
            $deliveryDate = date(
                "Y-m-d\TH:i:s ",
                mktime(
                    date("H"),
                    date("i"),
                    date("s"),
                    date("m"),
                    date("j") + $deliveryDays,
                    date("Y")
                )
            );

            $request['deliveryDate'] = $deliveryDate;

            $order->setCanSendNewEmailFlag(true);
        }
        return $request;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return array
     */
    protected function getOpenInvoiceData($order): array
    {
        $formFields = [
            'lineItems' => []
        ];

        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $this->cartRepository->get($order->getQuoteId());
        $currency = $cart->getCurrency();
        $discountAmount = 0;

        foreach ($cart->getAllVisibleItems() as $item) {
            $numberOfItems = (int)$item->getQty();

            // Summarize the discount amount item by item
            $discountAmount += $item->getDiscountAmount();

            $formattedPriceExcludingTax = $this->adyenHelper->formatAmount($item->getPrice(), $currency);

            $taxAmount = $item->getPrice() * ($item->getTaxPercent() / 100);
            $formattedTaxAmount = $this->adyenHelper->formatAmount($taxAmount, $currency);
            $formattedTaxPercentage = $item->getTaxPercent() * 100;

            $formFields['lineItems'][] = [
                'id' => $item->getId(),
                'itemId' => $item->getId(),
                'amountExcludingTax' => $formattedPriceExcludingTax,
                'taxAmount' => $formattedTaxAmount,
                'description' => $item->getName(),
                'quantity' => $numberOfItems,
                'taxCategory' => $item->getProduct()->getAttributeText('tax_class_id'),
                'taxPercentage' => $formattedTaxPercentage
            ];
        }

        // Discount cost
        if ($discountAmount != 0) {

            $description = __('Total Discount');
            $itemAmount = $this->adyenHelper->formatAmount($discountAmount, $currency);
            $itemVatAmount = "0";
            $itemVatPercentage = "0";
            $numberOfItems = 1;

            $formFields['lineItems'][] = [
                'itemId' => 'totalDiscount',
                'amountExcludingTax' => $itemAmount,
                'taxAmount' => $itemVatAmount,
                'description' => $description,
                'quantity' => $numberOfItems,
                'taxCategory' => 'None',
                'taxPercentage' => $itemVatPercentage
            ];
        }

        // Shipping cost
        if ($cart->getShippingAddress()->getShippingAmount() > 0 || $cart->getShippingAddress()->getShippingTaxAmount() > 0) {

            $priceExcludingTax = $cart->getShippingAddress()->getShippingAmount() - $cart->getShippingAddress()->getShippingTaxAmount();

            $formattedTaxAmount = $this->adyenHelper->formatAmount($cart->getShippingAddress()->getShippingTaxAmount(), $currency);

            $formattedPriceExcludingTax = $this->adyenHelper->formatAmount($priceExcludingTax, $currency);

            $formattedTaxPercentage = 0;

            if ($priceExcludingTax !== 0) {
                $formattedTaxPercentage = $cart->getShippingAddress()->getShippingTaxAmount() / $priceExcludingTax * 100 * 100;
            }
            
            $formFields['lineItems'][] = [
                'itemId' => 'shippingCost',
                'amountExcludingTax' => $formattedPriceExcludingTax,
                'taxAmount' => $formattedTaxAmount,
                'description' => $order->getShippingDescription(),
                'quantity' => 1,
                'taxPercentage' => $formattedTaxPercentage
            ];
        }

        return $formFields;
    }
}
