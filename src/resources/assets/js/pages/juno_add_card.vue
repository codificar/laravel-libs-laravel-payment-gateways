<script>
import axios from "axios";
import moment from "moment";
export default {
    props: ["JunoSandbox", "PublicToken"],
    data() {
        return {
            holder_id: "",
            holder_type: "",
            holder_token: "",
            cardNumber: "",
            holderName: "",
            securityCode: "",
            expirationMonth: "",
            expirationYear: "",
            show_card_added_msg: false
        };
    },
    methods: {
        saveCardApi(hashCard, cardType, lastFour) {
            new Promise((resolve, reject) => {
                axios
                .post('/libs/gateways/juno/add_card/' + this.holder_type, {
                    id: this.holder_id,
                    token: this.holder_token,
                    holder_type: this.holder_type,
                    last_four: lastFour,
                    card_type: cardType,
                    credit_card_hash: hashCard
                })
                .then((response) => {
                    if (response.data.success) {
                        this.show_card_added_msg = true;
                    } else {
                        this.$swal({
                            title: "Cartão Recusado",
                            type: 'error'
                        });
                    }
                })
                .catch((error) => {
                    this.$swal({
                        title: "Cartão Recusado",
                        type: 'error'
                    });
                    console.log(error);
                    reject(error);
                    return false;
                });
            });
        },
        addCard() {
            if(!this.cardNumber || !this.holderName || !this.securityCode || !this.expirationMonth || !this.expirationYear) {
                this.$swal({
                    title: "Preencha todos os campos",
                    type: 'warning'
                });
            } else {

                if(this.JunoSandbox.toString() == "1") {
                    var checkout = new DirectCheckout(this.PublicToken, false);
                } else {
                    var checkout = new DirectCheckout(this.PublicToken); 
                }

                var cardData = {
                    cardNumber: this.cardNumber.replace(/\s/g, ''), //remove space bar
                    holderName: this.holderName,
                    securityCode: this.securityCode,
                    expirationMonth: this.expirationMonth,
                    expirationYear: this.expirationYear.length == 2 ? "20" + this.expirationYear : this.expirationYear
                };
                var that = this;
                checkout.getCardHash(cardData, function(cardHash) {
                    var cardType = checkout.getCardType(cardData.cardNumber);
                    that.saveCardApi(cardHash, cardType, cardData.cardNumber.slice(-4));
                }, function(error) {
                    that.$swal({
                        title: "Dados do cartão inválido",
                        type: 'error'
                    });
                });
            }
        },
        loadJunoScripts() {
            let recaptchaScript = document.createElement('script');
            if(this.JunoSandbox.toString() == "1") {
                recaptchaScript.setAttribute('src', 'https://sandbox.boletobancario.com/boletofacil/wro/direct-checkout.min.js');
            }
            else {
                recaptchaScript.setAttribute('src', 'https://www.boletobancario.com/boletofacil/wro/direct-checkout.min.js');
            }
            document.head.appendChild(recaptchaScript);
        },
        findGetParameter(parameterName) {
            var result = null, tmp = [];
            var items = location.search.substr(1).split("&");
            for (var index = 0; index < items.length; index++) {
                tmp = items[index].split("=");
                if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
            }
            return result;
        },

        addMoreCard() {
            //when button "add more card" was clicked, so reset all card data
            this.show_card_added_msg = false;
            this.cardNumber = "";
            this.holderName = "";
            this.securityCode = "";
            this.expirationMonth = "";
            this.expirationYear = "";
        },
        getRouteParams() {
            this.holder_id = this.findGetParameter("holder_id");
            this.holder_type = this.findGetParameter("holder_type");
            this.holder_token = this.findGetParameter("holder_token");

            //if is not admin, check if has token and id
            if(this.holder_type != "admin" && (!this.holder_id || !this.holder_type || !this.holder_token)) {
                this.$swal({
                    title: "Usuário não autenticado",
                    type: 'error'
                });
            }
        }
        
    },
    created() {

    },
    mounted() {
        this.getRouteParams();
        this.loadJunoScripts();
    }
};
</script>
<template>
  <div>
      <div class="container" style="margin-top: 20px;">
        <div class="row">
            <div class="col-12 col-sm-12">
                <div v-if="show_card_added_msg">
                    <h4 style="margin-top: 50px; text-align: center; color:grey;">Cartão adicionado com sucesso! Volte ou adicione um novo cartão</h4 style="text-align: center; color:grey;">
                    <br>
                    <div class="col-md-4 text-center"> 
                        <button class="btn btn-sm btn-success" @click="addMoreCard"><i class="mdi mdi-gamepad-circle"></i> Adicionar mais um cartão</button>
                    </div>
                </div>
                <div v-else class="card">
                    <div class="card-header">
                        <strong>Cartão de crédito</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label for="name">Nome do titular</label>
                                    <input v-model="holderName" class="form-control" id="card_name" type="text">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label>Número do cartão</label>
                                    <input type="tel" v-model="cardNumber" v-mask="['#### #### #### ####']" class="form-control" id="card_number">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-6 col-sm-6 col-md-4">
                                <label for="ccmonth">Mês de Validade</label>
                                <select v-model="expirationMonth" class="form-control" id="card_month">
                                    <option v-for="op in ['', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']">{{op}}</option>
                                </select>
                            </div>
                            <div class="col-6 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Ano de Validade</label>
                                    <input type="tel" v-model="expirationYear" v-mask="['####']" class="form-control" id="card_year">
                                </div>
                            </div>
                            <div class="col-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input type="tel" v-model="securityCode" v-mask="['###']" class="form-control" id="card_cvv">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-success float-right" type="submit" @click="addCard"><i class="mdi mdi-gamepad-circle"></i> Cadastrar Cartão</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>