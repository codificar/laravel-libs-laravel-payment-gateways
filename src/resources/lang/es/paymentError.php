<?php

return array(
	'500' 					=> 'No pudimos comunicarnos con el servidor. Vuelve a intentarlo más tarde.',
	'504' 					=> 'No pudimos comunicarnos con el servidor. Vuelve a intentarlo más tarde.',
	'refused' 				=> 'Acción/Transacción Rechazada',
	
	// Adiq message
	'adiq_internal_error' 	=> 'No pudimos completar su pago, intente con otro método de pago.',
	'adiq_error_brand'		=> 'No pudimos completar su pago, intente con otra tarjeta.',

	//Ipag message
	'not_authorized'		=> 'Ups Acción/Transacción no autorizada',
	'customer_blacklisted'	=> 'La pasarela no aceptó su tarjeta, intente en 2 o 3 días o con otro método de pago',

	//Pagarme message
	'recipient_not_found_or_outdated' 		=> 'Destinatario no encontrado y/o desactualizado',
	'card_not_registered' 					=> '¡Tarjeta no registrada, por favor regístrese de nuevo!',
	'transaction_declined_cpf' 				=> 'Transacción rechazada: CPF no válido, ¡verifique sus detalles!',
	'transaction_declined' 					=> 'Transacción rechazada, intente nuevamente con otra tarjeta o método de pago diferente.',
	'transaction_declined_address_number' 	=> 'Transacción rechazada: número de dirección incorrecto o vacío, ¡inténtalo de nuevo con otra dirección!',
	'transaction_declined_neighborhood' 	=> 'Barrio incorrecto o vacío, inténtalo de nuevo con otra dirección.',

	'card_not_authorized' 	=> '¡Ups!No autorizado por el emisor de la tarjeta.',
	'customer_card_invalid' 	=> '¡Ups! Los detalles de la tarjeta no son válidos, verifíquelos e inténtelo de nuevo.',
	'canceled'	=> '¡Ups! cancelado, consulte sus datos y vuelva a intentarlo.',
	'error_retrieve_webhook' => 'Error de recuperación de webhooks: :error',
	'error_pix_gateway_disabled' => 'No fue posible recuperar webhooks, ya que la pasarela de pago no está activa',
	'error_ipag_pix_gateway_disabled' => 'Para recuperar webhooks, debe tener la puerta de enlace de IPAG activa',
	'admin_recipient_not_found' => 'No fue posible completar la solicitud, intente más tarde o con otro método de pago.',
);