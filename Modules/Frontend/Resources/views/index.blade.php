
@extends('frontend::layouts.master')


@section('title')
    {{ __('frontend.home') }}
@endsection




@section('content')
<!--
<style>
body{
    background-color: #1c2frf !important;
    color: #ffffff;
    font-family: 'Poppins', sans-serif !important;
}

</style>

-->
    <div id="banner-section" class="banner-section section-spacing-bottom px-0">



        @if (App\Models\MobileSetting::getCacheValueBySlug('banner') == 1)
        
      
        
        
            @include('frontend::components.section.banner', ['data' => $cachedResult['sliders'] ?? []])
        
        
         


        
        @endif
    </div>







    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">

              


<!-------------------live stream---------------------------------------->

     @if (isenablemodule('livetv') == 1)
     
     
    
                <div id="topchannel-section" class="section-wraper scroll-section section-hidden">
                
                    @if (!empty($data_channels))
                        @include('frontend::components.section.tvchannel', [
                            'top_channel' => $data_channels,
                            'title' => 'Live Stream' ?? __('frontend.top_channels'),
                        ])
                    @endif
                   
                </div>
            @endif

<!-------------------end live stream---------------------------------------->
 <!-------------------pay per view---------------------------------------->
<?php /* ?>
        <div id="pay-per-view-movie-section" class="section-wraper scroll-section section-hidden">

                @if (isset($cachedResult['payperview']) && count($cachedResult['payperview']) > 0)
                    @include('frontend::components.section.payperview', [
                        'data' => $cachedResult['payperview'] ?? [],
                        'title' => __('frontend.pay_per_view'),
                        'type' => 'pay-per-view',
                        'slug' => 'pay_per_view',
                    ])
                @endif

            </div>
<?php */ ?>
<!-------------------end pay per view---------------------------------------->

            <!----------------------------------------------------------->
            <?php /* ?>
               @if (isenablemodule('livetv') == 1)
                <div id="topchannel-section" class="section-wraper scroll-section section-hidden">
                
                    @if (isset($cachedResult['top-channels']['data']) && count($cachedResult['top-channels']['data']) > 0)
                        @include('frontend::components.section.tvchannel', [
                            'top_channel' => $cachedResult['top-channels']['data'] ?? [],
                            'title' => $cachedResult['top-channels']['name'] ?? __('frontend.top_channels'),
                        ])
                    @endif
                   
                </div>
            @endif
     <?php */ ?>
     
     
     
    
     
 

<!----------------------------------------------------------->

