<?php

return array(
	'500' 					=> 'Oops we were unable to contact the server, please try again later.',
	'504' 					=> 'Oops we were unable to contact the server, please try again later.',
	'refused' 				=> 'Oops Action/Transaction Declined',
	
	// Adiq message
	'adiq_internal_error' 	=> 'Oops, we were unable to complete your payment, please try another payment method.',
	'adiq_error_brand'		=> 'Oops, we were unable to complete your payment, please try a different card.',

	//Ipag message
	'not_authorized'		=> 'OOPS Action/Transaction Unauthorized',
	'customer_blacklisted'	=> 'The gateway did not accept your card, please try in 2 or 3 days or with another payment method',

	//Pagarme message
	'recipient_not_found_or_outdated' 		=> 'Recipient not found and/or out of date',
	'card_not_registered' 					=> 'Card not registered, please register again!',
	'transaction_declined_cpf' 				=> 'Transaction declined: Invalid CPF, please check your details!',
	'transaction_declined' 					=> 'Transaction declined, please try again with another card or different payment method!',
	'transaction_declined_address_number' 	=> 'Transaction declined: Incorrect or empty address number, please try again with another address!',
	'transaction_declined_neighborhood' 	=> 'Incorrect or empty neighborhood, please try again with another address!',

	'card_not_authorized' 	=> 'Oops!Not authorized by the card issuer.',
	'customer_card_invalid' 	=> 'Oops! Invalid card details, please check and try again.',
	'canceled'	=> 'Oops canceled, please check your data and try again.',
	'error_retrieve_webhook' => 'Error recovering webhooks: :error',
	'error_pix_gateway_disabled' => 'It was not possible to recover webhooks, as the payment gateway is not active',
	'error_ipag_pix_gateway_disabled' => 'To recover webhooks, you need to have the active iPag gateway',
	'admin_recipient_not_found' => 'It was not possible to complete the request, please try later or with another payment method.',
);