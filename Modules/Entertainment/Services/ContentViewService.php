
<?php

namespace Modules\Entertainment\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\ContentView;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\Video\Models\Video;

class ContentViewService
{
    public const DEVICE_TYPES = [
        'website',
        'android',
        'ios',
        'android_tv',
        'roku_tv',
        'amazon_fire_tv',
    ];

    /** Display / summary order: livetv, tvshow, episode, film, music, video */
    public const CONTENT_TYPES = ['livetv', 'tvshow', 'episode', 'film', 'music', 'video'];

    /** Accepted request aliases → stored content_type (film/music still re-resolved by network). */
    public const CONTENT_TYPE_ALIASES = [
        'movie' => 'film',
        'vedio' => 'video',
    ];

    public const FILM_NETWORK_KEYS = ['e360films', 'e360film'];

    public const MUSIC_NETWORK_KEYS = ['e360music'];

    public const THROTTLE_MINUTES = 30;

    public static function allowedContentTypeInputs(): array
    {
        return array_values(array_unique(array_merge(
            self::CONTENT_TYPES,
            array_keys(self::CONTENT_TYPE_ALIASES)
        )));
    }

    public function recordFromLegacy(Request $request, ?int $userId = null): array
    {
        $entertainmentId = (int) $request->input('entertainment_id');
        if ($entertainmentId < 1) {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'Invalid content_type or content_id',
            ];
        }

        $contentType = null;
        $contentId = $entertainmentId;

        $episode = Episode::where('id', $entertainmentId)->where('status', 1)->first();
        $movieOrShow = Entertainment::where('id', $entertainmentId)->where('status', 1)->first();
        $video = Video::where('id', $entertainmentId)->where('status', 1)->first();
        $channel = LiveTvChannel::where('id', $entertainmentId)->where('status', 1)->first();
        $requestedType = strtolower(trim((string) $request->input('entertainment_type', $request->input('content_type', ''))));

        if ($requestedType === 'episode' || ($episode && ! $movieOrShow)) {
            if ($episode) {
                $contentType = 'episode';
                $contentId = (int) $episode->id;
            }
        } elseif ($movieOrShow && in_array($movieOrShow->type, ['movie', 'tvshow'], true)) {
            $contentType = $this->resolveEntertainmentContentType($movieOrShow);
        } elseif (in_array($requestedType, ['video', 'vedio'], true) || $video) {
            $contentType = 'video';
        } elseif ($requestedType === 'livetv' || $channel) {
            $contentType = 'livetv';
        } elseif ($episode) {
            $contentType = 'episode';
            $contentId = (int) $episode->id;
        }

