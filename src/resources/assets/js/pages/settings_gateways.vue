<script>
import axios from "axios";
import moment from "moment";
export default {
  props: [
    "PaymentGateways", 
    "HasInvoiceBillet",
    "InvoiceBillet", 
    "Enums", 
    "Settings"
  ],
  data() {
    return {
      payment_gateways: {},
      invoice_billet: {},
      enums: {},
      settings: {},
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
          //Submit form if its valid and email doesnt exists
          new Promise((resolve, reject) => {
            axios
              .post("/libs/settings/save/gateways", {
                settings: this.settings,
              })
              .then((response) => {
                if (response.data.success) {
                  this.$swal({
                    title: this.trans("setting.success_set_gateway"),
                    type: "success",
                  }).then((result) => {});
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
              })
              .catch((error) => {
                console.log(error);
                reject(error);
                return false;
              });
          });
        }
      });
    },

    saveSettingsBillet() {
      this.$swal({
        title: this.trans("setting.edit_confirm"),
        type: "warning",
        showCancelButton: true,
        confirmButtonText: this.trans("setting.yes"),
        cancelButtonText: this.trans("setting.no"),
      }).then((result) => {
        if (result.value) {
          //Submit form if its valid and email doesnt exists
          console.log("inv:", this.invoice_billet);
          new Promise((resolve, reject) => {
            axios
              .post("/libs/settings/save/billet_invoice", {
                invoice_billet: this.invoice_billet,
              })
              .then((response) => {
                if (response.data.success) {
                  this.$swal({
                    title: this.trans("setting.success_set_gateway"),
                    type: "success",
                  }).then((result) => {});
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
              })
              .catch((error) => {
                console.log(error);
                reject(error);
                return false;
              });
          });
        }
      });
    },


  },
  created() {
    this.PaymentGateways ? (this.payment_gateways = JSON.parse(this.PaymentGateways)) : null;
    this.InvoiceBillet ? (this.invoice_billet = JSON.parse(this.InvoiceBillet)) : null;
    this.Settings ? (this.settings = JSON.parse(this.Settings)) : null;
    this.Enums ? (this.enums = JSON.parse(this.Enums)) : null;
  },
};
</script>
<template>
  <div>
    <!-- Row -->
    <div class="tab-content">
      <div class="card-outline-info">
        <!--Payment Gateway-->
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans("setting.pay_gateway") }}</h4>
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
                  v-model="settings.default_payment"
                  name="default_payment"
                  class="select form-control"
                >
                  <option
                    v-for="method in payment_gateways"
                    v-bind:value="method.value"
                    v-bind:key="method.value"
                  >
                    {{ trans(method.name) }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!--Configurações do Pagar.Me-->
          <div
            class="panel panel-default pagarme"
            v-if="settings.default_payment == 'pagarme'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">
                {{ trans("setting.pagarme_settings") }}
              </h3>
              <hr />
            </div>
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pay_encryption_key_me") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.pay_encryption_key_me')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="settings.pagarme.pagarme_encryption_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="usr">
                      {{ trans("setting.pagarme_recipient_id") }}
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="
                          trans('settingTableSeeder.pagarme_recipient_id')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="settings.pagarme.pagarme_recipient_id"
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
                      <a
                        href="#"
                        class="question-field"
                        data-toggle="tooltip"
                        :title="trans('settingTableSeeder.pagarme_api_key')"
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-pagarme"
                      v-model="settings.pagarme.pagarme_api_key"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Pagar.Me-->

          <!--Configurações do Braspag-->
          <div
            class="panel panel-default braspag"
            v-if="settings.default_payment == 'braspag'"
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
                      v-model="settings.braspag.braspag_merchant_id"
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
                      v-model="settings.braspag.braspag_merchant_key"
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
                      v-model="settings.braspag.braspag_token"
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
            v-if="settings.default_payment == 'braspag_cielo_ecommerce'"
          >
            <div class="panel-heading">
              <h3 class="panel-title">{{ trans("setting.braspag_cielo_ecommerce") }}</h3>
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
                      v-model="settings.braspag_cielo_ecommerce.braspag_client_id"
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
                        :title="
                          trans('setting.braspag_client_secret')
                        "
                      >
                        <span class="mdi mdi-comment-question-outline"></span>
                      </a>
                      <span class="required-field">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control input-getnet"
                      v-model="settings.braspag_cielo_ecommerce.braspag_client_secret"
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
            v-if="settings.default_payment == 'getnet'"
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
                      v-model="settings.getnet.getnet_client_id"
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
                      v-model="settings.getnet.getnet_client_secret"
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
                      v-model="settings.getnet.getnet_seller_id"
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
            v-if="settings.default_payment == 'directpay'"
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
                      v-model="settings.directpay.directpay_encrypt_key"
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
                      v-model="settings.directpay.directpay_encrypt_value"
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
                      v-model="settings.directpay.directpay_requester_id"
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
                      v-model="settings.directpay.directpay_requester_password"
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
                      v-model="settings.directpay.directpay_requester_token"
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
                      v-model="settings.directpay.directpay_unique_trx_id"
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
            v-if="settings.default_payment == 'cielo'"
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
                      v-model="settings.cielo.cielo_merchant_id"
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
                      v-model="settings.cielo.cielo_merchant_key"
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
            v-if="settings.default_payment == 'stripe'"
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
                      v-model="settings.stripe.stripe_secret_key"
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
                      v-model="settings.stripe.stripe_publishable_key"
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
                      v-model="settings.stripe.stripe_connect"
                      name="stripe_connect"
                      class="select form-control"
                    >
                      <option
                        v-for="method in enums.stripe_connect"
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
                      v-model="settings.stripe.stripe_total_split_refund"
                      name="stripe_total_split_refund"
                      class="select form-control"
                    >
                      <option
                        v-for="method in enums.stripe_total_split_refund"
                        v-bind:value="method.value"
                        v-bind:key="method.value"
                      >
                        {{ trans(method.name) }}
                      </option>
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
            v-if="settings.default_payment == 'zoop'"
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
                      v-model="settings.zoop.zoop_marketplace_id"
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
                      v-model="settings.zoop.zoop_publishable_key"
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
                      v-model="settings.zoop.zoop_seller_id"
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
            v-if="settings.default_payment == 'bancard'"
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
                      v-model="settings.bancard.bancard_public_key"
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
                      v-model="settings.bancard.bancard_private_key"
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
            v-if="settings.default_payment == 'transbank'"
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
                      v-model="settings.transbank.transbank_private_key"
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
                      v-model="settings.transbank.transbank_commerce_code"
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
                      v-model="settings.transbank.transbank_public_cert"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Configurações do Transbank-->

          <!--Save-->
          <div class="panel panel-default">
            <div class="form-group text-right">
              <button v-on:click="saveSettings()" class="btn btn-success">
                <span
                  class="glyphicon glyphicon-floppy-disk"
                  aria-hidden="true"
                ></span>
                {{ trans("setting.save") }}
              </button>
            </div>
          </div>
        </div>
      </div>


      <div v-if="HasInvoiceBillet" class="card-outline-info"><!-- Boleto -->
        <div class="card-header">
            <h4 class="m-b-0 text-white">{{ trans('setting.boleto_gateway') }}</h4>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="usr">
                            {{trans('setting.default_pay_gate_boleto')}}
                            <a href="#" class="question-field" data-toggle="tooltip" :title="trans('setting.boleto_gateway')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field">*</span>
                        </label>
                        <select v-model="invoice_billet.default_payment_boleto" class="select form-control">
														<option value="gerencianet">Gerencianet</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="usr">
                            {{trans('setting.operation_mode')}}
                            <a href="#" class="question-field" data-toggle="tooltip" :title="trans('setting.gerencianet_sandbox')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field">*</span>
                        </label>
                        <select v-model="invoice_billet.gerencianet_sandbox" class="form-control" required>
                            <option value="true"> {{trans('setting.Sandbox')}} </option>
                            <option value="false"> {{trans('setting.production')}} </option>
                        </select>
                    </div>
                </div>
            </div>
            <!--Configurações de boleto do gerencianet-->
            <div class="panel panel-default gerencianet">
                <div class="panel-heading">
                    <h3 class="panel-title">{{trans('setting.gerencianet_settings')}}</h3>
                    <hr>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="usr">
                                    {{trans('setting.gerencianet_client_id')}}
                                    <a href="#" class="question-field" data-toggle="tooltip" :title="trans('setting.gerencianet_client_id')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field">*</span>
                                </label>
                                <input v-model="invoice_billet.gerencianet_client_id" type="text" class="form-control input-gerencianet"  :data-error="trans('setting.field')">
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="usr">
                                    {{trans('setting.gerencianet_client_secret')}} 
                                    <a href="#" class="question-field" data-toggle="tooltip" :title="trans('setting.gerencianet_client_secret')"><span class="mdi mdi-comment-question-outline"></span></a> <span class="required-field">*</span>
                                </label>
                                <input v-model="invoice_billet.gerencianet_client_secret" type="text" class="form-control input-gerencianet" :data-error="trans('setting.field')">
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- / Configurações de boleto do Gerencianet-->

             <!--Save-->
            <div class="panel panel-default">
              <div class="form-group text-right">
                <button v-on:click="saveSettingsBillet()" class="btn btn-success">
                  <span
                    class="glyphicon glyphicon-floppy-disk"
                    aria-hidden="true"
                  ></span>
                  {{ trans("setting.save") }}
                </button>
              </div>
            </div>
        </div>
      </div> <!-- / Boleto -->

    </div>
  </div>
</template>