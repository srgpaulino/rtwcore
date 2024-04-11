<?php
namespace App\Provider\Payment;

interface PaymentProvider {
    public function createCustomer(array $data);
    public function stripePaymentIntent(array $data);
    public function stripePaymentVerify(array $data);
}