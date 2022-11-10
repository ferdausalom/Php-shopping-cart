<?php

namespace App\Controllers;

use App\Core\Request;
use App\Models\Cart;
use App\Models\Checkout;
use App\Validations\AddressValidation;

class CheckoutController
{
    // Show checkout page and items in the cart 
    public function index()
    {
        $cartItems = (new Cart)->cartItems();
        
        return view("front/checkoutIndex", [
            'cartItems' => $cartItems
        ]);
    }

    // Place order 
    public function place()
    {
        $clientIp = Request::values()['clientIp'] ?? '';
        $payment = Request::values()['payment'] ?? '';
        $amount = Request::values()['amount'] ?? '';

        // Validate address data 
       $address = (new AddressValidation)->validateAddress(Request::values());

    //    Store address 
       $addressId = (new Checkout)->storeAddress($address);

    //    Get cart items by IP address 
       $cartItemByClientIp = (new Cart)->cartItemByClientIp($clientIp);

    //    Check if the order already in the DB 
       $isOrderExists = (new Checkout)->isOrderExists($cartItemByClientIp);

       if ($isOrderExists) {
            $this->sessionCreate('error', 'message', 'Some items already in your order!');
            return redirect('/carts');
       }


       if ($cartItemByClientIp) {
           $orderId = (new Checkout)->processOrder($addressId, $cartItemByClientIp);
       }

       if ($payment == "onDelivery") {
            (new Checkout)->onDelivery($orderId, $clientIp, $address['email'], $amount, 'USD');
        } 
        else {
            
            (new Checkout)->paypalPay($amount);

       }

       return redirect("thankyou");
        
    }

    // Returned data from PAYPAL 
    public function returndata()
    {
        (new Checkout)->finalOrder();

        return redirect("thankyou");

    }

    // If showhow cancel the transaction show cancel page
    public function cancel()
    {
        return view("front/cancel");
    }

    // Thank you page 
    public function thankyou()
    {
        return view("front/thankyou");
    }

    private function sessionCreate($message, $name, $text)
    {
        sessionStart();
        sessionUnset($message);
        sessionSet($name, $text);
    }
}