<?php

namespace App\Provider\Payment;

use Stripe\Stripe;
use Stripe\Customer;
use App\Domain\TFCBlue\Orders\Exception;
use Illuminate\Database\Query\Builder;

class RealPaymentProvider implements PaymentProvider {

    
    private $client;
    private $logger;

    public function __construct(\TFCLog\TFCLogger $logger, Stripe $client, Builder $eloquent) {
        $this->logger = $logger;
        $this->client = $client;
        $this->eloquent = $eloquent;
    }
    
    public function createCustomer(array $data): string{
        try {
            $response = \Stripe\Customer::create($data);
        } catch (\Exception $ex) {
            throw new \App\Domain\TFCBlue\Orders\Exception\PaymentException($ex->getMessage());
            
        }
        
        return $response->id;
    }
    
    /**
     * this will perform payment intent and returns client_secret to the frontend
     * & insert intent_id to the database. 
     */
    public function stripePaymentIntent(array $data): array{
        try {

            $intent = \Stripe\PaymentIntent::create([
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
            ]);

            $intentSecretKey = $intent->client_secret;
            $intentId = $intent->id;
            $timeGenerated = date("Y-m-d h:i:s");

            $insert = $this->eloquent->insert(['intent_id' => $intentId, 'time_generated' => $timeGenerated]);

            if ($insert):
                return [
                    'secret_key' => $intentSecretKey,
                    'status' => false
                ];
            endif;
        } catch (\Exception $ex) {
            throw new \App\Domain\TFCBlue\Orders\Exception\PaymentException($ex->getMessage());
        }
    }
    
    /**
     * this is the Webhook which triggers on each payment_intent.succeed event.
     * @param array $data
     * @return array
     */
    public function stripePaymentVerify(array $data): array{
        try {
            $event = \Stripe\Webhook::constructEvent(
                    $data['payload'], $data['sig_header'], $data['endpoint_secret']
            );
        } catch (\UnexpectedValueException $e) {

            throw new \App\Domain\TFCBlue\Orders\Exception\PaymentException($e->getMessage());
        } catch (\Stripe\Exception\SignatureVerificationException $e) {

            throw new \App\Domain\TFCBlue\Orders\Exception\PaymentException($e->getMessage());
        }

        if ($event->type == "payment_intent.created") {
            $intent = $event->data->object;
            $this->eloquent->where('intent_id', $intent->id)->update(['status' => 1, 'status_checked' => date("Y-m-d h:i:s")]);
            return ['intent_id' => $intent->id, 'status' => 1];
        } elseif ($event->type == "payment_intent.payment_failed") {
            $intent = $event->data->object;
            $error_message = $intent->last_payment_error ? $intent->last_payment_error->message : "";
            $statusUpdateTime = date("Y-m-d h:i:s");
            $this->eloquent->where('intent_id', $intent->id)->update(['status' => 0, 'status_checked' => $statusUpdateTime]);
            return ['intent_id' => $intent->id, 'status' => $error_message];
        }
    }

}
