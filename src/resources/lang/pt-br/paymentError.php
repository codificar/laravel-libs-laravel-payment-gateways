<?php

return array(
	'500' 					=> 'Oops não conseguimos contatar o servidor, por favor tente mais tarde.',
	'504' 					=> 'Oops não conseguimos contatar o servidor, por favor tente mais tarde.',
	'refused' 				=> 'Oops Ação/Transação Recusado(a)',
	
	// Adiq message
	'adiq_internal_error' 	=> 'Oops, não conseguimos finalizar o seu pagamento, por favor tente com outra forma de pagamento.',
	'adiq_error_brand'		=> 'Oops, não conseguimos finalizar o seu pagamento, por favor tente com um cartão de outra bandeira.',

	//Ipag message
	'not_authorized'		=> 'Oops Ação/Transação não autorizada',
	'customer_blacklisted'	=> 'O gateway não aceitou seu cartão, por favor tente mais tarde ou com outro metodo de pagamento',

	//Pagarme message
	'recipient_not_found_or_outdated' 		=> 'Recebedor não encontrado e/ou desatualizado.',
	'card_not_registered' 					=> 'Cartão não cadastrado, por favor cadastre novamente!',
	'transaction_declined_cpf' 				=> 'Transação recusada: CPF inválido, por favor verifique os seus dados!',
	'transaction_declined' 					=> 'Transação recusada, por favor tente novamente com outro cartão ou método de pagamento diferente!',
	'transaction_declined_address_number' 	=> 'Transação recusada: Número do endereço incorreto ou vazio, por favor tente novamente com outro endereço!',
	'transaction_declined_neighborhood' 	=> 'Bairro incorreto ou vazio, por favor tente novamente com outro endereço!',
	
	'card_not_authorized' 	=> 'Oops! Não autorizado pelo emissor do cartão.',
	'customer_card_invalid' 	=> 'Oops! dados de cartão inválidos, por favor verifique e tente novamente.',
	'canceled'	=> 'Oops cancelado, por favor verifique seu dados e tente novamente.',
	'error_retrieve_webhook' => 'Erro ao recuperar webhooks: :error',
	'error_pix_gateway_disabled' => 'Não foi possível recuperar webhooks, pois o gateway de pagamento não está ativo',
	'error_ipag_pix_gateway_disabled' => 'Para recuperar webhooks, é necessário ter o gateway ipag ativo',
	'admin_recipient_not_found' => 'Não foi possível concluir a requisição, por favor tente mais tarde ou com outro método de pagamento.',
);