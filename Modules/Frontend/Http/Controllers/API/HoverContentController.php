<?php

namespace Modules\Frontend\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\Watchlist;
use Modules\Episode\Models\Episode;
use Modules\Season\Models\Season;
use Modules\Video\Models\Video;

class HoverContentController extends Controller
{
    private const ALLOWED_TYPES = ['movie', 'tvshow', 'video', 'episode', 'season'];

    public function show(Request $request, string $type, $id): JsonResponse
    {
        $type = strtolower($type);

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return response()->json(['message' => 'Invalid content type'], 404);
        }

        $payload = match ($type) {
            'movie', 'tvshow' => $this->entertainmentPayload($request, $id, $type),
            'video' => $this->videoPayload($request, $id),
            'episode' => $this->episodePayload($request, $id),
            'season' => $this->seasonPayload($request, $id),
        };

        if (!$payload) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $payload]);
    }

    private function entertainmentPayload(Request $request, $id, string $type): ?array
    {
        $item = Entertainment::query()
            ->select([
                'id', 'name', 'slug', 'description', 'short_description', 'type',
                'trailer_url_type', 'trailer_url', 'bunny_trailer_url', 'bunny_video_url',
                'movie_access', 'IMDb_rating', 'plan_id', 'language', 'duration',
                'release_date', 'poster_url', 'thumbnail_url', 'status',
            ])
            ->with(['entertainmentGenerMappings.genre', 'plan'])
            ->where('id', $id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return null;
        }

        $access = (string) ($item->movie_access ?? '');
        $isPayPerView = $access === 'pay-per-view';
        $isPaid = $access === 'paid';
        $userPlanLevel = (int) (auth()->user()?->subscriptionPackage?->level ?? 0);
        $contentPlanLevel = (int) ($item->plan_level ?? $item->plan?->level ?? 0);

        [$trailerType, $trailerUrl] = $this->resolveTrailer($item, $item->type ?: $type);

        return $this->formatPayload([
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'type' => $item->type ?: $type,
            'description' => $this->shortText($item->short_description ?: $item->description),
            'poster_image' => setBaseUrlWithFileName($item->poster_url, 'image', $item->type ?: $type),
            'thumbnail_url' => setBaseUrlWithFileName($item->thumbnail_url, 'image', $item->type ?: $type),
            'trailer_url_type' => $trailerType,
            'trailer_url' => $trailerUrl,
            'imdb_rating' => $item->IMDb_rating,
            'language' => $item->language,
            'duration' => $item->duration,
            'release_date' => $item->release_date,
            'genres' => $this->genreList($item->entertainmentGenerMappings),
            'is_pay_per_view' => $isPayPerView,
            'is_purchased' => $isPayPerView ? Entertainment::isPurchased($item->id, $item->type) : false,
            'show_premium_badge' => !$isPayPerView && $isPaid && $contentPlanLevel > $userPlanLevel,
            'is_watch_list' => $this->inWatchlist($request, $item->id, $item->type ?: $type),
        ]);
    }

    private function videoPayload(Request $request, $id): ?array
    {
        $item = Video::query()
            ->with('plan')
            ->where('id', $id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return null;
        }

        $access = (string) ($item->access ?? '');
        $isPayPerView = $access === 'pay-per-view';
        $userPlanLevel = (int) (auth()->user()?->subscriptionPackage?->level ?? 0);
        $contentPlanLevel = (int) ($item->plan?->level ?? 0);
        [$trailerType, $trailerUrl] = $this->resolveTrailer($item, 'video');

        return $this->formatPayload([
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'type' => 'video',
            'description' => $this->shortText($item->short_desc ?: $item->description),
            'poster_image' => setBaseUrlWithFileName($item->poster_url, 'image', 'video'),
            'thumbnail_url' => setBaseUrlWithFileName($item->thumbnail_url, 'image', 'video'),
            'trailer_url_type' => $trailerType,
            'trailer_url' => $trailerUrl,
            'imdb_rating' => $item->IMDb_rating,
            'language' => $item->language ?? null,
            'duration' => $item->duration,
            'release_date' => $item->release_date,
            'genres' => [],
            'is_pay_per_view' => $isPayPerView,
            'is_purchased' => $isPayPerView ? Entertainment::isPurchased($item->id, 'video') : false,
            'show_premium_badge' => $access === 'paid' && $contentPlanLevel > $userPlanLevel,
            'is_watch_list' => $this->inWatchlist($request, $item->id, 'video'),
        ]);
    }

    private function episodePayload(Request $request, $id): ?array
    {
        $item = Episode::query()
            ->with(['plan', 'entertainmentdata.entertainmentGenerMappings.genre'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return null;
        }

        $access = (string) ($item->access ?? '');
        $isPayPerView = $access === 'pay-per-view';
        $userPlanLevel = (int) (auth()->user()?->subscriptionPackage?->level ?? 0);
        $contentPlanLevel = (int) ($item->plan?->level ?? 0);
        [$trailerType, $trailerUrl] = $this->resolveTrailer($item, 'episode');

        return $this->formatPayload([
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'episode_slug' => $item->slug,
            'type' => 'episode',
            'description' => $this->shortText($item->short_desc ?: $item->description),
            'poster_image' => setBaseUrlWithFileName($item->poster_url, 'image', 'episode'),
            'thumbnail_url' => setBaseUrlWithFileName($item->poster_url, 'image', 'episode'),
            'trailer_url_type' => $trailerType,
            'trailer_url' => $trailerUrl,
            'imdb_rating' => $item->IMDb_rating,
            'language' => $item->entertainmentdata?->language,
            'duration' => $item->duration,
            'release_date' => $item->release_date,
            'genres' => $this->genreList($item->entertainmentdata?->entertainmentGenerMappings ?? []),
            'is_pay_per_view' => $isPayPerView,
            'is_purchased' => $isPayPerView ? Entertainment::isPurchased($item->id, 'episode') : false,
            'show_premium_badge' => $access === 'paid' && $contentPlanLevel > $userPlanLevel,
            'is_watch_list' => false,
        ]);
    }

    private function seasonPayload(Request $request, $id): ?array
    {
        $item = Season::query()
            ->with(['plan', 'entertainmentdata.entertainmentGenerMappings.genre'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return null;
        }

        $entertainment = $item->entertainmentdata;
        $access = (string) ($item->access ?? '');
        $isPayPerView = $access === 'pay-per-view';
        $userPlanLevel = (int) (auth()->user()?->subscriptionPackage?->level ?? 0);
        $contentPlanLevel = (int) ($item->plan?->level ?? 0);
        [$trailerType, $trailerUrl] = $this->resolveTrailer($item, 'tvshow');

        return $this->formatPayload([
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $entertainment->slug ?? $item->slug,
            'type' => 'season',
            'description' => $this->shortText($item->short_desc ?: $item->description),
            'poster_image' => setBaseUrlWithFileName($item->poster_url, 'image', 'tvshow'),
            'thumbnail_url' => setBaseUrlWithFileName($item->poster_url, 'image', 'tvshow'),
            'trailer_url_type' => $trailerType,
            'trailer_url' => $trailerUrl,
            'imdb_rating' => $entertainment?->IMDb_rating,
            'language' => $entertainment?->language,
            'duration' => null,
            'release_date' => null,
            'genres' => $this->genreList($entertainment?->entertainmentGenerMappings ?? []),
            'is_pay_per_view' => $isPayPerView,
            'is_purchased' => $isPayPerView ? Entertainment::isPurchased($item->id, 'season') : false,
            'show_premium_badge' => $access === 'paid' && $contentPlanLevel > $userPlanLevel,
            'is_watch_list' => false,
        ]);
    }

    private function resolveTrailer($model, string $pageType): array
    {
        $type = $model->trailer_url_type;
        $url = $model->trailer_url;
        $bunnyUrl = $model->bunny_trailer_url ?: $model->bunny_video_url;

        if ($type === 'Local' && !empty($bunnyUrl) && env('ACTIVE_STORAGE') === 'bunny') {
            return ['HLS', $bunnyUrl];
        }

        if ($type === 'Local') {
            $url = setBaseUrlWithFileName($url, 'video', $pageType);
        }

        return [$type, $url];
    }

    private function genreList($mappings): array
    {
        $genres = [];
        foreach ($mappings as $mapping) {
            $name = $mapping->genre->name ?? null;
            if ($name) {
                $genres[] = [
                    'id' => $mapping->genre->id ?? $mapping->id,
                    'name' => $name,
                ];
            }
        }

        return $genres;
    }

    private function inWatchlist(Request $request, $entertainmentId, string $type): bool
    {
        $userId = auth()->id();
        if (!$userId) {
            return false;
        }

        $profileId = $request->input('profile_id') ?: getCurrentProfile($userId, $request);

        return Watchlist::query()
            ->where('entertainment_id', $entertainmentId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('profile_id', $profileId)
            ->exists();
    }

    private function shortText(?string $text): string
    {
        return Str::limit(trim(strip_tags((string) $text)), 180, '');
    }

    private function formatPayload(array $payload): array
    {
        if (!empty($payload['release_date'])) {
            $payload['release_date'] = \Carbon\Carbon::parse($payload['release_date'])->toDateString();
        }

        $payload['tv_show_data'] = $payload['description'] ?? '';

        return $payload;
    }
}
