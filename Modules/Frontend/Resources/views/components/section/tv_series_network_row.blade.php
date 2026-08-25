@if (!empty($channel_data) && count($channel_data) > 0)
    @php
        $isSingleItem = count($channel_data) === 1;
        $omitMovieData = $omit_movie_data ?? true;
    @endphp

    <div class="moreinfinity-card">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title mb-0">{{ $category->name }}</h5>
            <a href="{{ url('tv-shows/' . $category->slug) }}"
                class="view-all-button text-decoration-none flex-none">
                <span>View All</span>
                <i class="ph ph-caret-right"></i>
            </a>
        </div>

        <div class="card-style-slider">
            @if ($isSingleItem)
                <div class="d-flex" style="justify-content: flex-start;">
                    <div style="flex: 0 0 auto; max-width: 500px;">
                        @include('frontend::components.card.card_tvshow', [
                            'values' => $channel_data,
                            'omit_movie_data' => $omitMovieData,
                        ])
                    </div>
                </div>
            @else
                <div class="slick-general tv-series-network-slider" data-items="7.5" data-items-desktop="6.5"
                    data-items-laptop="5.5" data-items-tab="3.5" data-items-mobile-sm="2.5" data-items-mobile="2"
                    data-speed="1000" data-autoplay="false" data-center="false" data-infinite="false"
                    data-navigation="true" data-pagination="false" data-spacing="12">
                    @include('frontend::components.card.card_tvshow', [
                        'values' => $channel_data,
                        'omit_movie_data' => $omitMovieData,
                    ])
                </div>
            @endif
        </div>
    </div>
@endif
