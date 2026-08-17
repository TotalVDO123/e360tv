@extends('frontend::layouts.master')


@section('title')
    {{ __('frontend.tv_series_shows') }}
@endsection



@section('content')



<?php /* ?>
    <div id="livetvthumbnail-section">
        @include('frontend::components.section.livetvthumbnail', [
            'livetvthumbnail' => $responseData['slider'],
        ])
    </div>
<?php */ ?>



    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">
            <div id="more-infinity-section">
                @include('frontend::components.section.tv_series_shows_network', [
                    'moreinfinity' => $seriesNetworks,
                ])
            </div>
        </div>
    </div>
@endsection
