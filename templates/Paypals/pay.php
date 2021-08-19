<?php
/**
 * @var \Cake\View\View $this
 * @var \PayPal\Model\Entity\OrderInterface $order
 * @var string $formAction
 */

use Cake\Routing\Router;
use Cake\Core\Configure;

$this->assign('title', __('Sie werden jetzt zu Paypal weitergeleitet'));

?>
<h1><?= __('Sie werden jetzt zu Paypal weitergeleitet...') ?></h1>
<form action="<?= $formAction ?>" id="paypalForm" method="post" target="_top">
    <input TYPE="hidden" name="cmd" value="_xclick">
    <?= $this->Form->control('business', [
        'value' => Configure::read('PayPal.receiverEmail'),
        'type' => 'hidden'
    ]) ?>
    <?= $this->Form->control(
        'item_name',
        ['value' => sprintf('Bestellung #%s', $order->id), 'type' => 'hidden']
    ) ?>
    <?= $this->Form->control(
        'amount',
        ['value' => number_format($order->getPayPalAmount(), 2), 'type' => 'hidden']
    ) ?>
    <?= $this->Form->control('currency_code', [
        'value' => Configure::read('PayPal.currency'),
        'type' => 'hidden'
    ]) ?>
    <?= $this->Form->control('email', ['value' => $order->getPayPalPayerEmail(), 'type' => 'hidden']) ?>
    <?= $this->Form->control('custom', ['value' => $order->id, 'type' => 'hidden']) ?>
    <?= $this->Form->control('return', [
        'value' => Router::url(['plugin' => null, 'controller' => 'Orders', 'action' => 'confirm'], true),
        'type' => 'hidden',
    ]) ?>
    <?= $this->Form->control('notify_url', [
        'value' => Router::url(['controller' => 'Paypals', 'action' => 'ipnHandler'], true),
        'type' => 'hidden',
    ]) ?>
    <button type="submit" class="btn btn-primary">
        <?= __('Wenn Sie nicht automatisch weitergeleitet werden, klicken Sie bitte hier.') ?>
    </button>
</form>

<script>document.getElementById('paypalForm').submit();</script>
