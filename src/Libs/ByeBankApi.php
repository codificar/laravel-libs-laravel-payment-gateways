<?php 


class ByeBankApi
{
    //url da Api para produção
    const APP_API_PROD = "https://api.byebnk.com";
    //url da Api para desenvolvimento
    const APP_API_DEV = "https://dev.api.byebnk.com";

    const APP_TIMEOUT = 200;

    const STATUS_FINISHED = 'finished';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_ERROR = 'error';
    const STATUS_CANCELED = 'canceled';
    const STATUS_CANCELLED = 'canceled';
    const STATUS_PENDING = 'pending';
    
    

    //Função para pagamento de cartão de credito
    public static function pay($clientId, $token, $userId, $amountCents, $description, $paymentType, $cardId, $expirationDate, $capture = true)
    {
        try
        {
            //Inicialização do Curl
            $session = curl_init();

            
            $api = self::apiEnvironment();

            //definir qual url da Api deve ser usada
            // if ($environment == "production") {
            //     $api = self::APP_API_PROD;
            // } else {
            //     $api = self::APP_API_DEV;
            // }

            //url para acesso do endpoint da Api
            $url = sprintf('%s/clients/%s/users/%s/payments', $api, $clientId, $userId);

            \Log::debug('[pay]Url: '.json_encode($url));                    

            //cabeçalho
            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[pay]Header: '.json_encode($header)); 

            //Camppos enviados pro endpoint
            $fields = array (
                'amount_cents' => $amountCents,
                'description' => $description,
                'payment_type' => $paymentType,
                'card_id' => $cardId,
                'expiration_date' => $expirationDate,
                'capture' => $capture
            );

            \Log::debug('[pay]Fields: '.json_encode($fields));             
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true );
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            
            
            //execução do curl
            $msg_chk = curl_exec($session);

            \Log::debug("[pay]Response: " .$msg_chk);            
            
            //codigo de retorno da resposta
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[pay]StatusCode: " .$httpcode);

            //resultado da session
            $result = json_decode($msg_chk);
            
            //captura de erros
            if($httpcode != 201)
                throw new Exception('pay: '. $result->errors);


            if($result->data->attributes->status != self::STATUS_SUCCEEDED)
            {
                return(array (
                        'success' => false,
                        'message' => 'chargeError'
                    )
                );
            }                   

            //retorno de sucesso
            return(array (
                    'success' => true,
                    'paymentId' => $result->data->id
                )
            );

        }
        //exceções
        catch(Exception  $ex)
        {
            $return = array(
                "success" 					=> false ,
                "message" 					=> $ex->getMessage()
            );
            
            //\Log::error($return);

            return $return;
        }         

        
    }

    //Função para Payback de transação
    public static function payback($clientId, $token, $userId, $paymentId)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/payments/%s/void', $api, $clientId, $userId, $paymentId);

            \Log::debug('[payback]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[payback]Header: '.json_encode($header));
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true );	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            
            
            $msg_chk = curl_exec($session);  
            
            \Log::debug('[payback]Response: '.json_encode($msg_chk));
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            \Log::debug('[payback]StatusCode: '.json_encode($httpcode));
            
            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('payback: '. $result->errors);             

            return(array (
                    'success' => true,
                )
            );

        }
        catch(Exception  $ex)
        {
            $return = array(
                "success" 					=> false ,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(json_encode($return));

            return $return;
        }         

        
    }
    
    //Captura uma transação
    public static function capture($clientId, $token, $userId, $paymentId, $amount)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/payments/%s/capture', $api, $clientId, $userId, $paymentId);

            \Log::debug('[capture]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[capture]Header: '.json_encode($header));

            $fields = array (
                'amount_capture' => $amount
            );

            \Log::debug('[capture]Fields: '.json_encode($fields));              
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true );	
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));            
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            

            $msg_chk = curl_exec($session);  
            
            \Log::debug('[capture]Response: '.json_encode($msg_chk));
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  

            \Log::debug('[capture]StatusCode: '.json_encode($httpcode));
            
            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('capture: '. $result->errors);

            if($result->data->attributes->status != self::STATUS_SUCCEEDED)
            {
                return(array (
                        'success' => false,
                        'message' => 'captureError'
                    )
                );
            }

            return(array (
                    'success' => true,
                )
            );

        }
        catch(Exception  $ex)
        {
            $return = array(
                "success" 					=> false ,
                "message" 					=> $ex->getMessage()
            );
            
            \Log::error(json_encode($return));

            return $return;
        }         

        
    }      
    
    //Recupera uma transação
    public static function retrieve($clientId, $token, $userId, $paymentId)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/payments/%s', $api, $clientId, $userId, $paymentId);

            \Log::debug('[retrieve]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[retrieve]Header: '.json_encode($header));                   
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);           	        
            
            $msg_chk = curl_exec($session);

            \Log::debug("[retrieve]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[retrieve]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('retrieve: '. $result->errors);

            return(array(
                    'success' => true,
                    'id' => $result->data->id,
                    'amount_cents' => $result->data->attributes->amount_cents,
                    'description' => $result->data->attributes->description,
                    'status' => $result->data->attributes->status,
                    'paymentable_type' => $result->data->attributes->paymentable_type,
                    'capture' => $result->data->attributes->capture
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
		}         
       
    }       

    //Cadastro de cartão de credito
    public static function createCard($clientId, $token, $userId, $cardNumber, $cardHolder, $cardExpirationMonth,$cardExpirationYear, $cardCvv)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/cards', $api, $clientId, $userId);

            \Log::debug('[createCard]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[createCard]Header: '.json_encode($header));            

            $fields = array (
                'number' => $cardNumber,
                'holder_name' => $cardHolder,
                'expiration_month' => $cardExpirationMonth,
                'expiration_year' => $cardExpirationYear,
                'security_code' => $cardCvv
            );

            \Log::debug('[createCard]Fields: '.json_encode($fields));             

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_POST, true );
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            
            
            $msg_chk = curl_exec($session);

            \Log::debug("[createCard]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[createCard]StatusCode: " .$httpcode);;            

            $result = json_decode($msg_chk);
            
            if($httpcode != 201)
                throw new Exception('card_add: '. $result->errors[0]);

            return(array (
                    'success' => true,
                    'card_id' => $result->data->id,
                    'last_digit' => $result->data->attributes->last_digits
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
		} 

    }  
    
    //Deletar Cartão de credito cadastrado
    public static function deleteCard($clientId, $token, $userId, $cardId)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/cards/%s', $api, $clientId, $userId, $cardId);

            \Log::debug('[deleteCard]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[deleteCard]Header: '.json_encode($header));

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE" );	
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            
            
            $msg_chk = curl_exec($session);

            \Log::debug("[deleteCard]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[deleteCard]StatusCode: " .$httpcode);;            

            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('card_delete: '. $result->errors);

            return(array (
                    'success' => true,
                    'card_id' => $cardId,
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
		} 

    }     

    //Cadastro de usuário
    public static function createUser($clientId, $token, $firstName, $lastName, $documentNumber, $email, $phone, $userId, $address)
    {

        $address = self::checkAddress($address);

        if ($address['success'] == false) {
            return $return = array(
                "success" 					=> false ,
                "message" 					=> $address['message']
            );
            } else {
                try
                {
                
                    $address = $address['body'];

                    $session = curl_init();

                    $api = self::apiEnvironment();

                    $url = sprintf('%s/clients/%s/users', $api, $clientId);

                    // $address = (object)$address;

                    \Log::debug('[createUser]Url: '.json_encode($url));            

                    $header = array (
                        'Content-Type: application/json',
                        'token:' .$token          
                    );   

                    \Log::debug('[createUser]Header: '.json_encode($header));                 

                    $fields = array (
                        'name'                  => $firstName,
                        'last_name'             => $lastName,
                        'email'                 => $email,
                        'cpf'                   => $documentNumber,
                        'description'           => ("User ID #" . $userId),
                        'phone_number'          => $phone,
                        'address_attributes'    => $address
                    );  
                    
                    \Log::debug('[createUser]Fields: '.json_encode($fields));               
                    
                    curl_setopt($session, CURLOPT_URL, $url);
                    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
                    curl_setopt($session, CURLOPT_POST, true );
                    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
                    curl_setopt($session, CURLOPT_HTTPHEADER, $header);    
                    
                    $msg_chk = curl_exec($session);

                    \Log::debug("[createUser]Response: " .$msg_chk);            
                    
                    $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
                    
                    \Log::debug("[createUser]StatusCode: " .$httpcode);           

                    $result = json_decode($msg_chk);
                    
                    if($httpcode != 201)
                        throw new Exception('client_add: '. $result->errors[0]);

                    return(array(
                            'success' => true,
                            'userId' => $result->data->id
                        )
                    );

                }
                catch(Exception  $ex)
                {
                    $return = array(
                        "success" 					=> false ,
                        "message" 					=> $ex->getMessage()
                    );
                    
                    //\Log::error($return);

                    return $return;
                }
                
            }
               
       
    } 
    
    //Listagem de suários cadastrados
    public static function listUser($clientId, $token, $cpf, $email)
    {
        try
        {
            $session = curl_init();

            $api = self::apiEnvironment();

            $url = sprintf('%s/clients/%s/users?cpf=%s&email=%s', $api, $clientId, $cpf, $email);

            \Log::debug('[listUser]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[listUser]Header: '.json_encode($header));  
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            	        
            
            $msg_chk = curl_exec($session);

            \Log::debug("[listUser]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[listUser]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('users_list: '. $result->errors[0]);

            \Log::debug("[listUser]data: " . json_encode($result->data));  

            if(count($result->data) <= 0)
            {
                    return(array(
                        'success' => true,
                        'userId' => null,
                        'firstName' => null,
                        'lastName' => null,
                        'cpf' => null,
                        'email' => null
                    )
                );                
            }   

            return(array(
                    'success' => true,
                    'userId' => $result->data[0]->id,
                    'firstName' => $result->data[0]->attributes->name,
                    'lastName' => $result->data[0]->attributes->last_name,
                    'cpf' => $result->data[0]->attributes->cpf,
                    'email' => $result->data[0]->attributes->email
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            \Log::error($return);

			return $return;
		}         
       
    }
    
    //Lista de cartões cadastrados
    public static function listCard($clientId, $token, $userId)
    {
        try
        {
            $session = curl_init();

            $environment = (App::environment());

            if ($environment == "production") {
                $api = self::APP_API_PROD;
            } else {
                $api = self::APP_API_DEV;
            }

            $url = sprintf('%s/clients/%s/users/%s/cards', $api, $clientId, $userId);

            \Log::debug('[listCard]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   

            \Log::debug('[listCard]Header: '.json_encode($header));  
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);

            $msg_chk = curl_exec($session);

            \Log::debug("[listCard]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[listCard]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                throw new Exception('listCard: '. $result->errors[0]);

            $cards = array();

            for($i=0;$i<count($result->data);$i++)
            {
                $card = array (
                    'id' => $result->data[$i]->id,
                    'last_digits' => $result->data[$i]->attributes->last_digits,
                    'holder_name' => $result->data[$i]->attributes->holder_name,
                    'expiration_month' => $result->data[$i]->attributes->expiration_month,
                    'expiration_year' => $result->data[$i]->attributes->expiration_year                    
                );

                array_push($cards, $card);
            }            

            return(array(
                    'success' => true,
                    'cards' => $cards
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
		}         
       
    }  

    private static function apiEnvironment(){

        $environment = (App::environment());    
        
            if ($environment == "production") {
                return self::APP_API_PROD;
            } else {
                return self::APP_API_DEV;
            }
    }


    
    //Criação de Conta de empresa
    public static function createBusinessAccount($name, $website, $email, $phone, $description, $mcc, $metadata,$ein, $firstName, $lastName, $owner_email, $owner_phone, $owner_cpf, $address)
    {
        try
        {
            $session = curl_init();

            $api = self::apiEnvironment();

            $url = sprintf('%s/clients/businesses', $api);

            \Log::debug('[createBusinessAccount]Url: '.json_encode($url));    
            
            $header = array (
                'Content-Type: application/json',
                        
            );   
            
            \Log::debug('[listCard]Header: '.json_encode($header)); 

            $address = (object)$address;

            $fields = array (
                'name' => $name,
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
                'description' => $description,
                'mcc' => $mcc,
                'metadata' => $metadata,
                'ein' => $ein,
                'owner_first_name' => $firstName,
                'owner_last_name' => $lastName,
                'owner_email' => $owner_email,
                'owner_phone' => $owner_phone,
                'owner_cpf' => $owner_cpf,
                'address_attributes' => $address,
            );  

            // $fields = (object)$fields;

            // $fields = json_encode($fields);
            // $endereco = $fields->$address;

            // \Log::debug('[createBusinessAccount]type1: '.gettype($fields));
            // \Log::debug('[createBusinessAccount]type2: '.gettype(($fields)));
            \Log::debug('[createBusinessAccount]objeto '.json_encode($fields));
            
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_POST, true );
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);
            
            \Log::debug("[createBusinessAccount]Session: " .$session); 
            
            $msg_chk = curl_exec($session);
            
            \Log::debug("[createBusinessAccount]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

            \Log::debug("[createBusinessAccount]StatusCode: " .$httpcode); 
            
            $result = json_decode($msg_chk);
            
            if($httpcode != 201)
                throw new Exception('Erro: '. $result->errors[0]);
            
            return(array(
                    'success'       => true,
                    'data'          => $result,
                    'client_id'     => $result->data->id,
                    'token'         => $result->data->attributes->authentication_token,
                    'message'       => 'Cliente criado'
                )
            );

            
        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
                "message" 					=> $ex->getMessage()
               
			);
            
            //\Log::error($return);

			return $return;
		}

    }  

    //Envio de documentos para cadastro de empresa
    public static function createDocument($clientId, $token, $file, $category, $description)
    {
        try
        {
            

            $session = curl_init();

            $environment = (App::environment());

            $api = self::apiEnvironment();
            
            $url = sprintf('%s/clients/%s/documents', $api, $clientId);

            \Log::debug('[createDocument]Url: '.$url);            

            $header = array (
                //'content-type: multipart/form-data',
                'token:' .$token          
            );

            $cFile = curl_file_create ( realpath($file) );
            
            $fields = [
                'file'          => $cFile ,
                'description'   => $description,
                'category'      => $category,
            ];

            

            \Log::debug('[createDocument]dir name:'.dirname(__FILE__));        
            \Log::debug('[createDocument]Header:'.json_encode($header));                 
            \Log::debug('[createDocument]fields:'.json_encode($fields));                 


            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "POST" );
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            	        
            curl_setopt($session, CURLOPT_POSTFIELDS, $fields);
            
            $msg_chk = curl_exec($session);

            \Log::debug("[createDocument]Response: " . $msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[createDocument]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 201)
                throw new Exception('Erro'.$result->errors);

            return(array(
                    'success' => true,
                    'Documents' => $result->data->id,
                    'message' => 'Documento Salvo'
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
        }
        


    } 

    //Criação de conta de banco
    public static function createBankAccount($clientId, $token, $holder_name, $bank_code, $routing_number, $account_number, $document)
    {
        try
        {
            
            $session = curl_init();

            $environment = (App::environment());

            $api = self::apiEnvironment();

            $url = sprintf('%s/clients/%s/bank_accounts', $api, $clientId);

            \Log::debug('[createBankAccount]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token: ' .$token          
            );   

            \Log::debug('[createBankAccount]Header: '.json_encode($header));                 

            $fields = array (
                'holder_name'       => $holder_name,
                'bank_code'         => $bank_code,
                'routing_number'    => $routing_number,
                'account_number'    => $account_number,
                'document'          => $document,
                
            );  
            
            \Log::debug('[createBankAccount]type1: '.gettype($fields));
            \Log::debug('[createBankAccount]type2: '.gettype(json_encode($fields)));
            \Log::debug('[createBankAccount]Fields: '.json_encode($fields).json_encode(gettype($fields)));
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_POST, true );
            curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            	        
            
            $msg_chk = curl_exec($session);

            \Log::debug("[createBankAccount]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[createBankAccount]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 201)
                throw new Exception('Erro'. $result->errors[0]);

            return(array(
                    'success' => true,
                    'userId' => $result->data->id,
                    'message' => 'Conta bancaria salva'
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            return $return;
        }
        
        

    } 
    
    //Listagem de documentos cadastrados
    public static function listDocument($clientId, $token)
    {
        try
        {
            $session = curl_init();

            $api = self::apiEnvironment();

            $url = sprintf('%s/clients/%s/documents', $api, $clientId);

            \Log::debug('[listDocument]Url: '.json_encode($url));            

            $header = array (
                'Content-Type: application/json',
                'token:' .$token          
            );   
            

            \Log::debug('[listDocument]Header: '.json_encode($header));  
            
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_TIMEOUT, self::APP_TIMEOUT);
            curl_setopt($session, CURLOPT_HTTPHEADER, $header);            	        
            
            $msg_chk = curl_exec($session);

            \Log::debug("[listDocument]Response: " .$msg_chk);            
            
            $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);  
            
            \Log::debug("[listDocument]StatusCode: " .$httpcode);           

            $result = json_decode($msg_chk);
            
            if($httpcode != 200)
                
                throw new Exception('users_list: '. $result->errors[0]);

            if(count($result->data) <= 0)
            {
                    return(array(
                        'success' => true,
                        'id' => null,
                        'token' => null,
                        'file' => null,
                        'category' => null,
                        'description' => null,
                        'metadata' => null
                    )
                );                
            }

            return(array(
                    'success' => true,
                    'id' => $result->data[0]->id,
                    'token' => $result->data[0]->attributes->token,
                    'file' => $result->data[0]->attributes->file,
                    'category' => $result->data[0]->attributes->category,
                    'description' => $result->data[0]->attributes->description,
                    'metadata' => $result->data[0]->attributes->metadata
                )
            );

        }
        catch(Exception  $ex)
        {
			$return = array(
				"success" 					=> false ,
				"message" 					=> $ex->getMessage()
			);
            
            //\Log::error($return);

			return $return;
		}         
       
    }

    public function checkAddress($address){

        if ($address['street_address'] == "" ||
            $address['neighborhood'] == "" ||
            $address['city'] == "" ||
            $address['state'] == "" ||
            $address['postal_code'] == "" ||
            $address['country_code'] == "" ) {
                return(array(
                    'success' => false,
                    'message' => "Favor atualizar o endereço"
                )
            );
        } else {
            $stateSplit = explode(" ", $address['state']);
            
            if (count($stateSplit) > 1) {
                $state = substr($stateSplit[0], 0, 1).substr($stateSplit[1], 0, 1);	
            } else {
                $state = substr($stateSplit[0], 0, 2);
            }
            
            $address['state'] = $state;

            return(array(
                'success' => true,
                'body' => $address
            )
            );
        }

        
    }

   

}

?>

