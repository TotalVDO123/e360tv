<div class="channel-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>
        @if (count($top_channel) > 8)
            <a href="{{ route('topChannelList') }}"
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


$currentDateTime = date('Y-m-d H:i:s');

$status = 'Upcoming';

if (!empty($streamData->recurring_program)) {
    
    
    


    $startTime = strtotime($streamData->upcoming_date);
    $endTime   = strtotime($streamData->upcoming_end_date);
    $current   = strtotime($currentDateTime);

    // Live now
    if ($current >= $startTime && $current <= $endTime)
    {
    ?>
        <span class="badge bg-success position-absolute top-0 end-0 m-2">
        🔴 LIVE
    </span>
    <?php
    }
    // Not started yet
    
    
    if ($current < $startTime)
    {
    ?>




     <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
        Upcoming<br>
       {{ \Carbon\Carbon::parse($upcoming_date)->isoFormat('DD MMM YYYY hh:mm A') }}
    </span>

    <?php
    }
    // Program ended (for recurring program you can show Next Live)
    else {
$day = date('l', strtotime($streamData->upcoming_date));
        ?>
         
 
 <?php /* ?>
  <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 text-center py-2"
        >
        🟡 <strong>NEXT LIVE</strong><br>
        {{ $day }} • {{ \Carbon\Carbon::parse($upcoming_date)->format('h:i A') }} PT
    </span>
  <?php */ ?>
 
 
 
 <span class="badge bg-danger text-white position-absolute bottom-0 end-0 m-2 px-2 py-1">
        🟡 <strong>NEXT LIVE</strong><br>
        {{ $day }} • {{ \Carbon\Carbon::parse($upcoming_date)->format('h:i A') }} PT
    </span>
 
 
 
 
 <?php

    }

}












//echo "=================".$streamData->recurring_program;
//exit;

/*
if( !empty($streamData->recurring_program))
{
    $day = date('l', strtotime($streamData->upcoming_date));

?>
         
 <span class="badge bg-warning text-dark position-absolute bottom: 0 end-0 m-2">
🟡 NEXT LIVE:<br> <?php echo   $day ?> • {{ \Carbon\Carbon::parse($upcoming_date)->format('h:i A') }} PT
    </span>                
 <?php
}

*/



 ?>                   
                
            </div>    
                </div>
            @endforeach
        </div>
    </div>
</div>
