<?php
function paymentProvider(string $method, string $provider, bool $required = true): ?string
{
    $options = ['Bank Transfer'=>['KBZ','AYA','UAB'], 'Mobile Payment'=>['Kpay','AYA MMQR']];
    if (!isset($options[$method])) return null;
    if ($provider === '' && !$required) return null;
    if (!in_array($provider, $options[$method], true)) throw new InvalidArgumentException('Please select a valid bank or mobile payment service.');
    return $provider;
}
function paymentMethodLabel(array $payment): string
{
    return (string)($payment['payment_method'] ?? '') . (!empty($payment['payment_provider']) ? ' — '.$payment['payment_provider'] : '');
}
