<?php $layout = '.master'; ?>
       
@extends('layout'.$layout)

@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('paymentgateway::setting.conf')}}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('paymentgateway::setting.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('paymentgateway::setting.gateways') }}</li>
		</ol>
	</div>
</div>	
@stop


@section('content')
	<div id="payment-gateways" class="col-sm-12">
		
		<settingsgateways 
			payment-methods="{{ json_encode($payment_methods)}}"
			gateways="{{ json_encode($gateways)}}"
			pix-gateways="{{ json_encode($pix_gateways)}}"
			carto="{{ json_encode($carto)}}"
			bancryp="{{ json_encode($bancryp)}}"
			prepaid="{{ json_encode($prepaid)}}"
			settings="{{ json_encode($settings)}}"
			certificates="{{ json_encode($certificates)}}"
			nomenclatures="{{ json_encode($nomenclatures) }}"
			enviroment-active={{$enviroment}}
		>
		</settingsgateways>
		
	</div>
@stop

@section('javascripts')
<script src="/libs/gateways/lang.trans/setting"> </script> 
<script src="{{ asset('vendor/codificar/payment-gateways/js/gateways.vue.js') }}"> </script> 
@stop
