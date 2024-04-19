<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cartão</title>
    @if(App::environment('production'))
        <script src="https://vpos.infonet.com.py/checkout/javascript/dist/bancard-checkout-3.0.0.js"></script>
    @else
        <script src="https://vpos.infonet.com.py:8888/checkout/javascript/dist/bancard-checkout-3.0.0.js"></script>
    @endif
</head>
<body>
    {{-- <h1 style="text-align: center">Adicionar Novo Cartão</h1> --}}
    <div style="height: 100vh; display:flex;">
        <div id="iframe-container" style="height: 80vh; width: full; margin: auto; margin-top:20vh"></div>
    </div>

    <script type="application/javascript">
        const styles = {
            "form-background-color": "#001b60",
            "button-background-color": "#4faed1",
            "button-text-color": "#fcfcfc",
            "button-border-color": "#dddddd",
            "input-background-color": "#fcfcfc",
            "input-text-color": "#111111",
            "input-placeholder-color": "#111111"
        };

        window.onload = function () {
            Bancard.Cards.createForm('iframe-container', '{{ $process_id }}', styles);
        };
    </script>
</body>
</html>
