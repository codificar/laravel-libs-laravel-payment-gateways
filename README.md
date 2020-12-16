# laravel-payment-gateways
laravel-payment-gateways é uma bilioteca de pagamento, que possui diversos gateways.

***
# createCard
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Payment` | Object | Instância da classe do model `Payment` |
| `User` | Object | Instância da classe do model `User` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se o cartão foi criado e `false` se foi recusado |
| `token` | String| Token do cartão, retornado pelo gateway. |
| `card_token` | String | Token do cartão, retornado pelo gateway. |
| `customer_id` | String | Alguns gateways possuem customer_id e outros não. Os que nao possui, será o card_token |
| `card_type` | String | Bandeira do cartão. Visa, mastercard etc. |
| `last_four` | String | Ultimos 4 digitos do cartão |
| `gateway` | String | Gateway que foi utilizado |

***
# charge
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Payment` | Object | Instância da classe do model `Payment` |
| `amount` | Float | Valor a ser cobrado |
| `description` | String | Descrição do pagamento |
| `capture` | Boolean | `true` se é para capturar o valor agora ou `false` para fazer uma pré-autorização |
| `User` | Object | Instância da classe do model `User` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se transação foi feita ou `false` se foi recusada |
| `captured` | Boolean| `true` se o valor foi capturado ou `false` se foi pre-autorizado |
| `paid` | Boolean | `true` se foi pago ou `false` se foi apenas autorizado (ou recusado) |
| `status` | String | `paid` se foi pago ou `authorized` se foi pre-autorizado |
| `transaction_id` | String | Id da transação retorno pelo gateway. |

***

# capture
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Transaction` | Object | Instância da classe do model `Transaction` |
| `amount` | Float | Valor que de fato será cobrado. Dependendo do gateway, esse valor pode ser igual ou menor que o pre-autorizado |
| `Payment` | Object | Instância da classe do model `Payment` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se a captura foi feita ou `false` se foi recusada |
| `status` | String | `paid` se foi pago outros valores caso não foi pago |
| `captured` | Boolean| `true` se o valor foi capturado ou false caso deu algum problema |
| `paid` | Boolean | `true` se foi pago ou `false` se houve um erro |
| `transaction_id` | String | Id da transação retornado pelo gateway |

***

# refund
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Transaction` | Object | Instância da classe do model `Transaction` |
| `Payment` | Object | Instância da classe do model `Payment` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se foi cancelado `false` se houve um erro ao cancelar |
| `status` | String | `refunded` se foi cancelado corretamente, ou outro valor se não conseguiu cancelar |
| `transaction_id` | String | Id da transação retornado pelo gateway |

***

# retrieve
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Transaction` | Object | Instância da classe do model `Transaction` |
| `Payment` | Object | Instância da classe do model `Payment` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se conseguiu pegar os dados `false` se houve um erro |
| `transaction_id` | String | Id da transação retornado pelo gateway |
| `amount` | Float | Valor cobrado |
| `destination` | String | Valor vazio |
| `status` | String | status da transação |
| `card_last_digits` | String | Últimos 4 digitos do cartão |


***

# billetCharge
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `amount` | Float | Valor do boleto |
| `client` | Object | Instância do usuário ou do prestador `User` ou `Provider` |
| `postbackUrl` | String | Rota do nosso servidor que o gateway envia para avisar que o boleto foi pago |
| `billetExpirationDate` | String | Data de vencimento do boleto. Possui o formato YYYY-MM-DD (Y-m-d) |
| `billetInstructions` | String | Instruções do boleto. |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se conseguiu gerar o boleto `false` se houve um erro |
| `captured` | Boolean| valor fixado como `true` |
| `paid` | Boolean | `true` se foi pago ou `false` se não foi pago ainda. Como o boleto acabou de ser gerado, então provavelmente será `false` |
| `status` | String | status da transação. Como o boleto acabou de ser gerado, então provavelmente será `waiting_payment` |
| `transaction_id` | String | Id da transação retornado pelo gateway |
| `billet_url` | String | Url do boleto retornado pelo gateway |
| `billet_expiration_date` | String | Data de expiração do boleto |

***

# createOrUpdateAccount
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `LedgerBankAccount` | Object | Instância do model `LedgerBankAccount` |

***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se conseguiu gerar o boleto `false` se houve um erro |
| `recipient_id` | String | Id do recebedor (id da conta bancária) |

***

# chargeWithSplit
| Parâmetros | Tipo | Descrição |
|---|---|---|
| `Payment` | Object | Instância da classe do model `Payment` |
| `Provider` | Object | Instância da classe do model `Provider` |
| `totalAmount` | Float | Valor a ser cobrado do usuário |
| `providerAmount` | Float | Valor que o prestador vai receber |
| `description` | String | Descrição do pagamento |
| `capture` | Boolean | `true` se é para capturar o valor agora ou `false` para fazer uma pré-autorização |
| `User` | Object | Instância da classe do model `User` |
***
| Retorno | Tipo | Descrição |
|---|---|---|
| `success` | Boolean | `true` se transação foi feita ou `false` se foi recusada |
| `captured` | Boolean| `true` se o valor foi capturado ou `false` se foi pre-autorizado |
| `paid` | Boolean | `true` se foi pago ou `false` se foi apenas autorizado (ou recusado) |
| `status` | String | `paid` se foi pago ou `authorized` se foi pre-autorizado |
| `transaction_id` | String | Id da transação retorno pelo gateway. |

***