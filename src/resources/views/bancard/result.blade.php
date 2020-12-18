<!DOCTYPE html> 
<html lang="en"> 
    <head> 
        <meta charset="UTF-8"> 
        <title>Tarjeta | Bancard</title> 
    </head> 

    

    <body> 
        @if($status == 'add_new_card_fail')
        <h1 style="text-align: center">Error: {{$description}}</h1>
        @elseif($status == 'add_new_card_success')
        <h1 style="text-align: center">Tarjeta registrada con éxito en Bancard! Vuelva a consultar para actualizar sus tarjetas.</h1>
        @endif
    </body> 

</html> 