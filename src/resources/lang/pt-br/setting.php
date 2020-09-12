<?php

return array(
	  'conf_install'												=> 'Configuração da Instalação'
	, 'conf_base_app'												=> 'Configurações Básicas da Aplicação'
	, 'conf'														=> 'Configurações'
	, 'default_distance_unit'										=> 'Unidade de Distância Padrão'
	, 'future_request_time'											=> 'Tempo de Requisições Futuras'
	, 'cancel_maximum_trip_time' 									=> 'Tempo Máximo para Cancelamento'
	, 'request_create_timeout' 										=> 'Tempo Limite para Redisparar Solicitação'
	, 'distances_greater_gps_error' 								=> 'Distância mínima para considerar erro de GPS'
	, 'maximum_distance_motoboy'									=> 'Distância máxima para considerar o motoboy no local'
	, 'enable_distance_provider_blocking'							=> 'Habilitar bloqueio por distância do motoboy ao local'
	, 'automatic_balance_collection' 								=> 'Permitir cobrança automática de saldo?'
	, 'resend_delay' 												=> 'Delay para Redisparar Solicitação'
	, 'ban_provider_time_resend' 									=> 'Tempo de Bloqueio de um Provider durando o Redisparo de uma mesma Corrida'
	, 'distance_calculation_with_waypoints_api' 					=> 'Calcula distância da corrida através da API WayPoints?'
	, 'estimate_calculation_base' 									=> 'Permitir calcular corrida a partir da estimativa?'
	, 'deactivate_provider_time' 									=> 'Tempo Máximo para Desativar Prestador'
	, 'provider_timeout'  											=> 'Tempo de Resposta do Prestador de Serviço'
	, 'change_provider_tolerance'  									=> 'Alterar Tolerância do Prestador de Serviço'
	, 'sms_notification'  											=> 'Notificação Via SMS'
	, 'email_notification'  										=> 'Notificação Via E-mail'
	, 'referral_code_activation'  									=> 'Ativação do Código de Desconto'
	, 'get_referral_profit_on_card_payment'  						=> 'Conseguir Taxa de Desconto com Pagamento com cartão'
	, 'get_referral_profit_on_cash_payment'  						=> 'Conseguir Taxa de Desconto com Pagamento com Dinheiro'
	, 'get_referral_profit_on_voucher_payment'  					=> 'Conseguir Taxa de Desconto com Pagamento com Voucher'
	, 'default_referral_bonus_to_refered_user'  					=> 'Bônus de Desconto Padrão para Usuário'
	, 'default_referral_bonus_to_refereel'  						=> 'Bônus de Desconto Padrão'
	, 'promotional_code_activation'  								=> 'Ativação de Código Promocional'
	, 'get_promotional_profit_on_card_payment'  					=> 'Conseguir Taxa de Promoção com Pagamento com Cartão'
	, 'get_promotional_profit_on_cash_payment'  					=> 'Conseguir Taxa de Promoção com Pagamento com Dinheiro'
	, 'get_promotional_profit_on_voucher_payment'  					=> 'Conseguir Taxa de Promoção com Pagamento com Voucher'
	, 'admin_phone_number'											=> 'Número de Telefone do Administrador'
	, 'map_center_latitude'  										=> 'Centro Latitudinal do Mapa'
	, 'map_center_longitude'  										=> 'Centro Longitudinal do Mapa'
	, 'default_search_radius'  										=> 'Raio de Pesquisa Padrão'
	, 'scheduled_request_pre_start_minutes'  						=> 'Pré-Início de Solicitação Programada'
	, 'number_of_try_for_scheduled_requests'  						=> 'Número de Tentativas para Solicitações Programada'
	, 'request_time_costing_type' 									=> 'Solicitar Preço por Tempo'
	, 'provider_amount_for_each_request_in_percentage'				=> 'Porcentagem de Dinheiro para Prestador de Serviço por Solicitação'
	, 'auto_transfer_provider_payment'  							=> 'Transferência Automática De Pagamento Para Prestador De Serviço'
	, 'date_format' 												=> 'Formato de Data e Hora'
	, 'auto_transfer_schedule_at_after_selected_number_of_days'		=> 'Transferência Automática De Pagamento Programado Após Determinados Dias'
	, 'show_user_referral_field'									=> 'Exibir Campo de Código de Indicação de usuário'
	, 'show_provider_referral_field' 								=> 'Exibir Campo de Código de Indicação de prestador'
	, 'distance_count_on_provider_start'							=> 'Início da Contagem da Distância'
	, 'visible_value_to_provider'									=> 'Valor Exibido ao Prestador no Fim da Corrida'
	, 'marker_maximum_arrival_time_visible'							=> 'Tempo Máximo Exibido no Marcador'
	, 'show_user_register' 											=> 'Exibir Tela de Cadastro de Usuário'
	, 'phone_code'													=> 'Código de Telefone'
	, 'country'														=> 'País'

	, 'day'															=>	'Dias'
	, 'yes'															=>	'Sim'
	, 'no'															=>	'Não'

	, 'advanced_settings'											=>	'Configurações Avançadas'
	, 'theme_settings'												=>	'Configurações do Tema'
	, 'image_png'													=>	'Por favor, carregue uma imagem no formato .png.'
	, 'image_ico'													=>	'Por favor, carregue uma imagem no formato .ico.'
	, 'layout_color' 												=> 	'Cor de layout'
	, 'back'														=>	'Voltar as Configurações'
	, 'mail_settings'												=>	'Configuração do E-mail'
	, 'choose_one_mail'												=>	'Selecione um e-mail'
	, 'mail'														=>	'E-mail'
	, 'mandrill'													=>	'Mandrill'
	, 'sendgrid'													=>	'Sendgrid' //---------------------SENDGRID
	, 'mail_address'												=>	'Emails Address'
	, 'sendgrid_secret'												=>	'Segredo do Sendgrid' //------------------Sendgrid
	, 'mandrill_user_name'											=>	'Nome de Usuário Mandrill'
	, 'amazon_ses_servername' 										=> 	'Sevidor'
	, 'amazon_ses_username' 										=> 	'Usuário'
	, 'amazon_ses_password' 										=> 	'Senha'
	, 'amazon_ses_port' 											=> 	'Porta'
	, 'amazon_ses_tls'												=> 	'TLS'

	, 'type_subject' 												=> 'Assunto'
	, 'type_key' 													=> 'Key'
	, 'type_copy_emails' 											=> 'Copy emails'
	
	, 'settings'													=> 'Configurações'
	, 'basic_config'												=> 'Configurações básicas'
	, 'email_settings'												=> 'Configurações de E-mail'
	, 'sendgrid_settings'											=> 'Configurações do Sendgrid'
	, 'mandrill_settings'											=> 'Configurações do Mandrill'
	, 'amazon_ses_settings'											=> 'Configurações do Amazon SES'
	, 'import_settings'												=> 'Importar Configurações'
	, 'pagarme_settings'											=> 'Pagar.me Configurações'
	, 'transbank_settings'											=> 'Transbank Configurações'
	, 'byebnk_settings'												=> 'ByeBnk Configurações'
	, 'stripe_settings'												=> 'Stripe Configurações'
	, 'brain_tree_settings'											=> 'Brain Tree Configurações'
	, 'zoop_settings'												=> 'Zoop Configurações'
	, 'bancard_settings'											=> 'Bancard Configurações'
	, 'mercadopago_settings'										=> 'Mercado Pago Configurações'
	, 'advanced_settings'											=> 'Configurações Avançadas'
	, 'push_settings'												=> 'Configurações de Push'
	, 'apple_push_settings'											=> 'APNS - Serviço de Notificação Push da Apple'
	, 'gcm_push_settings'											=> 'GCM - Google Cloud Messaging'
	, 'audio_push_setting'											=> 'Configuração do Áudio do Push'
	, 'google_maps_api'												=> 'Google Maps API'
	, 'sms_settings'												=> 'Configurações de SMS'
	, 'Twillio'														=> 'Twillio'
	, 'Zenvia'														=> 'Zenvia'
	, 'TWW'															=> 'TWW'
	, 'Twillio_settings'											=> 'Configurações do Twillio'
	, 'Zenvia_settings'												=> 'Configurações do Zenvia'
	, 'TWW_settings'												=> 'Configurações do TWW'
	, 'social_settings'												=> 'Configurações Sociais'
	, 'system_settings'												=> 'Configurações do Sistema'
	, 'default_settings'											=> 'Configurações Padrões'

	, 'pay_gateway'													=> 'Gateway de Pagamento'
	, 'pay_config'													=> 'Configurações de Pagamento'
	, 'monthly' 													=> 'Mensal'
	, 'percentage' 													=> 'Porcentagem'
	, 'pifou' 														=> 'Pifou'
	, 'stripe'														=> 'Stripe'
	, 'pagarme'														=> 'Pagar.me'
	, 'byebnk'														=> 'ByeBnk'		
	, 'braintree'													=> 'Brain Tree'
	, 'zoop'														=> 'Zoop'
	, 'bancard'														=> 'Bancard'
	, 'mercadopago'													=> 'Mercado Pago'
	, 'transbank'													=> 'Transbank'
	, 'pagarme_encryption_key'										=> 'Chave de Criptografia do Pagar.me'
	, 'Certificates'												=> 'Certificados'

	, 'ios'															=> 'iOS'
	, 'Sandbox'														=> 'Sandbox'
	, 'production'													=> 'Produção'
	, 'user'														=> 'Usuário'
	, 'see_down'													=> 'Ver / Download'
	, 'link'														=> 'Link do Aplicativo'
	, 'gcm'															=> 'GCM'
	, 'android'														=> 'Android'
	, 'time_wait_request'											=> 'Solicitar Tempo de Espera'
	, 'time_distance_with_base'										=> 'Tempo e Distância Baseados Em'
	, 'miles'														=> 'Milhas'
	, 'fixed_price'													=> 'Preço Fixo'
	, 'km'															=> 'Km'
	, 'total_time_request'											=> 'Solicitar Tempo Total'
	, 'pre_pay'														=> 'Pré Pagamento'
	, 'lance_pay'													=> 'Lançar Pagamento'

	, 'date_format_1' 												=> 'Y/m/d H:i:s'
	, 'date_format_2' 												=> 'mm/dd/yyyy H:i:s'
	, 'date_format_3' 												=> 'dd/mm/yyyy H:i:s'


	, 'daily' 														=> 'Diariamente'
	, 'weekly' 														=> 'Semanalmente'
	, 'monthly_2' 													=> 'Mensalmente'
	, 'monday' 														=> 'Segunda-feira'
	, 'tuesday' 													=> 'Terça-feira'
	, 'wednesday' 													=> 'Quarta-feira'
	, 'thursday' 													=> 'Quinta-feira'
	, 'friday' 														=> 'Sexta-feira'

	, 'charged_value'												=> 'Valor cobrado do usuário'
	, 'provider_value'												=> 'Valor recebido pelo prestador'
	, 'provider_start' 												=> 'Início do deslocamento do prestador até o cliente'
	, 'request_start' 												=> 'Início do Serviço'

	, 'maps_api_key'												=> 'Google Maps API Key'
	, 'sms_configuration'  											=> 'Configuração SMS'
	, 'url_configuration'  											=> 'Configuração de url e diretórios do site'

	, 'pifou_model'  												=> 'Pifou'
	, 'audio_push'  												=> 'Arquivo de Áudio'
	, 'audio_push_file_download' 									=> 'Download do Arquivo'
	, 'push_config'  												=> 'Configuração do áudio para o Push'
	, 'listen_audio'												=> 'Escutar arquivo de áudio'

	/**
	 * Opções do Select
	 */
	, 'yes'							=> 'Sim'
	, 'no'							=> 'Não'
	, 'mile'						=>	'Milhas'
	, 'kilometers'					=>	'Km'

	, 'request_wait_time'			=>	'Solicitar tempo de espera'
	, 'request_real_time'			=>	'Solicitar tempo total'
	, 'beginning_service'			=>	'Início do Serviço'
	, 'provider_started'			=>	'Início do deslocamento do prestador até o cliente'
	, 'value_charged_user'			=>	'Valor cobrado do usuário'
	, 'value_provider_received'		=>	'Valor recebido pelo prestador'
	, 'detailed_request'			=>	'Detalhamento da corrida'
	, 'day'							=>	'Dia'
	, 'days'						=>	'Dias'

	/**
	 * Email
	 */
	, 'Sendgrid'					=>	'Sendgrid'
	, 'Email'						=>	'E-mail'
	, 'Mandrill'					=>	'Mandrill'
	, 'amazon_ses'					=>	'Amazon SES'

	/**
	 * Modelo de Serviço
	 */
	, 'percentage'					=>	'Porcentagem'
	, 'monthly'						=>	'Mensal'
	, 'daily'						=>	'Diariamente'
	, 'weekly'						=>	'Semanalmente'
	, 'monthly_2'					=>	'Mensalmente'

	/**
	 * Dias da semana
	 */
	, 'sunday'						=>	'Domingo'
	, 'monday'						=>	'Segunda-feira'
	, 'tuesday'						=>	'Terça-feira'
	, 'wednesday'					=>	'Quarta-feira'
	, 'thursday'					=>	'Quinta-feira'
	, 'friday'						=>	'Sexta-feira'
	, 'saturday'					=>	'Sábado'

	/**
	 * Cores
	 */
	, 'blue'			=>	'Azul'
	, 'black'			=>	'Preto'

	/**
	 * Linguagem
	 */
	, 'brazil'			=>	'Português - Brasil'
	, 'united_states'	=>	'Inglês - Estados Unidos'
	, 'spain'			=>	'Espanhol - Espanha'

	/**
	 * Pagamento
	 */
	, 'pagarme'			=> 'Pagar.me'
	, 'byebnk'			=> 'ByeBnk'		
	, 'stripe' 			=> 'Stripe'
	, 'braintree'		=> 'Brain Tree'
	, 'zoop'			=> 'Zoop'
	, 'bancard'			=> 'Bancard'
	, 'mercadopago'		=> 'Mercado Pago'

	/**
	 * Stripe
	 */
	, 'CUSTOM_ACCOUNTS' 	=> 'Contas Gerenciadas'
	, 'EXPRESS_ACCOUNTS' 	=> 'Contas Expressas'
	, 'STANDARD_ACCOUNTS' 	=> 'Contas Padrão'

	/**
	 * PUSH
	 */
	, 'production' 			=> 'Produção'
	, 'sendbox' 			=> 'SendBox'
	
	/**
	 * Request Event Type
	 */
	, 'stop'				=> 'Parada'
	, 'refuel'				=> 'Abastecimento'
	, 'flat_tire'			=> 'Pneu Furado'
	, 'finished'			=> 'Finalizado'
	
	/**
	 * Tipos de Conta bancária
	 */
	, 'checking_account' 		=> 'Conta Corrente'
	, 'saving_account'			=> 'Conta Poupanca'
	, 'joint_checking_account' 	=> 'Conta Corrente Conjunta'
	, 'joint_saving_account'	=> 'Conta Poupança Conjunta'

	/**
	 * Dias do mês
	 */
	, '1_day'	=>	'1 Dia'
	, '2_days'	=>	'2 Dias'
	, '3_days'	=>	'3 Dias'
	, '4_days'	=>	'4 Dias'
	, '5_days'	=>	'5 Dias'
	, '6_days'	=>	'6 Dias'
	, '7_days'	=>	'7 Dias'
	, '8_days'	=>	'8 Dias'
	, '9_days'	=>	'9 Dias'
	, '10_days'	=>	'10 Dias'
	, '11_days'	=>	'11 Dias'
	, '12_days'	=>	'12 Dias'
	, '13_days'	=>	'13 Dias'
	, '14_days'	=>	'14 Dias'
	, '15_days'	=>	'15 Dias'
	, '16_days'	=>	'16 Dias'
	, '17_days'	=>	'17 Dias'
	, '18_days'	=>	'18 Dias'
	, '19_days'	=>	'19 Dias'
	, '20_days'	=>	'20 Dias'
	, '21_days'	=>	'21 Dias'
	, '22_days'	=>	'22 Dias'
	, '23_days'	=>	'23 Dias'
	, '24_days'	=>	'24 Dias'
	, '25_days'	=>	'25 Dias'
	, '26_days'	=>	'26 Dias'
	, '27_days'	=>	'27 Dias'
	, '28_days'	=>	'28 Dias'
	, '29_days'	=>	'29 Dias'
	, '30_days'	=>	'30 Dias'

	/*
	|--------------------------------------------------------------------------
	| Configurações do Sistema
	|--------------------------------------------------------------------------
	*/
	,'website_url' 					=> 'Site URL'
	,'path_cache_directory' 		=> 'Caminho para o diretório de cache'
	,'path_log_directory' 			=> 'Caminho para o diretório de log'
	,'default_theme' 				=> 'Tema padrão'
	,'time_limit_data_cache' 		=> 'Tempo limite de dados em cache'
	,'language' 					=> 'Língua'
	,'timezone' 					=> 'Fuso Horário'


	/*
	|--------------------------------------------------------------------------
	| Configurações da Aplicação
	|--------------------------------------------------------------------------
	*/
	,'logo'													=> 	'Logo'
	,'icon'													=> 	'Ícone'
	,'back_image_signup_application_home'					=> 	'Imagem de fundo da aplicação'
	,'back_image_signup_application_admin'					=> 	'Imagem de fundo da tela de login do administrador'
	,'back_image_signup_application_corp'					=> 	'Imagem de fundo da tela de login corporativa'
	,'back_image_signup_application_provider'				=> 	'Imagem de fundo da tela de login do motorista'
	,'back_image_signup_application_user'					=> 	'Imagem de fundo da tela de login do usuário'
	,'website_title' 										=> 	'Título do WebSite'
	,'standard_distance_unit' 								=> 	'Unidade De Distância Padrão'
	,'service_provider_response_time' 						=> 	'Tempo De Resposta Do Prestador De Serviço'
	,'change_service_provider_tolerance' 					=> 	'Alterar Tolerância Do Prestador De Serviço'
	,'administrator_phone_number' 							=> 	'Número De Telefone Do Administrador'
	,'scheduled_request_start' 								=> 	'Pré-início De Solicitação Programada'
	,'number_scheduled_request' 							=> 	'Número De Tentativas Para Solicitações Programadas'
	,'request_price_per_time' 								=> 	'Solicitar Preço Por Tempo'
	,'distance_counsting_start' 							=> 	'Início Da Contagem Da Distância'
	,'displayed_provider_end_race' 							=> 	'Valor Exibido Ao Prestador No Fim Da Corrida'
	,'maximum_time_marker' 									=> 	'Tempo Máximo Exibido No Marcador'
	,'view_user_master_screen' 								=> 	'Exibir Tela De Cadastro De Usuário'
	,'define_car_number_format' 							=> 	'Definir formato de números do carro'
	,'car_licence_plate_format' 							=> 	'Formato Da Placa Do Carro'
	,'maximum_time_disable_provider' 						=> 	'Tempo Máximo Para Desativar Prestador'
	,'latitudinal_center_map' 								=> 	'Centro Latitudinal Do Mapa'
	,'longitudinal_center_map' 								=> 	'Centro Longitudinal Do Mapa'
	,'standard_scanning_radius' 							=> 	'Raio De Pesquisa Padrão'
	,'facebook_pixel' 										=> 	'Pixel de Rastreamento - Facebook'
	,'google_analytics' 									=> 	'Google Analytics'
	,'app' 													=> 	'Aplicações'
	,'field' 												=> 	'Preencha este campo'
	,'select_item'         									=> 	'Selecione um item da lista.'
	,'reason_for_user_cancellation_during_the_service'		=> 	'Motivo de Cancelamento do serviço pelo usuário durante o serviço'
	,'delay_transfer_between_provider' 						=> 	'Tempo de Delay da transferência de solicitação entre prestadores (em segundos)'
	,'last_update_minutes'									=>	'Tempo Máximo de Inatividade do Prestador em Minutos'
	,'map_settings'											=>	'Configurações do Mapa'
	,'show_bank_account_provider_register_message'			=> 	'Pegar os dados bancários do prestador no momento do cadastro web'
	,'show_bank_account_provider_register'					=> 	'Dados Bancários do Prestador durante cadastro web'
	,'max_debt_allowed'										=>	'Valor máximo de dívida permitida ao usuário'
	,'show_payment_method_on_accept_request_screen'			=>	'Exibir método de pagamento para prestador'
	,'show_payment_method_on_accept_request_screen_message'	=>	'Exibir método de pagamento no momento que o prestador vai aceitar o serviço'
	,'allow_provider_to_choose_payment_method'				=>	'Permitir que o prestador escolha seu método de pagamento'
	,'allow_provider_to_choose_payment_method_message'		=> 	'O prestador pode selecionar os métodos de pagamento com os quais ele trabalhará'
	,'show_destination_to_provider_accept_request'			=>	'Mostrar o destino do usuário quando o prestador aceitar a solicitação'
	,'show_destination_to_provider_accept_request_message'	=> 	'Permitir que o prestador veja o destino do usuário quando ele aceitar a solicitação'
	


	/*
	|--------------------------------------------------------------------------
	| Configurações do Fluxo de Aprovação
	|--------------------------------------------------------------------------
	*/
	,'approval_flow'									=>	'Fluxo de Aprovação'
	,'want_to_enable_approval_flow'						=>	'Deseja Ativar Fluxo de Aprovação'
	,'approval_flow_settings'							=>  'Configurações do Fluxo de Aprovação'
	,'enable_or_disable_approval_flow_users_registered'	=>  'Ativa ou Inativa o fluxo de aprovação para os usuários cadastrados no sistema'





	/*
	|--------------------------------------------------------------------------
	| Configurações de SMS
	|--------------------------------------------------------------------------
	*/
	,'sid_twilio_account'  						=> 'SID da Conta Twilio'
	,'token_twilio_auth'  						=> 'Twilio Auth Token'
	,'twilio_number'  							=> 'Número Twilio'
	,'sms_notification' 						=> 'Notificaçao Via SMS'
	,'sms' 										=> 'SMS'

	/*
	|--------------------------------------------------------------------------
	| Configurações de URL e Diretórios
	|--------------------------------------------------------------------------
	*/
	,'public_directory'  						=> 'Diretório público do site'
	,'public_url_website'  						=> 'URL do site público'
	,'public_directory_provider'  				=> 'Diretório do site do prestador'
	,'public_url_website_provider'  			=> 'URL do site público do prestador'
	,'directory' 								=> 'Diretório'

	/*
	|--------------------------------------------------------------------------
	| Configurações de E-mail
	|--------------------------------------------------------------------------
	*/
	,'mail_provider_settings' 					=> 	'Configuração do E-mail do Prestador de Serviços'
	,'mail_address'								=> 	'Endereço de E-mail'
	,'admin_email_address'						=>	'E-mail do Administrador'
	,'show_name'								=> 	'Mostrar Nome'
	,'administrator_email' 						=> 	'E-mail do Administrador'
	,'notification_email' 						=> 	'Notificação Via E-mail'
	,'sendgrid_secrect' 						=> 	'Segredo do Sendgrid'
	,'sendgrid_host_name'						=> 	'Nome do Sendgrid Host'
	,'sendgrid_user_name'						=> 	'Nome do Usuário Sendgrid'
	,'mandrill_secret'							=> 	'Segredo do Mandrill'
	,'mandrill_host_name'						=> 	'Nome do Mandrill Host'
	,'email' 									=> 	'Email'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Pagamento
	|--------------------------------------------------------------------------
	*/
	, 'default_business_model' 								=> 'Modelo de Negócios'
	, 'provider_transfer_interval' 							=> 'Intervalo de transferência para o prestador'
	, 'provider_transfer_day' 								=> 'Dia da transferência para o prestador'
	, 'payment_by_client' 									=> 'Métodos de Pagamento por cliente'
	, 'payment_methods' 										=> 'Métodos de Pagamento'
	, 'money' 												=> 'Dinheiro'
	, 'card' 												=> 'Cartão de Crédito'
	, 'voucher' 												=> 'Voucher'
	, 'debt_machine'											=> 'Maquineta de Débito'
	, 'balance' 												=> 'Saldo de conta'
	, 'discount_payment_card' 								=> 'Conseguir Taxa De Desconto Com Pagamento Com Cartao'
	, 'standard_user_discount_bonus' 						=> 'Bônus De Desconto Padrão Para Usuário'
	, 'promotion_payment_money' 								=> 'Conseguir Taxa De Promoção Com Pagamento Com Dinheiro'
	, 'promotion_payment_card' 								=> 'Conseguir Taxa De Promoção Com Pagamento Com Cartao'
	, 'discount_code_activation' 							=> 'Ativação De Código De Desconto'
	, 'promotion_code_activation' 							=> 'Ativação De Código Promocional'
	, 'standard_bonus_discount' 								=> 'Bônus De Desconto Padrão'
	, 'discount_rate_payment_money' 							=> 'Conseguir Taxa De Desconto Com Pagamento Com Dinheiro'
	, 'default_pay_gate'										=> 'Intermediador Padrão de Pagamento'
	, 'default_pay_gate_boleto'								=> 'Intermediador Padrão de Pagamento por Boleto'
	, 'pay_encryption_key_me' 								=> 'Chave de Criptografia do Pagar.me'
	, 'pagarme_recipient_id' 								=> 'Id de Recebedor do Pagar.me'
	, 'pagarme_api_key' 										=> 'Chave de API do Pagar.me'
	, 'byebnk_api_key' 										=> 'Chave de API do ByeBnk'
	, 'byebnk_api_user' 										=> 'Id do Usuario ByeBnk'
	, 'stripe_secret' 										=> 'Chave Privada do Stripe'
	, 'stripe_public' 										=> 'Chave Pública do Stripe'
	, 'stripe_connect'										=> 'Usa Stripe Connect'	
	, 'custom_account'										=> 'Contas Gerenciadas'
	, 'express_account'										=> 'Contas Expressas'
	, 'standard_account'										=> 'Contas Padrão'
	, 'braintree_environment' 								=> 'Ambiente Brain Tree'
	, 'braintree_id'											=> 'ID do Comerciante Brain Tree'
	, 'braintree_public'										=> 'Chave Pública Brain Tree'
	, 'braintree_secret'										=> 'Chave Privada Brain Tree'
	, 'braintree_cript'										=> 'Chave de Criptografia do lado do cliente do Brain Tree'
	, 'zoop_marketplace_id' 								=> 'Chave Marketplace do Zoop'
	, 'zoop_publishable_key' 								=> 'Chave Produção do Zoop'
	, 'zoop_seller_id' 										=> 'Id Seller do Zoop'
	, 'bancard_public_key' 								=> 'Chave Pública da Bancard'
	, 'bancard_private_key' 								=> 'Chave Privada da Bancard'	
	, 'transbank_private_key' 								=> 'Chave Privada da Transbank'
	, 'transbank_commerce_code' 								=> 'Código do Comércio'
	, 'transbank_public_cert' 								=> 'Certificado Público da Transbank'
	, 'mercadopago_public_key' 								=> 'Chave Pública do Mercado Pago'
	, 'mercadopago_access_token' 								=> 'Access Token do Mercado Pago'
	, 'porcentage_money_provider_request' 					=> 'Porcentagem De Dinheiro Para Prestador De Serviço Por Solicitação'
	, 'automatic_transfer_payment_provider' 					=> 'Transferência Automática De Pagamento Para Prestador De Serviço'
	, 'automatic_transfer_payment_days' 						=> 'Transferência Automática De Pagamento Programado Após Determinados Dias'
	, 'payment' 												=> 'Pagamento'
	, 'bank_stripe_error'									=> 'Contas bancárias de teste não podem ser usadas com a chave live do Stripe'
	, 'This_file_upload_was_already_used_for_this_account.'	=> 'Esse arquivo já foi utilizado para essa conta'
	, 'stripe_total_split_refund_message'					=> 'Ao realizar o estorno, a empresa estornará o valor enviado ao prestador (colocar Não, se a empresa vai arcar com o preguízo)'
	, 'stripe_total_split_refund'							=> 'Estornar todo o valor da corrida (Empresa não fica no prejuízo pagando o valor enviado ao prestador)'
	, 'You_cannot_change_`legal_entity[verification][document]`_via_API_if_an_account_is_verified._Please_contact_support@stripe.com_if_you_need_to_change_the_legal_entity_information_associated_with_this_account.' => 'Você não pode alterar o arquivo do seu documento se sua conta já foi verificada. Por favor entre em contato com o nosso suporte se você precisa alterar alguma informação legal associada a sua conta.'
	, 'payment_methods_debt'									=> 'Métodos de Pagamento para Dívida'
	, 'boleto_gateway'										=> 'Gateway de Boleto'
	, 'gerencianet_settings'									=> 'Gerencianet Configurações'
	, 'gerencianet_client_id'								=> 'Gerencianet Client ID'
	, 'gerencianet_client_secret'							=> 'Gerencianet Client Secret'

	/*
	|--------------------------------------------------------------------------
	| Configurações Promocionais
	|--------------------------------------------------------------------------
	*/
	,'define_vouchers_active'             					=> 'Utiliza geracão de vouchers'
	,'promotional_configurations'                   		=> 'Configurações Promocionais' 
	,'with_draws'											=> 'Saques'
	,'with_draw_enabled_message'							=> 'Habilita opção de saque para usuário'
	,'with_draw_enabled'									=> 'Habilitar Saque'
	,'with_draw_max_limit_message'							=> 'Define valor máximo permitido para saque'
	,'with_draw_max_limit'									=> 'Valor máximo para saque'
	,'with_draw_min_limit_message'							=> 'Define valor mínimo permitido para saque'
	,'with_draw_min_limit'									=> 'Valor mínimo para saque'
	,'with_draw_tax_message'								=> 'Define taxa para saque'
	,'with_draw_tax'										=> 'Taxa de saque'
	,'with_draw_unabled'									=> 'Saque Desativado'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Push e Chaves
	|--------------------------------------------------------------------------
	*/
	,'push_notification' 						=> 'Notificação via Push'
	,'certificates_type'						=> 'Tipos de Certificados'
	,'certificates_file_user' 					=> 'Usuário'
	,'certificates_file_provider' 				=> 'Prestador de Serviço'
	,'certificates_file_user_download' 			=> 'Certificado do Usuário'
	,'certificates_file_provider_download' 		=> 'Certificado do Prestador'
	,'user_passphrase' 							=> 'Senha do Usuário'
	,'provider_passphrase' 						=> 'Senha do Prestador do Serviço'
	,'user_link_app_ios' 						=> 'Link do Aplicativo do Usuário (iOS)'
	,'provider_link_app_ios' 					=> 'Link do Aplicativo do Prestador de Serviço (iOS)'
	,'gcm_key' 									=> 'Chave de Navegação GCM'
	,'android_application_link_user' 			=> 'Link do Aplicativo do Usuário (Android)'
	,'android_application_link_service_provider'=> 'Link do Aplicativo do Prestador de Serviço (Android)'
	,'navigation_key' 							=> 'Chave de navegação do Google Maps API'
	,'load_certificate_pem_format'				=> 'Carregue o certificado no formato .pem'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Regras de Indicação
	|--------------------------------------------------------------------------
	*/
	,'completed_rides_goal_for_user' 			=> 'Corridas do usuário para conceder o bônus'
	,'completed_rides_goal_for_provider' 		=> 'Corridas do prestador para conceder o bônus'
	,'compensation_bonus_first_level' 			=> 'Bônus para primeiro estágio'
	,'compensation_bonus_second_level' 			=> 'Bônus para segundo estágio'
	,'compensation_bonus_third_level' 			=> 'Bônus para terceiro estágio'
	,'gross_value_to_provider_bonus'			=> 'Valor mínimo em BRL que o motorista deve completar em lucro para ativar o bônus.'
	,'percent_provider_bonus' 					=> 'Percentagem a pagar a quem indicou o prestador.'
	,'gross_value_to_user_bonus' 				=> 'Valor mínimo em BRL que o usuário deve consumir em corridas para ativar o bônus.'
	,'percent_user_bonus' 						=> 'Porcentagem a ser paga para quem indicou o usuário.'
	,'indication_rules'							=> 'Regras de indicação'
	,'simple_indication'						=> 'Indicação simples'
	,'activation_time'							=> 'Momento da habilitação'
	,'first_ride'								=> 'Primeira corrida'
	,'register'									=> 'Cadastro'
	,'indication_value_per_user'				=> 'Valor de Indicação por Usuário'
	,'indication_value_per_provider'			=> 'Valor de Indicação por Prestador'
	,'race_gain'								=> 'Ganhos na corrida'
	,'time_limit_rules'							=> 'Tempo limite em meses para validade da regra'
	,'zero_undefined'							=> 'zero é indefinido'
	,'level'									=> 'Nivel'
	,'distance_tolerance_estimate'				=> 'Percentual de distância tolerada fora da estimativa'
	,'allow_schedule_request'					=> 'Permitir Agendar Socilitação'
	,'allow_social_register'					=> 'Permitir Conta Social'

	/*
	|--------------------------------------------------------------------------
	| Configurações de Geolocalização
	|--------------------------------------------------------------------------
	*/
	,'geolocation' 					=>	'Geolocalização'
	,'directions'					=>	'API Directions'
	,'directions_optimization'		=>	'API Directions para otimização de rota (opcional e apenas Google)'
	,'directions_optimization_msg'	=>	'Caso coloque abaixo, a chave API Directions do Google, o botão OTIMIZAR ROTA aparecerá quando uma rota tiver 4 pontos ou mais.'
	,'geolocation_provider'			=>	'Provedor do serviço'
	,'geolocation_key'				=>	'Chave de autenticação'
	,'geolocation_url'				=>	'URL do servidor'
	,'geolocation_application_id'	=>	'ID da aplicação'
	,'geolocation_success'			=>	'Configurações de geolocalização atualizadas com sucesso!'
	,'field_not_find'				=>	'O campo :field não pôde ser atualizado.'
	,'places'						=>	'API Places'
	,'able_redundancy_rule'			=>	'Deseja habilitar redundância na consulta?'
	,'provider_redundancy'			=>	'Provedor do serviço de redundância'
	,'url_redundancy'				=>	'URL do servidor de redundância'
	,'key_redundancy'				=>	'Chave de autenticação de redundância'
	,'application_id_redundancy'	=>	'ID da aplicação de redundância'
	,'provider_cannot_equals'		=>	'O provedor de redundância não pode ser igual'


	/*
	|--------------------------------------------------------------------------
	| Languages
	|--------------------------------------------------------------------------
	*/
	,'define_language_active'             					=> 'Habilitar idioma para prestadores?'
	,'languages'             				                => 'Idiomas'

	/*
	|--------------------------------------------------------------------------
	| Contatos de Emergência
	|--------------------------------------------------------------------------
	*/
	,'define_emergency_active'             					=> 'Utiliza contatos de emergência?'
	,'emergency_contacts'                   		 		=> 'Contatos de Emergência'
	
	/*
	|--------------------------------------------------------------------------
	| Configurações de Faturamento
	|--------------------------------------------------------------------------
	*/
	,'billing_setting'										=> 'Configurações do Faturamento'
	,'how_billing_works'									=> 'Texto de como funciona faturamento'
	,'minimum_bill_generation_value'						=> 'Valor mínimo para geração de boleto'
	,'debit_note_percentage'								=> 'Qual porcentagem referente à nota de débito?'
	,'billing_expiration_day'								=> 'Qual dia do mês para vencimento padrão das contas de faturamento?'
	,'is_automatic_billing'									=> 'Deseja que o faturamento seja automático por padrão?'	
	,'allow_debit_note_on_billing' 							=> 'Deseja que o faturamento emita nota de débito por padrão?'
	,'billing_after_days'									=> 'Quantos dias após o faturamento automático que o vencimento deve ser gerado por padrão?'
	,'billing_period'										=> 'Qual período a ser faturado automaticamente por padrão?'
	,'billing_period_weekly'								=>	'Semanal'
	,'billing_period_biweekly'								=>	'Quinzenal'
	,'billing_period_monthly'								=>	'Mensal'

	/*
	|--------------------------------------------------------------------------
	| Botão de Emergência
	|--------------------------------------------------------------------------
	*/
	,'emergency_button_setting'								=> 'Configurações do Botão de Emergência'
	,'want_to_enable_emergency_button'						=> 'Deseja ativar o botão de emergência'
	,'enable_or_disable_emergency_button'					=> 'Ativa ou desativa o botão de emergência para o usuário do aplicativo durante as corridas'


	/*
	|--------------------------------------------------------------------------
	| Integração de API
	|--------------------------------------------------------------------------
	*/
	, 'api_key'												=> 'Chave de API'
	, 'integration'											=> 'Integração'
	, 'copy_key'											=> 'Copiar'
	, 'new_key'												=> 'Gerar Nova'
	, 'key_copied'											=> 'Copiada'
	, 'enable_integration'									=> 'Habilitar Integração'
	, 'enable'												=> 'Habilitar'
	, 'disable'												=> 'Desabilitar'
	, 'enabled'												=> 'Habilitado'
	, 'disabled'											=> 'Desabilitado'
	, 'confirm'												=> 'Tem certeza?'
	, 'api_documentation'									=> 'Documentação da API'



	//bgLocation
	, 'bg_location'						=> 'Configurações bgLocation'
	, 'update_location_interval'		=> 'Update Location Interval'
	, 'update_location_fast_interval'	=> 'Update Location Fast Interval'
	, 'distance_filter'					=> 'Distance Filter'
	, 'disable_elasticity'				=> 'Disable Elasticity'
	, 'heartbeat_interval'				=> 'Heartbeat Interval'
	, 'stop_timeout'					=> 'Stop Timeout'

	
	//confs lib
	,'gateways'													=> 'Configuração de Gateways de Pagamento'
	, 'home'													=> 'Home'
	, 'save'													=> 'Salvar'
	, 'edit_confirm'											=> 'Tem certeza que deseja atualizar as configurações de gateway?'
	, 'success_set_gateway'										=> 'Sua configurações de gateway foram atualizadas com sucesso.'
	, 'failed_set_gateway'										=> 'Houve uma falha ao atualizar suas configurações de gateway. Tente novamente!'
);
