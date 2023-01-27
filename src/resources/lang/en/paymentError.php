<?php

return array(
	'500' 					=> 'Oops we were unable to contact the server, please try again later.',
	'504' 					=> 'Oops we were unable to contact the server, please try again later.',
	'refused' 				=> 'Oops Action/Transaction Declined',
	
	// Adiq message
	'adiq_internal_error' 	=> 'Oops, we were unable to complete your payment, please try another payment method.',
	'adiq_error_brand'		=> 'Oops, we were unable to complete your payment, please try a different card.',

	//Ipag message
	'not_authorized'		=> 'Oops Action/Transaction Authorized',
	'customer_blacklisted'	=> 'The gateway did not accept your card, please try in 2 or 3 days or with another payment method',

	//Pagarme message
	'recipient_not_found_or_outdated' 		=> 'Recipient not found and/or out of date',
	'card_not_registered' 					=> 'Card not registered, please register again!',
	'transaction_declined_cpf' 				=> 'Transaction declined: Invalid CPF, please check your details!',
	'transaction_declined' 					=> 'Transaction declined, please try again with another card or different payment method!',
	'transaction_declined_address_number' 	=> 'Transaction declined: Incorrect or empty address number, please try again with another address!',
	'transaction_declined_neighborhood' 	=> 'Incorrect or empty neighborhood, please try again with another address!',

	'customer_card_invalid' 	=> 'Oops! Invalid card details, please check and try again.',
);
