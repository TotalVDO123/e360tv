@foreach ($moreinfinity as $category)
    @php
       
  
        //$channel_data = $category['channel_data']->toArray(request());
		
	//	$channel_data = DB::table('entertainments')
     //   ->whereRaw("FIND_IN_SET(?, network_id)", [$category->id])
    //    ->get()
     //   ->toArray();
		
		
	/*	
		$channel_data = DB::table('entertainments')
		 ->where('entertainments.status', 1)
		 
    ->whereRaw("FIND_IN_SET(?, network_id)", [$category->id])
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();
*/	
	

/*
$channel_data  = DB::table('entertainments')
    ->select(
        'id',
        'name',
        'slug',
        'description',
        'type',
        'trailer_url_type',
        'trailer_url',
        'movie_access',
        'imdb_rating',
        'plan_id',
        DB::raw("'' as plan_level"),
        'language',
        'duration',
        'release_date',
         DB::raw("CONCAT('" . url('/storage/tvshow/image/') . "/', poster_url) as poster_image"),
         DB::raw("CONCAT('" . url('/storage/tvshow/image/') . "/', thumbnail_url) as thumbnail_url"),
        DB::raw("'' as is_watch_list"),
        DB::raw("'' as genres"),
        DB::raw("'' as show_premium_badge"),
        DB::raw("'' as is_purchased"),
        DB::raw("'' as is_pay_per_view"),
        DB::raw("'' as is_paid")
    )
    ->where('entertainments.status', 1)
    ->whereRaw("FIND_IN_SET(?, network_id)", [$category->id])
    ->orderBy('sno_order', 'ASC')
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();

 */ 
/*
$channel_data = DB::table('entertainments')
    ->select(
        'id',
        'name',
        'slug',
        'description',
        'type',
        'trailer_url_type',
        'trailer_url',
        'movie_access',
        'imdb_rating',
        'plan_id',
        DB::raw("'' as plan_level"),
        'language',
        'duration',
        'release_date',
        DB::raw("CONCAT('" . url('/storage/tvshow/image/') . "/', poster_url) as poster_image"),
        DB::raw("CONCAT('" . url('/storage/tvshow/image/') . "/', thumbnail_url) as thumbnail_url"),
        DB::raw("'' as is_watch_list"),
        DB::raw("'' as genres"),
        DB::raw("'' as show_premium_badge"),
        DB::raw("'' as is_purchased"),
        DB::raw("'' as is_pay_per_view"),
        DB::raw("'' as is_paid")
    )
    ->where('status', 1)
    ->whereRaw("FIND_IN_SET(?, network_id)", [$category->id])
    ->whereExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('episodes')
              ->whereColumn('episodes.entertainment_id', 'entertainments.id');
    })
    ->orderBy('sno_order', 'ASC')
    ->get()
    ->map(function ($item) {
        return (array) $item;
    })
    ->toArray();
*/




$channel_data = DB::table('entertainments')
    ->select(
        'id',
        'name',
        'slug',
        'description',
        'type',
        'trailer_url_type',
        'trailer_url',
        'movie_access',
        'imdb_rating',
        'plan_id',
        DB::raw("'' as plan_level"),
        'language',
        'duration',
        'release_date',
        'poster_url',
        'thumbnail_url',
        DB::raw("'' as is_watch_list"),
        DB::raw("'' as genres"),
        DB::raw("'' as show_premium_badge"),
        DB::raw("'' as is_purchased"),
        DB::raw("'' as is_pay_per_view"),
        DB::raw("'' as is_paid")
    )
    ->where('status', 1)
    ->whereRaw("FIND_IN_SET(?, network_id)", [$category->id])
    ->whereExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('episodes')
              ->whereColumn('episodes.entertainment_id', 'entertainments.id');
    })
    ->orderBy('sno_order', 'ASC')
    ->limit(8)
    ->get()
    ->map(function ($item) {

        $item->poster_image = setBaseUrlWithFileName($item->poster_url, 'image', 'tvshow');
        $item->thumbnail_url = setBaseUrlWithFileName($item->thumbnail_url, 'image', 'tvshow');

        unset($item->poster_url);

        return (array) $item;
    })
    ->toArray();




		
    @endphp

    @if (!empty($channel_data) && count($channel_data) > 0)
        @php
            $isSingleItem = count($channel_data) === 1;
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
                    @php $resource = $channel_data[0]; @endphp
                    {{-- Single item: skip slider and align to left --}}
                    <div class="d-flex" style="justify-content: flex-start;">
                        <div style="flex: 0 0 auto; max-width: 500px;">
                            @include('frontend::components.card.card_tvshow', [
                                'values' => $channel_data,
                            ])
                        </div>
                    </div>
                @else
                    {{-- Multiple items: show slider --}}
                   
                   
                   <?php /* ?>
                    <div class="slick-general" data-items="5.5" data-items-desktop="5" data-items-laptop="4.5"
                        data-items-tab="3.5" data-items-mobile-sm="2.5" data-items-mobile="2.5" data-speed="1000"
                        data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
                        data-pagination="false" data-spacing="12">
                     <?php */ ?>
                   
                   <div class="slick-general tv-series-network-slider" data-items="7.5" data-items-desktop="6.5" data-items-laptop="5.5"
            data-items-tab="3.5" data-items-mobile-sm="2.5" data-items-mobile="2" data-speed="1000"
            data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
            data-pagination="false" data-spacing="12">    
                           
                                 
                                @include('frontend::components.card.card_tvshow', [
                                    'values' => $channel_data,
                                ])
                            
                       
                    </div>
                @endif
            </div>
        </div>
    @endif
    
    
    
@endforeach
