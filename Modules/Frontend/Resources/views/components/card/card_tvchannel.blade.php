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
        🔴 LIVE
    </span>
@elseif($upcoming_date)

    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
        Upcoming<br>
        {{ \Carbon\Carbon::parse($upcoming_date)->format('d M Y h:i A') }}
    </span>
@endif
   -->
   
   
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
    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2" >
        Upcoming<br>
       {{ \Carbon\Carbon::parse($upcoming_date)->isoFormat('DD MMM YYYY hh:mm A') }}
    </span>
@else
    <span class="badge bg-secondary position-absolute top-0 end-0 m-2">
        🚫 Offline
    </span>
@endif
    
</div>   
    
</div>
