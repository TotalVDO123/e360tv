<div class="detail-page-banner">
    <div class="video-player-wrapper">
        <!-- Video.js core -->
        <link rel="stylesheet" href="{{ asset('css/video-js.css') }}" />
        <script src="{{ asset('js/videojs/video.min.js') }}"></script>

        <!-- YouTube Support -->
        <script src="{{ asset('js/videojs/videojs-youtube.min.js') }}"></script>

        <!-- IMA SDK -->
        <script src="{{ asset('js/videojs/ima3.js') }}"></script>

        <!-- Video.js Ads & IMA plugins -->
        <script src="{{ asset('js/videojs/videojs-contrib-ads.min.js') }}"></script>
        <script src="{{ asset('js/videojs/videojs.ima.min.js') }}"></script>
        <link href="{{ asset('css/videojs.ima.css') }}" rel="stylesheet">
        
       <!-----------------------------------------------------------------> 
      
        
        
        
        
        <div class="video-player">
            <video id="videoPlayer" class="video-js vjs-default-skin vjs-ima" controls width="560" height="315" muted
                poster="{{ $thumbnail_image }}" data-setup='{"muted": true}' data-type="{{ $type }}"
                content-video-type="{{ $content_video_type }}"
                data-continue-watch="{{ isset($continue_watch) && $continue_watch ? 'true' : 'false' }}"
                @if ($type != 'Local') data-watch-time="{{ $watched_time ?? 0 }}"
                    data-movie-access="{{ $dataAccess ?? '' }}"
                    data-encrypted="{{ $data }}"
                @endif
               
               
               
                @if (isset($content_type) && isset($content_id)) data-contentType="{{ $content_type }}"
                    data-contentId="{{ $content_id }}"
                @endif
                data-forward-seconds="{{ setting('forward_seconds',30) }}"
                data-backward-seconds="{{ setting('backward_seconds',30) }}"
            >
                @if ($type == 'Local')
                    <source src="{{ $data }}" type="video/mp4" id="videoSource">
                @endif
            
            
            
            </video>

            <!-- Vimeo iframe for Vimeo videos -->
            <div id="vimeoContainer">
                <iframe id="vimeoIframe" frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
                </iframe>
            </div>

            <!-- Custom Ad Modal -->
            <div id="customAdModal">
                <div id="customAdContent">
                    <!-- Ad content will be injected here -->
                    <button id="customAdCloseBtn">&times;</button>
                </div>
            </div>
        </div>
        
        
     
      <!--------------------------------------------------------------->  
        
        
    </div>
</div>

