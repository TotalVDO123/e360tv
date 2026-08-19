
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

<?php 



       date_default_timezone_set('America/Los_Angeles');
        $currentDateTime = date('Y-m-d H:i:s');
     // echo "================".now();
      
   
     $start = date('Y-m-d') . ' 00:00:00';
    $end   = date('Y-m-d') . ' 23:59:59';
      
    
      
      
  /*   
  $data_channels = DB::table('live_tv_channel as c')
    ->join('live_tv_stream_content_mapping as m', 'c.id', '=', 'm.tv_channel_id')
    ->select('c.*', 'm.upcoming_date', 'm.recurring_program')
    ->where('c.status', 1)
    ->whereDate('m.upcoming_date', '>=', $currentDateTime)
    ->orderByDesc('m.recurring_program')
    ->orderBy('m.upcoming_date', 'ASC')
    ->limit(8)
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();
    */
    
    
    /*
    $data_channels = DB::table('live_tv_channel as c')
    ->join('live_tv_stream_content_mapping as m', 'c.id', '=', 'm.tv_channel_id')
    ->select('c.*', 'm.upcoming_date', 'm.recurring_program')
    ->where('c.status', 1)
    ->whereBetween('m.upcoming_date', [$start, $end])
    ->orderByDesc('m.recurring_program')
    ->orderBy('m.upcoming_date', 'ASC')
    ->limit(8)
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();
    */
    
    
    
    
   $currentDateTime = now();
   
//echo "=============".   date('l');
///exit;



/*
$currentDateTime = date('Y-m-d H:i:s');
$today = date('Y-m-d');





$today = date('l');
$currentTime = date('H:i:s');


$liveQuery = DB::table('live_tv_channel as c')
    ->join(
        'live_tv_stream_content_mapping as m',
        'c.id',
        '=',
        'm.tv_channel_id'
    )
    ->select(
        'c.*',
        'm.upcoming_date',
        'm.upcoming_end_date',
        'm.recurring_program',
        DB::raw('0 as sort_order')
    )
    ->where('c.status', 1)

    // Check today's DAY only
    ->whereRaw("DAYNAME(m.upcoming_date) = ?", [$today])

    // Check TIME only
    ->whereRaw("TIME(m.upcoming_date) <= ?", [$currentTime])
    ->whereRaw("TIME(m.upcoming_end_date) >= ?", [$currentTime]);


$otherQuery = DB::table('live_tv_channel as c')
    ->join(
        'live_tv_stream_content_mapping as m',
        'c.id',
        '=',
        'm.tv_channel_id'
    )
    ->select(
        'c.*',
        'm.upcoming_date',
        'm.upcoming_end_date',
        'm.recurring_program',
        DB::raw('1 as sort_order')
    )
    ->where('c.status', 1)

    // Check today's DAY only
    ->whereRaw("DAYNAME(m.upcoming_date)= ?", [$today])

    // Not currently LIVE
    ->where(function ($q) use ($currentTime) {
        $q->whereRaw("TIME(m.upcoming_date) > ?", [$currentTime])
          ->orWhereRaw("TIME(m.upcoming_end_date) < ?", [$currentTime]);
    });


$data_channels = $liveQuery
    ->unionAll($otherQuery)
    ->orderBy('sort_order', 'ASC')
    ->orderBy('upcoming_date', 'ASC')
    ->limit(18)
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();
      
    */ 
    
    
  
  date_default_timezone_set('America/Los_Angeles');

$currentDateTime = date('Y-m-d H:i:s');
$today = date('l');              // Monday, Tuesday, etc.
$currentTime = date('H:i:s');    // Current time only


/*
|--------------------------------------------------------------------------
| 1. LIVE RECORDS - TODAY'S DAY
|--------------------------------------------------------------------------
*/

