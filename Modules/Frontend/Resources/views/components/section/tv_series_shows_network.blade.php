@php
    $cardMovieDataMap = [];
    $eagerCount = $eagerCount ?? null;
    $networkChannelData = $networkChannelData ?? [];
    $networkList = collect($moreinfinity ?? []);
    $eagerNetworks = is_null($eagerCount) ? $networkList : $networkList->take(max(0, (int) $eagerCount));
    $lazyNetworks = is_null($eagerCount) ? collect() : $networkList->slice(max(0, (int) $eagerCount));
@endphp

@foreach ($eagerNetworks as $category)
    @php
        $networkId = (int) $category->id;
        $channel_data = $networkChannelData[$networkId] ?? [];

        foreach ($channel_data as $item) {
            $mapKey = ($item['type'] ?? 'tvshow') . ':' . $item['id'];
            if (!isset($cardMovieDataMap[$mapKey])) {
                $cardMovieDataMap[$mapKey] = slimCardMovieData($item);
            }
        }
    @endphp

    @include('frontend::components.section.tv_series_network_row', [
        'category' => $category,
        'channel_data' => $channel_data,
        'omit_movie_data' => true,
    ])
@endforeach

@foreach ($lazyNetworks as $category)
    <div class="lazy-home-section" data-lazy-section="network" data-id="{{ $category->id }}"
        aria-busy="true"></div>
@endforeach

@if (!empty($cardMovieDataMap))
    <script>
        window.cardMovieDataMap = Object.assign({}, window.cardMovieDataMap || {}, @json($cardMovieDataMap));
    </script>
@endif
