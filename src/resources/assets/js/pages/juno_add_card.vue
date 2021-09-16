<script>
import axios from "axios";
import moment from "moment";
export default {
    props: ["JunoSandbox", "PublicToken"],
    data() {
        return {
            cardNumber: "",
            holderName: "",
            securityCode: "",
            expirationMonth: "",
            expirationYear: ""
        };
    },
    methods: {
        addCard() {
            if(!this.cardNumber || !this.holderName || !this.securityCode || !this.expirationMonth || !this.expirationYear) {
                this.$swal({
                    title: "Preencha todos os campos",
                    type: 'warning'
                });
            } else {

                if(this.JunoSandbox) {
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
                console.log(cardData);
                var that = this;
                checkout.getCardHash(cardData, function(cardHash) {
                    var cardType = checkout.getCardType(cardData.cardNumber);
                    console.log("Carttao hashado: ", cardHash);
                    console.log("bandeira: ", cardType);
                }, function(error) {
                    that.$swal({
                        title: "Dados do cartão inválido",
                        type: 'error'
                    });
                });
            }
        }
    },
    created() {

    },
    mounted() {

        let recaptchaScript = document.createElement('script');
        if(this.JunoSandbox) {
            recaptchaScript.setAttribute('src', 'https://sandbox.boletobancario.com/boletofacil/wro/direct-checkout.min.js');
        }
        else {
            recaptchaScript.setAttribute('src', 'https://www.boletobancario.com/boletofacil/wro/direct-checkout.min.js');
        }
        document.head.appendChild(recaptchaScript);
    }
};
</script>
<template>
  <div>
      <div class="container" style="margin-top: 20px;">
        <div class="row">
            <div class="col-12 col-sm-12">
                <div class="card">
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
                                    <input v-model="cardNumber" v-mask="['#### #### #### ####']" class="form-control" id="card_number" type="text">
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
                                    <input v-model="expirationYear" v-mask="['####']" class="form-control" id="card_year" type="text">
                                </div>
                            </div>
                            <div class="col-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input v-model="securityCode" v-mask="['###']" class="form-control" id="card_cvv" type="text">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-success float-right" type="submit" @click="addCard"><i class="mdi mdi-gamepad-circle"></i> Cadastrar Cartão</button>
                        <button class="btn btn-sm btn-danger" type="reset"><i class="mdi mdi-lock-reset"></i> Sair</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>