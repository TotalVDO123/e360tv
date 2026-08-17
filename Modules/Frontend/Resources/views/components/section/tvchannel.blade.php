<style>
.live-now-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;

    /* Transparent green background */
    background: rgba(20, 139, 20, 0.65);

    color: #fff;
    font-size: 14px;
    font-weight: bold;
    border-radius: 8px;

    /* Green transparent border */
    border: 1px solid rgba(66, 215, 66, 0.8);

    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.25),
        0 2px 5px rgba(0, 0, 0, 0.25);

    text-transform: uppercase;
    font-family: Arial, sans-serif;

    /* Slight glass effect */
    backdrop-filter: blur(3px);
}

.live-dot {
    width: 10px;
    height: 10px;
    background: #f2b400;
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(255, 215, 0, 0.8);
}
</style>

<div class="channel-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>
        @if (count($top_channel) > 5)
            
            <?php /* ?>
            <a href="{{ route('topChannelList') }}"
                class="view-all-button text-decoration-none flex-none"><span>{{ __('frontend.view_all') }}</span> <i
                    class="ph ph-caret-right"></i></a>
                <?php */ ?>     
                    
             <a href="{{ url('livetv-channels/live') }}"
                class="view-all-button text-decoration-none flex-none"><span>{{ __('frontend.view_all') }}</span> <i
                    class="ph ph-caret-right"></i></a>        
                    
                    
        @endif
    </div>
    
   
    
    <div class="card-style-slider slide-data-less">
        
        <div class="slick-general slick-general-topchannel" data-items="6.5" data-items-laptop="5.5" data-items-tab="3.5"
            data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000" data-autoplay="false"
            data-center="false" data-infinite="false" data-navigation="true" data-pagination="false" data-spacing="12">
            @foreach ($top_channel as $data)
            
            
            <?php
            
            
            //print_r($data);
            
      
              $streamData = DB::table('live_tv_stream_content_mapping')
            ->where('tv_channel_id', $data['id'])
            ->first();
            
            
           // echo "====================".$data['id'];
            //print_r(  $streamData);
            //echo "*******************************<br>";
           
            $upcoming_date = $streamData->upcoming_date ?? '';
           
            if(!empty($streamData->server_url))
            {
                $playlist = @file_get_contents($streamData->server_url);
            }
            else
            {
                $playlist = @file_get_contents($streamData->embedded);
            }
            
            
            $isLive = false;
            
            if ($playlist) {
                if (
                    strpos($playlist, '#EXT-X-STREAM-INF') !== false ||
                    strpos($playlist, '#EXTINF') !== false
                ) {
                    $isLive = true;
                }
            }
            
          ?>  
            
            
            
                  <div class="slick-item">
                  <div class="position-relative">
                   
                   <?php /* ?>
                   <a href="{{ route('livetv-details', ['id' => $data['slug']]) }}"
                        class="channel-card d-flex align-content-center align-items-center justify-content-center rounded">
                        <img src="{{ setBaseUrlWithFileName($data['poster_url'], 'image', 'livetv')  }}" alt="channel icon"
                            class="img-fluid object-cover rounded channel-img" width="500" height="200">
                    </a>
                    <?php */ ?>
                   
                    <a href="{{ route('livetv-details', ['id' => $data['slug']]) }}"
                        class="d-flex align-content-center align-items-center justify-content-center rounded">
                        <img src="{{ setBaseUrlWithFileName($data['poster_url'], 'image', 'livetv')  }}" alt="channel icon"
                            class="img-fluid object-cover rounded channel-img" width="230" height="390">
                    </a>
             <!--   
                
                 @if($isLive)
                <span class="position-absolute top-0 end-0 badge bg-danger m-2">
                    ðŸ”´ LIVE
                </span>
                @endif
                
                
                @if(!$isLive && !empty($upcoming_date))
    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">
        Upcoming:<br>
        {{ \Carbon\Carbon::parse($upcoming_date)->format('D, d M h:i A') }}
    </span>
@endif
    -->            
        
@if(empty($streamData->recurring_program))  



          
           @php
           
           
            // 1. Safely parse the date if it exists
            $upcomingCarbon = $upcoming_date ? \Carbon\Carbon::parse($upcoming_date) : null;
            
            // 2. Strict boolean check for live status (handles string "false" or "0")
            $isCurrentlyLive = filter_var($isLive, FILTER_VALIDATE_BOOLEAN);
        @endphp
        
        @if($isCurrentlyLive)
            <span class="badge bg-success position-absolute top-0 end-0 m-2">
                🔴 LIVE
            </span>
        @elseif($upcomingCarbon && $upcomingCarbon->isFuture())
            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                Upcoming<br>
               {{ \Carbon\Carbon::parse($upcoming_date)->isoFormat('DD MMM YYYY hh:mm A') }}
            </span>
        @else
            <span class="badge bg-secondary position-absolute top-0 end-0 m-2">
                🚫 Offline
            </span>
        @endif
@endif

<?php


if (!empty($streamData->recurring_program)) {

    $currentDateTime = date('Y-m-d H:i:s');

    // Current day + time
    $currentDay  = date('l');
    $currentTime = date('H:i:s');

    // Start day + time
    $startDay  = date('l', strtotime($streamData->upcoming_date));
    $startTime = date('H:i:s', strtotime($streamData->upcoming_date));

    // End day + time
    $endDay  = date('l', strtotime($streamData->upcoming_end_date));
    $endTime = date('H:i:s', strtotime($streamData->upcoming_end_date));


    /*
    |--------------------------------------------------------------------------
    | Convert day + time to a comparable timestamp
    |--------------------------------------------------------------------------
    */

    $weekDays = [
        'Sunday'    => 0,
        'Monday'    => 1,
        'Tuesday'   => 2,
        'Wednesday' => 3,
        'Thursday'  => 4,
        'Friday'    => 5,
        'Saturday'  => 6,
    ];

    $current = strtotime(
        '2026-01-04 ' . $currentTime
    ) + ($weekDays[$currentDay] * 86400);

    $startTime = strtotime(
        '2026-01-04 ' . $startTime
    ) + ($weekDays[$startDay] * 86400);

    $endTime = strtotime(
        '2026-01-04 ' . $endTime
    ) + ($weekDays[$endDay] * 86400);


    $live_flag = false;

    // Live now
    if ($current >= $startTime && $current <= $endTime) {

        $live_flag = true;
        ?>

      <span class="live-now-btn position-absolute top-0 end-0 m-2">
        <span class="live-dot"></span>
        LIVE NOW
        </span>
        <?php

    } else {

       $day = date('l', strtotime($streamData->upcoming_date));
        ?>

      <span class="badge bg-danger text-white position-absolute bottom-0 end-0 m-2 px-2 py-1">
    🟡 <strong>NEXT LIVE</strong><br>
    {{ $day }} • {{ \Carbon\Carbon::parse($streamData->upcoming_date)->format('h:i A') }} PT
</span>


        <?php
    }
}








 ?>                   
                
            </div>    
                </div>
            @endforeach
        </div>
    </div>
</div>
