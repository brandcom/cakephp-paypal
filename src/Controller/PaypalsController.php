<?php
namespace PayPal\Controller;

use App\Model\Table\OrdersTable;
use Cake\Log\Log;
use PayPal\Model\Entity\OrderInterface;
use PayPal\Model\Table\OrdersTableInterface;
use PayPal\Model\Table\PaypalsTable;

/**
 * @property OrdersTable $Orders
 * @property PaypalsTable $Paypals
 */
class PaypalsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow();
        $this->Security->setConfig('unlockedActions', ['ipnHandler']);
        $this->loadModel('Orders');

        if (!$this->Orders instanceof OrdersTableInterface) {
            throw new \Exception('OrdersTable must implement \PayPal\Model\TableOrdersTableInterface');
        }
    }

    /**
     * Generates a html payment form
     *
     * @link https://developer.paypal.com/docs/paypal-payments-standard/integration-guide/formbasics/#
     */
    public function pay(int $orderId)
    {
        $order = $this->Orders->findById($orderId)->find('contained')->first();
        if (!$order) {
            $this->Flash->error(__('Order not found'));
            $this->redirect(['controller' => 'Orders', 'action' => 'add']);
            return;
        }
        if (!$order instanceof OrderInterface) {
            throw new \Exception('Order must implement \PayPal\Model\Entity\OrderInterface');
        }
        $formAction = $this->Paypals->getFormAction();
        $this->set(compact('order', 'formAction'));
        $this->viewBuilder()->setLayout('paypal');
    }

    /**
     * PayPal posts from variables to this url after someone paid.
     * We validate the notification, mark order as paid and queue exports.
     *
     * A list of variables is available here:
     * @link https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
     *
     * @link https://developer.paypal.com/docs/paypal-payments-standard/integration-guide/formbasics/#instant-payment-notification--notify_url
     */
    public function ipnHandler()
    {
        Log::debug("Received IPN Message\n" . file_get_contents('php://input'));

        $verified = $this->Paypals->verifyIPN();
        if (!$verified) {
            throw new \Exception('IPN could not be verified');
        }
        Log::debug('is verified');

        // Check the txn_id to make sure the IPN is not a duplicate.
        $paypal = $this->Paypals->findById($this->request->getData('txn_id'))->first();
        if ($paypal) {
            throw new \Exception('IPN message is a duplicate.');
        }

        // After we have authenticated an IPN message (received a VERIFIED response from PayPal),
        // we must perform additional checks before we can assume that the IPN is legitimate.
        $order = $this->Orders
            ->find('contained')
            ->findById($this->request->getData('custom'))
            ->first();
        if (!$order) {
            throw new \Exception('Order not found');
        }
        $isLegitimate = $this->Paypals->isLegitimate($this->request->getData(), $order);
        if (!$isLegitimate) {
            throw new \Exception('IPN is not legitimate');
        }
        Log::debug('IPN is legitimate');

        // Save the IPN to the Database, so we can check txn_id in future
        $data = [
            'id' => $this->request->getData('txn_id'),
            'data' => file_get_contents('php://input'),
            'order_id' => $order->id,
        ];
        Log::debug('Paypal data for db: ' . print_r($data, true));
        $paypal = $this->Paypals->newEntity($data);
        if (!$this->Paypals->save($paypal)) {
            throw new \Exception('The IPN could not be saved.');
        }

        Log::debug('The Payment has been saved.');

        $this->Orders->afterPayment($order);

        // Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
        header("HTTP/1.1 200 OK");
        exit();
    }
}
