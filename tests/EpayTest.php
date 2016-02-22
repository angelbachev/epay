<?php
namespace packages\AngelBachev\Epay\Test;


use AngelBachev\Epay\Epay;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use InvalidArgumentException;

class EpayTest extends TestCase
{
    use WithoutMiddleware;

    private $epay;

    public function setUp()
    {
        parent::setUp();

        $this->epay = new Epay();
    }

    public function testGetSubmitUrlCorrect()
    {
        $this->assertEquals('https://devep2.datamax.bg/ep2/epay2_demo/', $this->epay->getSubmitUrl());
    }

    public function testSetDataIncorrectInvoce()
    {
        try {
            $this->epay->setData('invoice', 1, '01.03.2016');

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invoice must contain only digits.', $e->getMessage());
        }
    }

    public function testSetDataIncorrectAmount()
    {
        try {
            $this->epay->setData(1, 'amount', '01.03.2016');

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Amount  must be a positve integer or float number with 1 or 2 digits after decimal point.', $e->getMessage());
        }
    }

    public function testSetDataIncorrectExpiration()
    {
        try {
            $this->epay->setData(1, 25.12, '01.03.2016 ds');

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Expiration time must be in format "DD.MM.YYYY[ hh:mm[:ss]]".', $e->getMessage());
        }
    }

    public function testSetDataIncorrectDescription()
    {
        try {
            $description = '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901';
            $this->epay->setData(1, 1, '01.03.2016', $description);

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Description accepts no more than 100 characters.', $e->getMessage());
        }
    }

    public function testSetDataIncorrectCurrency()
    {
        try {
            $this->epay->setData(1, 25.12, '01.03.2016', 'test', 'CHF');

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Currency accepts only "BGN", "USD", "EUR".', $e->getMessage());
        }
    }

    public function testSetDataIncorrectEncoding()
    {
        try {
            $this->epay->setData(1, 25.12, '01.03.2016', 'test', 'BGN', 'utf-16');

            $this->assertFalse(TRUE, 'An InvalidArgumentException have been thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Encoding accepts only "utf-8".', $e->getMessage());
        }
    }

    public function testSetDataCorrect()
    {
        $this->epay->setData(1, 25.12, '01.03.2016 10:30', 'test');

        $this->assertTrue(TRUE);
    }

    public function testReceiveNotificationCorrect()
    {
        $requestInputs = [
            'encoded'  => 'SU5WT0lDRT0zMzAwMDAwOlNUQVRVUz1QQUlEOlBBWV9USU1FPTIwMTYwMjIyMDMyMjE0OlNUQU49MDAwMDAwOkJDT0RFPTAwMDAwMAo=',
            'checksum' => '1f3bf5f9ed1d175b7aaf77f09b09dfa1e3081d70',
        ];

        $expectedResult = [
            'data'     => 'INVOICE=3300000:STATUS=PAID:PAY_TIME=20160222032214:STAN=000000:BCODE=000000
',
            'items'    =>
                array(
                    0 =>
                        array(
                            'invoice'  => '3300000',
                            'status'   => 'PAID',
                            'pay_date' => '20160222032214',
                            'stan'     => '000000',
                            'bcode'    => '000000',
                        ),
                ),
            'response' => 'INVOICE=3300000:STATUS=OK
',
        ];

        $this->assertEquals($expectedResult, $this->epay->receiveNotification($requestInputs));
    }

    public function testGenerateHiddenInputsIncorrect()
    {
        $this->epay->setData(22531, 15.75, '01.04.2016', 'test');
        $expectedResult = '
            <input type="hidden" name="PAGE" value="paylogin">
            <input type="hidden" name="ENCODED" value="TUlOPUQ2MDM2NDYxNTUKSU5WT0lDRT0yMjUzMQpBTU9VTlQ9MTUuNzUKRVhQX1RJTUU9MDEuMDMuMjAxNgpERVNDUj10ZXN0">
            <input type="hidden" name="CHECKSUM" value="b06b44f3c76a9ddb2f84625e600625529053ba62">
            ';
        $this->assertStringStartsNotWith($expectedResult, $this->epay->generateHiddenInputs());
    }

    public function testGenerateHiddenInputsCorrect()
    {
        $this->epay->setData(22531, 15.75, '01.03.2016', 'test');
        $expectedResult = '
            <input type="hidden" name="PAGE" value="paylogin">
            <input type="hidden" name="ENCODED" value="TUlOPUQ2MDM2NDYxNTUKSU5WT0lDRT0yMjUzMQpBTU9VTlQ9MTUuNzUKRVhQX1RJTUU9MDEuMDMuMjAxNgpERVNDUj10ZXN0">
            <input type="hidden" name="CHECKSUM" value="b06b44f3c76a9ddb2f84625e600625529053ba62">
            ';
        $this->assertStringStartsWith($expectedResult, $this->epay->generateHiddenInputs());
    }

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        // TODO: Implement createApplication() method.
    }
}