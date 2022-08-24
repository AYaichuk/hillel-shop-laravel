<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Repositories\Contracts\OrderRepositoryContract;
use App\Repositories\OrderRepository;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalPaymentController extends Controller
{
    const PAYMENT_SYSTEM = 'PAYPAL';
    protected PayPalClient $payPalClient;

    public function __construct()
    {
        $this->payPalClient = $this->getClient();
        $this->payPalClient->setApiCredentials(config('paypal'));
        $this->payPalClient->setAccessToken($this->payPalClient->getAccessToken());
    }

    public function create(CreateOrderRequest $request, OrderRepositoryContract $orderRepository)
    {
        try {
            DB::beginTransaction();

            $total = Cart::instance('cart')->total(2, '.', '');
            $invoiceId = 'invoice_id_' . time() . '_' . auth()->id();
            $paypalOrder = $this->createPaymentOrder($total, $invoiceId);
            $request = $request->validated();
            $request['vendor_order_id'] = $paypalOrder['id'];
            $request['invoice_id'] = $invoiceId;

            $order = $orderRepository->create($request, $total);

            DB::commit();

            return response()->json($order);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json($order);
        }
    }

    public function capture()
    {

    }

    protected function getClient()
    {
        return new PayPalClient();
    }

    protected function createPaymentOrder($total, $invoiceId)
    {
        return $this->payPalClient->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => config('paypal.currency'),
                        'value' => $total
                    ],
                    'invoice_id' => $invoiceId
                ]
            ]
        ]);
    }
}
