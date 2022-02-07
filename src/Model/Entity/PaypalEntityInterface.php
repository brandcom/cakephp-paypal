<?php
namespace PayPal\Model\Entity;

use Cake\Datasource\EntityInterface;

interface PaypalEntityInterface extends EntityInterface
{
    public function getPayPalAmount(): float;

    public function getPayPalInvoiceNumber(): string;

    public function getPayPalPayerEmail(): string;

    public function getPayPalReceiverEmail(): string;

    /**
     * Weather the order has already been paid.
     */
    public function isPaid(): bool;
}
