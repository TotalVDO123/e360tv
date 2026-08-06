

<?php

//print_r($resource);
///exit;
?>


  <?php



        
              $streamData = DB::table('live_tv_stream_content_mapping')
            ->where('tv_channel_id', $resource['id'])
            ->first();
            $upcoming_date = $streamData->upcoming_date ?? '';
          
            //$playlist = @file_get_contents($resource['server_url']);
            $playlist = !empty($resource['server_url'])
    ? @file_get_contents($resource['server_url'])
    : false;

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
          




<a href="{{ route('livetv-details', ['id' => $resource['slug']]) }}">
    <div class="livetv-card position-relative">
        <img src="{{ $resource['poster_image'] }}" alt="{{ $resource['name'] }}"
            class="livetv-img object-cover img-fluid w-100 rounded">

        @if ($resource['access'] == 'paid')
            @php
                $current_user_plan = auth()->user() ? auth()->user()->subscriptionPackage : null;
                $current_plan_level = $current_user_plan->level ?? 0;
            @endphp

            @if ($resource['plan_level'] > $current_plan_level || auth()->user() == null)
                <button type="button" class="product-premium border-0" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ __('messages.lbl_premium') }}">
                    <i class="ph ph-crown-simple"></i>
                </button>
            @endif
        @endif

       <!-- <span class="live-card-badge">
            <span class="live-badge fw-semibold text-uppercase">{{ __('frontend.live') }}</span>
        </span> -->
        
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
    
    @php
    // 1. Safely parse the date if it exists
    $upcomingCarbon = $upcoming_date ? \Carbon\Carbon::parse($upcoming_date) : null;
    
    // 2. Strict boolean check for live status (handles string "false" or "0")
    $isCurrentlyLive = filter_var($isLive, FILTER_VALIDATE_BOOLEAN);
@endphp

@if($isCurrentlyLive)
    <span class="badge bg-danger position-absolute top-0 end-0 m-2">
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

        
        
    </div>
</a>
