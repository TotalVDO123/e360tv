@extends('frontend::layouts.guest')

@section('title')
    {{ __('frontend.embeded_tvshows') }}
@endsection

@section('body_class')
    embed-tv-shows-page
@endsection

<style>
body.embed-tv-shows-page {
    color: #c4c2c2 !important;

    .text-primary {
        color: #00c3f9 !important;
    }
}
</style>

@section('content')
<div id="seasons">
    <div class="list-page section-spacing-bottom pt-4 mt-3">
        <div class="movie-lists">
            <div class="container-fluid">
                @php
                    $episodeItems = $episodes->toArray(request());
                @endphp

                @if (!empty($episodeItems))
                    <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 episode-list">
                        @foreach ($episodeItems as $index => $episode)
                            <div class="col">
                                @include('frontend::components.card.card_episode', [
                                    'data' => $episode,
                                    'index' => $index,
                                    'subtitle_info' => '',
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <img src="{{ asset('img/NoData.png') }}" alt="No Data Found" class="img-fluid">
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
    @include('frontend::components.partials.hover-modal-scripts')
@endsection