<?php
         
         
    if($content_type=='livetv')
    {     
         
         if(!empty($tv_channel_id))
                {
       // $today = date('l'); // Monday, Tuesday, Wednesday...
       
       /*
       
        $stream = DB::table('live_tv_stream_content_mapping')
            ->where('tv_channel_id', $tv_channel_id)
            ->where('recurring_program', 1)
            ->where('days_of_week', $today)
            ->where('upcoming_date', '<=', date('Y-m-d H:i:s'))
            ->orderBy('upcoming_date', 'DESC')
            ->first();
            
       */
       
       date_default_timezone_set('America/Los_Angeles');
        $currentDateTime = date('Y-m-d H:i:s');
        $currentDay      = date('l');
        $currentTime     = date('H:i:s');
       
       
       
       
        //echo "<br>";
        //echo "================".$currentDay;
        //echo "<br>";
        //echo "==============".$currentTime;
        
        
        ///echo "=============".$tv_channel_id;
      
     
     /*
        $stream = DB::table('live_tv_stream_content_mapping')
    ->where('tv_channel_id', $tv_channel_id)
    ->where('recurring_program', 1)
    
    ->whereRaw("DATE_FORMAT(upcoming_date, '%W') = ?", [$currentDay])
    ->whereRaw("TIME(upcoming_date)<=?", [$currentTime])
    ->whereRaw("TIME(upcoming_end_date)>=?", [$currentTime])
    ->get();
        
        
        print_r($stream);
        
        exit;
        
      */  
        
        //////////////////////////////////////////////
        
        
        
        $stream = DB::table('live_tv_stream_content_mapping')
            ->where(function ($query) use ($currentDateTime, $currentDay, $currentTime,$tv_channel_id) {

        // One-time program
        $query->where(function ($q) use ($currentDateTime,$tv_channel_id) {
            $q->where('recurring_program', 0)
                ->where('tv_channel_id', $tv_channel_id)
                ->where('upcoming_date', '<=', $currentDateTime)
                ->where('upcoming_end_date', '>=', $currentDateTime);
        })

        // Weekly recurring program
        ->orWhere(function ($q) use ($currentDay, $currentTime,$tv_channel_id) {
            $q->where('recurring_program', 1)
            ->where('tv_channel_id', $tv_channel_id)
            ->whereRaw("DAYNAME(upcoming_date) = ?",
    [$currentDay])
            ->whereRaw('TIME(upcoming_date) <= ?', [$currentTime])
            ->whereRaw('TIME(upcoming_end_date) >= ?', [$currentTime]);
        });

    })
    ->first();
       
       
           
         
       
       ////////////////////////////////////////////
       
           
       
       
       
            
            //echo "=================".$tv_channel_id;
            
            
           /// print_r($stream);
            //echo "******************";
            ///exit;
            
            
            if(!empty( $stream))
            {
            
            
            //echo "=======================";
            //exit;
            
            
                   /*
                   
                     $day = date('l', strtotime($stream->upcoming_date));
                    if(!empty($stream->days_of_week))
                    {
                        
                          echo $day . ' ' . date('h:i A', strtotime($stream->upcoming_date));
                    }
                    else
                    {
                        echo $stream->days_of_week . ' ' . date('h:i A', strtotime($stream->upcoming_date));
                            
                        //////////////////////////////////////////////////////////////////////
                    }   
                        
                     if(!empty($stream->recurring_program))  
                     {   
                        
                        
                                $currentTimestamp = strtotime(date('l h:i A'));
                            
                                $nextTimestamp = strtotime(
                                    $stream->days_of_week . ' ' .
                                    date('h:i A', strtotime($stream->upcoming_date))
                                );
            
                                if ($currentTimestamp <= $nextTimestamp) {
                                    echo "Live Started";
                                
                                 echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
                                ?>
                                    <!-- Include Video.js script if not already -->
                                  <!--<script src="{{ asset('js/videoplayer.min.js') }}"></script> -->
              
             
                                    
                                <?php    
                                } 
                     }
                     else
                     {
                     echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
                    ?>     
                         
                        <!-- Include Video.js script if not already -->
                       <!--<script src="{{ asset('js/videoplayer.min.js') }}"></script> -->
              
             
                         
                    <?php      
                     }
                     
                   */
                     
                // Include Video.js script if not already
                
                echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
                
                
            }
            else
            {
                
              $stream_map = DB::table('live_tv_stream_content_mapping')
            ->where('tv_channel_id', $tv_channel_id)
            ->where('recurring_program', 0)
            
            ->first();
            
                
            if(!empty($stream_map))
            {
                   // Include Video.js script if not already
                
                echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
                
            }
                
                
                
            }
            
                     
    /////////////////////////////////////////////////////////////////////////

        }
    }
    else
    {
    
    
    echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
    ?>    
   
   <!-- Include Video.js script if not already -->
<!--<script src="{{ asset('js/videoplayer.min.js') }}"></script> -->
  
   
        
    <?php 
     
    
    }
     echo "<script src=\"" . asset('js/videoplayer.min.js') . "\"></script>";
    
?>





<?php

if($content_type=='livetv')
{
?>



<script>
    var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    //var loginUrl = "{{ route('login') }}";
    var skipTrailerText = "{{ __('messages.skip_trailer') }}";
    var skipIntroText = "{{ __('messages.skip_intro') }}";
    var previousEpisodeText = "{{ __('messages.previous_episode') }}";
    var nextEpisodeText = "{{ __('messages.next_episode') }}";
    var backwardButtonText = "{{ __('messages.backward_button') }}";
    var forwardButtonText = "{{ __('messages.forward_button') }}";
    var defaultText = "{{ __('messages.default') }}";
    var errorLoadingAdText = "{{ __('messages.error_loading_ad') }}";
    var nextText = "{{ __('messages.next') }}";
</script>

<?php
}
else
{
?>
    <script>
   // var isAuthenticated = {{ auth()->check() ? 'false' : 'false' }};
  /// var loginUrl = "{{ route('login') }}";
  
    var isAuthenticated = true; // Force authenticated for this page only
    var loginUrl = "{{ route('login') }}";
   
  
    var skipTrailerText = "{{ __('messages.skip_trailer') }}";
    var skipIntroText = "{{ __('messages.skip_intro') }}";
    var previousEpisodeText = "{{ __('messages.previous_episode') }}";
    var nextEpisodeText = "{{ __('messages.next_episode') }}";
    var backwardButtonText = "{{ __('messages.backward_button') }}";
    var forwardButtonText = "{{ __('messages.forward_button') }}";
    var defaultText = "{{ __('messages.default') }}";
    var errorLoadingAdText = "{{ __('messages.error_loading_ad') }}";
    var nextText = "{{ __('messages.next') }}";
</script>







<?php
}
?>

