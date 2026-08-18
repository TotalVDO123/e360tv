<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Entertainment\Transformers\TvshowDetailResource;
<<<<<<< HEAD
=======
use Modules\Entertainment\Transformers\EpisodeResource;
>>>>>>> 725da5b48051e0a70583fc8dc361d18ce44ee6ee
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Like;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Genres\Models\Genres;
use Modules\Entertainment\Transformers\EpisodeDetailResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSearchHistory;
use Modules\Season\Models\Season;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Banner\Models\Banner;

use Modules\Banner\Transformers\Backend\SliderResourceV3;
use App\Services\RecommendationService;

use Modules\Frontend\Models\PayPerView;

<<<<<<< HEAD

=======
use Carbon\Carbon;
>>>>>>> 725da5b48051e0a70583fc8dc361d18ce44ee6ee
use Illuminate\Support\Facades\DB;



class TvShowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index()
    {
        return view('frontend::index');
    }

    public function tvShowList(Request $request,$slug="",$language = null)
    {
<<<<<<< HEAD
        
        
       // $networkId = abs($request->query('networkid'));

     ////  echo "=============".$slug;
       
      //// exit;
        


        $user_id = auth()->id();
        $user = Auth::user();

    $network_name="";
    $network_banner_image="";
    $networkId=0;
    if(!empty($slug))
    {
        ///$network = DB::table('series_networks')
           ///     ->select('id', 'name', 'image','banner_image')
            ////    ->where('id', $networkId)
            ////    ->first();
                
                
          ///      ->where('network_list_active', 1)      
        $network = DB::table('series_networks')
                ->select('id', 'name', 'image','banner_image')
                ->where('slug', $slug)
          
                ->first();        
            
        $network_name=$network->name;
        $networkId=$network->id;
        $network_banner_image=$network->banner_image;
        
        
    }  
=======
        $data = $this->resolveTvShowListData($slug);

        return view('frontend::tvShow', $data);
    }

    private function resolveTvShowListData(string $slug = ''): array
    {
        $network_name = '';
        $network_banner_image = '';
        $networkId = 0;

        if (!empty($slug)) {
            $network = DB::table('series_networks')
                ->select('id', 'name', 'image', 'banner_image')
                ->where('slug', $slug)
                ->first();

            if (!$network) {
                abort(404);
            }

            $network_name = $network->name;
            $networkId = $network->id;
            $network_banner_image = $network->banner_image;
        }
>>>>>>> 725da5b48051e0a70583fc8dc361d18ce44ee6ee

        $featured_tvshow = Banner::where('banner_for', 'tv_show')
            ->where('status', 1)
            ->limit(5)
            ->get();
        $sliders = SliderResourceV3::collection($featured_tvshow);
<<<<<<< HEAD
        $sliders =  $sliders->toArray(request());

        return view('frontend::tvShow', compact(

            'sliders','networkId','network_name','network_banner_image'

        ));
=======
        $sliders = $sliders->toArray(request());

        return compact('sliders', 'networkId', 'network_name', 'network_banner_image');
    }

    public function tvShowEmbedEpisodes(Request $request)
    {
        $embedData = $this->resolveEmbedTvShowEpisodesData();

        return view('frontend::tvShowEmbed', [
            'episodes' => $embedData['episodes'],
        ]);
    }

    private function resolveEmbedTvShowEpisodesData(): array
    {
        $userId = Auth::id();
        $dateRange = $this->resolveEmbedEpisodeDateRange();
        $episodes = collect();

        if ($dateRange) {
            $episodes = $this->getEmbedEpisodesBetween($dateRange['start'], $dateRange['end']);
        }

        return [
            'episodes' => EpisodeResource::collection(
                $episodes->map(fn ($episode) => new EpisodeResource($episode, $userId))
            ),
        ];
    }

    /**
     * Resolve which episode date range to show on the embed page.
     * 1. Today's episodes
     * 2. Previous days in the current week
     * 3. Earlier days in the current week
     * 4. Full current week
     * 5. Previous week(s)
     * 6. Earlier weeks/days, then latest available episode date
     */
    private function resolveEmbedEpisodeDateRange(): ?array
    {
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);

        // 1-3: Today, then walk back day-by-day through the current week.
        for ($date = $today->copy(); $date->gte($startOfWeek); $date->subDay()) {
            if ($this->embedEpisodesExistOnDate($date)) {
                return [
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                ];
            }
        }

        // 4: No single day matched — show the full current week.
        if ($this->embedEpisodesExistBetween($startOfWeek, $today)) {
            return [
                'start' => $startOfWeek->copy()->startOfDay(),
                'end' => $today->copy()->endOfDay(),
            ];
        }

        // 5-6: Walk back previous weeks, then earlier weeks.
        $weekStart = $startOfWeek->copy()->subWeek();
        for ($weekOffset = 0; $weekOffset < 8; $weekOffset++) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::MONDAY);
            if ($this->embedEpisodesExistBetween($weekStart, $weekEnd)) {
                return [
                    'start' => $weekStart->copy()->startOfDay(),
                    'end' => $weekEnd->copy()->endOfDay(),
                ];
            }
            $weekStart->subWeek();
        }

        // Last resort: use the single most recent episode date available.
        $latestDate = $this->embedEpisodeBaseQuery()
            ->max(DB::raw('DATE(episodes.created_at)'));

        if ($latestDate) {
            $latest = Carbon::parse($latestDate);

            return [
                'start' => $latest->copy()->startOfDay(),
                'end' => $latest->copy()->endOfDay(),
            ];
        }

        return null;
    }

    private function embedEpisodeBaseQuery()
    {
        return Episode::query()
            ->where('episodes.status', 1)
            ->whereNull('episodes.deleted_at')
            ->whereHas('entertainmentdata', function ($query) {
                $query->where('type', 'tvshow')
                    ->where('status', 1)
                    ->whereNull('deleted_at');
            })
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            });
    }

    private function embedEpisodesExistOnDate(Carbon $date): bool
    {
        return $this->embedEpisodeBaseQuery()
            ->whereDate('episodes.created_at', $date)
            ->exists();
    }

    private function embedEpisodesExistBetween(Carbon $start, Carbon $end): bool
    {
        return $this->embedEpisodeBaseQuery()
            ->whereBetween('episodes.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->exists();
    }

    private function getEmbedEpisodesBetween(Carbon $start, Carbon $end)
    {
        return $this->embedEpisodeBaseQuery()
            ->whereBetween('episodes.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('episodes.created_at')
            ->get();
>>>>>>> 725da5b48051e0a70583fc8dc361d18ce44ee6ee
    }

    public function tvshowDetail(Request $request, $slug)
    {
        $user_id = Auth::id();
        $cacheKey = "tvshow_details_{$slug}_user_{$user_id}";
        $is_search = $request->boolean('is_search', false);

        $movieGuard = Entertainment::where('slug', $slug)->first();
        if (empty($movieGuard) || (int) ($movieGuard->status) !== 1 || $movieGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        } else if($movieGuard->is_restricted == 1){
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                return redirect()->route('user.login');
            }
        }
        
        
      
        

        $season = Season::where('entertainment_id', $movieGuard->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$season) {
            return redirect()->route('user.login');
        }

        $episode = Episode::where('entertainment_id', $movieGuard->id)
            ->where('season_id', $season->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

       
      
      if(!empty($episode->access))
      {
        
        if($episode->access=='pay-per-view' )
        {
             if (auth()->check() && auth()->user()->user_type== 'test' )
            {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            
        }
        else
        {
           
         
            
             if(!auth()->check())
            {
            Auth::loginUsingId(26);
            $request->session()->regenerate();
            }
            
            
        }
        
      }
       
        
        if (!$episode) {
            return redirect()->route('user.login');
        }

        $data = cacheApiResponse($cacheKey, 10, function () use ($slug, $user_id) {

            if (!Cache::has('genres')) {
                $genresData = Genres::select('id', 'name')->get()->keyBy('id')->toArray();
                Cache::put('genres', $genresData, now()->addHours(2));
            }


            $tvshow = Entertainment::with([
                    'entertainmentGenerMappings.genre',
                    'plan',
                    'entertainmentReviews.user',
                    'entertainmentTalentMappings',
                    'season',
                    'episode',
                    'subtitles' => fn($q) => $q->where('type', 'tvshow'),
                    'entertainmentLike' => fn($q) => $q->where('user_id', $user_id)->where('is_like', 1),
                ])
                ->where('slug', $slug)
                ->first();

            if (!$tvshow) {
                abort(404, 'TV show not found.');
            }

            if (!empty($tvshow->trailer_url) && $tvshow->trailer_url_type !== 'Local') {
                $tvshow->trailer_url = Crypt::encryptString($tvshow->trailer_url);
            }

            if ($user_id) {
                $profile_id = getCurrentProfile($user_id, request());
                $tvshow->is_watch_list = Watchlist::where('entertainment_id', $tvshow->id)
                    ->where('user_id', $user_id)
                    ->where('type', 'tvshow')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $tvshow->subtitle_enable = $tvshow->subtitles->isNotEmpty();
                $tvshow->is_likes = $tvshow->entertainmentLike->isNotEmpty();

                $reviews = $tvshow->entertainmentReviews ?? collect();
                $yourReview = $reviews->where('user_id', $user_id)->first();

                $tvshow->your_review = $yourReview;
                $tvshow->reviews = $yourReview ? $reviews->where('user_id', '!=', $user_id) : $reviews;
                $tvshow->total_review = $reviews->count();
            }


            $season_id = Season::where('entertainment_id', $tvshow->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
             
                ->value('id');
            $episode = Episode::where('entertainment_id', $tvshow->id)
                ->where('season_id', $season_id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->with(['entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings'])
                ->first();

            if (!$episode) {
                abort(404, 'No episode found.');
            }

            $genre_ids = $tvshow->entertainmentGenerMappings->pluck('genre_id')->filter()->unique()->values();

            $episode->genre_data = Genres::whereIn('id', $genre_ids)->get();

            $episode->moreItems = Entertainment::where('type', 'tvshow')
                ->where('status', 1)
                ->where('id', '!=', $tvshow->id)
                ->whereHas('entertainmentGenerMappings', fn($q) => $q->whereIn('genre_id', $genre_ids))
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $data = (new TvshowDetailResource($tvshow))->toArray(request());
            $data['episodeData'] = (new EpisodeDetailResource($episode))->toArray(request());
            $data['seoData'] = (object) [
                "seo_image" => $tvshow->seo_image,
                "google_site_verification" => $tvshow->google_site_verification,
                "canonical_url" => $tvshow->canonical_url,
                "short_description" => $tvshow->short_description,
                "meta_title" => $tvshow->meta_title,
                "meta_keywords" => $tvshow->meta_keywords,
            ];

            return $data;
        });

        $entertainment = $data['data']['seoData'];

        if ($request->boolean('is_search')) {
            $userId = auth()->id() ?? $request->user_id;

            if ($userId) {
                $currentProfile = GetCurrentprofile($userId, $request);

                if ($currentProfile) {
                    $searchName = $data['data']['name'] ?? '';
                    $searchId   = $data['data']['id'] ?? '';
                    $searchType = $data['data']['type'] ?? '';

                    if (!empty($searchName)) {
                        $exists = UserSearchHistory::where([
                            'user_id'     => $userId,
                            'profile_id'  => $currentProfile,
                            'search_query'=> $searchName,
                        ])->exists();

                        if (!$exists) {
                            UserSearchHistory::create([
                                'user_id'     => $userId,
                                'profile_id'  => $currentProfile,
                                'search_query'=> $searchName,
                                'search_id'   => $searchId,
                                'type'        => $searchType,
                            ]);
                        }
                    }
                }
            }
        }

        return view('frontend::tvshowDetail', compact('data', 'entertainment'));
    }

    public function episodeDetail(Request $request, $slug)
    {
<<<<<<< HEAD
        
        
      
        
=======
>>>>>>> 725da5b48051e0a70583fc8dc361d18ce44ee6ee
        $user_id = auth()->id();
        $continue_watch = $request->boolean('continue_watch', false);
        $cacheKey = "episode_details_{$slug}_user_{$user_id}";
        $is_search = $request->boolean('is_search', false);

        $episodeGuard = Episode::where('slug', $slug)->with('entertainmentdata')->first();
        if (empty($episodeGuard) || (int) ($episodeGuard->status) !== 1 || $episodeGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        }

        if (empty($episodeGuard->entertainmentdata) ||
            (int) ($episodeGuard->entertainmentdata->status) !== 1 ||
            $episodeGuard->entertainmentdata->deleted_at !== null) {
            return redirect()->route('user.login');
        }

        if($episodeGuard->is_restricted == 1){
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                return redirect()->route('user.login');
            }
        }

        // ✅ Cache episode details using Redis
        $data = cacheApiResponse($cacheKey, 10, function () use ($slug, $user_id, $request) {

            // Load episode with relationships
            $episode = Episode::with([
                    'entertainmentdata.entertainmentGenerMappings.genre',
                    'plan',
                    'EpisodeStreamContentMapping',
                    'episodeDownloadMappings',
                ])
                ->where('slug', $slug)
                ->firstOrFail();
                
              
        if($episode->access=='pay-per-view')
        {
             if (auth()->check() && auth()->user()->user_type== 'test' )
            {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            
        }
        else
        {
           
         
            
             if(!auth()->check())
            {
            Auth::loginUsingId(26);
            $request->session()->regenerate();
            }
            
            
        }
                
                
                
                

            // Encrypt external URLs
            if (!empty($episode->trailer_url) && $episode->trailer_url_type !== 'Local') {
                $episode->trailer_url = Crypt::encryptString($episode->trailer_url);
            }

            if (!empty($episode->video_url_input) && $episode->video_upload_type !== 'Local') {
                $episode->video_url_input = Crypt::encryptString($episode->video_url_input);
            }

            $genreIds = $episode->entertainmentData
                ->entertainmentGenerMappings
                ->pluck('genre_id')->toArray();


            $episode->moreItems = !empty($genreIds)
                ? Entertainment::where('type', 'tvshow')
                    ->whereHas('entertainmentGenerMappings', fn($q) => $q->whereIn('genre_id', $genreIds))
                    ->where('id', '!=', $episode->id)
                    ->orderByDesc('id')
                    ->get()
                : collect();

            $episode->genre_data = Genres::whereIn('id', $genreIds)->get();


            if ($user_id) {
                $episode->continue_watch = ContinueWatch::where([
                    ['episode_id', $episode->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'tvshow'],
                ])->first();

                $episode->is_download = EntertainmentDownload::where([
                    ['entertainment_id', $episode->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'episode'],
                    ['is_download', 1],
                ])->exists();
            }

            $data = (new EpisodeDetailResource($episode))->toArray(request());
            $data['seoData'] = (object) [
                "seo_image" => $episode->seo_image,
                "google_site_verification" => $episode->google_site_verification,
                "canonical_url" => $episode->canonical_url,
                "short_description" => $episode->short_description,
                "meta_title" => $episode->meta_title,
                "meta_keywords" => $episode->meta_keywords,
            ];
            return $data;
        });

        $entertainment = $data['data']['seoData'];

        if ($request->boolean('is_search')) {
            $userId = auth()->id() ?? $request->user_id;

            if ($userId) {
                $currentProfile = GetCurrentprofile($userId, $request);

                if ($currentProfile) {
                    $searchName = $data['data']['name'] ?? '';
                    $searchId   = $data['data']['id'] ?? '';
                    $searchType = $data['data']['type'] ?? '';

                    if (!empty($searchName)) {
                        $exists = UserSearchHistory::where([
                            'user_id'     => $userId,
                            'profile_id'  => $currentProfile,
                            'search_query'=> $searchName,
                        ])->exists();

                        if (!$exists) {
                            UserSearchHistory::create([
                                'user_id'     => $userId,
                                'profile_id'  => $currentProfile,
                                'search_query'=> $searchName,
                                'search_id'   => $searchId,
                                'type'        => $searchType,
                            ]);
                        }
                    }
                }
            }
        }

        return view('frontend::episode_detail', compact('data', 'continue_watch', 'entertainment'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function stream($encryptedUrl)
    {
        $result = decryptVideoUrl($encryptedUrl);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result, 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function streamLocal($encryptedUrl, HttpRequest $request)
    {
        $url = Crypt::decryptString($encryptedUrl);

        if (!Storage::disk('local')->exists('test.mp4')) {
            abort(404, 'Video not found.');
        }

        return response()->stream(function () {
            $stream = Storage::disk('local')->readStream('test.mp4');

            fpassthru($stream);
            fclose($stream);
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => Storage::disk('local')->size('test.mp4'),
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="test.mp4"'
        ]);
    }

    public function checkEpisodePurchase(Request $request)
    {
        $episodeId = $request->episode_id;
        //$userId = auth()->id();
        
       $userId = $request->user_id ? $request->user_id : auth()->id();
        

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
                'is_purchased' => false
            ]);
        }

        $episode = Episode::find($episodeId);

        if (!$episode) {
            return response()->json([
                'status' => false,
                'message' => 'Episode not found',
                'is_purchased' => false
            ]);
        }

        // Check if episode is pay-per-view
        $isPayPerView = $episode->access === 'pay-per-view';

        if (!$isPayPerView) {
            return response()->json([
                'status' => true,
                'message' => 'Episode is not pay-per-view',
                'is_purchased' => true
            ]);
        }

        // Check if user has purchased the episode
        $hasPurchased = PayPerView::where('user_id', $userId)
            ->where('movie_id', $episodeId)
            ->where('type', 'episode')
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('first_play_date')
                    ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
            })
            ->exists();

        return response()->json([
            'status' => true,
            'message' => $hasPurchased ? 'Episode is purchased' : 'Episode is not purchased',
            'is_purchased' => $hasPurchased,
            'is_pay_per_view' => true,
            'episode_id' => $episodeId
        ]);
    }

    public function checkMoviePurchase(Request $request)
    {
        $movieId = $request->movie_id;
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
                'is_purchased' => false
            ]);
        }

        $movie = Entertainment::find($movieId);

        if (!$movie) {
            return response()->json([
                'status' => false,
                'message' => 'Movie not found',
                'is_purchased' => false
            ]);
        }

        // Check if movie is pay-per-view
        $isPayPerView = $movie->movie_access === 'pay-per-view';

        if (!$isPayPerView) {
            return response()->json([
                'status' => true,
                'message' => 'Movie is not pay-per-view',
                'is_purchased' => true
            ]);
        }

        // Check if user has purchased the movie
        $hasPurchased = PayPerView::where('user_id', $userId)
            ->where('movie_id', $movieId)
            ->where('type', 'movie')
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('first_play_date')
                    ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
            })
            ->exists();

        return response()->json([
            'status' => true,
            'message' => $hasPurchased ? 'Movie is purchased' : 'Movie is not purchased',
            'is_purchased' => $hasPurchased,
            'is_pay_per_view' => true,
            'movie_id' => $movieId
        ]);
    }
    
    
    
     /////////////ratnesh//////////////////////////////
    public function tvshow_series_list()
    {
        /*
        $channelData = LiveTvChannel::with('TvCategory','plan','TvChannelStreamContentMappings')->where('status',1)->orderBy('updated_at', 'desc')->take(6)->get();
        $categoryData = LiveTvCategory::with('tvChannels')->where('status',1)->orderBy('updated_at', 'desc')->get();

        $responseData['slider'] = LiveTvChannelResourceV3::collection($channelData)->toArray(request());

        $responseData['category_data'] = LiveTvCategoryResource::collection($categoryData)->toArray(request());
        
        */
        
         $featured_tvshow = Banner::where('banner_for', 'tv_show')
            ->where('status', 1)
            ->limit(5)
            ->get();
        $sliders = SliderResourceV3::collection($featured_tvshow);
        $sliders =  $sliders->toArray(request());
        
        $seriesNetworks = DB::table('series_networks')
        ->select('id', 'order', 'parent_id', 'name', 'image', 'banner_image', 'slug')
         ->where('network_list_active', 1)
        ->orderBy('order', 'ASC')
        ->get();
        
        
       // print_r($seriesNetworks);
        
       
        
        return view('frontend::tv_series_shows',compact('seriesNetworks'));
    }
    
    
    
    
    
    
    
    
    
}
