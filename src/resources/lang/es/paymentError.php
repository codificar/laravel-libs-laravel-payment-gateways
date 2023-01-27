<?php

return array(
	'500' 					=> 'No pudimos comunicarnos con el servidor. Vuelve a intentarlo más tarde.',
	'504' 					=> 'No pudimos comunicarnos con el servidor. Vuelve a intentarlo más tarde.',
	'refused' 				=> 'Acción/Transacción Rechazada',
	
	// Adiq message
	'adiq_internal_error' 	=> 'No pudimos completar su pago, intente con otro método de pago.',
	'adiq_error_brand'		=> 'No pudimos completar su pago, intente con otra tarjeta.',

	//Ipag message
	'not_authorized'		=> 'Acción/Transacción Autorizada',
	'customer_blacklisted'	=> 'La pasarela no aceptó su tarjeta, intente en 2 o 3 días o con otro método de pago',

	//Pagarme message
	'recipient_not_found_or_outdated' 		=> 'Destinatario no encontrado y/o desactualizado',
	'card_not_registered' 					=> '¡Tarjeta no registrada, por favor regístrese de nuevo!',
	'transaction_declined_cpf' 				=> 'Transacción rechazada: CPF no válido, ¡verifique sus detalles!',
	'transaction_declined' 					=> 'Transacción rechazada, intente nuevamente con otra tarjeta o método de pago diferente.',
	'transaction_declined_address_number' 	=> 'Transacción rechazada: número de dirección incorrecto o vacío, ¡inténtalo de nuevo con otra dirección!',
	'transaction_declined_neighborhood' 	=> 'Barrio incorrecto o vacío, inténtalo de nuevo con otra dirección.',

	'customer_card_invalid' 	=> '¡Ups! Los detalles de la tarjeta no son válidos, verifíquelos e inténtelo de nuevo.',
);