<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends BaseController
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Create a PaymentIntent (card payment)
     */
    public function createPaymentIntent()
    {
        try {
            $request = $this->request->getJSON(true);

            if (empty($request['amount']) || empty($request['payment_method_id'])) {
                return sendApiResponse(null, 'Amount and payment method are required', 400);
            }

            // Create PaymentIntent and confirm immediately with the provided PaymentMethod
            $paymentIntent = PaymentIntent::create([
                'amount' => $request['amount'] * 100,
                'currency' => 'usd',
                'payment_method' => $request['payment_method_id'], // from frontend
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
            ]);

            return sendApiResponse(
                ['status' => $paymentIntent->status],
                'Payment processed successfully',
                200
            );
        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }
}