<style>
    .video-player-wrapper {
        position: relative;
    }

    #vimeoContainer {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        display: none;
    }

    #vimeoIframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
    }

    #customAdModal {
        display: none;
        position: absolute;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        align-items: center;
        justify-content: center;
    }

    #customAdContent {
        position: relative;
        background: rgba(0, 0, 0, 0.0);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    #customAdCloseBtn {
        position: absolute;
        top: -20px;
        right: -20px;
        background: #f00;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        font-size: 20px;
        cursor: pointer;
        z-index: 2;
    }
</style>
<style>
    /* Hide ALL IMA Skip Elements */
    .ima-skip-container,
    .ima-skip-button,
    div[class*="ima-ad-skip"],
    div[class*="ima_skip"],
    div[class*="ima-skip"],
    button[class*="ima-skip"],
    button[class*="ima_skip"],
    div[class*="skip-button"],
    .ima-skip-button-container,
    [class*="skip-container"],
    [data-skip-button],
    [data-skip-container] {
        opacity: 0 !important;
        visibility: hidden !important;
        display: none !important;
        pointer-events: none !important;
        width: 0 !important;
        height: 0 !important;
        position: absolute !important;
        top: -9999px !important;
        left: -9999px !important;
    }

    /* Keep IMA Progress and Countdown Visible */
    .ima-controls-div,
    .ima-progress-div,
    .ima-countdown-div,
    .ima-seek-bar-div,
    .ima-progress-bar-div {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .vjs-texttrack-settings {
        display: none !important;
    }

    .video-js.vjs-ima {
        overflow: visible;
    }

    .video-js.vjs-ima .vjs-ima-ad-container {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        pointer-events: none;
    }

    .video-js.vjs-ima .vjs-ima-ad-container>div {
        pointer-events: auto;
    }

    .vjs-ad-cue {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        z-index: 10;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    .vjs-ad-cue:hover {
        width: 6px;
        opacity: 0.8;
    }

    .vjs-ad-cue[title*="Mid-roll"] {
        background-color: orange;
    }

    .vjs-ad-cue[title*="Post-roll"] {
        background-color: orange;
    }

    .vjs-ad-cue[title*="Overlay"] {
        background-color: orange;
    }

    /* Enhanced Skip Button Styling */
    .vjs-skip-ad-button {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        z-index: 9999 !important;
        font-size: 14px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .vjs-skip-ad-button:hover {
        background: rgba(255, 0, 0, 0.9) !important;
        transform: scale(1.05);
    }

    .overlay-ad {
        position: absolute;
        bottom: 60px;
        left: 20px;
        z-index: 1000;
        background: rgba(0, 0, 0, 0.8);
        padding: 5px;
        /* border-radius: 6px; */
    }

    .video-player {
        position: relative;
        z-index: 0;
    }

    #customAdModal {
        display: none;
        position: absolute;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        align-items: center;
        justify-content: center;
    }

    #customAdContent {
        max-width: 1000px;
        max-height:600px;
        width:100%;
        height:100%;
        padding:40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        position: relative;
    }
    #customAdContent img,
    #customAdContent a,
    #customAdContent video {
        max-width: 1000px;
        max-height: 600px;
        width: 100%;
        height: 100%;
        border-radius: 12px;
    }


    #customAdCloseBtn {
        position: absolute;
        top: 25px;
        transform: translate(-50%, -50%);
        right: -8px;
        background: #f00;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        font-size: 20px;
        cursor: pointer;
        z-index: 2;
    }
    @media (max-width: 575px) {
        #customAdContent {
            padding: 20px;
        }
        #customAdCloseBtn {
            top: 10px;
        }
    }
</style>
