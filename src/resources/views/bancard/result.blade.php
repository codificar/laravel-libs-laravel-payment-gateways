<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjeta | Bancard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f9;
            color: #333;
        }
        .message {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 90%;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .icon {
            margin-bottom: 20px;
        }
        
    </style>
</head>

<body>
    @if($status == 'add_new_card_fail')
    <div class="message error">
        <h1>Error: {{$description}}</h1>
    </div>
    @elseif($status == 'add_new_card_success')
    <div class="message success">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M16 8A8 8 0 11-1.49e-07 8a8 8 0 0116 0zM6.854 11.646a.5.5 0 00.707 0l4.5-4.5a.5.5 0 00-.708-.708L7.207 10.293 5.354 8.44a.5.5 0 00-.708.707l1.5 1.5z" clip-rule="evenodd"/>
        </svg>
        <h2>Tarjeta registrada con éxito en Bancard! Vuelva a consultar para actualizar sus tarjetas.</h2>
    </div>
    @endif
</body>
</html>
