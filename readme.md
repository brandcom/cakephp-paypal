# PayPal Plugin for Cakephp 3

## Installation and Usage

Install the Plugin

```
composer require jbennecker/cakephp-paypal
```


Load the Plugin

```
$ bin/cake plugin load PayPal
```

Run Plugin Migrations

```
$ bin/cake migrations migrate -p PayPal
```

Add a file called `app_paypal.php` to your config folder and enter the following info

```
<?php

return [
    'PayPal' => [
        'currency' => 'EUR',
    ],
];
```

Your Table must implement our \PayPal\Model\Table\PaypalTableInterface and
your Entity must implement our \PayPal\Model\Entity\PaypalEntityInterface


```
use PayPal\Model\Entity\PaypalEntityInterface

class Order extends Entity implements PaypalEntityInterface
{}
```

And OrdersTable must implement \PayPal\Model\TableOrdersTableInterface

```
use PayPal\Model\Table\PaypalTableInterface

class OrdersTable extends Table implements PaypalTableInterface
{}
```

After you have saved an order, you can redirect the user to PayPal using this:

```
return $this->redirect([
    'plugin' => 'PayPal',
    'controller' => 'Paypals',
    'action' => 'pay',
    '?' => [
        'fk_model' => 'Orders',
        'fk_id' => $order->id,
    ],
]);
```

This will redirect your customer to PayPal. After the customer has successfully paid, he will be redirected to

```
Router::url(['controller' => {{fk_model}}, 'action' => 'confirm'], true);
```

PayPal will send an IPN to the Plugin. If the IPN was successfully
validated, the Plugin calls the afterPayment-Callback on your Table-Class.

This Plugin uses [PayPal Payments Standard](https://developer.paypal.com/api/nvp-soap/paypal-payments-standard/gs-PayPalPaymentsStandard/) 

See [PayPal sandbox testing guide](https://developer.paypal.com/tools/sandbox/) for using Sandbox testing accounts.
```
public function afterPayment(EntityInterface $order): void
{
    // Further process the order
}
```
