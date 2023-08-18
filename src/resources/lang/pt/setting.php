<?php

return array(

	//Textos Gerais
	'conf'						=> 'Configurações',
	'gateways'					=> 'Formas de Pagamento',
	'edit_confirm'				=> 'Tem certeza que deseja atualizar as configurações de gateway?',
	'yes'						=> 'Sim',
	'no'						=> 'Não',
	'gateways'					=> 'Formas de Pagamento',
	'home'						=> 'Home',
	'save'						=> 'Salvar',
	'edit_confirm'				=> 'Tem certeza que deseja atualizar as configurações de gateway?',
	'success_set_gateway'		=> 'Configurações salvas com sucesso.',
	'failed_set_gateway'		=> 'Houve uma falha ao atualizar suas configurações de gateway. Tente novamente!',
	'gateway_has_change'		=> 'O gateway de cartão foi trocado. A atualização dos cartões para o novo gateway pode demorar. Não troque de gateway novamente por pelo menos ',
	'obs_billet_gateway'		=> 'Obs: o gateway de boleto será o mesmo gateway do cartão de crédito.',
	'obs_billet_gateway2'		=> '- O menu "Pagamentos" e "Saldo" são dinâmicos',
	'obs_billet_gateway2_msg'	=> 'Caso esteja habilitado pelo menos uma forma de pagamento pré-pago para o usuário (boleto e/ou cartão) o menu "Saldo" irá aparecer no app e o menu "Pagamentos" no painel do usuário. O mesmo acontece com o prestador e corp.',
	'payment_methods'			=> 'Formas de Pagamento',
	'choose_payment_methods'	=> 'Escolha as Formas de Pagamento',
	'money'						=> 'Dinheiro',
	'card'						=> 'Cartão de Crédito',
	'carto'						=> 'Carto',
	'machine'					=> 'Maquina',
	'debitCard'					=> 'Cartão de Débito',
	'crypt_coin'				=> 'Crypto Moeda',
	'payment_balance'			=> 'Pagamento por Saldo',
	'payment_prepaid'			=> 'Pré-Pago',
	'payment_prepaid_msg'		=> 'Permite ao usuário e/ou prestador adicionar saldo na plataforma (cartão ou boleto)',
	'payment_billing'			=> 'Pagamento por Faturamento',
	'pay_gateway'				=> 'Gateway de Pagamento - Cartão',
	'default_pay_gate'			=> 'Intermediador Padrão de Pagamento',
	'save_data'					=> 'Salvar Dados',
	'default_pay_gate_boleto'	=> 'Intermediador Padrão de Pagamento por Boleto',
	'compensate_provider_days'	=> 'Dias para compensar o prestador',
	'compensate_provider_msg'	=> 'Defina em quantos dias o prestador receberá o saldo em seu extrato de conta quando o pagamento é feito com cartão. Para o prestador receber no momento que for finalizado a solicitação, coloque 0. Se nenhum valor for selecionado, será considerado o tempo de compensação do gateway (geralmente 31 dias)',
	'uploaded'					=> 'enviado',
	'select'					=> 'Selecione',
	'billet_gateway_provider'	=> 'Metódo de pagamento por boleto',

	//Gateways de pagamento
	'pagarme'					=> 'Pagar.me',
	'stripe'					=> 'Stripe',
	'zoop'						=> 'Zoop',
	'cielo'						=> 'Cielo',
	'braspag'					=> 'Braspag',
	'braspag_cielo_ecommerce'	=> 'Braspag Cielo Ecommerce',
	'getnet'					=> 'GetNet',
	'directpay'					=> 'Directpay',
	'bancard'					=> 'Bancard',
	'transbank'					=> 'Transbank',
	'pagarapido'				=> 'Paga Rápido',
	'adiq'						=> 'Adiq',
	'bancinter'					=> 'Banco Inter',
	'ipag'						=> 'Ipag',
	'juno'						=> 'Juno',
	'ipag'						=> 'Ipag',
	'pagseguro'					=> 'PagSeguro',
	'sicoob'					=> 'Sicoob',
	'sicredi'					=> 'Sicredi',
	'santander'					=> 'Santander',
	'bancoDoBrasil'				=> 'Banco do Brasil',
	'itau'						=> 'Itaú',
	'bradesco'					=> 'Bradesco',
	'bradescoNet'				=> 'Bradesco Net',
	
	
	//Chaves dos gateways
	'pagarme_settings'			=> 'Pagar.me Configurações',
	'transbank_settings'		=> 'Transbank Configurações',
	'byebnk_settings'			=> 'ByeBnk Configurações',
	'stripe_settings'			=> 'Stripe Configurações',
	'brain_tree_settings'		=> 'Brain Tree Configurações',
	'zoop_settings'				=> 'Zoop Configurações',
	'bancard_settings'			=> 'Bancard Configurações',
	'mercadopago_settings'		=> 'Mercado Pago Configurações',
	'pagarapido_settings'		=> 'Paga Rápido Configurações',
	'advanced_settings'			=> 'Configurações Avançadas',
	'pagarme_secret_key'		=> 'Chave secreta Pagar.me',
	'pagarme_recipient_id'		=> 'Id de Recebedor do Pagar.me',
	'byebnk_api_key'			=> 'Chave de API do ByeBnk',
	'byebnk_api_user'			=> 'Id do Usuario ByeBnk',
	'stripe_secret'				=> 'Chave Privada do Stripe',
	'stripe_public'				=> 'Chave Pública do Stripe',
	'stripe_connect'			=> 'Usa Stripe Connect'	,
	'custom_account'			=> 'Contas Gerenciadas',
	'express_account'			=> 'Contas Expressas',
	'standard_account'			=> 'Contas Padrão',
	'braintree_environment'		=> 'Ambiente Brain Tree',
	'braintree_id'				=> 'ID do Comerciante Brain Tree',
	'braintree_public'			=> 'Chave Pública Brain Tree',
	'braintree_secret'			=> 'Chave Privada Brain Tree',
	'braintree_cript'			=> 'Chave de Criptografia do lado do cliente do Brain Tree',
	'zoop_marketplace_id'		=> 'Chave Marketplace do Zoop',
	'zoop_publishable_key'		=> 'Chave Produção do Zoop',
	'zoop_seller_id'			=> 'Id Seller do Zoop',
	'bancard_public_key'		=> 'Chave Pública da Bancard',
	'bancard_private_key'		=> 'Chave Privada da Bancard'	,
	'transbank_private_key'		=> 'Chave Privada da Transbank',
	'transbank_commerce_code'	=> 'Código do Comércio',
	'transbank_public_cert'		=> 'Certificado Público da Transbank',
	'mercadopago_public_key'	=> 'Chave Pública do Mercado Pago',
	'mercadopago_access_token'	=> 'Access Token do Mercado Pago',
	'payment'					=> 'Pagamento',
	'stripe_total_split_refund_message'	=> 'Ao realizar o estorno, a empresa estornará o valor enviado ao prestador (colocar Não, se a empresa vai arcar com o preguízo)',
	'stripe_total_split_refund'	=> 'Estornar todo o valor da corrida (Empresa não fica no prejuízo pagando o valor enviado ao prestador)',
	'boleto_gateway'			=> 'Gateway de Boleto da Fatura',
	'gerencianet_settings'		=> 'Gerencianet Configurações',
	'gerencianet_client_id'		=> 'Gerencianet Client ID',
	'gerencianet_client_secret'	=> 'Gerencianet Client Secret',
	'operation_mode'			=> 'Modo de operação',
	'choose_payment_methods'	=> 'Escolha as Formas de Pagamento',
	'prepaid_billet'			=> 'Inserção de saldo no boleto',
	'prepaid_card'				=> 'Inserção de saldo no cartão',
	'prepaid_min_billet_value'	=> 'Valor mínimo para gerar um boleto',
	'prepaid_tax_billet'		=> 'Taxa para gerar um boleto',
	'carto_keys'				=> 'Credenciais do Carto',
	'carto_login'				=> 'Login do Carto',
	'carto_password'			=> 'Senha do Carto',
	'carto_password'			=> 'Senha do Carto',
	'bancryp_keys'				=> 'Credenciais do Bancryp',
	'bancryp_api_key'			=> 'Chave de Api do Bancryp',
	'bancryp_secret_key'		=> 'Secret Key do Bancryp',
	'Sandbox'					=> 'Sandbox',
	'production'				=> 'Produção',
	'user'						=> 'Usuário',
	'provider'					=> 'Prestador',
	'corp'						=> 'Instituição',
	'bank_stripe_error'			=> 'Contas bancárias de teste não podem ser usadas com a chave live do Stripe',
	'cielo_merchant_id'			=> 'Cielo Merchant Id',
	'cielo_merchant_key'		=> 'Cielo Merchant Key',
	'braspag_merchant_id'		=> 'Braspag Merchant Id',
	'braspag_merchant_key'		=> 'Braspag Merchant Key',
	'braspag_token'				=> 'Braspag Token',
	'braspag_cielo_ecommerce'	=> 'Braspag Cielo Ecommerce',
	'braspag_client_id'			=> 'ID do cliente',
	'braspag_client_secret'		=> 'Senha do cliente',
	'getnet_client_id'			=> 'GetNet Client Id',
	'getnet_client_secret'		=> 'GetNet Client Secret',
	'getnet_seller_id'			=> 'GetNet Seller Id',
	'directpay_encrypt_key'		=> 'Directpay Encrypt Key',
	'directpay_encrypt_value'	=> 'Directpay Encrypt Value',
	'directpay_requester_id'	=> 'Directpay Requester Id',
	'directpay_requester_password'	=> 'Directpay Requester Password',
	'directpay_requester_token'	=> 'Directpay Requester Token',
	'directpay_unique_trx_id'	=> 'Directpay trx id unico',
	'directpay_name'			=> 'Directpay',
	'pagarapido_login'			=> 'Login',
	'pagarapido_password'		=> 'Senha',
	'pagarapido_gateway_key'	=> 'Gateway Key',
	'general_settings'			=> 'Configurações Gerais',
	'earnings_report_weekday'	=> 'Primeiro dia da semana do relatório de ganhos',
	'earnings_report_weekday_msg'=> 'Defina qual o primeiro dia da semana (weekday) que começará a ser contabilizado no relatório de ganhos',
	'show_user_account_statement'=> 'Mostrar menu "Extrato de Conta" para o usuário',
	'show_user_account_statement_msg'=> 'Mostrar menu "Extrato de Conta" para o usuário no app e no painel do usuário',
	'gateway_split_taxes_msg'		=> 'Quando ativo, o Prestador também é cobrado pelas taxas do gateway.',	
	'liable_split_msg'			=> 'Quando ativo, o Prestador também é responsável pela transação em caso de chargeback.',
	'charge_remainder_fee_msg'	=> 'Quando ativo, caso haja fundos restantes após a divisão, esses fundos serão repassados ao prestador(caso contrário, será necessária uma solução manual).',	
	'sunday'					=> 'Domingo',
	'monday'					=> 'Segunda-feira',
    'tuesday'					=> 'Terça-feira',
    'wednesday'					=> 'Quarta-feira',
    'thursday'					=> 'Quinta-feira',
    'friday'					=> 'Sexta-feira',
	'saturday'					=> 'Sábado',

	'adiq_client_id'			=> 'ID do cliente Adiq',
	'adiq_client_secret'		=> 'Segredo do cliente Adiq',
	'adiq_token'				=> 'Token de transação Adiq',

	'banco_inter_settings'		=> 'Configurações Banco Inter',
	'banco_inter_account'		=> 'Numero da conta do Banco Inter',
	'cnpj_for_banco_inter'		=> 'CNPJ',
	'banco_inter_crt'			=> 'Arquivo do Certificado',
	'banco_inter_key'			=> 'Arquivo da Chave',
	'ipag_api_id'				=> 'Ipag API Id',
	'ipag_api_key'				=> 'Ipag API Key',
	'ipag_token'				=> 'Ipag Token',
	'ipag_expiration_time'		=> 'Tempo de Expiração do Pix (min)',
	'ipag_version'				=> 'Versão do IPag',
	'ipag_version_1'			=> 'Versão 1',
	'ipag_version_2'			=> 'Versão 2',
	'ipag_antifraud_title'		=> 'Deseja fazer transações com antifraude?',

	'Ipag_settings'				=> 'Ipag Configurações',
	'juno_settings'				=> 'Juno Configurações',
	'juno_client_id'			=> 'Client ID',
	'juno_secret'				=> 'Secret (Client Secret)',
	'juno_resource_token'		=> 'Token de recurso (token privado)',
	'juno_public_token'			=> 'Token Público',
	'juno_sandbox'				=> 'Modo de Operação',

	'credit_card' 				=> 'Cartão de crédito',
	'holder_name'			 	=> 'Nome do titular',
	'card_number' 				=> 'Número do cartão',
	'exp_month' 				=> 'Mês de Validade',
	'exp_year' 					=> 'Ano de Validade',
	'cvv' 						=> 'CVV',
	'create_card' 				=> 'Cadastrar Cartão',
	'card_added_msg' 			=> 'Cartão adicionado com sucesso! Volte para ver a lista de cartões.',
	'user_not_auth' 			=> 'Usuário não autenticado',
	'invalid_card' 				=> 'Dados do cartão inválido',
	'expired_card' 				=> 'O cartão está vencido',
	'invalid_cvv' 				=> 'O CVV está inválido',
	'invalid_card_number' 		=> 'O número do cartão é inválido',
	'fill_the_field' 			=> 'Preencha o campo',
	'refused_card' 				=> 'Cartão Recusado',
	'card_success_added'		=> 'Cartão adicionado com sucesso!',
	'nomenclatures' 			=> 'Nomenclaturas',
	'custom_nomenclatures'		=> 'Nomenclaturas Personalizadas',
	'juno_postback_msg'			=> 'Obs: Entre no painel da Juno, clique no menu "Plugins & API", no campo "NOTIFICAÇÃO DE PAGAMENTOS" coloque o seguinte link:',
	'gateway_product_title'		=> 'Descrição do produto',
	'gateway_split_taxes'		=> 'O recebedor vinculado a regra de split será cobrado pelas taxas?',
	'liable_split'				=> 'Em caso se chargeback, o prestador também será responsável?',
	'charge_remainder_fee'		=> 'Caso fundos não sejam alocados após a divisão, esses fundos deverão ser repassados ao prestador?',
	'payment_direct_pix'		=> 'Pix Direto',
	'payment_direct_pix_msg'	=> 'O Pix é realizado no app do banco do usuário no final da solicitação, utilizando a chave pix que o prestador fornecer para o cliente. O fluxo é semelhante ao pagamento por Dinheiro ou Máquina de Cartão.',
	'payment_gateway_pix'		=> 'Pix Gateway',
	'payment_gateway_pix_msg'	=> 'O Pix é realizado no banco do usuário no final da solicitação, utilizando a chave pix "Copia e Cola" fornecida pelo app ou então escaneando o código QR disponível no app do prestador. Para esse tipo de pix, é necessário um Gateway de Pagamento.',
	'default_pay_gateway_pix'	=> 'Intermediador Padrão de Pagamento por Pix',
	'prepaid_pix'				=> 'Inserção de saldo Pix Gateway',
	'obs_pix_prepaid'			=> '- Pagamentos por pix precisam de gateway de pagamento.',
	'pix_key'					=> 'Chave Pix Aleatória',
	'list_webhooks'				=> 'Webhooks',
	
	'pix_juno_settings'			=> 'Juno Configurações',
	'pix_juno_client_id'		=> 'Client ID',
	'pix_juno_secret'			=> 'Secret (Client Secret)',
	'pix_juno_resource_token'	=> 'Token de recurso (token privado)',
	'pix_juno_public_token'		=> 'Token Público',
	'pix_juno_sandbox'			=> 'Modo de Operação',

	'enviromentActive' 			=> 'Âmbiente ativo',
	'local'						=> 'Teste (Sandbox)',
	'production'	      		=> 'Produção',

	'failed_retreive_webhook'	=> 'Falha ao tentar Recuperar lista de webhooks',
	'Unauthorized'	 			=> 'Falha ao tentar autenticar as chaves de acesso',
);
