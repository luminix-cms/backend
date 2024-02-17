<div id="luminix-embed" style="display: none">
    @if (config('luminix.boot.method', 'api') === 'embed')
        <div id="luminix-data-boot" data-json="1" data-value="{{json_encode(
            app(\Luminix\Backend\Services\Manifest::class)->makeBoot()
        )}}"></div>
    @endif
</div>
