<script>
import axios from "axios";
import moment from "moment";
export default {
  props: [
    "EditPermission",
    "DeletePermission",
    "StatusBilling",
    "Institution",
    "AdminsList",
    "Settings",
    "csrfToken"
  ],
  data() {
    return {
      settings: {},
      adminlists: {},
    };
  },
  methods: {
    setBilling() {
      this.$swal({
        title: this.trans("billing.edit_confirm"),
        type: "warning",
        showCancelButton: true,
        confirmButtonText: this.trans("billing.yes"),
        cancelButtonText: this.trans("billing.no")
      }).then(result => {
        if (result.value) {
          //Submit form if its valid and email doesnt exists
          new Promise((resolve, reject) => {
            axios
              .post("/admin/billing/set", {
                settings: this.settings
              })
              .then(response => {
                console.log(response);
                if (response.data.success) {
                  this.$swal({
                    title: this.trans("billing.success_set_billing"),
                    type: "success"
                  }).then(result => {
                    $("#modalSetBilling").modal("hide");
                  });
                } else {
                  this.$swal({
                    title: this.trans("billing.failed_set_billing"),
                    html:
                      '<label class="alert alert-danger alert-dismissable text-left">' +
                      response.data.errors +
                      "</label>",
                    type: "error"
                  }).then(result => {});
                }
              })
              .catch(error => {
                console.log(error);
                reject(error);
                return false;
              });
          });
        }
      });
    }
  },
  created() {
     this.Settings ? (this.settings = JSON.parse(this.Settings)) : null;

	this.AdminsList ? (this.adminlists = JSON.parse(this.AdminsList)) : null;
  console.log('lista:', this.adminlists);
  }
};
</script>
<template>
  <div>
    <!-- Row -->
    <div class="tab-content">
      <div class="col-lg-12">
        <div class="card card-outline-info">
          <div class="card-header">
            <h4 class="m-b-0 text-white">{{trans('billing.billing_settings')}}</h4>
          </div>
          <div class="card-block">
            <div class="row">
              <form data-toggle="validator" class="col-lg-12" v-on:submit.prevent="setBilling()">
                <input type="hidden" name="_token" :value="csrfToken" />
                <div class="col-md-12 col-sm-12">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.how_billing_works') }}</label>
                    <input
                      type="text"
                      class="form-control"
                      name="how_billing_works"
                      id="how_billing_works"
                      required
                      v-model="settings.how_billing_works"
                    />
                  </div>
                </div>

                <div class="col-md-12 col-sm-12">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.minimum_bill_generation_value') }}</label>
                    <input
                      type="text"
                      class="form-control"
                      name="minimum_bill_generation_value"
                      id="minimum_bill_generation_value"
                      required
                      v-model="settings.minimum_bill_generation_value"
                    />
                  </div>
                </div>

                <div class="col-md-12 col-sm-12">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.billing_expiration_day') }}</label>
                    <input
                      type="number"
                      min="0"
                      max="31"
                      class="form-control"
                      name="billing_expiration_day"
                      id="billing_expiration_day"
                      required
                      v-model="settings.billing_expiration_day"
                    />
                  </div>
                </div>

                <div class="col-md-12 col-sm-12">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.allow_debit_note_on_billing') }}</label>
                    <select
                      v-model="settings.allow_debit_note_on_billing"
                      name="allow_debit_note_on_billing"
                      class="select form-control"
                    >
                      <option
                        v-for="method in [{'value': '1', 'name': 'Sim'},{'value': '0', 'name': 'Não'}]"
                        v-bind:value="method.value"
                        v-bind:key="method.value"
                      >{{ method.name }}</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12" v-if="settings.allow_debit_note_on_billing == 1">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.debit_note_percentage') }}</label>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      class="form-control"
                      name="debit_note_percentage"
                      id="debit_note_percentage"
                      required
                      v-model="settings.debit_note_percentage"
                    />
                  </div>
                </div>

                <div class="col-md-12 col-sm-12">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.is_automatic_billing') }}</label>
                    <select
                      v-model="settings.is_automatic_billing"
                      name="is_automatic_billing"
                      class="select form-control"
                    >
                      <option
                        v-for="method in [{'value': '1', 'name': 'Sim'},{'value': '0', 'name': 'Não'}]"
                        v-bind:value="method.value"
                        v-bind:key="method.value"
                      >{{ method.name }}</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12" v-if="settings.is_automatic_billing == 1">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.billing_after_days') }}</label>
                    <input
                      type="number"
                      min="0"
                      max="31"
                      class="form-control"
                      name="billing_after_days"
                      id="billing_after_days"
                      required
                      v-model="settings.billing_after_days"
                    />
                  </div>
                </div>

                <div class="col-md-12 col-sm-12" v-if="settings.is_automatic_billing == 1">
                  <div class="form-group">
                    <label class="control-label">{{trans('setting.billing_period') }}</label>
                    <select
                      v-model="settings.billing_period"
                      name="billing_period"
                      class="select form-control"
                    >
                      <option
                        v-for="method in [{'value': 'weekly', 'name': trans('setting.billing_period_weekly')},{'value': 'biweekly', 'name': trans('setting.billing_period_biweekly')},{'value': 'monthly', 'name': trans('setting.billing_period_monthly')}]"
                        v-bind:value="method.value"
                        v-bind:key="method.value"
                      >{{ method.name }}</option>
                    </select>
                  </div>
                </div>

                <!-- Action -->
                <br />
                <div class="form-group">
                  <button type="submmit" class="btn btn-success pull-right">{{trans('billing.save')}}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>