$liveQuery = DB::table('live_tv_channel as c')
    ->join(
        'live_tv_stream_content_mapping as m',
        'c.id',
        '=',
        'm.tv_channel_id'
    )
    ->select(
        'c.*',
        'c.poster_url as poster_image',
        'm.id as mapping_id',
        'm.upcoming_date',
        'm.upcoming_end_date',
        'm.recurring_program'
    )
    ->where('c.status', 1)

    // Check DAY only
    ->whereRaw(
        "DAYNAME(m.upcoming_date) = ?",
        [$today]
    )

    // Check TIME only
    ->whereRaw(
        "TIME(m.upcoming_date) <= ?",
        [$currentTime]
    )
    ->whereRaw(
        "TIME(m.upcoming_end_date) >= ?",
        [$currentTime]
    )
    ->get();


/*
|--------------------------------------------------------------------------
| 2. TODAY'S OTHER RECORDS
|--------------------------------------------------------------------------
*/

$otherQuery = DB::table('live_tv_channel as c')
    ->join(
        'live_tv_stream_content_mapping as m',
        'c.id',
        '=',
        'm.tv_channel_id'
    )
    ->select(
        'c.*',
        'c.poster_url as poster_image',
        'm.id as mapping_id',
        'm.upcoming_date',
        'm.upcoming_end_date',
        'm.recurring_program'
    )
    ->where('c.status', 1)

    // Check DAY only
    ->whereRaw(
        "DAYNAME(m.upcoming_date) = ?",
        [$today]
    )

    // Not currently LIVE
    ->where(function ($q) use ($currentTime) {

        // Upcoming
        $q->whereRaw(
            "TIME(m.upcoming_date) > ?",
            [$currentTime]
        )

        // OR already completed
        ->orWhereRaw(
            "TIME(m.upcoming_end_date) < ?",
            [$currentTime]
        );
    })

    ->orderBy('m.upcoming_date', 'ASC')
    ->get();


/*
|--------------------------------------------------------------------------
| Get IDs from Query 1 and Query 2
|--------------------------------------------------------------------------
*/

$excludedIds = $liveQuery
    ->pluck('mapping_id')
    ->merge($otherQuery->pluck('mapping_id'))
    ->unique()
    ->values()
    ->toArray();


/*
|--------------------------------------------------------------------------
| 3. ALL REMAINING RECORDS
|--------------------------------------------------------------------------
*/

$thirdQuery = DB::table('live_tv_channel as c')
    ->join(
        'live_tv_stream_content_mapping as m',
        'c.id',
        '=',
        'm.tv_channel_id'
    )
    ->select(
        'c.*',
        'c.poster_url as poster_image',
        'm.id as mapping_id',
        'm.upcoming_date',
        'm.upcoming_end_date',
        'm.recurring_program'
    )
    ->where('c.status', 1)

    ->when(!empty($excludedIds), function ($query) use ($excludedIds) {
        $query->whereNotIn('m.id', $excludedIds);
    })

    ->orderBy('m.upcoming_date', 'ASC')
    ->get();


/*
|--------------------------------------------------------------------------
| FINAL RESULT - MAXIMUM 18 RECORDS
|--------------------------------------------------------------------------
*/

$data_channels = $liveQuery
    ->concat($otherQuery)
    ->concat($thirdQuery)

    // IMPORTANT: Maximum 18 records TOTAL
    ->take(18)

    ->map(function ($item) {

        $item->poster_image = setBaseUrlWithFileName(
            $item->poster_image,
            'image',
            'livetv'
        );

        return (array) $item;
    })
    ->values()
    ->toArray();
    
    
    
   
?>

     @if (isenablemodule('livetv') == 1)
     
     
    
                <div id="topchannel-section" class="section-wraper scroll-section section-hidden">
                
                    @if (isset($data_channels) && count($data_channels) > 0)
                        @include('frontend::components.section.tvchannel', [
                            'top_channel' =>$data_channels ?? [],
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

            initializeSections();

            setTimeout(() => {
                initializeCustomAdsSlider();
            }, 100);
        });
    </script>
@endpush
