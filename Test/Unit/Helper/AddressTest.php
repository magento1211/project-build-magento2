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

namespace Adyen\Payment\Tests\Helper;

use Adyen\Payment\Gateway\Data\Order\AddressAdapter;
use Adyen\Payment\Helper\Address;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{

    const HOUSE_NUMBER = '123';
    const HOUSE_NUMBER_LETTER = '456B';
    const HOUSE_NUMBER_SPACE_LETTER = '789 C';
    const HOUSE_NUMBERS = [self::HOUSE_NUMBER, self::HOUSE_NUMBER_LETTER, self::HOUSE_NUMBER_SPACE_LETTER];
    const STREET_NAME = "John-Paul's Ave.";


    /**
     * @var Address
     */
    private $addressHelper;
    /**
     * @var AddressAdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $addressAdapter;

    protected function setUp(): void
    {
        $this->addressHelper = new Address();
        $this->addressAdapter = $this->getMockBuilder(AddressAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider addressConfigProvider
     * @param $houseNumberStreetLine
     * @param $address
     * @param $expectedResult
     * @param $streetLinesEnabled
     */
    public function testGetStreetAndHouseNumberFromAddress($address, $houseNumberStreetLine, $streetLinesEnabled, $expectedResult)
    {
        /*
         * Each test case provided by the dataProvider contains an address array,
         * houseNumberStreetLine and streetLinesEnabled config options,
         * and the expected result which should be returned from getStreetAndHouseNumberFromAddress()
         */
        for ($i = 1; $i <= count($address); $i++) {
            /*
             * Set the mock addressAdapter->getStreetLine1()...getStreetLine4() methods
             * to return the corresponding item in the address array for each test case.
             */
            $this->addressAdapter->method('getStreetLine'.$i)->willReturn($address[$i-1]);
        }
        $this->addressAdapter->method('getCountryId')->willReturn("se");

        $this->assertEquals($expectedResult,
            $this->addressHelper->getStreetAndHouseNumberFromAddress(
                $this->addressAdapter,
                $houseNumberStreetLine,
                $streetLinesEnabled
            )
        );
    }

    public static function addressConfigProvider(): array
    {
        $addressConfigs = [];
        foreach (self::HOUSE_NUMBERS as $house_number) {
            $addressConfigs = array_merge($addressConfigs, [
                [
                    '$address' => [
                        $house_number,
                        self::STREET_NAME
                    ],
                    '$houseNumberStreetLine' => 1,
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [
                        self::STREET_NAME,
                        $house_number
                    ],
                    '$houseNumberStreetLine' => 2,
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [
                        self::STREET_NAME,
                        '',
                        $house_number
                    ],
                    '$houseNumberStreetLine' => 3,
                    '$streetLinesEnabled' => 3,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [
                        self::STREET_NAME,
                        '',
                        '',
                        $house_number
                    ],
                    '$houseNumberStreetLine' => 4,
                    '$streetLinesEnabled' => 4,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                /*
                 * The following test case is an example of misconfiguration;
                 * houseNumberStreetLine is set (non-zero) but full address is provided in house number field
                 */
                [
                    '$address' => [self::STREET_NAME . ' ' . $house_number, ''],
                    '$houseNumberStreetLine' => 1,
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => '',
                        'house_number' => self::STREET_NAME . ' ' . $house_number
                    ]
                ],
                /* The following test cases will use the regex fallback to detect the house number and street name */
                [
                    '$address' => [self::STREET_NAME . ' ' . $house_number, ''],
                    '$houseNumberStreetLine' => 0, // Config is disabled
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [$house_number . ' ' . self::STREET_NAME, ''],
                    '$houseNumberStreetLine' => 0, // Config is disabled
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [self::STREET_NAME . ' ' . $house_number],
                    '$houseNumberStreetLine' => 2,
                    '$streetLinesEnabled' => 1, // Not enough street lines enabled
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
                [
                    '$address' => [$house_number . ' ' . self::STREET_NAME], // House number field is empty
                    '$houseNumberStreetLine' => 2,
                    '$streetLinesEnabled' => 2,
                    '$expectedResult' => [
                        'name' => self::STREET_NAME,
                        'house_number' => $house_number
                    ]
                ],
            ]);
        }

        return $addressConfigs;
    }
}
