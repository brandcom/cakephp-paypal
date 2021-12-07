<?php
namespace PayPal\Controller;

use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use PayPal\Model\Entity\PaypalEntityInterface;
use PayPal\Model\Table\PaypalsTable;
use PayPal\Model\Table\PaypalTableInterface;

/**
 * @property PaypalsTable $Paypals
 */
class PaypalsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow();
        $this->Security->setConfig('unlockedActions', ['ipnHandler']);
    }

    /**
     * Generates a html payment form
     *
     * @link https://developer.paypal.com/docs/paypal-payments-standard/integration-guide/formbasics/#
     */
    public function pay()
    {
        $className = $this->getRequest()->getQuery('fk_model');
        $id = $this->getRequest()->getQuery('fk_id');
        if (empty($className) || empty($id)) {
            throw new \Exception('Missing parameters');
        }

        /** @var PaypalTableInterface&Table $table */
        $table = $this->getTableLocator()->get($className);
        if (!$table instanceof PaypalTableInterface) {
            throw new \Exception('Order must implement \PayPal\Model\Table\PaypalTableInterface');
        }

        /** @var PaypalEntityInterface&EntityInterface $order */
        $order = $table->getContained($id);
        if (!$order instanceof PaypalEntityInterface) {
            throw new \Exception('Order must implement \PayPal\Model\Table\PaypalTableInterface');
        }
        if (!$order) {
            $this->Flash->error(__('Order not found'));
            return $this->redirect('/');
        }
        if ($order->isPaid()) {
            $this->Flash->error(__('Order already paid'));
            return $this->redirect('/');
        }

        $formAction = $this->Paypals->getFormAction();
        $custom = sprintf('%s-%s', $className, $id);

        $exploded = explode('.', $className);
        if (count($exploded) > 1) {
            $plugin = $exploded[0];
            $controller = $exploded[1];
        } else {
            $plugin = false;
            $controller = $className;
        }
        $return = [
            'plugin' => $plugin,
            'controller' => $controller,
            'action' => 'confirm',
        ];


        $this->set(compact('custom', 'order', 'formAction', 'return'));
        $this->viewBuilder()->setLayout('paypal');
    }

    /**
     * PayPal posts from variables to this url after someone paid.
     * We validate the notification, mark order as paid and queue exports.
     *
     * A list of variables is available here:
     *
     * @link https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
     * @link https://developer.paypal.com/docs/paypal-payments-standard/integration-guide/formbasics/#instant-payment-notification--notify_url
     */
    public function ipnHandler()
    {
        Log::debug("Received IPN Message\n" . file_get_contents('php://input'));

        $verified = $this->Paypals->verifyIPN();
        if (!$verified) {
            Log::debug('IPN could not be verified.');
            $this->exit();
        }
        Log::debug('IPN is verified.');


        // Check the txn_id to make sure the IPN is not a duplicate.
        $paypal = $this->Paypals->findById($this->request->getData('txn_id'))->first();
        if ($paypal) {
            Log::debug('IPN message is a duplicate.');
            $this->exit();
        }


        // After we have authenticated an IPN message (received a VERIFIED response from PayPal),
        // we must perform additional checks before we can assume that the IPN is legitimate.
        [$className, $id] = explode('-', $this->request->getData('custom'));
        /** @var PaypalTableInterface&Table $table */
        $table = $this->getTableLocator()->get($className);
        /** @var PaypalEntityInterface&EntityInterface $order */
        $order = $table->getContained($id);
        if (!$order) {
            Log::debug('Order not found');
            $this->exit();
        }


        $isLegitimate = $this->Paypals->isLegitimate($this->request->getData(), $order);
        if (!$isLegitimate) {
            Log::debug('IPN is not legitimate.');
            $this->exit();
        }
        Log::debug('IPN is legitimate');

        // Save the IPN to the Database, so we can check txn_id in future
        $data = [
            'id' => $this->request->getData('txn_id'),
            'data' => file_get_contents('php://input'),
            'fk_id' => $id,
            'fk_model' => $className,
        ];
        Log::debug('Paypal data for db: ' . print_r($data, true));
        $paypal = $this->Paypals->newEntity($data);
        if (!$this->Paypals->save($paypal)) {
            Log::debug('Could not save IPN to database');
            $this->exit();
        }

        Log::debug('The Payment has been saved.');
        $table->afterPayment($order);
        $this->exit();
    }

    /**
     * Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
     */
    private function exit(): void
    {
        header("HTTP/1.1 200 OK");
        exit();
    }
}
