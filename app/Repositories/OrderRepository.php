<?php

namespace App\Repositories;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Repositories\Contracts\OrderRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\ImagesService;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryContract
{

    public function create(array $request, float $total): Order|bool
    {
        $user = auth()->user();
        $status = OrderStatus::defaultStatus()->first();

        $request = array_merge($request, [
            'status_id' => $status->id,
            'total' => $total
        ]);

        $order = $user->orders()->create($request);

//        $this->addProductsToOrder($order);

        return $order;
    }

    public function setTransaction(string $transactionOrderId, Transaction $transaction)
    {
        // TODO: Implement setTransaction() method.
    }
}
