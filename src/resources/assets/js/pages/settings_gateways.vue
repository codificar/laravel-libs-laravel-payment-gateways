<script>
import axios from "axios";
import moment from "moment";
export default {
  props: [
    "PaymentMethods", 
    "Gateways", 
    "PixGateways", 
    "Carto", 
    "Bancryp", 
    "Prepaid", 
    "Settings", 
    "Certificates", 
    "Nomenclatures",
    "EnviromentActive"
  ],
  data() {
    return {
      isLoading: false,
      listWebhooks: [],
      isWebhooks: false,
      messageWebhook: '',
      gateways: {},
      pix_gateways : {},
      payment_methods: {},
      prepaid: {},
			carto: {},
      certificates: {},
      nomenclatures: {}
    };
  },
  methods: {
    saveSettings() {
      this.$swal({
        title: this.trans("setting.edit_confirm"),
        type: "warning",
        showCancelButton: true,
        confirmButtonText: this.trans("setting.yes"),
        cancelButtonText: this.trans("setting.no"),
      }).then((result) => {
        if (result.value) {
          this.isLoading = true;
          //Submit form if its valid and email doesnt exists
          new Promise((resolve, reject) => {
            axios
              .post("/libs/settings/save/gateways", {
                payment_methods: this.payment_methods,
                gateways: this.gateways,
                pix_gateways: this.pix_gateways,
								carto: this.carto,
								bancryp: this.bancryp,
								prepaid: this.prepaid,
                settings: this.settings,
                nomenclatures: this.nomenclatures
              })
              .then((response) => {
                if (response.data.success) {
                  if(this.gateways.default_payment_boleto == 'bancointer' && (this.certificates.crt || this.certificates.key)){
                    var formData = new FormData();
                    formData.append("crt", this.certificates.crt);
                    formData.append("key", this.certificates.key);
                    axios.post("/libs/gateways/banco_inter/save_certificates",formData,{
                      headers: {
                        'Content-Type': 'multipart/form-data'
                      }
                    });
                  }
                  if(response.data.is_updating_cards && response.data.estimate_update_cards) {
                    this.$swal({
                      title: this.trans("setting.success_set_gateway"),
                      text: this.trans("setting.gateway_has_change") + response.data.estimate_update_cards,
                      type: "warning",
                    }).then((result) => {});
                  } else {
                    this.$swal({
                      title: this.trans("setting.success_set_gateway"),
                      type: "success",
                    }).then((result) => {});
                  }
                  //Atualizar a lista de webhooks pix
                  this.retrievePixWebHooks();
                } else {
                  this.$swal({
                    title: this.trans("setting.failed_set_gateway"),
                    html:
                      '<label class="alert alert-danger alert-dismissable text-left">' +
                      response.data.errors +
                      "</label>",
                    type: "error",
                  }).then((result) => {});
                }
                this.isLoading = false;
              })
              .catch((error) => {
                this.isLoading = false;
                console.log(error);
                reject(error);
                return false;
              });
          });
        }
      });
    },

    retrievePixWebHooks() {
      this.isLoading = true;
      this.messageWebhook = '';
      new Promise((resolve, reject) => {
        axios
          .get("/libs/settings/retrieve/webhooks")
          .then((response) => {
            this.isLoading = false;
            this.listWebhooks = response.data.data.webhooks;
            this.isWebhooks = response.data.data.success;
            this.messageWebhook = this.trans("setting.failed_retreive_webhook");
            if(response.data.message) {
              this.messageWebhook = response.data.message;
            }
          })
          .catch((error) => {
            this.isLoading = false;
            console.log(error);
            reject(error);
            return false;
          });
      });
    },

    processFile(event, type) {
      if(type == 'crt'){
        this.gateways.bancointer.banco_inter_crt = event.target.files[0].name
        this.certificates.crt = event.target.files[0]
      }else if(type == 'key'){
        this.gateways.bancointer.banco_inter_key = event.target.files[0].name
        this.certificates.key = event.target.files[0]
      }
    },

    paymentCustomNames() {
      this.nomenclatures.payments_custom_name = !this.nomenclatures.payments_custom_name;
    },

    getOriginUrl() {
      return window.location.origin;
    }
  },
  created() {
    this.PaymentMethods ? (this.payment_methods = JSON.parse(this.PaymentMethods)) : null;
    this.Gateways ? (this.gateways = JSON.parse(this.Gateways)) : null;
    this.PixGateways ? (this.pix_gateways = JSON.parse(this.PixGateways)) : null;
		this.Carto ? (this.carto = JSON.parse(this.Carto)) : null;
		this.Bancryp ? (this.bancryp = JSON.parse(this.Bancryp)) : null;
		this.Prepaid ? (this.prepaid = JSON.parse(this.Prepaid)) : null;
    this.Settings ? (this.settings = JSON.parse(this.Settings)) : null;
    this.Certificates ? (this.certificates = JSON.parse(this.Certificates)) : null;
    this.Nomenclatures ? (this.nomenclatures = JSON.parse(this.Nomenclatures)) : null;
    this.nomenclatures.payments_custom_name = parseInt(this.nomenclatures.payments_custom_name) == 1 ? true : false;
    //Atualizar a lista de webhooks pix
    this.retrievePixWebHooks();
  },
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

    <!-- formas de pagamento -->
    <div class="tab-content">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">
            {{ trans("setting.payment_methods") }}
          </h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-12">
              <div class="panel-heading">
                <h3 class="panel-title">
                  {{ trans("setting.choose_payment_methods") }}
                </h3>
                <hr />
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_money"
                    v-model="payment_methods.payment_money"
                  />
                  <label class="form-check-label" for="payment_money">{{
                    trans("setting.money")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_card"
                    v-model="payment_methods.payment_card"
                  />
                  <label class="form-check-label" for="payment_card">{{
                    trans("setting.card")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_debitCard"
                    v-model="payment_methods.payment_debitCard"
                  />
                  <label class="form-check-label" for="payment_debitCard">{{
                    trans("setting.debitCard")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_machine"
                    v-model="payment_methods.payment_machine"
                  />
                  <label class="form-check-label" for="payment_machine">{{
                    trans("setting.machine")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_carto"
                    v-model="payment_methods.payment_carto"
                  />
                  <label class="form-check-label" for="payment_carto">{{
                    trans("setting.carto")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_crypt"
                    v-model="payment_methods.payment_crypt"
                  />
                  <label class="form-check-label" for="payment_crypt">{{
                    trans("setting.crypt_coin")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_balance"
                    v-model="payment_methods.payment_balance"
                  />
                  <label class="form-check-label" for="payment_balance">{{
                    trans("setting.payment_balance")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_prepaid"
                    v-model="payment_methods.payment_prepaid"
                  />
                  <label class="form-check-label" for="payment_prepaid">
                    {{ trans("setting.payment_prepaid") }}
                    <a
                      href="#"
                      class="question-field"
                      data-toggle="tooltip"
                      :title="trans('setting.payment_prepaid_msg')"
                      ><span class="mdi mdi-comment-question-outline"></span
                    ></a>
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_billing"
                    v-model="payment_methods.payment_billing"
                  />
                  <label class="form-check-label" for="payment_billing">{{
                    trans("setting.payment_billing")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_direct_pix"
                    v-model="payment_methods.payment_direct_pix"
                  />
                  <label class="form-check-label" for="payment_direct_pix">
                    {{ trans("setting.payment_direct_pix") }}
                    <a
                      href="#"
                      class="question-field"
                      data-toggle="tooltip"
                      :title="trans('setting.payment_direct_pix_msg')"
                      ><span class="mdi mdi-comment-question-outline"></span
                    ></a>
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="payment_gateway_pix"
                    v-model="payment_methods.payment_gateway_pix"
                  />
                  <label class="form-check-label" for="payment_gateway_pix">
                    {{ trans("setting.payment_gateway_pix") }}
                    <a
                      href="#"
                      class="question-field"
                      data-toggle="tooltip"
                      :title="trans('setting.payment_gateway_pix_msg')"
                      ><span class="mdi mdi-comment-question-outline"></span
                    ></a>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / formas de pagamento -->


    <!-- nomenclatures -->
    <div class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header d-flex align-items-center">
          <h4 class="m-b-0 text-white">{{ trans("setting.custom_nomenclatures") }} </h4>
          <toggle-button 
            style="padding-left: 10px; margin:0px" 
            @change="paymentCustomNames"
            :height="20"
            :width="43"
            :value="nomenclatures.payments_custom_name"
          />
        </div>
        <div class="card-block" v-if="nomenclatures.payments_custom_name">
          <div class="panel-body">
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.money") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-money"
                    v-model="nomenclatures.name_payment_money"
                    :placeholder="trans('setting.money')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.card") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-card"
                    v-model="nomenclatures.name_payment_card"
                    :placeholder="trans('setting.card')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.debitCard") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-debitCard"
                    v-model="nomenclatures.name_payment_debitCard"
                    :placeholder="trans('setting.debitCard')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.machine") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-machine"
                    v-model="nomenclatures.name_payment_machine"
                    :placeholder="trans('setting.machine')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.carto") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-carto"
                    v-model="nomenclatures.name_payment_carto"
                    :placeholder="trans('setting.carto')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.crypt_coin") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-crypt_coin"
                    v-model="nomenclatures.name_payment_crypt"
                    :placeholder="trans('setting.crypt_coin')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.payment_balance") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-payment_balance"
                    v-model="nomenclatures.name_payment_balance"
                    :placeholder="trans('setting.payment_balance')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.payment_prepaid") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-payment_prepaid"
                    v-model="nomenclatures.name_payment_prepaid"
                    :placeholder="trans('setting.payment_prepaid')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.payment_billing") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-payment_billing"
                    v-model="nomenclatures.name_payment_billing"
                    :placeholder="trans('setting.payment_billing')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.payment_direct_pix") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-direct_pix"
                    v-model="nomenclatures.name_payment_direct_pix"
                    :placeholder="trans('setting.payment_direct_pix')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="usr">
                    {{ trans("setting.payment_gateway_pix") }}
                  </label>
                  <input
                    type="text"
                    class="form-control input-gateway_pix"
                    v-model="nomenclatures.name_payment_gateway_pix"
                    :placeholder="trans('setting.payment_gateway_pix')"
                  />
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / nomenclatures -->

    <!-- general settings -->
    <div class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans("setting.general_settings") }}</h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{trans('setting.earnings_report_weekday')}}
                  <a href="" class="question-field" data-toggle="tooltip" :title="trans('setting.earnings_report_weekday_msg')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field"></span> 
                </label>
                <select
                  v-model="settings.earnings_report_weekday"
                  name="earnings_report_weekday"
                  class="select form-control"
                >
                  <option
                    v-for="day in settings.enum.week_days"
                    v-bind:value="day.value"
                    v-bind:key="day.value"
                  >
                    {{ trans(day.name) }}
                  </option>
                </select>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{trans('setting.show_user_account_statement')}}
                  <a href="" class="question-field" data-toggle="tooltip" :title="trans('setting.show_user_account_statement_msg')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field"></span> 
                </label>
                <select
                  v-model="settings.show_user_account_statement"
                  name="show_user_account_statement"
                  class="select form-control"
                >
                  <option value="1"> {{ trans('setting.yes') }} </option>
                  <option value="0"> {{ trans('setting.no') }} </option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / general settings -->

    <!--Payment Gateway-->
    <div
      v-if="payment_methods.payment_card || payment_methods.payment_debitCard"
      class="card-margin-top"
    >
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans("setting.pay_gateway") }}</h4>
          <span class="enviroment text-white" v-if="EnviromentActive">
            {{ trans("setting.enviromentActive") }}: <b>{{ trans(`setting.${EnviromentActive}`) || '' }}</b>
          </span>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.default_pay_gate") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('settingTableSeeder.default_pay_gate')"
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>

                <select
                  v-model="gateways.default_payment"
                  name="default_payment"
                  class="select form-control"
                >
                  <option
                    v-for="method in gateways.list_gateways"
                    v-bind:value="method.value"
                    v-bind:key="method.value"
                  >
                    {{ trans(method.name) }}
                  </option>
                </select>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.compensate_provider_days") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="
                      trans('setting.compensate_provider_msg')
                    "
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>
                <input
                  type="number"
                  min="0"
                  step="1"
                  class="form-control input-compensate_provider_days"
                  v-model="gateways.compensate_provider_days"
                />
                <div class="help-block with-errors"></div>
              </div>
            </div>
          </div>

          <!--Configurações do Ipag-->
          <div
            class="panel panel-default ipag"
            v-if="gateways.default_payment == 'ipag'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.ipag") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_api_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.ipag_api_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-ipag"
                      v-model="gateways.ipag.ipag_api_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_api_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.ipag_api_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-ipag"
                      v-model="gateways.ipag.ipag_api_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_antifraud_title") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.ipag_antifraud_title')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>

                    <select
                      v-model="gateways.ipag.ipag_antifraud"
                      name="ipag_antifraud"
                      class="select form-control"
                    >
                      <option value="1">{{ trans('setting.yes') }}</option>
                      <option value="0">{{ trans('setting.no') }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.gateway_product_title") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.gateway_product_title')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-ipag"
                      maxlength="80"
                      v-model="gateways.ipag.gateway_product_title"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.billet_gateway_provider") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.billet_gateway_provider')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <select
                      v-model="gateways.ipag.billet_gateway_provider"
                      name="billet_gateway_provider"
                      class="select form-control"
                    >
                      <option
                        v-for="methodBillet in gateways.billets"
                        v-bind:value="methodBillet.value"
                        v-bind:key="methodBillet.value"
                      >
                        {{ trans(methodBillet.name) }}
                      </option>
                    </select>
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do ipag-->

          <!--Configurações do Adiq-->
          <div
            class="panel panel-default adiq"
            v-if="gateways.default_payment == 'adiq'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.adiq") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.adiq_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.adiq_client_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-adiq"
                      v-model="
                        gateways.adiq.adiq_client_id
                      "
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.adiq_client_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.adiq_client_secret')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-adiq"
                      v-model="
                        gateways.adiq.adiq_client_secret
                      "
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Config. Adiq-->

          <!--Configurações do Pagar.Me-->
          <div
            class="panel panel-default pagarme"
            v-if="gateways.default_payment == 'pagarmev2'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.pagarme_settings") }} V2
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_encryption_key") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarmev2.pagarme_encryption_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_recipient_id") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarmev2.pagarme_recipient_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_api_key") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarmev2.pagarme_api_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Pagar.Me-->

          <!--Configurações do Pagar.Me-->
          <div
            class="panel panel-default pagarme"
            v-if="gateways.default_payment == 'pagarme'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.pagarme_settings") }} V5
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_secret_key") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarme.pagarme_secret_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_recipient_id") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarme.pagarme_recipient_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.gateway_product_title") }}
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="gateways.pagarme.gateway_product_title"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.gateway_split_taxes") }}
                      <a class="question-field" data-toggle="tooltip" :title="trans('setting.gateway_split_taxes_msg')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field"></span>
                    </label>
                    <select
                      v-model="gateways.pagarme.gateway_split_taxes"
                      name="gateway_split_taxes"
                      class="select form-control"
                    >
                      <option value="1"> {{ trans('setting.yes') }} </option>
                      <option value="0"> {{ trans('setting.no') }} </option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.liable_split") }}
                      <a class="question-field" data-toggle="tooltip" :title="trans('setting.liable_split_msg')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field"></span>
                    </label>
                    <select
                      v-model="gateways.pagarme.charge_remainder_fee"
                      name="liable_split"
                      class="select form-control"
                    >
                      <option value="1"> {{ trans('setting.yes') }} </option>
                      <option value="0"> {{ trans('setting.no') }} </option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.charge_remainder_fee") }}
                      <a class="question-field" data-toggle="tooltip" :title="trans('setting.charge_remainder_fee_msg')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field"></span>
                    </label>
                    <select
                      v-model="gateways.pagarme.liable_split"
                      name="charge_remainder_fee"
                      class="select form-control"
                    >
                      <option value="1"> {{ trans('setting.yes') }} </option>
                      <option value="0"> {{ trans('setting.no') }} </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Pagar.Me-->

          <!--Configurações do Braspag-->
          <div
            class="panel panel-default braspag"
            v-if="gateways.default_payment == 'braspag'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.braspag") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.braspag_merchant_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.braspag_merchant_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-braspag"
                      v-model="gateways.braspag.braspag_merchant_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.braspag_merchant_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.braspag_merchant_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-braspag"
                      v-model="gateways.braspag.braspag_merchant_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.braspag_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.braspag_token')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-braspag"
                      v-model="gateways.braspag.braspag_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Braspag-->

          <!--Configurações do Braspag Cielo Ecommerce-->
          <div
            class="panel panel-default braspag_cielo_ecommerce"
            v-if="gateways.default_payment == 'braspag_cielo_ecommerce'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.braspag_cielo_ecommerce") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.braspag_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.braspag_client_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="
                        gateways.braspag_cielo_ecommerce.braspag_client_id
                      "
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.braspag_client_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.braspag_client_secret')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="
                        gateways.braspag_cielo_ecommerce.braspag_client_secret
                      "
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Config. Braspag Cielo Ecommerce-->

          <!--Configurações do Getnet-->
          <div
            class="panel panel-default getnet"
            v-if="gateways.default_payment == 'getnet'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.getnet") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.getnet_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.getnet_client_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="gateways.getnet.getnet_client_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.getnet_client_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.getnet_client_secret')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="gateways.getnet.getnet_client_secret"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.getnet_seller_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.getnet_seller_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="gateways.getnet.getnet_seller_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Getnet-->

          <!--Configurações do Directpay-->
          <div
            class="panel panel-default directpay"
            v-if="gateways.default_payment == 'directpay'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.directpay") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_encrypt_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.directpay_encrypt_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_encrypt_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_encrypt_value") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.directpay_encrypt_value')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_encrypt_value"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_requester_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.directpay_requester_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_requester_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_requester_password") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans(
                            'settingTableSeeder.directpay_requester_password'
                          )
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_requester_password"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_requester_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.directpay_requester_token')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_requester_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.directpay_unique_trx_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.directpay_unique_trx_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-directpay"
                      v-model="gateways.directpay.directpay_unique_trx_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do directpay-->

          <!--Configurações do Cielo-->
          <div
            class="panel panel-default cielo"
            v-if="gateways.default_payment == 'cielo'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.cielo") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.cielo_merchant_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.cielo_merchant_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-cielo"
                      v-model="gateways.cielo.cielo_merchant_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.cielo_merchant_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.cielo_merchant_key')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-cielo"
                      v-model="gateways.cielo.cielo_merchant_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações da Cielo-->

          <!--Configurações do Strip-->
          <div
            class="panel panel-default stripe"
            v-if="gateways.default_payment == 'stripe'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.stripe_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.stripe_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.stripe_secret')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-strip"
                      v-model="gateways.stripe.stripe_secret_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.stripe_public") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.stripe_public')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-strip"
                      v-model="gateways.stripe.stripe_publishable_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.stripe_connect") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.stripe_connect')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>

                    <select
                      v-model="gateways.stripe.stripe_connect"
                      name="stripe_connect"
                      class="select form-control"
                    >
                      <option value="no_connect">{{ trans('setting.no') }}</option>
                      <option value="custom_accounts">{{ trans('setting.CUSTOM_ACCOUNTS') }}</option>
                      <option value="express_accounts">{{ trans('setting.EXPRESS_ACCOUNTS') }}</option>
                      <option value="standard_accounts">{{ trans('setting.STANDARD_ACCOUNTS') }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.stripe_total_split_refund") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.stripe_total_split_refund_message')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>

                    <select
                      v-model="gateways.stripe.stripe_total_split_refund"
                      name="stripe_total_split_refund"
                      class="select form-control"
                    >
                      <option value="false">{{ trans('setting.no') }}</option>
                      <option value="true">{{ trans('setting.yes') }}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!--/ Configurações do Strip-->

          <!--Configurações do Zoop-->
          <div
            class="panel panel-default zoop"
            v-if="gateways.default_payment == 'zoop'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.zoop_settings") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.zoop_marketplace_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.zoop_marketplace_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-zoop"
                      v-model="gateways.zoop.zoop_marketplace_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.zoop_publishable_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.zoop_publishable_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-zoop"
                      v-model="gateways.zoop.zoop_publishable_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.zoop_seller_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.zoop_seller_id')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-zoop"
                      v-model="gateways.zoop.zoop_seller_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Zoop-->

          <!--Configurações do Bancard-->
          <div
            class="panel panel-default bancard"
            v-if="gateways.default_payment == 'bancard'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.bancard_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.bancard_public_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.bancard_public_key')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-bancard"
                      v-model="gateways.bancard.bancard_public_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.bancard_private_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.bancard_private_key')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-bancard"
                      v-model="gateways.bancard.bancard_private_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Bancard-->

          <!--Configurações do Transbank-->
          <div
            class="panel panel-default transbank"
            v-if="gateways.default_payment == 'transbank'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.transbank_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.transbank_private_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.transbank_private_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-tranbank"
                      v-model="gateways.transbank.transbank_private_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.transbank_commerce_code") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.transbank_commerce_code')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-tranbank"
                      v-model="gateways.transbank.transbank_commerce_code"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.transbank_public_cert") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.transbank_public_cert')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-tranbank"
                      v-model="gateways.transbank.transbank_public_cert"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Transbank-->

          <!--Configurações do Pagarapido-->
          <div
            class="panel panel-default pagarapido"
            v-if="gateways.default_payment == 'pagarapido'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.pagarapido_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarapido_login") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pagarapido_login')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarapido"
                      v-model="gateways.pagarapido.pagarapido_login"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarapido_password") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pagarapido_password')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="password"
                      class="form-control input-pagarapido"
                      v-model="gateways.pagarapido.pagarapido_password"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarapido_gateway_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.pagarapido_gateway_key')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarapido"
                      v-model="gateways.pagarapido.pagarapido_gateway_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.operation_mode") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.operation_mode')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <select
                      v-model="gateways.pagarapido.pagarapido_production"
                      class="select form-control"
                      required
                    >
                      <option value="0"> {{ trans("setting.Sandbox") }} </option>
                      <option value="1">{{ trans("setting.production") }}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Pagarapido-->

          <!--Configurações da Juno-->
          <div
            class="panel panel-default juno"
            v-if="gateways.default_payment == 'juno'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.juno_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.juno_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.juno_client_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="gateways.juno.juno_client_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.juno_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.juno_secret')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="gateways.juno.juno_secret"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.juno_resource_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.juno_resource_token')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="gateways.juno.juno_resource_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.juno_public_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.juno_public_token')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="gateways.juno.juno_public_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.operation_mode") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.operation_mode')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <select
                      v-model="gateways.juno.juno_sandbox"
                      class="select form-control"
                      required
                    >
                      <option value="0">{{ trans("setting.production") }}</option>
                      <option value="1"> {{ trans("setting.Sandbox") }} </option>
                    </select>
                  </div>
                </div>
                <!-- this will reset juno auth token and expiration date if save config. It's importante, because when change gateway sandbox to production, its need reset-->
                <div class="col-lg-6">
                  {{this.gateways.juno.juno_auth_token = ""}}
                  {{this.gateways.juno.juno_auth_token_expiration_date = ""}}
                </div>
              </div>
              <div style="align-items:center">
                <p> {{ trans("setting.juno_postback_msg") + " "}}</p>
                <p style="color: blue"> {{ getOriginUrl() + '/libs/finance/postback/juno' }}</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>


		<!-- Carto -->
    <div v-if="payment_methods.payment_carto" class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans("setting.carto") }}</h4>
        </div>
        <div class="card-block">
          <!--Configurações de boleto do gerencianet-->
          <div class="panel panel-default gerencianet">
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.carto_keys") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.carto_login") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.carto_login')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="carto.carto_login"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.carto_password") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.carto_password')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="carto.carto_password"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
		<!-- Fim Carto -->

		<!-- Bancryp -->
    <div v-if="payment_methods.payment_crypt" class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans("setting.crypt_coin") }}</h4>
        </div>
        <div class="card-block">
          <!--Configurações de boleto do gerencianet-->
          <div class="panel panel-default gerencianet">
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.bancryp_keys") }}</h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.bancryp_api_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.bancryp_api_key')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="bancryp.bancryp_api_key"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.bancryp_secret_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.bancryp_secret_key')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="bancryp.bancryp_secret_key"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
		<!-- Fim Bancryp -->

    <!-- prepaid -->
    <div v-if="payment_methods.payment_prepaid" class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">
            {{ trans("setting.payment_prepaid") }}
          </h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.prepaid_min_billet_value") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="
                      trans('settingTableSeeder.prepaid_min_billet_value')
                    "
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>
                <input
                  type="number"
                  class="form-control input-braspag"
                  v-model="prepaid.prepaid_min_billet_value"
                />
                <div class="help-block with-errors"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.prepaid_tax_billet") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('settingTableSeeder.prepaid_tax_billet')"
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>
                <input
                  type="number"
                  class="form-control input-braspag"
                  v-model="prepaid.prepaid_tax_billet"
                />
                <div class="help-block with-errors"></div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-4">
              <div class="panel-heading">
                <h3 class="panel-title">
                  {{ trans("setting.prepaid_billet") }}
                </h3>
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_billet_user"
                    v-model="prepaid.prepaid_billet_user"
                  />
                  <label class="form-check-label" for="prepaid_billet_user">{{
                    trans("setting.user")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_billet_provider"
                    v-model="prepaid.prepaid_billet_provider"
                  />
                  <label
                    class="form-check-label"
                    for="prepaid_billet_provider"
                    >{{ trans("setting.provider") }}</label
                  >
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_billet_corp"
                    v-model="prepaid.prepaid_billet_corp"
                  />
                  <label class="form-check-label" for="prepaid_billet_corp">{{
                    trans("setting.corp")
                  }}</label>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="panel-heading">
                <h3 class="panel-title">
                  {{ trans("setting.prepaid_card") }}
                </h3>
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_card_user"
                    v-model="prepaid.prepaid_card_user"
                  />
                  <label class="form-check-label" for="prepaid_card_user">{{
                    trans("setting.user")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_card_provider"
                    v-model="prepaid.prepaid_card_provider"
                  />
                  <label class="form-check-label" for="prepaid_card_provider">{{
                    trans("setting.provider")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_card_corp"
                    v-model="prepaid.prepaid_card_corp"
                  />
                  <label class="form-check-label" for="prepaid_card_corp">{{
                    trans("setting.corp")
                  }}</label>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="panel-heading">
                <h3 class="panel-title">
                  {{ trans("setting.prepaid_pix") }}
                </h3>
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_pix_user"
                    v-model="prepaid.prepaid_pix_user"
                  />
                  <label class="form-check-label" for="prepaid_pix_user">{{
                    trans("setting.user")
                  }}</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_pix_provider"
                    v-model="prepaid.prepaid_pix_provider"
                  />
                  <label
                    class="form-check-label"
                    for="prepaid_pix_provider"
                    >{{ trans("setting.provider") }}</label
                  >
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input checkbox-style"
                    type="checkbox"
                    id="prepaid_pix_corp"
                    v-model="prepaid.prepaid_pix_corp"
                  />
                  <label class="form-check-label" for="prepaid_pix_corp">{{
                    trans("setting.corp")
                  }}</label>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-12">
              <span>{{ trans("setting.obs_billet_gateway") }}</span>
            </div>
            <div class="col-lg-12">
              <span>{{ trans("setting.obs_billet_gateway2") }}
                 <a
                    href=""
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('setting.obs_billet_gateway2_msg')"
                    ><span class="mdi mdi-comment-question-outline"></span
                  ></a>
              </span>
            </div>
            <div class="col-lg-12">
              <span>{{ trans("setting.obs_pix_prepaid") }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / prepaid -->

    <!-- Boleto Gerencianet -->
    <div v-if="payment_methods.payment_billing" class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">
            {{ trans("setting.boleto_gateway") }}
          </h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.default_pay_gate_boleto") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('setting.boleto_gateway')"
                    ><span class="mdi mdi-comment-question-outline"></span
                  ></a>
                  <span class="required-field">*</span>
                </label>
                <select
                  v-model="gateways.default_payment_boleto"
                  name="default_payment_boleto"
                  class="select form-control"
                >
                  <option value="gerencianet">Gerencianet</option>
                  <option value="bancointer">Banco Inter</option>
                </select>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.operation_mode") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('setting.gerencianet_sandbox')"
                    ><span class="mdi mdi-comment-question-outline"></span
                  ></a>
                  <span class="required-field">*</span>
                </label>
                <select
                  v-model="gateways.gerencianet.gerencianet_sandbox"
                  class="select form-control"
                  required
                >
                  <option value="true"> {{ trans("setting.Sandbox") }} </option>
                  <option value="false">
                    {{ trans("setting.production") }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          <!--Configurações de boleto do gerencianet-->
          <div class="panel panel-default gerencianet" v-if="gateways.default_payment_boleto == 'gerencianet'">
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.gerencianet_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.gerencianet_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.gerencianet_client_id')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="gateways.gerencianet.gerencianet_client_id"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.gerencianet_client_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.gerencianet_client_secret')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="gateways.gerencianet.gerencianet_client_secret"
                      type="text"
                      class="form-control input-gerencianet"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações de boleto do gerencianet-->

          <!-- Configurações de boleto do banco Inter-->
          <div class="panel panel-default gerencianet" v-if="gateways.default_payment_boleto == 'bancointer'">
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.banco_inter_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.banco_inter_account") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.banco_inter_account')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="gateways.bancointer.banco_inter_account"
                      type="text"
                      class="form-control"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.cnpj_for_banco_inter") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.cnpj_for_banco_inter')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      v-model="gateways.bancointer.cnpj_for_banco_inter"
                      type="text"
                      class="form-control"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.banco_inter_crt") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.banco_inter_crt')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">{{certificates.crt ? trans('setting.uploaded') : "*"}}</span>
                    </label>
                    <input
                      type="file"
                      class="form-control"
                      accept=".crt"
                      @change="processFile($event,'crt')"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.banco_inter_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.banco_inter_key')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">{{certificates.key ? trans('setting.uploaded') : "*"}}</span>
                    </label>
                    <input
                      type="file"
                      class="form-control"
                      accept=".key"
                      @change="processFile($event,'key')"
                      :data-error="trans('setting.field')"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações de boleto do banco Inter-->
        </div>
      </div>
    </div>
    <!-- / Boleto -->


    <!-- gateway pix -->
    <!-- As configuracoes do pix eh ativada quando existe a forma de pagamento pix OU quando o pix prepago esta ativado-->
    <div v-if="payment_methods.payment_gateway_pix || prepaid.prepaid_pix_corp || prepaid.prepaid_pix_provider || prepaid.prepaid_pix_user" class="card-margin-top">
      <div class="card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">
            {{ trans("setting.payment_gateway_pix") }}
          </h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.default_pay_gateway_pix") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="trans('setting.default_pay_gateway_pix')"
                    ><span class="mdi mdi-comment-question-outline"></span
                  ></a>
                  <span class="required-field">*</span>
                </label>
                <select
                  v-model="pix_gateways.default_payment_pix"
                  name="default_payment_pix"
                  class="select form-control"
                >
                  <option :selected="!!pix_gateways.default_payment_pix" disabled>
                    {{ trans("setting.select") }}
                  </option>
                  <option 
                    v-for="gatewayPix in pix_gateways.list_gateways" 
                    :key="gatewayPix.value"
                    :value="gatewayPix.value">
                    {{trans(gatewayPix.name)}}
                  </option>
                </select>
              </div>
            </div>
            <div v-if="pix_gateways.default_payment_pix == 'juno'" class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.pix_key") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="
                      trans('setting.pix_key')
                    "
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pix_gateways.pix_key"
                />
                <div class="help-block with-errors"></div>
              </div>
            </div>
            <div v-if="pix_gateways.default_payment_pix == 'ipag'" class="col-lg-6">
              <div class="form-group">
                <label for="usr">
                  {{ trans("setting.ipag_version") }}
                  <a
                    href="#"
                    class="question-field"
                    data-toggle="tooltip"
                    :title="
                      trans('setting.ipag_version')
                    "
                  >
                    <span class="mdi mdi-comment-question-outline"></span>
                  </a>
                  <span class="required-field">*</span>
                </label>
                    <select
                      v-model="pix_gateways.ipag.pix_ipag_version"
                      class="select form-control"
                      required
                    >
                      <option value="1">{{ trans("setting.ipag_version_1") }}</option>
                      <option value="2"> {{ trans("setting.ipag_version_2") }} </option>
                    </select>
                <div class="help-block with-errors"></div>
              </div>
            </div>
          </div>

          <!--Configurações de pix da juno-->
          <div
            class="panel panel-default juno"
            v-if="pix_gateways.default_payment_pix == 'juno'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.juno_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pix_juno_client_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pix_juno_client_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="pix_gateways.juno.pix_juno_client_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pix_juno_secret") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pix_juno_secret')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="pix_gateways.juno.pix_juno_secret"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pix_juno_resource_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pix_juno_resource_token')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="pix_gateways.juno.pix_juno_resource_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pix_juno_public_token") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.pix_juno_public_token')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-juno"
                      v-model="pix_gateways.juno.pix_juno_public_token"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.operation_mode") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('setting.operation_mode')"
                        ><span class="mdi mdi-comment-question-outline"></span
                      ></a>
                      <span class="required-field">*</span>
                    </label>
                    <select
                      v-model="pix_gateways.juno.pix_juno_sandbox"
                      class="select form-control"
                      required
                    >
                      <option value="0">{{ trans("setting.production") }}</option>
                      <option value="1"> {{ trans("setting.Sandbox") }} </option>
                    </select>
                  </div>
                </div>
                <!-- this will reset juno auth token and expiration date if save config. It's importante, because when change gateway sandbox to production, its need reset-->
                <div class="col-lg-6">
                  {{this.pix_gateways.juno.pix_juno_auth_token = ""}}
                  {{this.pix_gateways.juno.pix_juno_auth_token_expiration_date = ""}}
                </div>
              </div>
            </div>
          </div>
          <!--Configurações de pix da juno-->
          <div
            class="panel panel-default juno"
            v-if="pix_gateways.default_payment_pix == 'ipag'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.ipag_settings") }}
              </h3>
              <span class="enviroment" v-if="EnviromentActive">
                {{ trans("setting.enviromentActive") }}: <b>{{ trans(`setting.${EnviromentActive}`) || '' }}</b>
              </span>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_api_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.ipag_api_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-ipag"
                      v-model="pix_gateways.ipag.pix_ipag_api_id"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_api_key") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.ipag_api_key')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-ipag"
                      v-model="pix_gateways.ipag.pix_ipag_api_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.ipag_expiration_time") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.ipag_expiration_time')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="number"
                      class="form-control input-ipag"
                      v-model="pix_gateways.ipag.pix_ipag_expiration_time"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>

              <!-- Webhooks Ipag -->
              <div v-if="isWebhooks" class="row">
                <div class="col-lg-12">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.list_webhooks") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('setting.list_webhooks')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <div class="webhooks-ipag">
                      <ol v-if="listWebhooks.length > 0" >
                        <li v-for="webhook in listWebhooks" :key="webhook.id">
                          <p v-if="webhook.url">
                            {{ webhook.url }} - 
                            <i v-if="webhook.is_active" class="fa fa-check text-success" aria-hidden="true"></i>
                            <i v-else class="fa fa-times text-danger" aria-hidden="true"></i>
                          </p>
                          <p v-if="webhook.http_method">{{ trans("setting.method") }} <span class="badge badge-info">{{ webhook.http_method }}</span></p>
                          <p v-if="webhook.actions">{{ trans("setting.actions") }} </p>
                            <div v-if="webhook.actions" class="actions-container">
                              <p v-for="action in webhook.actions" class="actions-info">
                                  <span class="badge badge-primary"> {{ trans(`setting.${action}`) }}</span>
                              </p>
                            </div>
                        </li>
                      </ol>
                      <h5 v-else class="ml-3">{{ trans("setting.webhook_notfound") }}</h5>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else>
                <ul>
                  <li>
                    <p class="Error-webhook">{{messageWebhook}}</p>
                  </li>  
                </ul>
              </div>
            </div>
          </div>
          <!-- / Configurações de boleto do gerencianet-->
        </div>
      </div>
    </div>
    <!-- / gateway pix -->

    <!--Save-->
    <div
      style="background-color: white; padding-bottom: 2px; padding-right: 10px;"
      class="panel panel-default"
    >
      <div class="form-group text-right">
        <button v-on:click="saveSettings()" class="btn btn-success">
          <span
            class="glyphicon glyphicon-floppy-disk"
            aria-hidden="true"
          ></span>
          {{ trans("setting.save_data") }}
        </button>
      </div>
    </div>
  </div>
</template>

<style>
.checkbox-style {
  width: 17px !important;
  height: 17px !important;
  margin-left: 0px !important;
  margin-top: 2px !important;
}

.card-margin-top {
  margin-top: 30px !important;
}

.enviroment {
  font-size: 11px;
}
.text-white {
  color: '#FFFFFF' !important;
}

.actions-container {
  display: flex;
  flex-direction: row;
  max-width: 590px;
  min-height: 115px;
  flex-wrap: wrap;
}

.actions-info {
  margin-right: 2px;
  margin-block-end: 1px;
  margin-bottom: 1px;
  line-height: 1px;
  height: fit-content;
  font-size: 18px;
}
</style>
