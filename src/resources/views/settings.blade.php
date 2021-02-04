<?php $layout = '.master'; ?>
       
@extends('layout'.$layout)

@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('settingsTrans::setting.conf')}}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('settingsTrans::setting.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('settingsTrans::setting.gateways') }}</li>
		</ol>
	</div>
</div>	
@stop


@section('content')
	<div id="VueJs">
		
		<settingsgateways 
			payment-methods="{{ json_encode($payment_methods)}}"
			gateways="{{ json_encode($gateways)}}"
			carto="{{ json_encode($carto)}}"
			bancryp="{{ json_encode($bancryp)}}"
			prepaid="{{ json_encode($prepaid)}}"
		>
		</settingsgateways>
		
	</div>
@stop

@section('javascripts')
<script src="/libs/gateways/lang.trans/setting"> </script> 
<script src="{{ elixir('vendor/codificar/laravel-payment-gateways/gateways.vue.js') }}"> </script> 
@stop
