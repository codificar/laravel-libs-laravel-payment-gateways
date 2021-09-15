<?php

return array(

	//Textos Gerais
	'conf'						=> 'Settings',
	'gateways'					=> 'Payment methods',
	'edit_confirm'				=> 'Are you sure you want to update the gateway settings?',
	'yes'						=> 'Yea',
	'no'						=> 'No',
	'gateways'					=> 'Payment methods',
	'home'						=> 'Home',
	'save'						=> 'To save',
	'edit_confirm'				=> 'Are you sure you want to update the gateway settings?',
	'success_set_gateway'		=> 'Settings saved successfully.',
	'failed_set_gateway'		=> 'There was a failure to update your gateway settings. Try again!',
	'gateway_has_change'		=> 'The card gateway has been changed. Updating the cards to the new gateway can take time. Do not change the gateway again for at least ',
	'obs_billet_gateway'		=> 'Note: the boleto gateway will be the same as the credit card gateway.',
	'obs_billet_gateway2'		=> 'Important: the "Payments" and "Balance" menu are dynamic',
	'obs_billet_gateway2_msg'	=> 'If at least one prepaid payment method is enabled for the user (boleto and / or card) the "Balance" menu will appear in the app and the "Payments" menu on the users panel. The same happens with the provider and corp.',
	'payment_methods'			=> 'Payment methods',
	'choose_payment_methods'	=> 'Choose Payment Methods',
	'money'						=> 'Cash',
	'card'						=> 'Credit card',
	'carto'						=> 'Carto',
	'machine'					=> 'Machine',
	'debitCard'					=> 'Debit card',
	'crypt_coin'				=> 'Crypto Currency',
	'payment_balance'			=> 'Balance Payment',
	'payment_prepaid'			=> 'Prepaid',
	'payment_prepaid_msg'		=> 'Allows the user and / or provider to add balance on the platform (card or boleto)',
	'payment_billing'			=> 'Billing Payment',
	'pay_gateway'				=> 'Payment Gateway - Card',
	'default_pay_gate'			=> 'Standard Payment Intermediary',
	'save_data'					=> 'Save Data',
	'default_pay_gate_boleto'	=> 'Standard Intermediary for Boleto Payment',
	'compensate_provider_days'	=> 'Days to compensate the provider',
	'compensate_provider_msg'	=> 'Define how many days the provider will receive the balance on your account statement when payment is made with a card. For the provider to receive the moment the request is finalized, enter 0. If no value is selected, the gateway compensation time (usually 31 days) will be considered.',
	'uploaded'					=> 'uploaded',

	//Gateways de pagamento
	'pagarme'					=> 'Pay me',
	'stripe'					=> 'Stripe',
	'zoop'						=> 'Zoop',
	'cielo'						=> 'Cielo',
	'braspag'					=> 'Braspag',
	'braspag_cielo_ecommerce'	=> 'Braspag Cielo Ecommerce',
	'getnet'					=> 'GetNet',
	'directpay'					=> 'Directpay',
	'bancard'					=> 'Bancard',
	'transbank'					=> 'Transbank',
	'pagarapido'				=> 'Paga Rapido',
	'adiq'						=> 'Adiq',
	'bancinter'					=> 'Banco Inter',
	'ipag'						=> 'Ipag',

	

	
	//Chaves dos gateways
	'pagarme_settings'			=> 'Pagar.me Settings',
	'transbank_settings'		=> 'Transbank Settings',
	'byebnk_settings'			=> 'ByeBnk Settings',
	'stripe_settings'			=> 'Stripe Settings',
	'brain_tree_settings'		=> 'Brain Tree Settings',
	'zoop_settings'				=> 'Zoop Settings',
	'bancard_settings'			=> 'Bancard Settings',
	'mercadopago_settings'		=> 'Market Paid Settings',
	'pagarapido_settings'		=> 'Paga Rapido Settings',
	'advanced_settings'			=> 'Advanced Settings',
	'pay_encryption_key_me'		=> 'Pagar.me Encryption Key',
	'pagarme_recipient_id'		=> 'Pagar.me Recipient Id',
	'pagarme_api_key'			=> 'Pagar.me API Key',
	'byebnk_api_key'			=> 'ByeBnk API key',
	'byebnk_api_user'			=> 'ByeBnk User Id',
	'stripe_secret'				=> 'Stripe Private Key',
	'stripe_public'				=> 'Stripe Public Key',
	'stripe_connect'			=> 'Use Stripe Connect',
	'custom_account'			=> 'Managed Accounts',
	'express_account'			=> 'Express Accounts',
	'standard_account'			=> 'Standard Accounts',
	'braintree_environment'		=> 'Brain Tree Environment',
	'braintree_id'				=> 'Brain Tree Merchant ID',
	'braintree_public'			=> 'Brain Tree Public Key',
	'braintree_secret'			=> 'Brain Tree Private Key',
	'braintree_cript'			=> 'Brain Tree Client Side Encryption Key',
	'zoop_marketplace_id'		=> 'Zoop Marketplace Key',
	'zoop_publishable_key'		=> 'Zoop Production Key',
	'zoop_seller_id'			=> 'Zoop Id Seller',
	'bancard_public_key'		=> 'Bancard Public Key',
	'bancard_private_key'		=> 'Bancard Private Key',
	'transbank_private_key'		=> 'Transbank Private Key',
	'transbank_commerce_code'	=> 'Code of Commerce',
	'transbank_public_cert'		=> 'Transbank Public Certificate',
	'mercadopago_public_key'	=> 'Paid Market Public Key',
	'mercadopago_access_token'	=> 'Paid Market Access Token',
	'payment'					=> 'Payment',
	'stripe_total_split_refund_message'	=> 'When carrying out the chargeback, the company will reverse the amount sent to the provider (put No, if the company is going to pay for the laziness)',
	'stripe_total_split_refund'	=> 'Reverse the entire amount of the race (Company does not lose money by paying the amount sent to the provider)',
	'boleto_gateway'			=> 'Bill of Sale Gateway',
	'gerencianet_settings'		=> 'Gerencianet Settings',
	'gerencianet_client_id'		=> 'Gestãonet Client ID',
	'gerencianet_client_secret'	=> 'Gerencianet Client Secret',
	'operation_mode'			=> 'Operation mode',
	'choose_payment_methods'	=> 'Choose Payment Methods',
	'prepaid_billet'			=> 'Insertion of balance in the boleto',
	'prepaid_card'				=> 'Inserting credit card balance',
	'prepaid_min_billet_value'	=> 'Minimum amount to generate a boleto',
	'prepaid_tax_billet'		=> 'Fee to generate a boleto',
	'carto_keys'				=> 'Card Credentials',
	'carto_login'				=> 'Carto Login',
	'carto_password'			=> 'Card Password',
	'carto_password'			=> 'Card Password',
	'bancryp_keys'				=> 'Bancryp credentials',
	'bancryp_api_key'			=> 'Bancryp Api Key',
	'bancryp_secret_key'		=> 'Bancryps Secret Key',
	'Sandbox'					=> 'Sandbox',
	'production'				=> 'Production',
	'user'						=> 'User',
	'provider'					=> 'Provider',
	'corp'						=> 'Institution',
	'bank_stripe_error'			=> 'Test bank accounts cannot be used with the Stripe live key',
	'cielo_merchant_id'			=> 'Cielo Merchant Id',
	'cielo_merchant_key'		=> 'Cielo Merchant Key',
	'braspag_merchant_id'		=> 'Braspag Merchant Id',
	'braspag_merchant_key'		=> 'Braspag Merchant Key',
	'braspag_token'				=> 'Braspag Token',
	'braspag_cielo_ecommerce'	=> 'Braspag Cielo Ecommerce',
	'braspag_client_id'			=> 'Customer ID',
	'braspag_client_secret'		=> 'Customer password',
	'getnet_client_id'			=> 'GetNet Client Id',
	'getnet_client_secret'		=> 'GetNet Client Secret',
	'getnet_seller_id'			=> 'GetNet Seller Id',
	'directpay_encrypt_key'		=> 'Directpay Encrypt Key',
	'directpay_encrypt_value'	=> 'Directpay Encrypt Value',
	'directpay_requester_id'	=> 'Directpay Requester Id',
	'directpay_requester_password'	=> 'Directpay Requester Password',
	'directpay_requester_token'	=> 'Directpay Requester Token',
	'directpay_unique_trx_id'	=> 'Directpay trx unique id',
	'directpay_name'			=> 'Directpay',
	'pagarapido_login'			=> 'Login',
	'pagarapido_password'		=> 'Password',
	'pagarapido_gateway_key'	=> 'Gateway Key',
	'general_settings'			=> 'General Settings',
	'earnings_report_weekday'	=> 'First day of the week on the earnings report',
	'earnings_report_weekday_msg'=> 'Define the first day of the week (weekday) that will start counting in the earnings report',
	'show_user_account_statement'=> 'Show Account Statement menu to the user',
	'show_user_account_statement_msg'=> 'Show Account Statement menu to the user in the app and on the user panel',
	'sunday'					=> 'Sunday',
	'monday'					=> 'Monday',
    'tuesday'					=> 'Tuesday',
    'wednesday'					=> 'Wednesday',
    'thursday'					=> 'Thursday',
    'friday'					=> 'Friday',
	'saturday'					=> 'Saturday',
	'ipag_api_id'				=> 'Ipag API Id',
	'ipag_api_key'				=> 'Ipag API Key',
	'ipag_token'				=> 'Ipag Token',,
	'ipag_antifraud_title'		=> 'Do you want to make transactions with anti-fraud?',

	'adiq_client_id'			=> 'Adiq Client Id',
	'adiq_client_secret'		=> 'Adiq Client Secret',
	'adiq_token'				=> 'Adiq Transaction Token',

	'banco_inter_settings'		=> 'Banco Inter Configurations',
	'banco_inter_account'		=> 'Banco Inter account number',
	'cnpj_for_banco_inter'		=> 'CNPJ',
	'banco_inter_crt'			=> 'Certificate',
	'banco_inter_key'			=> 'Key',
);
