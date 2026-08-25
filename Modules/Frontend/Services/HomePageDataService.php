<?php

namespace Modules\Frontend\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomePageDataService
{
    public const CACHE_TTL = 300;

    public const LIVE_STREAM_CACHE_TTL = 60;

    public function getLiveStreamChannels(): array
    {
        return Cache::remember('home_live_stream_channels', self::LIVE_STREAM_CACHE_TTL, function () {
            return $this->buildLiveStreamChannels();
        });
    }

    public function getNetworkChannelDataMap($networks, ?int $limit = null): array
    {
        $list = collect($networks);
        if (!is_null($limit)) {
            $list = $list->take(max(0, $limit));
        }

        $map = [];
        foreach ($list as $network) {
            $id = (int) (is_array($network) ? ($network['id'] ?? 0) : $network->id);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = getSeriesNetworkChannelData($id);
        }

        return $map;
    }

    protected function buildLiveStreamChannels(): array
    {
        $previousTz = date_default_timezone_get();
        date_default_timezone_set('America/Los_Angeles');

        try {
            $currentDateTime = date('Y-m-d H:i:s');
            $today = date('Y-m-d');
            $currentDay = date('l');
            $currentTime = date('H:i:s');

            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $currentDayIndex = array_search($currentDay, $weekDays, true);

            $rows = DB::table('live_tv_channel as c')
                ->join('live_tv_stream_content_mapping as m', 'c.id', '=', 'm.tv_channel_id')
                ->select('c.*', 'm.upcoming_date', 'm.upcoming_end_date', 'm.recurring_program')
                ->where('c.status', 1)
                ->whereNull('c.deleted_at')
                ->whereNull('m.deleted_at')
                ->where(function ($q) use ($currentDateTime) {
                    $q->where(function ($q2) use ($currentDateTime) {
                        $q2->where('m.recurring_program', 0)
                            ->where('m.upcoming_end_date', '>=', $currentDateTime);
                    })->orWhere('m.recurring_program', 1);
                })
                ->get();

            $classified = $rows->map(function ($item) use ($currentDateTime, $currentTime, $today, $weekDays, $currentDayIndex) {
                $item = (array) $item;

                if ($item['recurring_program'] == 1) {
                    $showDay = date('l', strtotime($item['upcoming_date']));
                    $startTime = date('H:i:s', strtotime($item['upcoming_date']));
                    $endTime = date('H:i:s', strtotime($item['upcoming_end_date']));
                    $showDayIndex = array_search($showDay, $weekDays, true);
                    $dayRank = ($showDayIndex - $currentDayIndex + 7) % 7;

                    if ($dayRank === 0) {
                        if ($startTime <= $currentTime && $endTime >= $currentTime) {
                            $item['sort_order'] = 0;
                        } elseif ($startTime > $currentTime) {
                            $item['sort_order'] = 1;
                        } else {
                            $item['sort_order'] = 2;
                        }
                    } else {
                        $item['sort_order'] = 1;
                    }

                    $item['day_rank'] = $dayRank;
                    $item['sort_upcoming'] = date('Y-m-d', strtotime($today . ' +' . $dayRank . ' days')) . ' ' . $startTime;
                } else {
                    $upcomingDate = date('Y-m-d', strtotime($item['upcoming_date']));
                    $dayDiff = (int) floor((strtotime($upcomingDate) - strtotime($today)) / 86400);

                    if ($dayDiff === 0) {
                        if ($item['upcoming_date'] <= $currentDateTime && $item['upcoming_end_date'] >= $currentDateTime) {
                            $item['sort_order'] = 0;
                        } elseif ($item['upcoming_date'] > $currentDateTime) {
                            $item['sort_order'] = 1;
                        } else {
                            $item['sort_order'] = 2;
                        }
                        $item['day_rank'] = 0;
                    } elseif ($dayDiff > 0) {
                        $item['sort_order'] = 1;
                        $item['day_rank'] = $dayDiff;
                    } else {
                        $item['sort_order'] = 2;
                        $item['day_rank'] = 999;
                    }

                    $item['sort_upcoming'] = $item['upcoming_date'];
                }

                return $item;
            });

            $liveAndNext = $classified->filter(function ($item) {
                return in_array($item['sort_order'], [0, 1], true);
            });

            $todayLiveAndNext = $liveAndNext->filter(function ($item) {
                return (int) $item['day_rank'] === 0;
            });

            if ($todayLiveAndNext->isNotEmpty()) {
                $data_channels = $todayLiveAndNext;
            } else {
                $nextDayRank = $liveAndNext->min('day_rank');

                $data_channels = $nextDayRank !== null
                    ? $liveAndNext->filter(function ($item) use ($nextDayRank) {
                        return (int) $item['day_rank'] === (int) $nextDayRank;
                    })
                    : collect();
            }

            return $data_channels
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['sort_upcoming', 'asc'],
                ])
                ->take(18)
                ->values()
                ->toArray();
        } finally {
            date_default_timezone_set($previousTz);
        }
    }
}
