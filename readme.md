# PayPal Plugin for Cakephp 4.x

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

The Plugin expects that you have a table called orders and the corresponding entity and table classes. The Order Entity
class must implement the PayPal OrderInterface

```
use PayPal\Model\Entity\OrderInterface;

class Order extends Entity implements OrderInterface
{}
```

And OrdersTable must implement \PayPal\Model\TableOrdersTableInterface

```
use PayPal\Model\TableOrdersTableInterface;

class OrdersTable extends Table implements OrdersTableInterface
{}
```

After you have saved an order you can redirect the user to PayPal using this:

```
return $this->redirect([
    'plugin' => 'PayPal',
    'controller' => 'Paypals',
    'action' => 'pay',
    $order->id,
]);
```

This will redirect your customer to PayPal. After the customer has successfully paid, he will be redirected to

```
Router::url(['controller' => 'Orders', 'action' => 'confirm'], true);
```

PayPal will send an IPN to the Plugin. Make sure you have IPN enabled in you account settings. If the IPN successfully
validated, the Plugin calls an Callbackmethod on your OrdersTable class.

```
public function afterPayment(Order $order): void
{
    // Further process the order
}
```
