<script>

export default {
  props: [
    "AdminsList",
  ],
  data() {
    return {
      adminlists: {},
    };
  },
  methods: {

	
  },
  created() {
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
            <h4 class="m-b-0 text-white">{{trans('generic.vue_trans_example')}}</h4>
          </div>
          <div class="card-block">
				    <p>Lista de admins (exemplo de integração do vue com laravel e models):</p>
              
              <table class="table table-bordered">
                <tr>
                  <th>admins teste</th>
                </tr>
                <tr v-for="admin in adminlists">
                  <td>{{ admin.username }}</td>
                </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>