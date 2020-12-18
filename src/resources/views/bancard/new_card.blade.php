@extends('layout.clean')

@section('content')

<!--    <body class="preloader"> -->
<!--        <div align="center" style="margin-top: 50px;" >
            <img id="loading_iframe" src="{{URL::to("images/ajax-loader.gif")}}"/>
        </div>-->
<h1 style="text-align: center">Nueva tarjeta en Bancard</h1> 
<div style="height: auto; width: auto; margin: auto" id="iframe-container"></div> 
<!--</body>--> 
@stop


@section('javascripts')

@if(App::environment('production'))
    <script src="{{$bancard_url_prod}}/checkout/javascript/dist/bancard-checkout-2.0.0.js"></script> 
@else
    <script src="{{$bancard_url_dev}}/checkout/javascript/dist/bancard-checkout-2.0.0.js"></script> 
@endif

<script type="text/javascript">

    styles = {
        "form-background-color": "#001b60",
        "button-background-color": "#4faed1",
        "button-text-color": "#fcfcfc",
        "button-border-color": "#dddddd",
        "input-background-color": "#fcfcfc",
        "input-text-color": "#111111",
        "input-placeholder-color": "#111111"
    };

    window.onload = function () {//  
        Bancard.Cards.createForm('iframe-container', '{{$process_id}}', styles);
        document.getElementById("loading_iframe").style.display = "none";
    };

</script> 
@stop