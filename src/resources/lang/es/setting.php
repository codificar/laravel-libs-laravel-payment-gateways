<?php

return array(
	  'conf_install'												=> 'Configuración de instalación'
	, 'conf_base_app'												=> 'Configuración básica de la aplicación'
	, 'conf'														=> 'Configuración'
	, 'default_distance_unit'										=> 'Unidad de distancia estándar'
	, 'future_request_time'											=> 'Tiempo para solicitudes futuras'
	, 'cancel_maximum_trip_time' 									=> 'Tiempo máximo de cancelación'
	, 'request_create_timeout' 										=> 'Tiempo de espera para volver a mostrar la solicitud'
	, 'distances_greater_gps_error' 								=> 'Distancia mínima para considerar un error de GPS'
	, 'maximum_distance_motoboy'									=> 'Distancia máxima a considerar motoboy en el sitio'
	, 'enable_distance_provider_blocking'							=> 'Habilitar bloqueo de distancia motoboy a la ubicación'
	, 'automatic_balance_collection' 								=> "¿Permitir el cobro automático de saldos?"
	, 'resend_delay' 												=> 'Retraso para volver a mostrar la solicitud'
	, 'ban_provider_time_resend' 									=> 'Tiempo de Bloqueo de Proveedor durante el Redisparo de la misma Carrera'
	, 'distance_calculation_with_waypoints_api' 					=> "¿Calcular la distancia desde la carrera a través de la API WayPoints?"
	, 'estimate_calculation_base' 									=> '¿Permitir calcular la ejecución a partir de la estimación?'
	, 'deactivate_provider_time' 									=> 'Tiempo máximo para deshabilitar al proveedor'
	, 'provider_timeout'  											=> 'Tiempo de respuesta del proveedor de servicios'
	, 'change_provider_tolerance'  									=> 'Cambiar la tolerancia del proveedor de servicios'
	, 'sms_notification'  											=> 'Notificación por SMS'
	, 'email_notification'  										=> 'Notificación de correo electrónico'
	, 'referral_code_activation'  									=> 'Activación del código de descuento'
	, 'get_referral_profit_on_card_payment'  						=> 'Obtenga tasa de descuento con pago con tarjeta'
	, 'get_referral_profit_on_cash_payment'  						=> 'Obtenga tasa de descuento con pago en efectivo'
	, 'get_referral_profit_on_voucher_payment'  					=> 'Obtenga tasa de descuento con pago de cupón'
	, 'default_referral_bonus_to_refered_user'  					=> 'Bonificación de descuento de usuario estándar'
	, 'default_referral_bonus_to_refereel'  						=> 'Bono de descuento estándar'
	, 'promotional_code_activation'  								=> 'Activación de código promocional'
	, 'get_promotional_profit_on_card_payment'  					=> 'Obtenga la tarifa de promoción con el pago con tarjeta'
	, 'get_promotional_profit_on_cash_payment'  					=> 'Obtener tarifa de promoción con pago en efectivo'
	, 'get_promotional_profit_on_voucher_payment'  					=> 'Obtener tarifa de promoción con pago de cupón'
	, 'admin_phone_number'											=> 'Número de teléfono del administrador'
	, 'map_center_latitude'  										=> 'Mapa del centro latitudinal'
	, 'map_center_longitude'  										=> 'Centro longitudinal del mapa'
	, 'default_search_radius'  										=> 'Radio de búsqueda estándar'
	, 'scheduled_request_pre_start_minutes'  						=> 'Pre-inicio de solicitud programada'
	, 'number_of_try_for_scheduled_requests'  						=> 'Número de intentos para solicitudes programadas'
	, 'request_time_costing_type' 									=> 'Solicitar precio por tiempo'
	, 'provider_amount_for_each_request_in_percentage'				=> 'Porcentaje de dinero para el proveedor de servicios por solicitud'
	, 'auto_transfer_provider_payment'  							=> 'Transferencia automática de pago al proveedor de servicios'
	, 'date_format' 												=> 'Formato de fecha y hora'
	, 'auto_transfer_schedule_at_after_selected_number_of_days'		=> 'Transferencia de pago automática programada después de ciertos días'
	, 'show_user_referral_field'									=> 'Mostrar campo de código de referencia de usuario'
	, 'show_provider_referral_field' 								=> 'Mostrar campo de código de proveedor'
	, 'distance_count_on_provider_start'							=> 'Inicio del recuento de distancia'
	, 'visible_value_to_provider'									=> 'Valor mostrado al proveedor al final de la carrera'
	, 'marker_maximum_arrival_time_visible'							=> 'Tiempo máximo de visualización del marcador'
	, 'show_user_register' 											=> 'Mostrar pantalla de registro de usuario'
	, 'phone_code'													=> 'Código de teléfono'
	, 'country'														=> 'Padres'
	, 'gerencianet_sandbox'											=> 'Modo de operación'
	, 'sandbox_mode_false'											=> 'Modo de producción'
	, 'sandbox_mode_true'											=> 'Modo de prueba'

	, 'day'															=>'Dias'
	, 'yes'															=>'Si'
	, 'no'															=>'No'

	, 'advanced_settings'											=>'Configuraciones avanzadas'
	, 'theme_settings'												=>'Configuración de temas'
	, 'image_png'													=>'Sube una imagen en formato .png.'
	, 'image_ico'													=>'Sube una imagen en formato .ico.'
	, 'layout_color' 												=>'Color de diseño'
	, 'back'														=>'Regresar a Configuración'
	, 'mail_settings'												=>'Configuración de correo electrónico'
	, 'choose_one_mail'												=>'Seleccione un correo electrónico'
	, 'mail'														=>'Email'
	, 'mandrill'													=>'Mandril'
	, 'sendgrid'													=>'Sendgrid' // --------------------- SENDGRID
	, 'mail_address'												=>'Dirección de correo electrónico'
	, 'sendgrid_secret'												=>'El secreto de Sendgrid' // ------------------ Sendgrid
	, 'mandrill_user_name'											=>'Nombre de usuario de Mandrill'
	, 'amazon_ses_servername' 										=> 'Sevidor'
	, 'amazon_ses_username' 										=> 'Usuario'
	, 'amazon_ses_password' 										=> 'Contraseña'
	, 'amazon_ses_port' 											=> 'Puerta'
	, 'amazon_ses_tls'												=> 'TLS'

	, 'type_subject' 												=> 'Tema en cuestion'
	, 'type_key' 													=>  'Llave'
	, 'type_copy_emails' 											=>  'Copiar correos electrónicos'
	
	, 'settings'													=>  'Configuración'
	, 'basic_config'												=>  'Ajustes básicos'
	, 'email_settings'												=>  'Ajustes del correo electrónico'
	, 'sendgrid_settings'											=>  'Configuración de Sendgrid'
	, 'mandrill_settings'											=>  'Configuración de mandril'
	, 'amazon_ses_settings'											=>  'Configuración de Amazon SES'
	, 'import_settings'												=>  'Importar ajustes'
	, 'pagarme_settings'											=>  'Configuración de Pagar.me'
	, 'transbank_settings'											=>  'Configuración de Transbank'
	, 'byebnk_settings'												=>  'Configuración de ByeBnk'
	, 'stripe_settings'												=>  'Configuración de bandas'
	, 'brain_tree_settings'											=>  'Configuración del árbol del cerebro'
	, 'zoop_settings'												=>  'Configuración de Zoop'
	, 'bancard_settings'											=>  'Configuración de tarjeta bancaria'
	, 'mercadopago_settings'										=>  'Configuración de pago del mercado'
	, 'advanced_settings'											=>  'Configuraciones avanzadas'
	, 'push_settings'												=>  'Configuración de inserción'
	, 'apple_push_settings'											=>  'APNS - Servicio de notificaciones push de Apple'
	, 'gcm_push_settings'											=>  'GCM: mensajería en la nube de Google'
	, 'audio_push_setting'											=>  'Configuración de audio push'
	, 'google_maps_api'												=>  'API de Google Maps'
	, 'sms_settings'												=>  'Configuración de SMS'
	, 'Twillio'														=>  'Twillio'
	, 'Zenvia'														=>  'Zenvia'
	, 'TWW'															=>  'TWW'
	, 'Twillio_settings'											=>  'Configuración de Twillio'
	, 'Zenvia_settings'												=>  'Configuración de Zenvia'
	, 'TWW_settings'												=>  'Configuración TWW'
	, 'social_settings'												=>  'Ajustes sociales'
	, 'system_settings'												=>  'Ajustes del sistema'
	, 'default_settings'											=>  'Configuración por defecto'

	, 'pay_gateway'													=>  'Gateway de Pago'
	, 'pay_config'													=>  'Configuración de pago'
	, 'monthly' 													=>  'Mensual'
	, 'percentage' 													=>  'Porcentaje'
	, 'pifou' 														=>  'Pifou'
	, 'stripe'														=>  'Raya'
	, 'pagarme'														=>  'Págame'
	, 'byebnk'														=>  'ByeBnk'
	, 'braintree'													=>  'Árbol del cerebro'
	, 'zoop'														=>  'Zoop'
	, 'bancard'														=>  'Bancard'
	, 'mercadopago'													=>  'Mercado pagado'
	, 'transbank'													=>  'Transbank'
	, 'pagarme_encryption_key'										=>  'Clave de cifrado de Pagar.me'
	, 'Certificates'												=>  'Certificados'
	
	, 'cielo'														=>  'Cielo'
	, 'cielo_merchant_id'											=>  'Cielo Merchant Id'
	, 'cielo_merchant_key'											=>  'Clave de comerciante de Cielo'
	
	, 'braspag'														=>  'Braspag'
	, 'braspag_merchant_id'											=>  'Identificación de comerciante de Braspag'
	, 'braspag_merchant_key'										=>  'Llave de comerciante de Braspag'
	, 'braspag_token'												=>  'Token Braspag'

	, 'getnet'														=>  'GetNet'
	, 'getnet_client_id'											=>  'Identificación de cliente GetNet'
	, 'getnet_client_secret'										=>  'Secreto de cliente GetNet'
	, 'getnet_seller_id'											=>  'Id de vendedor de GetNet'

	,'directpay'													=>  'Directpay'
	,'directpay_encrypt_key'										=>  'Clave de cifrado de Directpay'
	,'directpay_encrypt_value'										=>  'Valor de cifrado de Directpay'
	,'directpay_requester_id'										=>  'Id. Del solicitante de Directpay'
	,'directpay_requester_password'									=>  'Contraseña del solicitante de Directpay'
	,'directpay_requester_token'									=>  'Token de solicitante de Directpay'
	,'directpay_unique_trx_id'										=>  'Identificación única de Directpay trx'
	,'directpay_name'												=>  'Directpay'

	, 'ios'															=>  'iOS'
	, 'Sandbox'														=>  'Salvadera'
	, 'production'													=>  'Producción'
	, 'user'														=>  'Usuario'
	, 'see_down'													=>  'Ver descarga'
	, 'link'														=>  'Enlace de aplicación'
	, 'gcm'															=>  'GCM'
	, 'android'														=>  'Androide'
	, 'time_wait_request'											=>  'Pide tiempo fuera'
	, 'time_distance_with_base'										=>  'Basado en tiempo y distancia'
	, 'miles'														=>  'Millas'
	, 'fixed_price'													=>  'Precio fijo'
	, 'km'															=>  'Km'
	, 'total_time_request'											=>  'Solicitar tiempo total'
	, 'pre_pay'														=>  'Pago por adelantado'
	, 'lance_pay'													=>  'Post Pago'

	, 'date_format_1' 												=> 'Y/m/d H:i:s'
	, 'date_format_2' 												=> 'mm/dd/yyyy H:i:s'
	, 'date_format_3' 												=> 'dd/mm/yyyy H:i:s'


	, 'daily' 														=>'Diario'
	, 'weekly' 														=> 'Semanal'
	, 'monthly_2' 													=> 'Mensual'
	, 'monday' 														=> 'Lunes'
	, 'tuesday' 													=> 'Martes'
	, 'wednesday' 													=> 'Miércoles'
	, 'thursday' 													=> 'Jueves'
	, 'friday' 														=> 'Viernes'

	, 'charged_value'												=> 'Importe cobrado por el usuario'
	, 'provider_value'												=> 'Importe recibido por el proveedor'
	, 'provider_start' 												=> 'Inicio del viaje del proveedor al cliente'
	, 'request_start' 												=> 'Inicio del servicio'

	, 'maps_api_key'												=> 'Clave de API de Google Maps'
	, 'sms_configuration'  											=> 'Configuración de SMS'
	, 'url_configuration'  											=> 'Configuración de directorio y URL del sitio web'

	, 'pifou_model'  												=> 'Pifou'
	, 'audio_push'  												=> 'Archivo de audio'
	, 'audio_push_file_download' 									=> 'Descarga de archivos'
	, 'push_config'  												=> 'Configuración de audio para Push'
	, 'listen_audio'												=> 'Escuchar archivo de audio'

	/**
	 * Opções do Select
	 */
	, 'yes'							=> 'Si'
	, 'no'							=> 'No'
	, 'mile'						=>'Millas'
	, 'kilometers'					=>'Km'

	, 'request_wait_time'			=>'Solicitar tiempo de espera'
	, 'request_real_time'			=>'Solicitar tiempo total'
	, 'beginning_service'			=>'Inicio del servicio'
	, 'provider_started'			=>'Inicio del viaje del proveedor al cliente'
	, 'value_charged_user'			=>'Importe cobrado por el usuario'
	, 'value_provider_received'		=>'Importe recibido por el proveedor'
	, 'detailed_request'			=>'Detalles de la carrera'
	, 'day'							=>'Día'
	, 'days'						=>'Dias'

	/**
	 * Email
	 */
	, 'Sendgrid'					=>'Sendgrid'
	, 'Email'						=>'Email'
	, 'Mandrill'					=>'Mandril'
	, 'amazon_ses'					=>'Amazon SES'

	/**
	 * Modelo de Serviço
	 */
	, 'percentage'					=>'Porcentaje'
	, 'monthly'						=>'Mensual'
	, 'daily'						=>'Diario'
	, 'weekly'						=>'Semanal'
	, 'monthly_2'					=>'Mensual'

	/**
	 * Dias da semana
	 */
	, 'sunday'						=>'Domingo'
	, 'monday'						=>'Lunes'
	, 'tuesday'						=>'Martes'
	, 'wednesday'					=>'Miércoles'
	, 'thursday'					=>'Jueves'
	, 'friday'						=>'Viernes'
	, 'saturday'					=>'Sábado'

	/**
	 * Cores
	 */
	, 'blue'			=>'Azul'
	, 'black'			=>'Negro'

	/**
	 * Linguagem
	 */
	, 'brazil'			=>'Portugués - Brasil'
	, 'united_states'	=>'Inglés Estados Unidos'
	, 'spain'			=>'Español - España'

	/**
	 * Pagamento
	 */
	, 'pagarme'			=> 'Págame'
	, 'byebnk'			=> 'ByeBnk'
	, 'stripe' 			=> 'Raya'
	, 'braintree'		=> 'Árbol del cerebro'
	, 'zoop'			=> 'Zoop'
	, 'bancard'			=> 'Bancard'
	, 'mercadopago'		=> 'Mercado pagado'
	, 'gerencianet'		=> 'Gestãonet'

	/**
	 * Stripe
	 */
	, 'CUSTOM_ACCOUNTS' 	=> 'Cuentas administradas'
	, 'EXPRESS_ACCOUNTS' 	=> 'Cuentas Express'
	, 'STANDARD_ACCOUNTS' 	=> 'Cuentas estándar'

	/**
	 * PUSH
	 */
	, 'production' 			=> 'Producción'
	, 'sendbox' 			=> 'SendBox'
	
	/**
	 * Request Event Type
	 */
	, 'stop'				=> 'Detener'
	, 'refuel'				=> 'Suministro'
	, 'flat_tire'			=> 'Pinchazo'
	, 'finished'			=> 'Terminado'
	
	/**
	 * Tipos de Conta bancária
	 */
	, 'checking_account' 		=> 'Cuenta corriente'
	, 'saving_account'			=> 'Cuenta de ahorros'
	, 'joint_checking_account' 	=> 'Cuenta corriente conjunta'
	, 'joint_saving_account'	=> 'Cuenta de ahorros conjunta'

	/**
	 * Dias do mês
	 */
	, '1_day'	=>'1 día'
	, '2_days'	=>'2 días'
	, '3_days'	=>'3 días'
	, '4_days'	=>'4 días'
	, '5_days'	=>'5 días'
	, '6_days'	=>'6 días'
	, '7_days'	=>'7 días'
	, '8_days'	=>'8 dias'
	, '9_days'	=>'9 días'
	, '10_days'	=>'10 dias'
	, '11_days'	=>'11 días'
	, '12_days'	=>'12 días'
	, '13_days'	=>'13 días'
	, '14_days'	=>'14 dias'
	, '15_days'	=>'15 días'
	, '16_days'	=>'16 días '
	, '17_days'	=>'17 días '
	, '18_days'	=>'18 días '
	, '19_days'	=>'19 días '
	, '20_days'	=>'20 días'
	, '21_days'	=>'21 días'
	, '22_days'	=>'22 días '
	, '23_days'	=>'23 días '
	, '24_days'	=>'24 días '
	, '25_days'	=>'25 días'
	, '26_days'	=>'26 días '
	, '27_days'	=>'27 días '
	, '28_days'	=>'28 días '
	, '29_days'	=>'29 días '
	, '30_days'	=>'30 días'

	/*
	|--------------------------------------------------------------------------
	| Configurações do Sistema
	|--------------------------------------------------------------------------
	*/
	,'website_url' 					=> 'Sitio URL'
	,'path_cache_directory' 		=> 'Ruta al directorio de caché'
	,'path_log_directory' 			=> 'Ruta al directorio de registro'
	,'default_theme' 				=> 'Tema predeterminado'
	,'time_limit_data_cache' 		=> 'Tiempo de espera de datos en caché'
	,'language' 					=> 'Idioma'
	,'timezone' 					=> 'Huso horario'


	/*
	|--------------------------------------------------------------------------
	| Configurações da Aplicação
	|--------------------------------------------------------------------------
	*/
	,'logo'													=>'Pronto'
	,'icon'													=> 'Icono'
	,'back_image_signup_application_home'					=> 'Imagen de fondo de la aplicación'
	,'back_image_signup_application_admin'					=> 'Imagen de fondo de la pantalla de inicio de sesión del administrador'
	,'back_image_signup_application_corp'					=> 'Imagen de fondo de la pantalla de inicio de sesión corporativa'
	,'back_image_signup_application_provider'				=> 'Imagen de fondo de la pantalla de inicio de sesión del conductor'
	,'back_image_signup_application_user'					=> 'Imagen de fondo de la pantalla de inicio de sesión del usuario'
	,'website_title' 										=> 'Título de la página'
	,'standard_distance_unit' 								=> 'Unidad de distancia estándar'
	,'service_provider_response_time' 						=> 'Tiempo de respuesta del proveedor de servicios'
	,'change_service_provider_tolerance' 					=> 'Cambiar la tolerancia del proveedor de servicios'
	,'administrator_phone_number' 							=> 'Número de teléfono del administrador'
	,'scheduled_request_start' 								=> 'Pre-inicio de la solicitud programada'
	,'number_scheduled_request' 							=> 'Número de intentos para solicitudes programadas'
	,'request_price_per_time' 								=> 'Solicitar precio por hora'
	,'distance_counsting_start' 							=> 'Inicio del recuento de distancia'
	,'displayed_provider_end_race' 							=> 'Valor mostrado al proveedor al final de la carrera'
	,'maximum_time_marker' 									=> 'Tiempo máximo mostrado en el marcador'
	,'view_user_master_screen' 								=> 'Mostrar pantalla de registro de usuario'
	,'define_car_number_format' 							=> 'Establecer formato de número de coche'
	,'car_licence_plate_format' 							=> 'Forma de placa de coche'
	,'maximum_time_disable_provider' 						=> 'Tiempo máximo para deshabilitar al proveedor'
	,'latitudinal_center_map' 								=> 'Mapa del centro latitudinal'
	,'longitudinal_center_map' 								=> 'Centro de mapa longitudinal'
	,'standard_scanning_radius' 							=> 'Radio de búsqueda predeterminado'
	,'facebook_pixel' 										=> 'Píxel de seguimiento - Facebook'
	,'google_analytics' 									=> 'Google analitico'
	,'app' 													=> 'Aplicaciones'
	,'field' 												=> 'Rellena este campo'
	,'select_item'         									=> "Seleccione un elemento de la lista."
	,'reason_for_user_cancellation_during_the_service'		=> 'Motivo de la cancelación del servicio por parte del usuario durante el servicio'
	,'delay_transfer_between_provider' 						=> 'Tiempo de demora para la transferencia de solicitudes entre proveedores (en segundos)'
	,'last_update_minutes'									=>'Máximo tiempo de inactividad del proveedor en minutos'
	,'map_settings'											=>'Configuración del mapa'
	,'show_bank_account_provider_register_message'			=> 'Obtenga los datos bancarios del proveedor en el momento del registro web'
	,'show_bank_account_provider_register'					=> 'Datos bancarios del proveedor durante el registro web'
	,'max_debt_allowed'										=>'Monto máximo de deuda permitido al usuario'
	,'show_payment_method_on_accept_request_screen'			=>'Mostrar método de pago para el proveedor'
	,'show_payment_method_on_accept_request_screen_message'	=>'Mostrar método de pago en el momento en que el proveedor acepte el servicio'
	,'allow_provider_to_choose_payment_method'				=>'Permita que el proveedor elija su método de pago'
	,'allow_provider_to_choose_payment_method_message'		=> 'El proveedor puede seleccionar los métodos de pago con los que trabajará'
	,'show_destination_to_provider_accept_request'			=>'Mostrar el destino del usuario cuando el proveedor acepta la solicitud'
	,'show_destination_to_provider_accept_request_message'	=> 'Permitir al proveedor ver el destino del usuario cuando acepta la solicitud'
	


	/*
	|--------------------------------------------------------------------------
	| Configurações do Fluxo de Aprovação
	|--------------------------------------------------------------------------
	*/
	,'approval_flow'									=>'Flujo de aprobación'
	,'want_to_enable_approval_flow'						=>'Desea habilitar el flujo de aprobación'
	,'approval_flow_settings'							=>  'Configuración del flujo de aprobación'
	,'enable_or_disable_approval_flow_users_registered'	=>  'Habilita o deshabilita el flujo de aprobación para usuarios registrados en el sistema'





	/*
	|--------------------------------------------------------------------------
	| Configurações de SMS
	|--------------------------------------------------------------------------
	*/
	,'sid_twilio_account'  						=> 'Twilio Account SID'
	,'token_twilio_auth'  						=> 'Token de autenticación Twilio'
	,'twilio_number'  							=> 'Número Twilio'
	,'sms_notification' 						=> 'Notificación por SMS'
	,'sms' 										=> 'SMS'

	/*
	|--------------------------------------------------------------------------
	| Configurações de URL e Diretórios
	|--------------------------------------------------------------------------
	*/
	,'public_directory'  						=> 'Directorio de sitios web públicos'
	,'public_url_website'  						=> "URL del sitio web público"
	,'public_directory_provider'  				=> 'Directorio de sitios web de proveedores'
	,'public_url_website_provider'  			=> "URL del sitio web público del proveedor"
	,'directory' 								=> 'Directorio'

	/*
	|--------------------------------------------------------------------------
	| Configurações de E-mail
	|--------------------------------------------------------------------------
	*/
	,'mail_provider_settings' 					=>'Configuración de correo electrónico del proveedor de servicios'
	,'mail_address'								=> 'Dirección de correo electrónico'
	,'admin_email_address'						=>'Correo electrónico del administrador'
	,'show_name'								=> 'Mostrar nombre'
	,'administrator_email' 						=> 'Correo electrónico del administrador'
	,'notification_email' 						=> 'Notificación de correo electrónico'
	,'sendgrid_secrect' 						=> 'El secreto de Sendgrid'
	,'sendgrid_host_name'						=> 'Nombre de host de Sendgrid'
	,'sendgrid_user_name'						=> 'Nombre de usuario de Sendgrid'
	,'mandrill_secret'							=> 'El secreto de Mandrill'
	,'mandrill_host_name'						=> 'Nombre de host de Mandrill'
	,'email' 									=> 'Email'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Pagamento
	|--------------------------------------------------------------------------
	*/
	, 'default_business_model' 								=> 'Modelo de negocio'
	, 'provider_transfer_interval' 							=> 'Intervalo de transferencia para el proveedor'
	, 'provider_transfer_day' 								=> 'Día de transferencia al proveedor'
	, 'payment_by_client' 									=> 'Métodos de pago por cliente'
	, 'payment_methods' 										=> 'Métodos de pago'
	, 'money' 												=> 'Dinero'
	, 'card' 												=> 'Tarjeta de crédito'
	, 'voucher' 												=> 'Vale'
	, 'debt_machine'											=> 'Débito Dobby'
	, 'balance' 												=> 'Saldo de la cuenta'
	, 'discount_payment_card' 								=> 'Obtener tasa de descuento con pago con tarjeta'
	, 'standard_user_discount_bonus' 						=> 'Bonificación de descuento de usuario estándar'
	, 'promotion_payment_money' 								=> 'Obtener tarifa de promoción con pago en efectivo'
	, 'promotion_payment_card' 								=> 'Obtener tarifa de promoción con pago con tarjeta'
	, 'discount_code_activation' 							=> 'Activación del código de descuento'
	, 'promotion_code_activation' 							=> 'Activación de código promocional'
	, 'standard_bonus_discount' 								=> 'Bono de descuento estándar'
	, 'discount_rate_payment_money' 							=> 'Obtenga tasa de descuento con pago en efectivo'
	, 'default_pay_gate'										=> 'Intermediario de pago estándar'
	, 'default_pay_gate_boleto'								=> 'Intermediario estándar para el pago de boleto'
	, 'pay_encryption_key_me' 								=> 'Clave de cifrado de Pagar.me'
	, 'pagarme_recipient_id' 								=> 'Identificación del destinatario de Pagar.me'
	, 'pagarme_api_key' 										=> 'Clave API de Pagar.me'
	, 'byebnk_api_key' 										=> 'Clave de API ByeBnk'
	, 'byebnk_api_user' 										=> 'ID de usuario de ByeBnk'
	, 'stripe_secret' 										=> 'Clave privada de banda'
	, 'stripe_public' 										=> 'Clave pública de banda'
	, 'stripe_connect'										=> 'Usar Stripe Connect'
	, 'custom_account'										=> 'Cuentas administradas'
	, 'express_account'										=> 'Cuentas Express'
	, 'standard_account'										=> 'Cuentas estándar'
	, 'braintree_environment' 								=> 'Entorno del árbol del cerebro'
	, 'braintree_id'											=> 'ID de comerciante de árbol cerebral'
	, 'braintree_public'										=> 'Clave pública del árbol del cerebro'
	, 'braintree_secret'										=> 'Clave privada de Brain Tree'
	, 'braintree_cript'										=> 'Clave de cifrado del lado del cliente de Brain Tree'
	, 'zoop_marketplace_id' 								=> 'Clave de Zoop Marketplace'
	, 'zoop_publishable_key' 								=> 'Zoop Production Key'
	, 'zoop_seller_id' 										=> 'Zoop Id Seller'
	, 'bancard_public_key' 								=> 'Clave pública de Bancard'
	, 'bancard_private_key' 								=> 'Clave privada de Bancard'
	, 'transbank_private_key' 								=> 'Clave privada de Transbank'
	, 'transbank_commerce_code' 								=> 'Código de comercio'
	, 'transbank_public_cert' 								=> 'Certificado Transbancario Público'
	, 'mercadopago_public_key' 								=> 'Clave pública de mercado pagado'
	, 'mercadopago_access_token' 								=> 'Token de acceso al mercado de pago'
	, 'porcentage_money_provider_request' 					=> 'Porcentaje de dinero para el proveedor de servicios por solicitud'
	, 'automatic_transfer_payment_provider' 					=> 'Transferencia automática de pago al proveedor de servicios'
	, 'automatic_transfer_payment_days' 						=> 'Transferencia de pago automática programada después de ciertos días'
	, 'payment' 												=> 'Pago'
	, 'bank_stripe_error'									=> 'Las cuentas bancarias de prueba no se pueden usar con la clave activa de Stripe'
	, 'This_file_upload_was_already_used_for_this_account.'	=> 'Este archivo ya se ha utilizado para esta cuenta'
	, 'stripe_total_split_refund_message'					=> 'Al realizar el contracargo, la empresa revertirá el monto enviado al proveedor (ponga No, si la empresa pagará por la pereza)'
	, 'stripe_total_split_refund'							=> 'Revertir el monto total de la carrera (la empresa no pierde al pagar el monto enviado al proveedor)'
	, 'You_cannot_change_`legal_entity[verification][document]`_via_API_if_an_account_is_verified._Please_contact_support@stripe.com_if_you_need_to_change_the_legal_entity_information_associated_with_this_account.' => 'No puede cambiar su archivo de documentos si su cuenta ya ha sido verificada. Comuníquese con nuestro soporte si necesita cambiar cualquier información legal asociada con su cuenta. '
	, 'payment_methods_debt'									=> 'Métodos de pago de la deuda'
	, 'boleto_gateway'										=> 'Puerta de Boleto de factura'
	, 'gerencianet_settings'									=> 'Configuración de Gestãonet'
	, 'gerencianet_client_id'								=> 'ID de cliente Gestãonet'
	, 'gerencianet_client_secret'							=> 'Secreto del cliente Gestãonet'
	, 'operation_mode'										=> 'Modo de operación'

	/*
	|--------------------------------------------------------------------------
	| Configurações Promocionais
	|--------------------------------------------------------------------------
	*/
	,'define_vouchers_active'             					=> 'Utiliza la generación de cupones'
	,'promotional_configurations'                   		=> 'Configuración promocional'
	,'with_draws'											=> 'Retiros'
	,'with_draw_enabled_message'							=> 'Habilitar la opción de retiro para el usuario'
	,'with_draw_enabled'									=> 'Habilitar retiro de efectivo'
	,'with_draw_max_limit_message'							=> 'Define la cantidad máxima permitida para el retiro'
	,'with_draw_max_limit'									=> 'Cantidad máxima de retiro'
	,'with_draw_min_limit_message'							=> 'Definir la cantidad mínima permitida para el retiro'
	,'with_draw_min_limit'									=> 'Monto mínimo de retiro'
	,'with_draw_tax_message'								=> 'Establecer tarifa de retiro'
	,'with_draw_tax'										=> 'Cargo por retiro'
	,'with_draw_unabled'									=> 'Retirar deshabilitado'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Push e Chaves
	|--------------------------------------------------------------------------
	*/
	,'push_notification' 						=>'Notificación de inserción'
	,'certificates_type'						=> 'Tipos de certificados'
	,'certificates_file_user' 					=> 'Usuario'
	,'certificates_file_provider' 				=> 'Prestador de servicio'
	,'certificates_file_user_download' 			=> 'Certificado de usuario'
	,'certificates_file_provider_download' 		=> 'Certificado de proveedor'
	,'user_passphrase' 							=> 'Contraseña de usuario'
	,'provider_passphrase' 						=> 'Contraseña del proveedor de servicios'
	,'user_link_app_ios' 						=> 'Enlace de aplicación de usuario (iOS)'
	,'provider_link_app_ios' 					=> 'Enlace de aplicación de proveedor de servicios (iOS)'
	,'gcm_key' 									=> 'Tecla de navegación GCM'
	,'android_application_link_user' 			=> 'Enlace de aplicación de usuario (Android)'
	,'android_application_link_service_provider'=> 'Enlace de aplicación del proveedor de servicios (Android)'
	,'navigation_key' 							=> 'Tecla de navegación de la API de Google Maps'
	,'load_certificate_pem_format'				=> 'Cargue el certificado en formato .pem'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Regras de Indicação
	|--------------------------------------------------------------------------
	*/
	,'completed_rides_goal_for_user' 			=> 'El usuario corre para otorgar la bonificación'
	,'completed_rides_goal_for_provider' 		=> 'El proveedor corre para otorgar la bonificación'
	,'compensation_bonus_first_level' 			=> 'Bonificación de la primera etapa'
	,'compensation_bonus_second_level' 			=> 'Bonus por segunda etapa'
	,'compensation_bonus_third_level' 			=> 'Bonificación por tercera etapa'
	,'gross_value_to_provider_bonus'			=> 'Cantidad mínima en BRL que el conductor debe completar en beneficio para activar el bono'
	,'percent_provider_bonus' 					=> 'Porcentaje a pagar a quien refirió al proveedor.'
	,'gross_value_to_user_bonus' 				=> 'Cantidad mínima en BRL que el usuario debe consumir en carreras para activar el bono'
	,'percent_user_bonus' 						=> 'Porcentaje a pagar a quien haya referido al usuario'
	,'indication_rules'							=> 'Reglas de referencia'
	,'simple_indication'						=> 'Indicación simple'
	,'activation_time'							=> 'Momento de habilitación'
	,'first_ride'								=> 'Primera carrera'
	,'register'									=> 'Registrarse'
	,'indication_value_per_user'				=> 'Valor de referencia por usuario'
	,'indication_value_per_provider'			=> 'Valor de referencia por proveedor'
	,'race_gain'								=> 'Carrera gana'
	,'time_limit_rules'							=> 'Plazo en meses para la validez de la regla'
	,'zero_undefined'							=> 'cero no está definido'
	,'level'									=> 'Nivel'
	,'distance_tolerance_estimate'				=> 'Porcentaje de distancia tolerada fuera de estimación'
	,'allow_schedule_request'					=> 'Permitir la asistencia de horarios'
	,'allow_social_register'					=> 'Permitir cuenta social'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Geolocalização
	|--------------------------------------------------------------------------
	*/
	,'geolocation' 					=>'Geolocalización'
	,'directions'					=>'Direcciones API'
	,'directions_optimization'		=>"API de indicaciones para la optimización de rutas (opcional y solo para Google)"
	,'directions_optimization_msg'	=>"Si pones la clave API de Google Directions a continuación, el botón OPTIMIZAR RUTA aparecerá cuando una ruta tenga 4 puntos o más"
	,'geolocation_provider'			=>'Proveedor de servicio'
	,'geolocation_key'				=>'Clave de autenticación'
	,'geolocation_url'				=>'URL del servidor'
	,'geolocation_application_id'	=>'ID de aplicación'
	,'geolocation_success'			=>'¡La configuración de geolocalización se actualizó correctamente!'
	,'field_not_find'				=>"El campo: campo no se pudo actualizar"
	,'places'						=>"API de Places"
	,'able_redundancy_rule'			=>"¿Desea habilitar la redundancia de consultas?"
	,'provider_redundancy'			=>'Proveedor de servicios de redundancia'
	,'url_redundancy'				=>'URL del servidor de redundancia'
	,'key_redundancy'				=>'Clave de autenticación de redundancia'
	,'application_id_redundancy'	=>'ID de aplicación de redundancia'
	,'provider_cannot_equals'		=>'El proveedor de redundancia no puede ser el mismo'


	/*
	|--------------------------------------------------------------------------
	| Languages
	|--------------------------------------------------------------------------
	*/
	,'define_language_active'             					=> '¿Habilitar el lenguaje para proveedores?'
	,'languages'             				                => 'Idiomas'

	/*
	|--------------------------------------------------------------------------
	| Contatos de Emergência
	|--------------------------------------------------------------------------
	*/
	,'define_emergency_active'             					=> '¿Utiliza contactos de emergencia?'
	,'emergency_contacts'                   		 		=> 'Contactos de emergencia'
	
	/*
	|--------------------------------------------------------------------------
	| Configurações de Faturamento
	|--------------------------------------------------------------------------
	*/
	,'billing_setting'										=>'Configuración de facturación'
	,'how_billing_works'									=> 'Texto sobre cómo funciona la facturación'
	,'minimum_bill_generation_value'						=> 'Valor mínimo para la generación de palanquilla'
	,'debit_note_percentage'								=> '¿Qué porcentaje se refiere a la nota de débito?'
	,'billing_expiration_day'								=> "¿Qué día del mes vencen las cuentas de facturación estándar?"
	,'is_automatic_billing'									=> "¿Quiere que la facturación sea automática de forma predeterminada?"
	,'allow_debit_note_on_billing' 							=> "¿Quiere que la facturación emita una nota de débito de forma predeterminada?"
	,'billing_after_days'									=> "¿Cuántos días después de la facturación automática se debe generar la fecha de vencimiento de forma predeterminada?"
	,'billing_period'										=> "¿Qué período se factura automáticamente de forma predeterminada?"
	,'billing_period_weekly'								=>'Semanal'
	,'billing_period_biweekly'								=>'Quincenal'
	,'billing_period_monthly'								=>'Mensual'

	/*
	|--------------------------------------------------------------------------
	| Botão de Emergência
	|--------------------------------------------------------------------------
	*/
	,'emergency_button_setting'								=> 'Configuración del botón de emergencia'
	,'want_to_enable_emergency_button'						=> '¿Quieres activar el botón de emergencia?'
	,'enable_or_disable_emergency_button'					=> 'Activa o desactiva el botón de emergencia para el usuario de la aplicación durante las carreras'


	/*
	|--------------------------------------------------------------------------
	| Integração de API
	|--------------------------------------------------------------------------
	*/
	, 'api_key'												=> 'Clave API'
	, 'integration'											=> 'Integración'
	, 'copy_key'											=> 'Copiar'
	, 'new_key'												=> 'Generar nuevo'
	, 'key_copied'											=> 'Copiado'
	, 'enable_integration'									=> 'Habilitar integración'
	, 'enable'												=> 'Habilitar'
	, 'disable'												=> 'Inhabilitar'
	, 'enabled'												=> 'Poder'
	, 'disabled'											=> 'Discapacitado'
	, 'confirm'												=> '¿Está seguro?'
	, 'api_documentation'									=> 'Documentación de API'



	//bgLocation
	, 'bg_location'						=> 'Configuración de BgLocation'
	, 'update_location_interval'		=> 'Actualizar intervalo de ubicación'
	, 'update_location_fast_interval'	=> 'Actualizar intervalo rápido de ubicación'
	, 'distance_filter'					=> 'Filtro de distancia'
	, 'disable_elasticity'				=> 'Desactivar elasticidad'
	, 'heartbeat_interval'				=> 'Intervalo de latidos'
	, 'stop_timeout'					=> 'Detener tiempo de espera'

	
	//confs lib
	,'gateways'													=> 'Configuración de pasarelas de pago'
	, 'home'													=> 'Casa'
	, 'save'													=> 'Ahorrar'
	, 'edit_confirm'											=> "¿Está seguro de que desea actualizar la configuración de la puerta de enlace?"
	, 'success_set_gateway'										=> "La configuración de la puerta de enlace se ha actualizado correctamente"
	, 'failed_set_gateway'										=> 'No se pudo actualizar la configuración de la puerta de enlace. ¡Inténtalo de nuevo!'
);