<!-----------------------START XXXXXXXX------------------------------------>
<?php /* ?>

            @php
                $is_enable_continue_watching = App\Models\MobileSetting::getValueBySlug('continue-watching');
            @endphp




            @if (
                    $is_enable_continue_watching == 1 &&
                    isset($cachedResult['continue_watch']) &&
                    count($cachedResult['continue_watch']) > 0)
                <div id="continue-watch-section" class="section-wraper scroll-section section-hidden">

                    @include('frontend::components.section.continue_watch', [
                        'continuewatchData' => $cachedResult['continue_watch'] ?? [],
                    ])

                </div>
            @endif



            @if (isenablemodule('movie') == 1)
                <div id="top-10-moive-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['top_10']['data']) && count($cachedResult['top_10']['data']) > 0)
                        @include('frontend::components.section.top_10_movie', [
                            'top10' => $cachedResult['top_10']['data'] ?? [],
                            'sectionName' => $cachedResult['top_10']['name'] ?? __('frontend.top_10'),
                        ])
                    @endif
                </div>

                <!-- Custom Ad Section: Only for placement 'home_page' -->
                @if (isset($cachedResult['custom_ads']) && count($cachedResult['custom_ads']) > 0)
                    <div id="custom-homepage-ad-section" class="section-wraper section-hidden">
                        @include('frontend::components.section.custom_ads_slider', [
                            'placement' => 'home_page',
                            'content_id' => null,
                            'content_type' => null,
                            'category_id' => null,
                            'ads_data' => $cachedResult['custom_ads'],
                        ])
                    </div>
                @endif

                <div id="latest-moive-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['latest_movie']['data']) && count($cachedResult['latest_movie']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['latest_movie']['data'] ?? [],
                            'title' => $cachedResult['latest_movie']['name'] ?? __('frontend.latest_movie'),
                            'type' => 'movie',
                            'slug' => 'latest_movie',
                        ])
                    @endif
                </div>
            @endif

            <div id="pay-per-view-movie-section" class="section-wraper scroll-section section-hidden">

                @if (isset($cachedResult['payperview']) && count($cachedResult['payperview']) > 0)
                    @include('frontend::components.section.payperview', [
                        'data' => $cachedResult['payperview'] ?? [],
                        'title' => __('frontend.pay_per_view'),
                        'type' => 'pay-per-view',
                        'slug' => 'pay_per_view',
                    ])
                @endif

            </div>

            <div id="language-section" class="section-wraper scroll-section section-hidden">


                @if (isset($cachedResult['popular_language']['data']) && count($cachedResult['popular_language']['data']) > 0)
                    @include('frontend::components.section.language', [
                        'popular_language' => $cachedResult['popular_language']['data'] ?? [],
                        'title' => $cachedResult['popular_language']['name'] ?? __('frontend.popular_language'),
                    ])
                @endif
            </div>


            @if (isenablemodule('movie') == 1)
                <div id="popular-moive-section" class="section-wraper scroll-section section-hidden">

                    @if (isset($cachedResult['popular_movie']['data']) && count($cachedResult['popular_movie']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['popular_movie']['data'] ?? [],
                            'title' => $cachedResult['popular_movie']['name'] ?? __('frontend.popular_movie'),
                            'type' => 'movie',
                            'slug' => 'popular_movie',
                        ])
                    @endif
                </div>
            @endif



<!--

            @if (isenablemodule('livetv') == 1)
                <div id="topchannel-section" class="section-wraper scroll-section section-hidden">
                
                    @if (isset($cachedResult['top-channels']['data']) && count($cachedResult['top-channels']['data']) > 0)
                        @include('frontend::components.section.tvchannel', [
                            'top_channel' => $cachedResult['top-channels']['data'] ?? [],
                            'title' => $cachedResult['top-channels']['name'] ?? __('frontend.top_channels'),
                        ])
                    @endif
                   
                </div>
            @endif

-->
            @if (isenablemodule('tvshow') == 1)
                <div id="popular-tvshow-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['popular_tvshow']['data']) && count($cachedResult['popular_tvshow']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['popular_tvshow']['data'] ?? [],
                            'title' => $cachedResult['popular_tvshow']['name'] ?? __('frontend.popular_tvshow'),
                            'type' => 'tvshow',
                            'slug' => 'popular_tvshow',
                        ])
                    @endif
                </div>
            @endif



<!---
            <div id="popular-personality" class="section-wraper scroll-section section-hidden">
                @if (isset($cachedResult['popular_personality']['data']) && count($cachedResult['popular_personality']['data']) > 0)
                    @include('frontend::components.section.castcrew', [
                        'data' => $cachedResult['popular_personality']['data'] ?? [],
                        'title' =>
                            $cachedResult['popular_personality']['name'] ?? __('frontend.personality'),
                        'slug' => 'popular_personality',
                    ])
                @endif
            </div>

-->
            @if (isenablemodule('movie') == 1)
                @if (isset($cachedResult['free_movie']['data']) && count($cachedResult['free_movie']['data']) > 0)
                    <div id="free-movie-section" class="section-wraper scroll-section section-hidden">

                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['free_movie']['data'] ?? [],
                            'title' => $cachedResult['free_movie']['name'] ?? __('frontend.free_movie'),
                            'type' => 'movie',
                            'slug' => 'free_movie',
                        ])

                    </div>
                @endif
            @endif




            @if (isset($cachedResult['genre']['data']) && count($cachedResult['genre']['data']) > 0)
                <div id="genres-section" class="section-wraper scroll-section section-hidden">

                    @include('frontend::components.section.geners', [
                        'genres' => $cachedResult['genre']['data'] ?? [],
                        'title' => $cachedResult['genre']['name'] ?? __('frontend.genre'),
                        'slug' => 'genre',
                    ])

                </div>
            @endif


            @if (isenablemodule('video') == 1)
                @if (isset($cachedResult['popular_video']['data']) && count($cachedResult['popular_video']['data']) > 0)
                    <div id="video-section" class="section-wraper scroll-section section-hidden">
                        @include('frontend::components.section.video', [
                            'data' => $cachedResult['popular_video']['data'] ?? [],
                            'title' => $cachedResult['popular_video']['name'] ?? __('frontend.popular_video'),
                            'type' => 'video',
                            'slug' => 'popular_video',
                        ])
                    </div>
                @endif
            @endif


            @if ($user_id != null && isenablemodule('movie') == 1)
                <div id="base-on-last-watch-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['based_on_last_watch']['data']) && count($cachedResult['based_on_last_watch']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['based_on_last_watch']['data'] ?? [],
                            'title' =>
                                $cachedResult['based_on_last_watch']['name'] ??
                                __('frontend.based_on_last_watch'),
                            'type' => 'movie',
                            'slug' => 'based_on_last_watch',
                        ])
                    @endif
                </div>


                <div id="most-like-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['liked_movie']['data']) && count($cachedResult['liked_movie']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['liked_movie']['data'] ?? [],
                            'title' => $cachedResult['liked_movie']['name'] ?? __('frontend.liked_movie'),
                            'type' => 'movie',
                            'slug' => 'liked_movie',
                        ])
                    @endif
                </div>

                <div id="most-view-section" class="section-wraper scroll-section section-hidden">

                    @if (isset($cachedResult['viewed_movie']['data']) && count($cachedResult['viewed_movie']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['viewed_movie']['data'] ?? [],
                            'title' => $cachedResult['viewed_movie']['name'] ?? __('frontend.viewed_movie'),
                            'type' => 'movie',
                            'slug' => 'viewed_movie',
                        ])
                    @endif

                </div>

                <div id="tranding-in-country-section" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['trending_movie']['data']) && count($cachedResult['trending_movie']['data']) > 0)
                        @include('frontend::components.section.entertainment', [
                            'data' => $cachedResult['trending_movie']['data'] ?? [],
                            'title' => $cachedResult['trending_movie']['name'] ?? __('frontend.tranding_in_country'),
                            'type' => 'movie',
                            'slug' => 'trending_movie',
                        ])
                    @endif
                </div>
            @endif

            @if ($user_id != null)
                @if (isset($cachedResult['favorite_gener']['data']) && count($cachedResult['favorite_gener']['data']) > 0)
                    <div id="favorite-genres-section" class="section-wraper scroll-section section-hidden">
                        @include('frontend::components.section.geners', [
                            'genres' => $cachedResult['favorite_gener']['data'] ?? [],
                            'title' => $cachedResult['favorite_gener']['name'] ?? __('frontend.genre'),
                            'slug' => 'favorite_gener',
                        ])
                    </div>
                @endif

                <div id="user-favorite-personality" class="section-wraper scroll-section section-hidden">
                    @if (isset($cachedResult['user_favorite_personality']['data']) && count($cachedResult['user_favorite_personality']['data']) > 0)
                        @include('frontend::components.section.castcrew', [
                            'data' => $cachedResult['user_favorite_personality']['data'] ?? [],
                            'title' =>
                                $cachedResult['user_favorite_personality']['name'] ??
                                __('frontend.favorite_personality'),
                            'slug' => 'user_favorite_personality',
                        ])
                    @endif
                </div>
            @endif
            
            
            
       
            
            

            @if (isset($cachedResult['dynamic_data']) && count($cachedResult['dynamic_data']) > 0)
                @foreach ($cachedResult['dynamic_data'] as $key => $dynamic_data)
                    @if ($dynamic_data['type'] == 'movie' && isenablemodule('movie') == 1)
                        <div id="{{ $key }}-section" class="section-wraper scroll-section section-hidden">
                            @if (isset($dynamic_data['data']) && count($dynamic_data['data']) > 0)
                                @include('frontend::components.section.entertainment', [
                                    'data' => $dynamic_data['data'] ?? [],
                                    'title' => $dynamic_data['name'] ?? __('frontend.latest_movie'),
                                    'type' => 'movie',
                                    'slug' => $key,
                                ])
                            @endif
                        </div>
                    @elseif ($dynamic_data['type'] == 'tvshow' && isenablemodule('tvshow') == 1)
                        <div id="{{ $key }}-section" class="section-wraper scroll-section section-hidden">
                            @if (isset($dynamic_data['data']) && count($dynamic_data['data']) > 0)
                                @include('frontend::components.section.entertainment', [
                                    'data' => $dynamic_data['data'] ?? [],
                                    'title' => $dynamic_data['name'] ?? __('frontend.popular_tvshow'),
                                    'type' => 'tvshow',
                                    'slug' => $key,
                                ])
                            @endif
                        </div>
                    @elseif ($dynamic_data['type'] == 'video' && isenablemodule('video') == 1)
                        <div id="{{ $key }}-section" class="section-wraper scroll-section section-hidden">
                            @if (isset($dynamic_data['data']) && count($dynamic_data['data']) > 0)
                                @include('frontend::components.section.video', [
                                    'data' => $dynamic_data['data'] ?? [],
                                    'title' => $dynamic_data['name'] ?? __('frontend.popular_video'),
                                    'type' => 'video',
                                    'slug' => $key,
                                ])
                            @endif
                        </div>
                    @elseif ($dynamic_data['type'] == 'channel' && isenablemodule('livetv') == 1)
                        <div id="{{ $key }}-section" class="section-wraper scroll-section section-hidden">
                            @if (isset($dynamic_data['data']) && count($dynamic_data['data']) > 0)
                                @include('frontend::components.section.tvchannel', [
                                    'top_channel' => $dynamic_data['data'] ?? [],
                                    'title' => $dynamic_data['name'] ?? __('frontend.top_channels'),
                                    'slug' => $key,
                                ])
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
            
            <?php */ ?>
            
          <!-----------------------END XXXXXXXX------------------------------------> 
          
          
          
       <!-- <div class="container-fluid padding-right-0"> -->
            <div  class="section-wraper scroll-section section-hidden">
           
            <div class="overflow-hidden">
                <div id="more-infinity-section">
                    @include('frontend::components.section.tv_series_shows_network', [
                        'moreinfinity' => $seriesNetworks,
                        'eagerCount' => 1,
                        'networkChannelData' => $networkChannelData ?? [],
                    ])
                </div>
            </div>
        </div>
          
          
          
          
          
           
                  <div id="popular-personality" class="section-wraper scroll-section section-hidden">
                @if (isset($cachedResult['popular_personality']['data']) && count($cachedResult['popular_personality']['data']) > 0)
                    @include('frontend::components.section.castcrew', [
                        'data' => $cachedResult['popular_personality']['data'] ?? [],
                        'title' =>
                            $cachedResult['popular_personality']['name'] ?? __('frontend.personality'),
                        'slug' => 'popular_personality',
                    ])
                @endif
            </div>

            
            
            
            
            
            
            
        </div>
    </div>