        if (! $contentType) {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'Invalid content_type or content_id',
            ];
        }

        $bridge = Request::create('/api/v3/save-content-view', 'POST', [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'device_id' => $request->input('device_id'),
            'device_type' => $request->input('device_type') ?: $request->header('device-type'),
        ]);
        $bridge->headers->replace($request->headers->all());

        return $this->recordFromRequest($bridge, $userId);
    }

    public function recordFromRequest(Request $request, ?int $userId = null): array
    {
        $contentType = $this->normalizeContentTypeInput((string) $request->input('content_type', ''));
        $contentId = (int) $request->input('content_id');
        $deviceType = $this->resolveDeviceType($request);
        $deviceId = $request->filled('device_id') ? substr((string) $request->input('device_id'), 0, 191) : null;

        if (! in_array($deviceType, self::DEVICE_TYPES, true)) {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'device-type must be one of: ' . implode(', ', self::DEVICE_TYPES),
            ];
        }

        if ($contentId < 1 || $contentType === '') {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'Invalid content_type or content_id',
            ];
        }

        // Resolve film/music/tvshow from series_networks (no DB schema change).
        if (in_array($contentType, ['tvshow', 'film', 'music', 'movie'], true)) {
            $entertainment = Entertainment::where('id', $contentId)
                ->whereIn('type', ['movie', 'tvshow'])
                ->where('status', 1)
                ->first();

            if (! $entertainment) {
                return [
                    'ok' => false,
                    'http' => 422,
                    'message' => 'Invalid content_type or content_id',
                ];
            }

            $contentType = $this->resolveEntertainmentContentType($entertainment);
        }

        if (! in_array($contentType, self::CONTENT_TYPES, true)) {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'Invalid content_type or content_id',
            ];
        }

        if (! $this->findPlayable($contentType, $contentId)) {
            return [
                'ok' => false,
                'http' => 422,
                'message' => 'Invalid content_type or content_id',
            ];
        }

        $throttled = $this->isThrottled($contentType, $contentId, $deviceType, $userId, $deviceId);
        $isUniqueUser = false;

        if (! $throttled) {
            $isUniqueUser = ! $this->hasPriorViewer($contentType, $contentId, $deviceType, $userId, $deviceId);

            ContentView::create([
                'content_type' => $contentType,
                'content_id' => $contentId,
                'user_id' => $userId,
                'device_type' => $deviceType,
                'device_id' => $deviceId,
            ]);
        }

        $this->forgetCountCache($contentType, $contentId);
        Cache::forget('content_view:summary:' . now()->year);

        return [
            'ok' => true,
            'http' => 200,
            'message' => $throttled ? 'View already counted' : 'View saved',
            'data' => [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'user_id' => $userId,
                'device_type' => $deviceType,
                'device_id' => $deviceId,
                'counted' => ! $throttled,
                'throttled' => $throttled,
                'is_unique_user' => $isUniqueUser,
                'view_count' => $this->getCountPayload($contentType, $contentId),
            ],
        ];
    }

    public function getCountPayload(string $contentType, int $contentId): array
    {
        $cacheKey = $this->countCacheKey($contentType, $contentId);

        return Cache::remember($cacheKey, 60, function () use ($contentType, $contentId) {
            $byDevice = $this->emptyDeviceCounts();
            $totalViews = 0;
            $uniqueUsers = 0;

            $rows = ContentView::query()
                ->whereIn('content_type', $this->contentTypeQueryValues($contentType))
                ->where('content_id', $contentId)
                ->select('device_type')
                ->selectRaw('COUNT(*) as total_views')
                ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
                ->groupBy('device_type')
                ->get();

            foreach ($rows as $row) {
                $device = (string) $row->device_type;
                if (! isset($byDevice[$device])) {
                    continue;
                }

                $deviceTotal = (int) $row->total_views;
                $deviceUnique = (int) $row->unique_users;
                $byDevice[$device] = [
                    'total_views' => $deviceTotal,
                    'unique_users' => $deviceUnique,
                ];
                $totalViews += $deviceTotal;
                $uniqueUsers += $deviceUnique;
            }

            return [
                'total_views' => $totalViews,
                'unique_users' => $uniqueUsers,
                'by_device' => $byDevice,
            ];
        });
    }

    public function getSummaryPayload(?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        return Cache::remember("content_view:summary:{$year}", 60, function () use ($year) {
            $baseQuery = ContentView::query()->whereYear('created_at', $year);

            $byDevice = $this->emptyDeviceCounts();
            $byContentType = [];

            foreach (self::CONTENT_TYPES as $type) {
                $byContentType[$type] = [
                    'total_views' => 0,
                    'unique_users' => 0,
                    'by_device' => $this->emptyDeviceCounts(),
                ];
            }

            $viewRows = (clone $baseQuery)
                ->select('content_type', 'device_type')
                ->selectRaw('COUNT(*) as total_views')
                ->groupBy('content_type', 'device_type')
                ->get();

            foreach ($viewRows as $row) {
                $type = $this->canonicalContentType((string) $row->content_type);
                $device = (string) $row->device_type;
                $views = (int) $row->total_views;

                if (! isset($byContentType[$type]) || ! isset($byDevice[$device])) {
                    continue;
                }

                $byContentType[$type]['by_device'][$device]['total_views'] += $views;
                $byContentType[$type]['total_views'] += $views;
                $byDevice[$device]['total_views'] += $views;
            }

            $uniqueTypeDevice = (clone $baseQuery)
                ->select('content_type', 'device_type')
                ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
                ->groupBy('content_type', 'device_type')
                ->get();

            foreach ($uniqueTypeDevice as $row) {
                $type = $this->canonicalContentType((string) $row->content_type);
                $device = (string) $row->device_type;
                $unique = (int) $row->unique_users;

                if (! isset($byContentType[$type]['by_device'][$device])) {
                    continue;
                }

                $byContentType[$type]['by_device'][$device]['unique_users'] += $unique;
            }

            $uniqueByType = (clone $baseQuery)
                ->select('content_type')
                ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
                ->groupBy('content_type')
                ->get();

            foreach ($uniqueByType as $row) {
                $type = $this->canonicalContentType((string) $row->content_type);
                if (! isset($byContentType[$type])) {
                    continue;
                }
                $byContentType[$type]['unique_users'] += (int) $row->unique_users;
            }

            $uniqueByDevice = (clone $baseQuery)
                ->select('device_type')
                ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
                ->groupBy('device_type')
                ->get();

            foreach ($uniqueByDevice as $row) {
                $device = (string) $row->device_type;
                if (! isset($byDevice[$device])) {
                    continue;
                }
                $byDevice[$device]['unique_users'] = (int) $row->unique_users;
            }

            $totalViews = array_sum(array_column($byDevice, 'total_views'));

            return [
                'year' => $year,
                'total_views' => $totalViews,
                'unique_users' => (int) (clone $baseQuery)
                    ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
                    ->value('unique_users'),
                'by_device' => $byDevice,
                'by_content_type' => $byContentType,
                'by_month' => $this->getMonthlyPayload($year),
            ];
        });
    }

    private function getMonthlyPayload(int $year): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'period' => sprintf('%04d-%02d', $year, $month),
                'total_views' => 0,
                'unique_users' => 0,
                'by_device' => $this->emptyDeviceCounts(),
                'by_content_type' => $this->emptyContentTypeCounts(),
            ];
        }

        $baseQuery = ContentView::query()->whereYear('created_at', $year);

        $viewRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month, device_type')
            ->selectRaw('COUNT(*) as total_views')
            ->groupByRaw('MONTH(created_at), device_type')
            ->get();

        foreach ($viewRows as $row) {
            $month = (int) $row->view_month;
            $device = (string) $row->device_type;
            if (! isset($months[$month]['by_device'][$device])) {
                continue;
            }

            $views = (int) $row->total_views;
            $months[$month]['by_device'][$device]['total_views'] = $views;
            $months[$month]['total_views'] += $views;
        }

        $contentTypeRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month, content_type, device_type')
            ->selectRaw('COUNT(*) as total_views')
            ->groupByRaw('MONTH(created_at), content_type, device_type')
            ->get();

        foreach ($contentTypeRows as $row) {
            $month = (int) $row->view_month;
            $type = $this->canonicalContentType((string) $row->content_type);
            $device = (string) $row->device_type;
            if (! isset($months[$month]['by_content_type'][$type]['by_device'][$device])) {
                continue;
            }

            $views = (int) $row->total_views;
            $months[$month]['by_content_type'][$type]['by_device'][$device]['total_views'] += $views;
            $months[$month]['by_content_type'][$type]['total_views'] += $views;
        }

        $uniqueMonthRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month')
            ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
            ->groupByRaw('MONTH(created_at)')
            ->get();

        foreach ($uniqueMonthRows as $row) {
            $month = (int) $row->view_month;
            if (! isset($months[$month])) {
                continue;
            }
            $months[$month]['unique_users'] = (int) $row->unique_users;
        }

        $uniqueMonthDeviceRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month, device_type')
            ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
            ->groupByRaw('MONTH(created_at), device_type')
            ->get();

        foreach ($uniqueMonthDeviceRows as $row) {
            $month = (int) $row->view_month;
            $device = (string) $row->device_type;
            if (! isset($months[$month]['by_device'][$device])) {
                continue;
            }
            $months[$month]['by_device'][$device]['unique_users'] = (int) $row->unique_users;
        }

        $uniqueMonthTypeRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month, content_type')
            ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
            ->groupByRaw('MONTH(created_at), content_type')
            ->get();

        foreach ($uniqueMonthTypeRows as $row) {
            $month = (int) $row->view_month;
            $type = $this->canonicalContentType((string) $row->content_type);
            if (! isset($months[$month]['by_content_type'][$type])) {
                continue;
            }
            $months[$month]['by_content_type'][$type]['unique_users'] += (int) $row->unique_users;
        }

        $uniqueMonthTypeDeviceRows = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as view_month, content_type, device_type')
            ->selectRaw($this->uniqueUserCountSql() . ' as unique_users')
            ->groupByRaw('MONTH(created_at), content_type, device_type')
            ->get();

        foreach ($uniqueMonthTypeDeviceRows as $row) {
            $month = (int) $row->view_month;
            $type = $this->canonicalContentType((string) $row->content_type);
            $device = (string) $row->device_type;
            if (! isset($months[$month]['by_content_type'][$type]['by_device'][$device])) {
                continue;
            }
            $months[$month]['by_content_type'][$type]['by_device'][$device]['unique_users'] += (int) $row->unique_users;
        }

        return array_values($months);
    }

    private function emptyContentTypeCounts(): array
    {
        $out = [];
        foreach (self::CONTENT_TYPES as $type) {
            $out[$type] = [
                'total_views' => 0,
                'unique_users' => 0,
                'by_device' => $this->emptyDeviceCounts(),
            ];
        }

        return $out;
    }

    private function emptyDeviceCounts(): array
    {
        $out = [];
        foreach (self::DEVICE_TYPES as $device) {
            $out[$device] = [
                'total_views' => 0,
                'unique_users' => 0,
            ];
        }

        return $out;
    }

    public function adminCellHtml(string $contentType, int $contentId): string
    {
        $contentType = $this->canonicalContentType($contentType);
        if (! in_array($contentType, self::CONTENT_TYPES, true)) {
            return '-';
        }

        $payload = $this->getCountPayload($contentType, $contentId);
        $total = (int) $payload['total_views'];
        if ($total < 1) {
            return '-';
        }

        $by = $payload['by_device'];
        $breakdown = sprintf(
            'Web %s · Android %s · iOS %s · Android TV %s · Roku %s · Fire TV %s',
            number_format($by['website']['total_views']),
            number_format($by['android']['total_views']),
            number_format($by['ios']['total_views']),
            number_format($by['android_tv']['total_views']),
            number_format($by['roku_tv']['total_views']),
            number_format($by['amazon_fire_tv']['total_views'])
        );

        return '<div>' . number_format($total) . '</div>'
            . '<div class="small text-muted">' . e($breakdown) . '</div>'
            . '<div class="small">Unique: ' . number_format((int) $payload['unique_users']) . '</div>';
    }

    /**
     * Resolve stored view content_type for an entertainment (tvshow/movie) from its networks.
     * e360films/e360film → film, e360music → music, otherwise tvshow (movie without those networks → film).
     */
    public function resolveEntertainmentContentType($entertainment): string
    {
        if ($this->entertainmentBelongsToNetworkKeys($entertainment, self::FILM_NETWORK_KEYS)) {
            return 'film';
        }

        if ($this->entertainmentBelongsToNetworkKeys($entertainment, self::MUSIC_NETWORK_KEYS)) {
            return 'music';
        }

        $type = strtolower((string) (is_array($entertainment)
            ? ($entertainment['type'] ?? '')
            : ($entertainment->type ?? '')));

        return $type === 'movie' ? 'film' : 'tvshow';
    }

    public function normalizeContentTypeInput(string $raw): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return '';
        }

        return self::CONTENT_TYPE_ALIASES[$value] ?? $value;
    }

    public function canonicalContentType(string $contentType): string
    {
        $value = strtolower(trim($contentType));
        if ($value === 'movie') {
            return 'film';
        }
        if ($value === 'vedio') {
            return 'video';
        }

        return $value;
    }

    private function contentTypeQueryValues(string $contentType): array
    {
        $contentType = $this->canonicalContentType($contentType);
        if ($contentType === 'film') {
            return ['film', 'movie'];
        }
        if ($contentType === 'video') {
            return ['video', 'vedio'];
        }

        return [$contentType];
    }

    private function entertainmentBelongsToNetworkKeys($entertainment, array $keys): bool
    {
        $networkId = is_array($entertainment)
            ? (string) ($entertainment['network_id'] ?? '')
            : (string) ($entertainment->network_id ?? '');

        $ids = array_values(array_filter(array_map('intval', explode(',', $networkId))));
        if ($ids === []) {
            return false;
        }

        $normalizedKeys = array_map(static function ($key) {
            return strtolower(preg_replace('/[\s\-_]+/', '', (string) $key));
        }, $keys);

        $networks = DB::table('series_networks')
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'slug']);

        foreach ($networks as $network) {
            $nameKey = strtolower(preg_replace('/[\s\-_]+/', '', (string) $network->name));
            $slugKey = strtolower(preg_replace('/[\s\-_]+/', '', (string) $network->slug));
            if (in_array($nameKey, $normalizedKeys, true) || in_array($slugKey, $normalizedKeys, true)) {
                return true;
            }
        }

        return false;
    }

    public function resolveDeviceType(Request $request): string
    {
        $raw = $request->input('device_type')
            ?: $request->header('device-type')
            ?: $request->header('Device-Type')
            ?: '';

        $mapped = $this->mapDeviceType((string) $raw);
        if ($mapped) {
            return $mapped;
        }

        return $this->deviceTypeFromUserAgent((string) $request->userAgent());
    }

    public function mapDeviceType(string $raw): string
    {
        $value = strtolower(trim(str_replace([' ', '-'], '_', $raw)));
        $aliases = [
            'website' => 'website',
            'web' => 'website',
            'desktop' => 'website',
            'laptop' => 'website',
            'android' => 'android',
            'android_phone' => 'android',
            'ios' => 'ios',
            'iphone' => 'ios',
            'ipad' => 'ios',
            'android_tv' => 'android_tv',
            'androidtv' => 'android_tv',
            'tv' => 'android_tv',
            'roku' => 'roku_tv',
            'roku_tv' => 'roku_tv',
            'rokutv' => 'roku_tv',
            'amazon_fire_tv' => 'amazon_fire_tv',
            'fire_tv' => 'amazon_fire_tv',
            'firetv' => 'amazon_fire_tv',
            'amazon_firetv' => 'amazon_fire_tv',
        ];

        return $aliases[$value] ?? '';
    }

    private function deviceTypeFromUserAgent(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'website';
        }

        if (preg_match('/roku/i', $userAgent)) {
            return 'roku_tv';
        }
        if (preg_match('/afts|aftb|aftt|aftm|amazon/i', $userAgent)) {
            return 'amazon_fire_tv';
        }
        if (preg_match('/android\s*tv|smart-tv|googletv|hbbtv/i', $userAgent)) {
            return 'android_tv';
        }
        if (preg_match('/iphone|ipad|ipod|ios/i', $userAgent)) {
            return 'ios';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'android';
        }

        return 'website';
    }

    private function findPlayable(string $contentType, int $contentId)
    {
        switch ($contentType) {
            case 'film':
            case 'music':
            case 'tvshow':
                return Entertainment::where('id', $contentId)
                    ->whereIn('type', ['movie', 'tvshow'])
                    ->where('status', 1)
                    ->first();
            case 'episode':
                return Episode::where('id', $contentId)->where('status', 1)->first();
            case 'video':
                return Video::where('id', $contentId)->where('status', 1)->first();
            case 'livetv':
                return LiveTvChannel::where('id', $contentId)->where('status', 1)->first();
            default:
                return null;
        }
    }

    private function isThrottled(
        string $contentType,
        int $contentId,
        string $deviceType,
        ?int $userId,
        ?string $deviceId
    ): bool {
        $query = ContentView::query()
            ->whereIn('content_type', $this->contentTypeQueryValues($contentType))
            ->where('content_id', $contentId)
            ->where('device_type', $deviceType)
            ->where('created_at', '>=', now()->subMinutes(self::THROTTLE_MINUTES));

        if (! empty($userId)) {
            $query->where('user_id', $userId);
        } elseif (! empty($deviceId)) {
            $query->where('device_id', $deviceId);
        } else {
            return false;
        }

        return $query->exists();
    }

    private function hasPriorViewer(
        string $contentType,
        int $contentId,
        string $deviceType,
        ?int $userId,
        ?string $deviceId
    ): bool {
        $query = ContentView::query()
            ->whereIn('content_type', $this->contentTypeQueryValues($contentType))
            ->where('content_id', $contentId)
            ->where('device_type', $deviceType);

        if (! empty($userId)) {
            $query->where('user_id', $userId);
        } elseif (! empty($deviceId)) {
            $query->where('device_id', $deviceId);
        } else {
            return false;
        }

        return $query->exists();
    }

    private function uniqueUserCountSql(): string
    {
        return "COUNT(DISTINCT CASE
            WHEN user_id IS NOT NULL AND user_id > 0 THEN CONCAT('u:', user_id)
            WHEN device_id IS NOT NULL AND device_id != '' THEN CONCAT('d:', device_id)
            ELSE CONCAT('r:', id)
        END)";
    }

    private function countCacheKey(string $contentType, int $contentId): string
    {
        return "content_view:{$contentType}:{$contentId}";
    }

    private function forgetCountCache(string $contentType, int $contentId): void
    {
        Cache::forget($this->countCacheKey($contentType, $contentId));
    }
}
