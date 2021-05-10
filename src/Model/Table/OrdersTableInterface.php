<?php


namespace PayPal\Model\Table;


use App\Model\Entity\Order;

interface OrdersTableInterface
{
    public function afterPayment(Order $order): void;
}
