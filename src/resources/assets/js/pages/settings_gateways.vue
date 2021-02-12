<script>
import axios from "axios";
import moment from "moment";
export default {
  props: ["PaymentMethods", "Gateways", "Carto", "Bancryp", "Prepaid"],
  data() {
    return {
      gateways: {},
      payment_methods: {},
			carto: {}
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
                payment_methods: this.payment_methods,
                gateways: this.gateways,
								carto: this.carto,
								bancryp: this.bancryp,
								prepaid: this.prepaid
              })
              .then((response) => {
                if (response.data.success) {
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
    this.PaymentMethods ? (this.payment_methods = JSON.parse(this.PaymentMethods)) : null;
    this.Gateways ? (this.gateways = JSON.parse(this.Gateways)) : null;
		this.Carto ? (this.carto = JSON.parse(this.Carto)) : null;
		this.Bancryp ? (this.bancryp = JSON.parse(this.Bancryp)) : null;
		this.Prepaid ? (this.prepaid = JSON.parse(this.Prepaid)) : null;
  },
};
</script>
<template>
  <div>
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
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / formas de pagamento -->

    <!--Payment Gateway-->
    <div
      v-if="payment_methods.payment_card || payment_methods.payment_debitCard"
      class="card-margin-top"
    >
      <div class="card-outline-info">
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
          </div>

          <!--Configurações do Pagar.Me-->
          <div
            class="panel panel-default pagarme"
            v-if="gateways.default_payment == 'pagarme'"
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
                      v-model="gateways.pagarme.pagarme_encryption_key"
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
                      v-model="gateways.pagarme.pagarme_api_key"
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
            <div class="col-lg-6">
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
            <div class="col-lg-6">
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
          </div>
          <div class="row">
            <div class="col-lg-12">
              <span>{{ trans("setting.obs_billet_gateway") }}</span>
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
          <div class="panel panel-default gerencianet">
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
          <!-- / Configurações de boleto do Gerencianet-->
        </div>
      </div>
    </div>
    <!-- / Boleto -->

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
</style>
