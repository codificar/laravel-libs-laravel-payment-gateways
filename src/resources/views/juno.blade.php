<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>Juno</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	</head>
	<body>
		<div class="content">
			<div id="VueJs">
				<junoaddcard
					juno-sandbox="{{ $juno_sandbox }}"
					public-token="{{ $public_token }}"
				>
				</junoaddcard>
			</div>
		</div>
	</body>
	
	<script src="/libs/gateways/lang.trans/setting"> </script> 
	<script src="{{ elixir('vendor/codificar/laravel-payment-gateways/gateways.vue.js') }}"> </script> 

</html>