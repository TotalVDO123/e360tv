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



 <?php

              $streamData = DB::table('live_tv_stream_content_mapping')
            ->where('tv_channel_id', $value['id'])
            ->first();
            
       
           
            $upcoming_date = $streamData->upcoming_date ?? '';
           $playlist="";
            if (!empty($value['server_url']))
            {
            $playlist = @file_get_contents($value['server_url']);
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



<div class="col">
    <div class="position-relative" style="width:230px; overflow:visible;">
    <a href="{{ route('livetv-details', ['id' => $value['slug']]) }}"
        class="livetv-card d-block position-relative">
        <div class="image-box position-relative">
            
            
            <?php /* ?>
            <img src="{{ $value['poster_image'] }}" alt="{{ $value['name'] }}"
                class="livetv-img object-cover img-fluid w-100 rounded">
                
            <?php */ ?>    
            
            <img src="{{ $value['poster_image'] }}" alt="{{ $value['name'] }}"
                class="object-cover img-fluid rounded" width="230" height="390">
            
            
                
            @if (!empty($value['show_premium_badge']))
                <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                    data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}">
                    <i class="ph ph-crown-simple"></i>
                </button>
            @endif

          <!--  <span class="live-card-badge">
                <span
                    class="live-badge fw-semibold text-uppercase">{{ __('frontend.live') }}</span>
            </span>-->
        </div>
    </a>
    
  


<!--
@if($isLive)
    <span class="badge bg-danger position-absolute top-0 end-0 m-2">
        ðŸ”´ LIVE
    </span>
@elseif($upcoming_date)

    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
        Upcoming<br>
        {{ \Carbon\Carbon::parse($upcoming_date)->format('d M Y h:i A') }}
    </span>
@endif
   -->
 <?php /* ?>  
 @if(empty($streamData->recurring_program))    
@php
    // 1. Safely parse the date if it exists
    $upcomingCarbon = $upcoming_date ? \Carbon\Carbon::parse($upcoming_date) : null;
    
    // 2. Strict boolean check for live status (handles string "false" or "0")
    $isCurrentlyLive = filter_var($isLive, FILTER_VALIDATE_BOOLEAN);
@endphp

@if($isCurrentlyLive)
    <span class="badge bg-success position-absolute top-0 end-0 m-2">
        ðŸ”´ LIVE
    </span>
@elseif($upcomingCarbon && $upcomingCarbon->isFuture())
    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2" >
        Upcoming<br>
       {{ \Carbon\Carbon::parse($upcoming_date)->isoFormat('DD MMM YYYY hh:mm A') }}
    </span>
@else
    <span class="badge bg-secondary position-absolute top-0 end-0 m-2">
        ðŸš« Offline
    </span>
@endif
 
@endif   

<?php */ ?>  

<?php
 date_default_timezone_set('America/Los_Angeles');

$currentDateTime = date('Y-m-d H:i:s');

$status = 'Upcoming';

if (!empty($streamData->recurring_program)) {
    
    
  
           /////////////////////////////////////////////////////
           
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


 

  
    // Live now
    if ($current >= $startTime && $current <= $endTime && $currentDay== $startDay ) {

     
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

           
           
   
   
   
            ////////////////////////////////////////////////
   
  
   
   
}
    else
    {
                    $day = date('l', strtotime($streamData->upcoming_date));
    ?>    
        
        
        <?php /* ?>
                            <span class="badge bg-danger text-white position-absolute bottom-0 end-0 m-2 px-2 py-1">
                                🟡 <strong>NEXT LIVE</strong><br>
                                {{ $day }} • {{ \Carbon\Carbon::parse($upcoming_date)->format('h:i A') }} PT
                            </span> 
              
              <?php */ ?>              
        
     <?php   
    }
    
 
 ?>                   






</div>   
    
</div>
