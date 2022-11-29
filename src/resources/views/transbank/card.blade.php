{{--  @extends('layout.clean')  --}}

{{--  @section('content')  --}}
    <form action="{{ $action }}" method="post" class="form-inline" role="form" id="form-transbank">
        <input type="hidden" name="TBK_TOKEN" value="{{ $token }}">
    </form>
{{--  @stop  --}}

{{--  @section('javascripts')  --}}
    <script type="text/javascript">
        document.getElementById('form-transbank').submit();

    </script>
{{--  @stop  --}}
