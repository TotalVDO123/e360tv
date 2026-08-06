<!-- Horizontal Menu Start -->
<style>
.network-scroll{
    max-height: 600px;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Optional scrollbar design */

.network-scroll::-webkit-scrollbar{
    width: 5px;
}

.network-scroll::-webkit-scrollbar-thumb{
    background: #999;
    border-radius: 10px;
}
</style>


<nav id="navbar_main" class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
  <div class="container-fluid p-lg-0">
    <div class="offcanvas-header">
      <div class="navbar-brand p-0">
        <!--Logo -->
        @include('frontend::components.partials.logo')

      </div>
      <button type="button" class="btn-close p-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <ul class="navbar-nav iq-nav-menu  list-unstyled" id="header-menu">
      <li class="nav-item">
        <a class="nav-link"  href="{{route('user.login')}}">
          <span class="item-name">{{__('frontend.home')}}</span>
        </a>
      </li>
      
      
      
      
      
      
      <li class="nav-item">
        <a class="nav-link" href="#">
          <span class="item-name">{{__('frontend.all_content')}}</span>
        </a>
        <ul class="sub-menu list-unstyled">
         
         <?php /* ?>
         
          @if(isenablemodule('movie'))
          <li class="nav-item">
            <a class="nav-link py-1"  href="{{ route('movies') }}">
              <span class="item-name">{{__('frontend.movies')}}</span>
            </a>
          </li>
          @endif
          <?php */ ?>
          
          @if(isenablemodule('tvshow'))
          <li class="nav-item">
            <a class="nav-link py-1"  href="{{ route('tvshow') }}">
              <span class="item-name">TV Shows by Network</span>
            </a>
          </li>
          @endif
          
          
          <?php /* ?>
          @if(isenablemodule('tvshow'))
          <li class="nav-item">
            <a class="nav-link py-1"  href="{{ route('tv-shows') }}">
              <span class="item-name">{{__('frontend.tvshows')}}</span>
            </a>
          </li>
          @endif
         <?php */ ?>
         
         
           <?php /* ?>
          @if(isenablemodule('video'))
          <li class="nav-item">
            <a class="nav-link py-1"  href="{{ route('videos') }}">
              <span class="item-name">{{__('frontend.video')}}</span>
            </a>
          </li>
          @endif
          
            <?php */ ?>
         
          
          
          
          
        </ul>
      </li>
     
     <?php
   
         $data = DB::table('series_networks')
        ->orderBy('order', 'ASC')
        ->where('network_list_active', 1)
        ->get();
         
  
/*
$data = DB::table('series_networks as sn')
    ->join('entertainments as e', function ($join) {
        $join->whereRaw('FIND_IN_SET(sn.id, e.network_id)');
    })
    ->select(
        'e.id as entertainment_id',
        'e.name',
        'e.tmdb_id',
        'e.thumbnail_url',
        'e.poster_url',
        'e.slug',
        DB::raw('GROUP_CONCAT(sn.name ORDER BY sn.`order`) as networks')
    )
    ->groupBy(
        'e.id',
        'e.name',
        'e.tmdb_id',
        'e.thumbnail_url',
        'e.poster_url',
        'e.slug'
    )
    ->get();
    
    */
    
     ?>
     
     
     <li class="nav-item">
    <a class="nav-link" href="#">
        <span class="item-name">Networks</span>
    </a>

    <ul class="sub-menu list-unstyled network-scroll">

        <?php foreach($data as $row) { 
        
        
        $data = DB::table('entertainments')
    ->whereRaw('FIND_IN_SET(?, network_id)', [$row->id])
    ->where('status', 1)
    ->get();
        if ($data->isEmpty()) {
    // No records found
        } else {
        
        ?>

            <li class="nav-item">
                <a class="nav-link py-1"
                   href="{{ url('tv-shows/'. $row->slug) }}">

                    <span class="item-name text-capitalize">
                      
                        {{ $row->name }}
                    </span>

                </a>
            </li>

        <?php 
        }
        } ?>

    </ul>
</li>
     
     
     
     
     
      <?php /* ?>
      <!-- @if(isenablemodule('movie'))
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('movies') }}">
          <span class="item-name">{{__('frontend.movies')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('tvshow'))
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('tv-shows') }}">
          <span class="item-name">{{__('frontend.tvshows')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('video'))
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('videos') }}">
          <span class="item-name">{{__('frontend.video')}}</span>
        </a>
      </li>
      @endif -->
     <!-- <li class="nav-item">
        <a class="nav-link"  href="{{ route('comingsoon') }}">
          <span class="item-name">{{__('frontend.coming_soon')}}</span>
        </a>
      </li> -->
      
       <?php */ ?>
      
      @if(isenablemodule('livetv'))
      <li class="nav-item">
        <a class="nav-link"  href="{{url('live_list')}}">
          <span class="item-name">{{__('frontend.livetv')}}</span>
        </a>
      </li>
      @endif


          <li class="nav-item">
           <a class="nav-link" href="{{ url('tv-shows/film-festivals-online') }}">
              <span class="item-name">Film Festivals Online </span>
            </a>
          </li>
          
         <li class="nav-item">
           <a class="nav-link" href="{{ url('tv-shows/e360films') }}">
              <span style="text-transform: lowercase !important;" class="item-name">e360films </span>
            </a>
          </li> 
          
          <li class="nav-item">
           <a class="nav-link" href="{{ url('tv-shows/e360-music') }}">
              <span style="text-transform: lowercase !important;" class="item-name">e360music </span>
            </a>
          </li>  


    </ul>
  </div>
  <!-- container-fluid.// -->
</nav>
<!-- Horizontal Menu End -->
