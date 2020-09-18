<?php

namespace Codificar\PaymentGateways\Omnipay;

use Payment;
use Provider;
use Transaction;
use User;
use LedgerBankAccount;

interface IGateway
{

    /**
     * Charge a credit card with split rules
     *
     * @param Payment       $payment
     * @param Provider      $provider 
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount 
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. 
     * @param User          $user               The customer that will be charged in this request
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function chargeWithSplit(Payment $payment, Provider $provider, $totalAmount, $providerAmount, $description, $capture = true, User $user = null);

    /**
     * Charge a credit card
     *
     * @param Payment       $payment
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param String        $description        An arbitrary string which you can attach to describe a Charge object
     * @param Boolean       $capture            Whether to immediately capture the charge. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. 
     * @param User          $user               The customer that will be charged in this request
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function charge(Payment $payment, $amount, $description, $capture = true, $user = null);

    /**
     * Capture the payment of an existing, uncaptured, charge with split rules
     *
     * @param Transaction   $transaction
     * @param Provider      $provider
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Decimal       $providerAmount 
     * @param Payment       $payment               
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function captureWithSplit(Transaction $transaction, Provider $provider, $totalAmount, $providerAmount, Payment $payment = null);

    /**
     * Capture the payment of an existing, uncaptured, charge
     *
     * @param Transaction   $transaction
     * @param Decimal       $totalAmount        A positive decimal representing how much to charge
     * @param Payment       $payment     
     * 
     * @return Array ['success', 'status', 'captured', 'paid', 'transaction_id']
     */
    public function capture(Transaction $transaction, $amount, Payment $payment = null);

    /**
     * Refund a charge that has previously been created with split rules
     *
     * @param Transaction   $transaction
     * @param Payment       $payment 
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */
    public function refundWithSplit(Transaction $transaction, Payment $payment);

    /**
     * Refund a charge that has previously been created
     *
     * @param Transaction   $transaction
     * @param Payment       $payment 
     * 
     * @return Array ['success', 'status', 'transaction_id']
     */
    public function refund(Transaction $transaction, Payment $payment);

    /**
     * Retrieves the details of a charge that has previously been created
     *
     * @param Transaction   $transaction
     * 
     * @return Array ['success', 'transaction_id', 'amount', 'destination', 'status', 'card_last_digits']
     */
    public function retrieve(Transaction $transaction, Payment $payment = null);

    /**
     *  Create a new credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     * 
     * @return Array ['success', 'token', 'card_token', 'customer_id', 'card_type', 'last_four']
     */
    public function createCard(Payment $payment, $user = null);

    /**
     *  Delete a existing credit card
     *
     * @param Payment       $payment
     * @param User          $user               The customer that this card belongs to
     * 
     * @return Array ['success']
     */
    public function deleteCard(Payment $payment, User $user = null);


    /**
     *  Create accounts for users
     *
     * @param LedgerBankAccount       $ledgerBankAccount
     * 
     * @return Array ['success', 'recipient_id']
     */
    public function createOrUpdateAccount(LedgerBankAccount $ledgerBankAccount);

    /**
     *  Return a gateway fee
     * 
     * @return Decimal
     */
    public function getGatewayFee();

    /**
     *  Return a gateway tax
     * 
     * @return Decimal
     */
    public function getGatewayTax();

    /**
     *  Return a date for the next compensation
     * 
     * @return Carbon
     */
    public function getNextCompensationDate();


    /**
     *  Return a bool value that determine if auto transfer to provider is enabled
     * 
     * @return bool
     */
    public function checkAutoTransferProvider();
}