@endsection

@push('after-scripts')
    <style>
        .lazy-home-section:not(.is-loaded) {
            min-height: 320px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            function initializeSections() {
                const sections = document.querySelectorAll('.section-hidden');
                sections.forEach(section => {
                    section.classList.remove('section-hidden');
                    section.classList.add('section-visible');
                });
            }

            function initializeCustomAdsSlider() {
                const adSection = document.getElementById('custom-homepage-ad-section');
                if (adSection && adSection.querySelector('.custom-ad-slider')) {
                    if (window.$ && typeof $.fn.slick === 'function') {
                        $('.custom-ad-slider').slick({
                            dots: true,
                            arrows: false,
                            infinite: true,
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            adaptiveHeight: true,
                            autoplay: true,
                            autoplaySpeed: 5000,
                            fade: false,
                            cssEase: 'linear',
                            pauseOnHover: true,
                            pauseOnFocus: true,
                            speed: 800,
                            easing: 'ease-in-out'
                        });
                    }
                }
            }

            function initSlickIn(root) {
                if (!window.jQuery || typeof jQuery.fn.slick !== 'function') return;
                const isRTL = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';
                jQuery(root).find('.slick-general').each(function() {
                    const slider = jQuery(this);
                    if (slider.hasClass('slick-initialized')) return;
                    const slideSpacing = slider.data('spacing');
                    if (slideSpacing) {
                        slider.css('--spacing', slideSpacing + 'px');
                    }
                    slider.slick({
                        slidesToShow: slider.data('items'),
                        slidesToScroll: 1,
                        speed: slider.data('speed'),
                        autoplay: slider.data('autoplay'),
                        centerMode: slider.data('center'),
                        infinite: slider.data('infinite'),
                        arrows: slider.data('navigation'),
                        dots: slider.data('pagination'),
                        prevArrow: "<span class='slick-arrow-prev'><span class='slick-nav'><i class='ph ph-caret-left'></i></span></span>",
                        nextArrow: "<span class='slick-arrow-next'><span class='slick-nav'><i class='ph ph-caret-right'></i></span></span>",
                        rtl: isRTL,
                        responsive: [
                            { breakpoint: 1600, settings: { slidesToShow: slider.data('items-desktop') } },
                            { breakpoint: 1400, settings: { slidesToShow: slider.data('items-laptop') } },
                            { breakpoint: 1200, settings: { slidesToShow: slider.data('items-tab') } },
                            { breakpoint: 768, settings: { slidesToShow: slider.data('items-mobile-sm') } },
                            { breakpoint: 576, settings: { slidesToShow: slider.data('items-mobile') } }
                        ]
                    });
                });
            }

            function loadLazyHomeSection(el) {
                if (!el || el.dataset.loaded === '1' || el.dataset.loading === '1') return;
                const type = el.getAttribute('data-lazy-section');
                const id = el.getAttribute('data-id');
                if (type !== 'network' || !id) return;

                el.dataset.loading = '1';
                const baseUrl = (document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '').replace(/\/$/, '');

                fetch(baseUrl + '/api/frontend/home/network/' + encodeURIComponent(id), {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then((res) => res.ok ? res.json() : Promise.reject())
                .then((payload) => {
                    el.dataset.loaded = '1';
                    el.dataset.loading = '0';
                    window.cardMovieDataMap = Object.assign({}, window.cardMovieDataMap || {}, payload.cardMovieDataMap || {});
                    if (!payload.html) {
                        el.remove();
                        return;
                    }
                    el.innerHTML = payload.html;
                    el.classList.add('is-loaded');
                    el.removeAttribute('aria-busy');
                    initSlickIn(el);
                })
                .catch(() => {
                    el.dataset.loading = '0';
                });
            }

            function observeLazyHomeSections() {
                const placeholders = document.querySelectorAll('.lazy-home-section[data-lazy-section="network"]');
                if (!placeholders.length) return;

                if (!('IntersectionObserver' in window)) {
                    placeholders.forEach(loadLazyHomeSection);
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        observer.unobserve(entry.target);
                        loadLazyHomeSection(entry.target);
                    });
                }, {
                    rootMargin: '240px 0px',
                    threshold: 0.01
                });

                placeholders.forEach((el) => observer.observe(el));
            }

            initializeSections();
            observeLazyHomeSections();

            setTimeout(() => {
                initializeCustomAdsSlider();
            }, 100);
        });
    </script>
@endpush