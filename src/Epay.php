<?php namespace AngelBachev\Epay;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GuzzleHttp\Client;

/**
 * Epay API Class
 *
 * Wrapper for Epay.bg API
 *
 * @author Angel Bachev <angelbachev@gmail.com>
 * @version 1.0.0
 * @package angelbachev\epay
 */
class Epay
{
    /**
     * @var Array
     */
    private $config;

    /**
     * @var Array
     */
    private $data;

    /**
     * @var hash_hmac
     */
    private $checksum;

    /**
     * Create a new Epay Instance
     */
    public function __construct()
    {
        $config = config('epay');

        $mode = $config['mode'];
        if ($mode !== 'prod') {
            $mode = 'stage';
        }

        $this->config = $config[$mode];
    }

    /**
     * Get form submit url
     * @method getSubmitUrl
     * @return string       form action url
     */
    public function getSubmitUrl()
    {
        return $this->config['submit_url'];
    }

    /**
     * Set invoice data
     * @method setData
     * @param  integer $invoice required
     * @param  decimal $amount required
     * @param  DateString $expiration required   d.m.Y example - 01.08.2020
     * @param  string $description [description]
     * @param  string $currency optional (possible values 'BGN', 'USD', 'EUR')
     * @param  string $encoding optional (possible value 'utf-8')
     */
    public function setData($invoice, $amount, $expiration, $description = '', $currency = 'BGN', $encoding = NULL)
    {
        $this->validateInvoice($invoice);
        $this->validateAmount($amount);
        $this->validateExpiration($expiration);
        $this->validateDescription($description);
        $this->validateCurrency($currency);
        $this->validateEncoding($encoding);

        $data = [
            'MIN'      => $this->config['client_id'],
            'INVOICE'  => $invoice,
            'AMOUNT'   => $amount,
            'EXP_TIME' => $expiration,
            'DESCR'    => $description,
        ];

        if (in_array($currency, ['USD', 'EUR'])) {
            $data['CURRENCY'] = $currency;
        }

        if ($encoding === 'utf-8') {
            $data['ENCODING'] = $encoding;
        }

        $formattedData = "";
        foreach ($data as $key => $value) {
            $formattedData .= "$key=$value\n";
        }

        $this->data = base64_encode(rtrim($formattedData, "\n"));

        $this->setChecksum();
    }

    /**
     * Receive Handler
     * @method receiveNotification
     * @param  array $requestInputs Request inputs (post data)
     * @return array                              Response and invoice data
     */
    public function receiveNotification($requestInputs)
    {
        $encoded = $requestInputs['encoded'];
        $checksum = $requestInputs['checksum'];

        $hmac = $this->generateChecksumHash($encoded);
        if ($hmac === $checksum) {
            $result = [
                'data'  => base64_decode($encoded),
                'items' => [],
            ];

            $lines = explode("\n", $result['data']);
            $response = '';

            $statuses = [
                'PAID'   => 'OK',
                'DENIED' => 'ERR',
            ];

            foreach ($lines as $line) {
                if (preg_match("/^INVOICE=(\d+):STATUS=(PAID|DENIED|EXPIRED)(:PAY_TIME=(\d+):STAN=(\d+):BCODE=([0-9a-zA-Z]+))?$/", $line, $regs)) {

                    $item = [
                        'invoice'  => $regs[1],
                        'status'   => $regs[2],
                        'pay_date' => $regs[4] ? $regs[4] : '',
                        'stan'     => $regs[5] ? $regs[5] : '',
                        'bcode'    => $regs[6] ? $regs[6] : '',
                    ];

                    $result['items'][] = $item;
                    // No if expired or other
                    $status = array_get($statuses, $item['status'], 'NO');
                    $response .= "INVOICE=${item['invoice']}:STATUS=$status\n";

                }
            }
            $result['response'] = $response;

            return $result;
        } else {
            \Log::error("Checksum doesn't match!");
        }
    }

    /**
     * Generate html hidden inputs
     * @method generateHiddenInputs
     * @param  string $successUrl Return after success
     * @param  string $cancelUrl Return after cancel
     * @return string generated html
     */
    public function generateHiddenInputs($successUrl = FALSE, $cancelUrl = FALSE)
    {
        $successUrl = $successUrl ? $successUrl : $this->config['success_url'];
        $cancelUrl = $cancelUrl ? $cancelUrl : $this->config['cancel_url'];

        return '
            <input type="hidden" name="PAGE" value="paylogin">
            <input type="hidden" name="ENCODED" value="' . $this->getData() . '">
            <input type="hidden" name="CHECKSUM" value="' . $this->getChecksum() . '">
            <input type="hidden" name="URL_OK" value="' . $successUrl . '">
            <input type="hidden" name="URL_CANCEL" value="' . $cancelUrl . '">';
    }

    /**
     * Get data encoded with base64
     * @method getData
     * @return string  base64 encoded data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set checksum based on data
     * @method setChecksum
     */
    private function setChecksum()
    {
        $this->checksum = $this->generateChecksumHash();
    }

    /**
     * Generate hash_hmac based string
     * @method generateChecksumHash
     * @param  $data $data    overwrite data
     * @return string                       return hash_hmac
     */
    private function generateChecksumHash($data = FALSE)
    {
        if (!$data) {
            $data = $this->getData();
        }

        return hash_hmac('sha1', $data, $this->config['secret']);
    }

    /**
     * Get generated checksum
     * @method getChecksum
     * @return string      hashed data
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    private function validateInvoice($invoice)
    {
        if (!preg_match('/^\d+$/', '' . $invoice)) {
            throw new \InvalidArgumentException('Invoice must contain only digits. Input was: ' . $invoice);
        }
    }

    private function validateAmount($amount)
    {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException(
                'Amount  must be a positve integer or float number with 1 or 2 digits after decimal point. Input was: ' . $amount
            );
        }
    }

    private function validateExpiration($expiration)
    {
        if (!preg_match('/^\d{2}\.\d{2}.\d{4}( \d{2}:\d{2})?(:\d{2})?$/', $expiration)) {
            throw new \InvalidArgumentException(
                'Expiration time must be in format "DD.MM.YYYY[ hh:mm[:ss]]". Input was: ' . $expiration
            );
        }
    }

    private function validateDescription($description)
    {
        if (strlen($description) > 100) {
            throw new \InvalidArgumentException('Description accepts no more than 100 characters. Input was: ' . $description);
        }
    }

    private function validateCurrency($currency)
    {
        if (!in_array($currency, ['BGN', 'USD', 'EUR'])) {
            throw new \InvalidArgumentException('Currency accepts only "BGN", "USD", "EUR". Input was: ' . $currency);
        }
    }

    private function validateEncoding($encoding)
    {
        if (!in_array($encoding, [NULL, 'utf-8'])) {
            throw new \InvalidArgumentException('Encoding accepts only "utf-8". Input was: ' . $encoding);
        }
    }
}
