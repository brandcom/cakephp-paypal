<?php
namespace PayPal\Model\Table;

use App\Model\Entity\Order;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use PayPal\Model\Entity\Paypal;

class PaypalsTable extends Table
{
    const INVALID = 'INVALID';
    const VALID = 'VERIFIED';

    const SANDBOX_FORM_ACTION = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    const FORM_ACTION = 'https://www.paypal.com/cgi-bin/webscr';

    const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    const VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('paypals');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('id')
            ->maxLength('id', 255)
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('data')
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

        return $validator;
    }

    /**
     * Verification Function
     * Sends the incoming post data back to PayPal using the cURL library.
     */
    public function verifyIPN(): bool
    {
        // Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
        // Instead, read raw POST data from the input stream.
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = [];
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                // Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
                if ($keyval[0] === 'payment_date') {
                    if (substr_count($keyval[1], '+') === 1) {
                        $keyval[1] = str_replace('+', '%2B', $keyval[1]);
                    }
                }
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        // Build the body of the verification post request, adding the _notify-validate command.
        $req = 'cmd=_notify-validate';
        foreach ($myPost as $key => $value) {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }

        // Post the data back to PayPal, using curl. Throw exceptions if errors occur.
        $ch = curl_init($this->getPaypalUri());
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];

        if ($http_code != 200) {
            throw new \Exception("PayPal responded with http code $http_code");
        }

        if (!($res)) {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: [$errno] $errstr");
        }
        curl_close($ch);

        // Check if PayPal verifies the IPN data, and if so, return true.
        if ($res == self::VALID) {
            return true;
        } else {
            Log::debug('IPN not verified');
            Log::debug($raw_post_data);
            Log::debug(print_r($res, true));
            return false;
        }
    }

    /**
     * Get paypal's api endpoint.
     *
     * @return string
     */
    public function getPaypalUri(): string
    {
        if (Configure::read('debug')) {
            return self::SANDBOX_VERIFY_URI;
        }
        return self::VERIFY_URI;
    }

    public function getFormAction(): string
    {
        if (Configure::read('debug')) {
            return self::SANDBOX_FORM_ACTION;
        }
        return self::FORM_ACTION;
    }

    /**
     * Check that the payment_status is Completed.
     * Check that the receiver_email is an email address registered in out PayPal account.
     * Check that the price (carried in mc_gross) and the currency (carried in mc_currency) are correct.
     *
     * @param array $ipn
     * @param Order $order
     * @return bool
     */
    public function isLegitimate(array $ipn): bool
    {
        if (!in_array($ipn['payment_status'], ['Completed'])) {
            Log::debug('inlegitimate payment status');
            return false;
        }

        if ($ipn['receiver_email'] != Configure::read('PayPal.receiverEmail')) {
            Log::debug('inlegitimate receiver_email');
            return false;
        }

        if ($ipn['mc_currency'] != Configure::read('PayPal.currency')) {
            Log::debug('inlegitimate currency');
            return false;
        }

        return true;
    }
}
