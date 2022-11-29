@extends('layout.clean')

@section('content')
    <form action="{{ $formAction }}" method="post" class="form-inline" role="form" id="form-transbank">
        <input type="hidden" name="token_ws" value="{{ $tokenWs }}">
    </form>
@stop

@section('javascripts')
    <script type="text/javascript">
        document.getElementById('form-transbank').submit();

    </script>
@stop
