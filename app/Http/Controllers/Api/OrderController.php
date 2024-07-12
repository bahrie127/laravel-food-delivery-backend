<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Xendit\Configuration;

class OrderController extends Controller
{
    //User: create new order
    public function createOrder(Request $request)
    {
        $request->validate([
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'restaurant_id' => 'required|integer|exists:users,id',
            'shipping_cost' => 'required|integer',

        ]);

        $totalPrice = 0;
        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $totalPrice += $product->price * $item['quantity'];
        }

        $totalBill = $totalPrice + $request->shipping_cost;

        $user = $request->user();
        $data = $request->all();
        $data['user_id'] = $user->id;
        $shippingAddress = $user->address;
        $data['shipping_address'] = $shippingAddress;
        $shippingLatLong = $user->latlong;
        $data['shipping_latlong'] = $shippingLatLong;
        $data['status'] = 'pending';
        $data['total_price'] = $totalPrice;
        $data['total_bill'] = $totalBill;

        $order = Order::create($data);

        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $orderItem = new OrderItem([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
            $order->orderItems()->save($orderItem);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    //update purchase status
    public function updatePurchaseStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();
        $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    //order history
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order history',
            'data' => $orders
        ]);
    }

    //cancel order
    public function cancelOrder(Request $request, $id)
    {
        $order = Order::find($id);
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }

    //get orders by status for restaurant
    public function getOrdersByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $user = $request->user();
        $orders = Order::where('restaurant_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status',
            'data' => $orders
        ]);
    }

    //get orders by status for restaurant
    public function getOrdersByRestaurantId(Request $request)
    {
        // $request->validate([
        //     'status' => 'required|string|in:pending,processing,completed,cancelled',
        // ]);

        $user = $request->user();
        $orders = Order::where('restaurant_id', $user->id)
            ->get();
        //->where('status', $request->status)


        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status',
            'data' => $orders
        ]);
    }

    //update order status for restaurant
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        if ($request->status == 'ready_for_delivery') {
            $this->sendNotificationDriver('Order Ready for Delivery', 'An order is ready for delivery.');
        } else {
            $this->sendNotification($order->user_id, 'Order Prepared', 'An order has been prepared by Restaurant.');
        }



        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    //get orders by status for driver
    public function getOrdersByStatusDriver(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        $user = $request->user();
        $orders = Order::where('driver_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status',
            'data' => $orders
        ]);
    }

    //get order status ready for delivery
    public function getOrderStatusReadyForDelivery(Request $request)
    {
        // $user = $request->user();
        $orders = Order::with('restaurant')
            ->where('status', 'ready_for_delivery')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all orders by status ready for delivery',
            'data' => $orders
        ]);
    }

    //update order status for driver
    public function updateOrderStatusDriver(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,on_the_way,delivered',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        $this->sendNotification($order->user_id, 'Order On The Way', 'An order Delivery to your address.');

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    // Get Payment Method
    public function getPaymentMethods()
    {
        $paymentMethods = [
            'e_wallet' => [
                'ID_OVO' => 'OVO',
                'ID_DANA' => 'DANA',
                'ID_LINKAJA' => 'LinkAja',
                'ID_SHOPEEPAY' => 'ShopeePay',
            ]
        ];

        return response()->json([
            'message' => 'Payment methods retrieved successfully',
            'payment_methods' => $paymentMethods
        ], 200);
    }

    public function purchaseOrderWithToken(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'payment_method_id' => 'nullable|required_if:payment_method,e_wallet|string',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            $for_user_id = auth()->id();

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'payment_method_id' => $validated['payment_method_id'],
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $order->user->id,
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, $for_user_id, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result], 200);
            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 500);
            }
        } else {
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order], 200);
        }
    }

    // Method for send notification to restaurant/user/driver
    public function sendNotification($userId, $title, $message)
    {
        $restaurant = User::find($userId);
        if ($restaurant && $restaurant->fcm_id) {
            $token = $restaurant->fcm_id;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = Notification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }

    public function sendNotificationDriver($title, $message)
    {
        // $restaurant = User::find($userId);
        // if ($restaurant && $restaurant->fcm_id) {
        // $token = $restaurant->fcm_id;


        $messaging = app('firebase.messaging');
        $notification = Notification::create($title, $message);

        $message = CloudMessage::withTarget('topic', 'driver')
            ->withNotification($notification);



        try {
            $messaging->send($message);
        } catch (\Exception $e) {
            Log::error('Failed to send notification', ['error' => $e->getMessage()]);
        }
        // }
    }

    //Callback / webhook
    public function webhook(Request $request)
    {
        Log::info('Received webhook:', $request->all());

        $event = $request->input('event');
        $data = $request->input('data');

        if (isset($data['payment_request_id']) && isset($data['status'])) {
            $payment = Payment::where('xendit_id', $data['payment_request_id'])->first();

            if ($payment) {
                $order = Order::where('id', $payment->order_id)->first();

                if (!$order) {
                    return response()->json(['message' => 'Order not found'], 404);
                }

                if ($event === 'payment.succeeded') {
                    $order->status = 'purchase';
                    $payment->status = 'success';
                    $order->save();
                    $payment->save();

                    // send notification
                    $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

                    return response()->json(['message' => 'Order updated successfully and notification sent'], 200);
                } elseif ($event === 'payment.failed') {
                    $order->status = 'cancel';
                    $payment->status = 'failed';
                    $order->save();
                    $payment->save();

                    return response()->json(['message' => 'Order updated to cancelled'], 200);
                }
            } else {
                return response()->json(['message' => 'Payment not found'], 404);
            }
        }

        return response()->json(['message' => 'Invalid callback data'], 400);
    }

    public function __construct()
    {
        Configuration::setXenditKey('xnd_development_2WdZYFWXIGBXjQ78cNNIxdzqJ3tiSQZyyjmNkOQvAXhtKl7UTIy533kYioq171');
    }

    ///One-Time Payment via Redirect URL Xendit
    public function purchaseOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'mobile_number' => 'nullable|required_if:payment_e_wallet,ID_OVO|string'
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            //$for_user_id = auth()->id();

            $channel_properties = [
                'success_return_url' => 'flutter://payment_success?order_id=' . $orderId,
                'failure_return_url' => 'flutter://payment_failed?order_id=' . $orderId,
            ];

            // add mobile_number if e-wallet is OVO
            if ($validated['payment_e_wallet'] === 'ID_OVO') {
                $channel_properties['mobile_number'] = $validated['mobile_number'];
            }

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'country' => 'ID',
                'payment_method' => [
                    'type' => 'EWALLET',
                    'ewallet' => [
                        'channel_code' => $validated['payment_e_wallet'],
                        'channel_properties' => $channel_properties
                    ],
                    'reusability' => 'ONE_TIME_USE'
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, null, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);
                $order->payment_method = $validated['payment_e_wallet'];
                // $order->payment_e_wallet = ;
                $order->save();

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result], 200);
            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 500);
            }
        } else {
            // Hanya memperbarui status order jika metode pembayaran bukan e-wallet
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order], 200);
        }
    }

    public function getOrdersWaitingPickup()
    {
        $orders = Order::where('status', 'ready_for_delivery')->with('user', 'restaurant')->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }
}
