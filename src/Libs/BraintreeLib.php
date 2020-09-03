<?php 

class Braintree{
   public static function create($paymentToken){
      Braintree_Configuration::environment(Settings::findByKey('braintree_environment'));
      Braintree_Configuration::merchantId(Settings::findByKey('braintree_merchant_id'));
      Braintree_Configuration::publicKey(Settings::findByKey('braintree_public_key'));
      Braintree_Configuration::privateKey(Settings::findByKey('braintree_private_key'));
      $result = Braintree_Customer::create(array(
               'paymentMethodNonce' => $paymentToken
      ));

      return $result;
   }
}