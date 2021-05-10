<?php


namespace PayPal\Model\Entity;


interface OrderInterface
{
    public function getPayPalAmount(): float;

    public function getPayPalPayerEmail(): string;
}
