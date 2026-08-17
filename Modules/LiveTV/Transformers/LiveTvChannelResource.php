<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;

class LiveTvChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        
        ///echo "====".$this->TvChannelStreamContentMappings->server_url ?? null;
        
        ///exit;
    
     $isLive = false;
    if(!empty(optional($this->TvChannelStreamContentMappings)->server_url ))
    {
        
       $playlist = @file_get_contents(optional($this->TvChannelStreamContentMappings)->server_url ?? '');
            if ($playlist) {
                if (
                    strpos($playlist, '#EXT-X-STREAM-INF') !== false ||
                    strpos($playlist, '#EXTINF') !== false
                ) {
                    $isLive = true;
                }
            }
    }
    

      $upcoming_date = optional($this->TvChannelStreamContentMappings)->upcoming_date ?? '';

        
      
    // 1. Safely parse the date if it exists
    $upcomingCarbon = $upcoming_date ? \Carbon\Carbon::parse($upcoming_date) : null;
    
    // 2. Strict boolean check for live status (handles string "false" or "0")
    $isCurrentlyLive = filter_var($isLive, FILTER_VALIDATE_BOOLEAN);

$currentlyLive="";
if($isCurrentlyLive)
 {   
        $currentlyLive.='LIVE';
 }  
elseif($upcomingCarbon && $upcomingCarbon->isFuture())
  {  
         $currentlyLive.='Upcoming<br>'.
        \Carbon\Carbon::parse($upcoming_date)->isoFormat('DD MMM YYYY hh:mm A') ;
  } 
else
   { 
        $currentlyLive.='Offline';
    
}
        
       

          
        
        
        
        
        

        return [
            'id' => $this->id,
            'show_premium_badge' => $this->show_premium_badge ?? false,
            'name' => $this->name,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan->level ?? 0,
            'slug' => $this->slug,
            'description' => strip_tags($this->description),
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
            
            'thumbnail_url' => setBaseUrlWithFileName($this->thumb_url , 'image', 'livetv'),
            
            
            
            'category' => optional($this->TvCategory)->name ?? null,
            'stream_type' => optional($this->TvChannelStreamContentMappings)->stream_type ?? null,
            'embedded' => optional($this->TvChannelStreamContentMappings)->embedded ?? null,
            'server_url' => optional($this->TvChannelStreamContentMappings)->server_url ?? null,
            'server_url1' => optional($this->TvChannelStreamContentMappings)->server_url1 ?? null,
            
            'trailer_url' =>optional($this->TvChannelStreamContentMappings)->trailer_url ?? null,
            
            'trailer_url_type' =>optional($this->TvChannelStreamContentMappings)->trailer_url_type ?? null,
            'currently_live'=> $currentlyLive,
            
            
            'status' => $this->status,
            'access'=>$this->access,
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url, 'image', 'livetv'),
        ];
    }
}
