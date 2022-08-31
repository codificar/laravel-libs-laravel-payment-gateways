<script>
import axios from "axios";
import moment from "moment";
export default {
    props: ["JunoSandbox", "PublicToken"],
    data() {
        return {
            isLoading: false,
            holder_id: "",
            holder_type: "",
            holder_token: "",
            cardNumber: "",
            holderName: "",
            securityCode: "",
            expirationMonth: "",
            expirationYear: "",
            show_content: false // quando um cartao e adicionado, o usuario e redirecionado para uma pagina sem conteudo. Dessa forma, basta que o iframe (ou webview) tenha o evento de troca de url para saber quando um cartao foi adicionado 
        };
    },
    methods: {
        saveCardApi(hashCard) {
            new Promise((resolve, reject) => {
                axios
                .post("/libs/finance/" + this.holder_type + "/add_credit_card", {
                    id: this.holder_id,
                    provider_id: this.holder_id,
                    user_id: this.holder_id,
                    token: this.holder_token,
                    card_holder: this.holderName,
                    card_number: this.cardNumber.replace(/\s/g, ''),
                    card_cvv: this.securityCode,
                    card_expiration_year: this.expirationYear,
                    card_expiration_month:this.expirationMonth
                })
                .then((response) => {
                    if (response.data.success) {
                        console.log("Resposta", response.data);
                        this.tokenizationCard(hashCard, response.data.data[0].card_id);
                    } else {
                        this.isLoading = false;
                        this.showAlert(this.trans('setting.refused_card'), "error");
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.showAlert(this.trans('setting.refused_card'), "error");
                    console.log(error);
                    reject(error);
                    return false;
                });
            });
        },
        tokenizationCard(hashCard, card_id) {
            new Promise((resolve, reject) => {
                axios
                .post('/libs/gateways/juno/add_card/' + this.holder_type, {
                    id: this.holder_id,
                    user_id: this.holder_id,
                    provider_id: this.holder_id,
                    token: this.holder_token,
                    credit_card_hash: hashCard,
                    card_id: card_id
                })
                .then((response) => {
                    this.isLoading = false;
                    if (response.data.success) {
                        //change url
                        this.$swal({
                            title: this.trans('setting.card_success_added'),
                            type: 'success'
                        }).then((result) => {
                            location.reload();
                            document.location.href = "add_card#hide_content";
                        }); 

                    } else {
                        this.showAlert(this.trans('setting.refused_card'), "error");
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.showAlert(this.trans('setting.refused_card'), "error");
                    console.log(error);
                    reject(error);
                    return false;
                });
            });
        },
        showAlert(msg, type = 'warning') {
            this.$swal({
                title: msg,
                type: type
            });
        },
        addCard() {
            var emptyInput = 
                !this.holderName ? this.trans('setting.holder_name') :
                !this.cardNumber ? this.trans('setting.card_number') :
                !this.expirationMonth ? this.trans('setting.exp_month') :
                !this.expirationYear ? this.trans('setting.exp_year') :
                !this.securityCode ? this.trans('setting.cvv') : "";

            if(emptyInput) {
                this.showAlert(this.trans('setting.fill_the_field') + " " + emptyInput);
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
                if(!checkout.isValidCardNumber(cardData.cardNumber)) {
                    this.showAlert(this.trans('setting.invalid_card_number'));
                } else if(!checkout.isValidSecurityCode(cardData.cardNumber, cardData.securityCode)) {
                    this.showAlert(this.trans('setting.invalid_cvv'));
                } else if (!checkout.isValidExpireDate(cardData.expirationMonth, cardData.expirationYear)) {
                    this.showAlert(this.trans('setting.expired_card'));
                } else {
                    var that = this;
                    that.isLoading = true;
                    checkout.getCardHash(cardData, function(cardHash) {
                        var cardType = checkout.getCardType(cardData.cardNumber);
                        that.saveCardApi(cardHash);
                    }, function(error) {
                        that.isLoading = false;
                        that.showAlert(that.trans('setting.invalid_card'), 'error');
                    });
                } 
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
        getRouteParams() {

            if(window.location.hash != "#hide_content" && window.location.hash != "hide_content") {
                this.show_content = true;

                this.holder_id = this.findGetParameter("holder_id");
                this.holder_type = this.findGetParameter("holder_type");
                this.holder_token = this.findGetParameter("holder_token");

                if(!this.holder_id || !this.holder_type || !this.holder_token) {
                    this.showAlert(this.trans('setting.user_not_auth'), "error");
                }
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

    <!-- loading -->
    <loading 
        :active.sync="isLoading" 
        :is-full-page="true"
        :loader="'dots'"
        :color="'#007bff'"
    ></loading>
    <!-- loading -->

      <div class="container" style="margin-top: 20px;">
        <div class="row">
            <div class="col-12 col-sm-12">
                <div v-if="show_content" class="card">
                    <div class="card-header">
                        <strong>{{ trans("setting.credit_card") }}</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label for="name">{{ trans("setting.holder_name") }}</label>
                                    <input v-model="holderName" class="form-control" id="card_name" type="text">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label>{{ trans("setting.card_number") }}</label>
                                    <input type="tel" v-model="cardNumber" v-mask="['#### #### #### ####']" class="form-control" id="card_number">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-6 col-sm-6 col-md-4">
                                <label for="ccmonth">{{ trans("setting.exp_month") }}</label>
                                <select v-model="expirationMonth" class="form-control" id="card_month">
                                    <option v-for="op in ['', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']">{{op}}</option>
                                </select>
                            </div>
                            <div class="col-6 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>{{ trans("setting.exp_year") }}</label>
                                    <input type="tel" v-model="expirationYear" v-mask="['####']" class="form-control" id="card_year">
                                </div>
                            </div>
                            <div class="col-12 col-sm-12 col-md-4">
                                <div class="form-group">
                                    <label>{{ trans("setting.cvv") }}</label>
                                    <input type="tel" v-model="securityCode" v-mask="['###']" class="form-control" id="card_cvv">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-success float-right" type="submit" @click="addCard"><i class="mdi mdi-gamepad-circle"></i> {{ trans("setting.create_card") }} </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>