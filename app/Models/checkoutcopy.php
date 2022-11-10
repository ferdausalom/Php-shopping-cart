<?php

namespace App\Models;

use PDO;
use Omnipay\Omnipay;

define('CLIENT_ID', "AR0w8c1ml-O8MNccspoXFdPh26s7PoAPtvuJG5PqeF45sNccOhEWm_gWUYy4mAhOEpKndqibNjDIPHN0");
define('SECRET_KEY', "EAw3_R7xlXBNkp8lwtAwMisQA92YVeYOAe2EEiuO4rjYvkI8FWcfmnNlOh0GefegWef1vmnR-KX70ODR");
define('PAYPAL_RETURN_URL', "http://ecommerce.test/success");
define('PAYPAL_CANCEL_URL', "http://ecommerce.test/cancel");

class Checkout
{

    public $orderId;

    public function storeAddress($address)
    {
        $insert = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            "addresses",
            implode(", ", array_keys($address)),
            ":". implode(", :", array_keys($address))
        );

        $pdo = DBConnect();

        $stm = $pdo->prepare($insert);
        $stm->execute($address);
        return $pdo->lastInsertId();
    }

    public function isOrderExists($cartItemByClientIp)
    {
        $select = "SELECT product_id, quantity FROM orders";

        $stm = DBConnect()->prepare($select);
        $stm->execute();
        $items = $stm->fetchAll(PDO::FETCH_OBJ);

        
        foreach($items as $item)
        {
            foreach($cartItemByClientIp as $cartItem)
            {

                if ($item->product_id == $cartItem->product_id && $item->quantity == $cartItem->quantity) {
                    
                    return true;
                    
                }

            }
        }

    }

    public function processOrder($address_id, $cartItemByClientIp)
    {
        foreach($cartItemByClientIp as $item)
        {
            $data = [
                'user_id' => $item->user_id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'user_id' => $item->user_id,
                'address_id' => $address_id
            ];

            $insert = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                "orders",
                 implode(", ", array_keys($data)),
                ":". implode(", :", array_keys($data))
    
            );

            $pdo = DBConnect();
            $stm =  $pdo->prepare($insert);
            $stm->execute($data);
            return $pdo->lastInsertId();
        }        
    }

    public function onDelivery($orderId, $clientIp, $email, $amount, $currency)
    {
        $data = [
            "order_id" => $orderId,
            "payment_id" => $clientIp."#".rand(),
            "payer_id" => $clientIp,
            "payer_email" => $email,
            "amount" => $amount,
            "currency" => $currency,
            "payment_method" => "Cash on delivery"
        ];

        $insert = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            "payments",
            implode(", ", array_keys($data)),
            ":". implode(", :", array_keys($data))
        );
        
       try {
        $stm = DBConnect()->prepare($insert);
        $stm->execute($data);
       } catch (\Throwable $th) {
        die($th->getMessage());
       }
    }

    public function paypal($orderId, $amount, $currency)
    {
        $this->orderId = $orderId;

       $getway = Omnipay::create("PayPal_Rest");

       $getway->setClientId(CLIENT_ID);
       $getway->setSecret(SECRET_KEY);
       $getway->setTestMode(true);

       try {
            $response = $getway->purchase([
                'amount' => $amount,
                'currency' => $currency,
                'returnUrl' => PAYPAL_RETURN_URL,
                'cancelUrl' => PAYPAL_CANCEL_URL
            ])->send();

            if ($response->isRedirect()) {
                $response->redirect();
            }

       } catch (\Throwable $th) {
        echo $th->getMessage();
       }
    }

    public function paypalProce()
    {
        $getway = Omnipay::create("PayPal_Rest");

        $getway->setClientId(CLIENT_ID);
        $getway->setSecret(SECRET_KEY);
        $getway->setTestMode(true);

        // dd($_GET);

        if (
            array_key_exists('paymentId', $_GET) && 
            array_key_exists('token', $_GET)  && 
            array_key_exists('PayerID', $_GET)
            )
            {
            $transaction = $getway->completePurchase([
                'payer_id' => $_GET['PayerID'],
                'transactionReference' => $_GET['paymentId']
            ]);

            $respose = $transaction->send();

            if ($respose->isSuccessful()) {
                $data = $respose->getData();

                $processData = [
                    "payment_id" => $data['id'],
                    "payer_id" => $data['payer']['payer_info']['payer_id'],
                    "payer_email" => $data['payer']['payer_info']['email'],
                    "amount" => $data['transactions'][0]['amount']['total'],
                    "currency" => 'USD',
                    "payment_method" => "Paypal",
                    "status"=> $data['state']
                ];
        
                $insert = sprintf(
                    "INSERT INTO %s (%s) VALUES (%s)",
                    "payments",
                    implode(", ", array_keys($processData)),
                    ":". implode(", :", array_keys($processData))
                );
                
               try {
                $stm = DBConnect()->prepare($insert);
                $stm->execute($processData);

                return true;
               } catch (\Throwable $th) {
                die($th->getMessage());
               }
            }
        }

    }
  
}
