<?php

return array(

	//Textos Gerais
	'conf'						=> 'Ajustes',
	'gateways'					=> 'Formas de pago',
	'edit_confirm'				=> '¿Está seguro de que desea actualizar la configuración de la puerta de enlace?',
	'yes'						=> 'Sí',
	'no'						=> 'No',
	'gateways'					=> 'Formas de pago',
	'home'						=> 'Casa',
	'save'						=> 'Ahorrar',
	'edit_confirm'				=> '¿Está seguro de que desea actualizar la configuración de la puerta de enlace?',
	'success_set_gateway'		=> 'Configuración guardada correctamente.',
	'failed_set_gateway'		=> 'No se pudo actualizar la configuración de la puerta de enlace. ¡Inténtalo de nuevo!',
	'gateway_has_change'		=> 'Se ha cambiado la puerta de enlace de la tarjeta. Actualizar las tarjetas a la nueva puerta de enlace puede llevar tiempo. No vuelva a cambiar la puerta de enlace durante al menos ',
	'obs_billet_gateway'		=> 'Nota: la puerta de enlace de boleto será la misma que la puerta de enlace de la tarjeta de crédito.',
	'obs_billet_gateway2'		=> '- Los menús "Pagos" y "Saldo" son dinámicos',
	'obs_billet_gateway2_msg'	=> 'Si al menos un método de pago prepago está habilitado para el usuario (boleto y / o tarjeta), aparecerá el menú "Saldo" en la aplicación y el menú "Pagos" en el panel del usuario. Lo mismo ocurre con el proveedor y corp.',
	'payment_methods'			=> 'Formas de pago',
	'choose_payment_methods'	=> 'Elija métodos de pago',
	'money'						=> 'Dinero en efectivo',
	'card'						=> 'Tarjeta de crédito',
	'carto'						=> 'Carto',
	'machine'					=> 'Máquina',
	'debitCard'					=> 'Tarjeta de débito',
	'crypt_coin'				=> 'Moneda criptográfica',
	'payment_balance'			=> 'Pago del saldo',
	'payment_prepaid'			=> 'Pagado por adelantado',
	'payment_prepaid_msg'		=> 'Permite al usuario y / o proveedor agregar saldo en la plataforma (tarjeta o boleto)',
	'payment_billing'			=> 'Pago de facturación',
	'pay_gateway'				=> 'Pasarela de pago - Tarjeta',
	'default_pay_gate'			=> 'Intermediario de pago estándar',
	'save_data'					=> 'Guardar datos',
	'default_pay_gate_boleto'	=> 'Intermediario estándar para el pago de boleto',
	'compensate_provider_days'	=> 'Días para compensar al proveedor',
	'compensate_provider_msg'	=> 'Defina cuántos días recibirá el proveedor el saldo en su estado de cuenta cuando el pago se realice con una tarjeta. Para que el proveedor lo reciba en el momento en que finalice la solicitud, ingrese 0. Si no se selecciona ningún valor, se considerará el tiempo de compensación de la puerta de enlace (generalmente 31 días).',
	'uploaded'					=> 'enviado',
	'select'					=> 'Seleccione',

	//Gateways de pagamento
	'pagarme'					=> 'Págame',
	'stripe'					=> 'Raya',
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
	'juno'						=> 'Juno',
	'ipag'						=> 'Ipag',
	
	
	//Chaves dos gateways
	'pagarme_settings'			=> 'Configuración de Pagar.me',
	'transbank_settings'		=> 'Configuración de Transbank',
	'byebnk_settings'			=> 'Configuración de ByeBnk',
	'stripe_settings'			=> 'Configuración de bandas',
	'brain_tree_settings'		=> 'Configuración del árbol del cerebro',
	'zoop_settings'				=> 'Configuración de Zoop',
	'bancard_settings'			=> 'Configuración de tarjeta bancaria',
	'mercadopago_settings'		=> 'Configuración de pago del mercado',
	'pagarapido_settings'		=> 'Configuración de Paga Rapido',
	'advanced_settings'			=> 'Configuraciones avanzadas',
	'pagarme_secret_key'		=> 'Clave secreta Pagar.me',
	'pagarme_recipient_id'		=> 'Identificación del destinatario de Pagar.me',
	'byebnk_api_key'			=> 'ByeBnk API key',
	'byebnk_api_user'			=> 'ID de usuario de ByeBnk',
	'stripe_secret'				=> 'Clave privada de banda',
	'stripe_public'				=> 'Clave pública de banda',
	'stripe_connect'			=> 'Usar Stripe Connect',
	'custom_account'			=> 'Cuentas administradas',
	'express_account'			=> 'Cuentas Express',
	'standard_account'			=> 'Cuentas estándar',
	'braintree_environment'		=> 'Entorno del árbol del cerebro',
	'braintree_id'				=> 'ID de comerciante de árbol cerebral',
	'braintree_public'			=> 'Clave pública del árbol del cerebro',
	'braintree_secret'			=> 'Clave privada de Brain Tree',
	'braintree_cript'			=> 'Clave de cifrado del lado del cliente de Brain Tree',
	'zoop_marketplace_id'		=> 'Clave de Zoop Marketplace',
	'zoop_publishable_key'		=> 'Zoop Production Key',
	'zoop_seller_id'			=> 'Zoop Id Seller',
	'bancard_public_key'		=> 'Clave pública de Bancard',
	'bancard_private_key'		=> 'Clave privada de Bancard',
	'transbank_private_key'		=> 'Clave privada de Transbank',
	'transbank_commerce_code'	=> 'Código de Comercio',
	'transbank_public_cert'		=> 'Certificado Público Transbank',
	'mercadopago_public_key'	=> 'Clave pública de mercado pagado',
	'mercadopago_access_token'	=> 'Token de acceso al mercado de pago',
	'payment'					=> 'Pago',
	'stripe_total_split_refund_message'	=> 'Al realizar el contracargo, la empresa revertirá el monto enviado al proveedor (ponga No, si la empresa va a pagar por la pereza)',
	'stripe_total_split_refund'	=> 'Revertir el importe total de la carrera (la empresa no pierde al pagar el importe enviado al proveedor)',
	'boleto_gateway'			=> 'Pasarela de factura de venta',
	'gerencianet_settings'		=> 'Configuración de Gerencianet',
	'gerencianet_client_id'		=> 'ID de cliente Gestãonet',
	'gerencianet_client_secret'	=> 'Secreto del cliente de Gerencianet',
	'operation_mode'			=> 'Modo de operación',
	'choose_payment_methods'	=> 'Elija métodos de pago',
	'prepaid_billet'			=> 'Inserción de equilibrio en el boleto',
	'prepaid_card'				=> 'Insertar saldo de tarjeta',
	'prepaid_min_billet_value'	=> 'Cantidad mínima para generar un boleto',
	'prepaid_tax_billet'		=> 'Tarifa para generar un boleto',
	'carto_keys'				=> 'Credenciales de tarjeta',
	'carto_login'				=> 'Carto Login',
	'carto_password'			=> 'Contraseña de la tarjeta',
	'carto_password'			=> 'Contraseña de la tarjeta',
	'bancryp_keys'				=> 'Credenciales de Bancryp',
	'bancryp_api_key'			=> 'Bancryp Api Key',
	'bancryp_secret_key'		=> 'Clave secreta de Bancryp',
	'Sandbox'					=> 'Salvadera',
	'production'				=> 'Producción',
	'user'						=> 'Usuario',
	'provider'					=> 'Proveedor',
	'corp'						=> 'Institución',
	'bank_stripe_error'			=> 'Las cuentas bancarias de prueba no se pueden utilizar con la tecla activa de Stripe',
	'cielo_merchant_id'			=> 'Cielo Merchant Id',
	'cielo_merchant_key'		=> 'Cielo Merchant Key',
	'braspag_merchant_id'		=> 'Identificación de comerciante de Braspag',
	'braspag_merchant_key'		=> 'Llave de comerciante de Braspag',
	'braspag_token'				=> 'Ficha Braspag',
	'braspag_cielo_ecommerce'	=> 'Braspag Cielo Ecommerce',
	'braspag_client_id'			=> 'Identificación del cliente',
	'braspag_client_secret'		=> 'Contraseña del cliente',
	'getnet_client_id'			=> 'GetNet Client Id',
	'getnet_client_secret'		=> 'Secreto del cliente GetNet',
	'getnet_seller_id'			=> 'GetNet Seller Id',
	'directpay_encrypt_key'		=> 'Clave de cifrado de Directpay',
	'directpay_encrypt_value'	=> 'Valor de cifrado de Directpay',
	'directpay_requester_id'	=> 'Id. De solicitante de Directpay',
	'directpay_requester_password'	=> 'Contraseña de solicitante de Directpay',
	'directpay_requester_token'	=> 'Token de solicitante de Directpay',
	'directpay_unique_trx_id'	=> 'Identificación única de Directpay trx',
	'directpay_name'			=> 'Directpay',
	'pagarapido_login'			=> 'Login',
	'pagarapido_password'		=> 'Contrasena',
	'pagarapido_gateway_key'	=> 'Gateway Key',
	'general_settings'			=> 'Configuración general',
	'earnings_report_weekday'	=> 'Primer día de la semana en el informe de ganancias',
	'earnings_report_weekday_msg'=> 'Defina el primer día de la semana (día de la semana) que comenzará a contarse en el informe de ganancias',
	'show_user_account_statement'=> 'Mostrar el menú Estado de cuenta al usuario',
	'show_user_account_statement_msg'=> 'Mostrar el menú Estado de cuenta al usuario en la aplicación y en el panel de usuario',
	'sunday'					=> 'Domingo',
	'monday'					=> 'Lunes',
    'tuesday'					=> 'Martes',
    'wednesday'					=> 'Miércoles',
    'thursday'					=> 'Jueves',
    'friday'					=> 'Viernes',
	'saturday'					=> 'Sábado',
	'adiq_client_id'			=> 'ID de cliente de Adiq',
	'adiq_client_secret'		=> 'Secreto de cliente de Adiq',
	'adiq_token'				=> 'Token de transacción Adiq',

	'banco_inter_settings'		=> 'Configuración  Banco Inter',
	'banco_inter_account'		=> 'Número de cuenta de Banco Inter',
	'cnpj_for_banco_inter'		=> 'CNPJ',
	'banco_inter_crt'			=> 'Certificado',
	'banco_inter_key'			=> 'Clave',
	'ipag_api_id'				=> 'Ipag API Id',
	'ipag_api_key'				=> 'Ipag API Key',
	'ipag_token'				=> 'Ipag Token',
	'ipag_expiration_time'		=> 'Tiempo de expiración de Pix',
	'ipag_version'				=> 'Versión de IPag',
	'ipag_version_1'			=> 'Versión 1',
	'ipag_version_2'			=> 'Versión 2',
	'ipag_antifraud_title'		=> '¿Quieres realizar transacciones con antifraude?',

	'juno_settings'				=> 'Juno',
	'juno_client_id'			=> 'Client ID',
	'juno_secret'				=> 'Secret (Client Secret)',
	'juno_resource_token'		=> 'Token de recurso (token privado)',
	'juno_public_token'			=> 'Token Público',
	'juno_sandbox'				=> 'Operation Mode',
	
	'credit_card' 				=> 'Tarjeta de crédito',
	'holder_name' 				=> 'nombre del titular',
	'card_number' 				=> 'Número de tarjeta',
	'exp_month' 				=> 'Mes válido',
	'exp_year' 					=> 'Año de vencimiento',
	'cvv'						=> 'CVV',
	'create_card' 				=> 'Registrar tarjeta',
	'card_added_msg'		 	=> '¡Tarjeta agregada correctamente! Regrese para ver la lista de tarjetas. ',
	'user_not_auth' 			=> 'Usuario no autenticado',
	'invalid_card' 				=> 'Detalles de tarjeta inválidos',
	'expired_card' 				=> 'Tarjeta caducada',
	'invalid_cvv' 				=> 'CVV no es válido',
	'invalid_card_number' 		=> 'El número de tarjeta no es válido',
	'fill_the_field' 			=> 'Rellena el campo',
	'refused_card '				=>' Tarjeta rechazada ',
	'card_success_added' 		=> '¡Tarjeta agregada correctamente!',
	'nomenclatures' 			=> 'Nomenclaturas',
	'custom_nomenclatures'		=> 'Nomenclaturas Personalizadas',
	'juno_postback_msg'			=> 'Nota: Ingrese al panel de Juno, haga clic en el menú "Complementos y API", en el campo "NOTIFICACIÓN DE PAGO", coloque el siguiente enlace:',
	'gateway_product_title'		=> 'Descripción de lo producto',
	'payment_direct_pix'		=> 'Pix Directo',
	'payment_direct_pix_msg'	=> 'El pix se realiza en la aplicación bancaria del usuario al finalizar la solicitud, utilizando la clave de pix que el proveedor proporciona al cliente. El flujo es similar al pago en efectivo o con tarjeta.',
	'payment_gateway_pix'		=> 'Pix Gateway',
	'payment_gateway_pix_msg'	=> 'El Pix se realiza en el banco del usuario al finalizar la solicitud, utilizando la clave de pix "Copia e Cola" proporcionada por la aplicación o escaneando el código QR disponible en la aplicación del proveedor. Para este tipo de pix, se requiere una Gateway de Pago.',
	'default_pay_gateway_pix'	=> 'Intermediario estándar de pago por Pix',
	'prepaid_pix'				=> 'Inserción de equilibrio Pix Gateway',
	'obs_pix_prepaid'			=> '- Los pagos con Pix necesitan una pasarela de pago.',
	'pix_key'					=> 'Clave de Pix Aleatoria',
	
	'pix_juno_settings'			=> 'Juno ajustes',
	'pix_juno_client_id'		=> 'Client ID',
	'pix_juno_secret'			=> 'Secret (Client Secret)',
	'pix_juno_resource_token'	=> 'Token de recurso (token privado)',
	'pix_juno_public_token'		=> 'Token Público',
	'pix_juno_sandbox'			=> 'Modo de operación',
);
