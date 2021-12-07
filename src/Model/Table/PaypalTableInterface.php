<?php
namespace PayPal\Model\Table;

use Cake\Datasource\EntityInterface;

interface PaypalTableInterface
{
    /**
     * After Payment Callback. Use this to further process the order.
     */
    public function afterPayment(EntityInterface $order): void;

    /**
     * Should return the order.
     */
    public function getContained(int $id): EntityInterface;
}
