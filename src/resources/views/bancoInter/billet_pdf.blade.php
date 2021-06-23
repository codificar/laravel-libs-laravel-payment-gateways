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
<div class='container'>
    <div class='col-sm-8'>
        {{ $pdfName }}

        <embed
            src="{{ action('BancoInterController@getbilletPdf', ['pdfName'=> $pdfName]) }}"
            style="width:600px; height:800px;"
            frameborder="0"
        >	
    </div>
</div>
@endsection

@section('javascripts')
<script src="/libs/gateways/lang.trans/setting"> </script> 
<script src="{{ elixir('vendor/codificar/laravel-payment-gateways/gateways.vue.js') }}"> </script> 
@stop
