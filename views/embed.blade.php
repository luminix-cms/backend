<div id="luminix-embed" style="display: none">
    @foreach(app(\Luminix\Backend\Services\Js::class)->all() as $key => $value)
        @if (is_string($value) || is_numeric($value))
            <div id="luminix-data-{{$key}}" data-value="{{$value}}"></div>
        @else
            <div id="luminix-data-{{$key}}" data-json="1" data-value="{{json_encode($value)}}"></div>
        @endif
    @endforeach
    @foreach(app(\Luminix\Backend\Services\Js::class)->catchables() as $error)
        @error($error)
            <div id="luminix-data-error::{{$error}}" data-value="{{$message}}"></div>
        @enderror
    @endforeach
</div>
