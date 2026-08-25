<?php

namespace Modules\Frontend\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeSectionController extends Controller
{
    public function network($id): JsonResponse
    {
        $networkId = (int) $id;
        $network = DB::table('series_networks')
            ->select('id', 'name', 'slug')
            ->where('id', $networkId)
            ->where('network_list_active', 1)
            ->first();

        if (!$network) {
            return response()->json(['html' => '', 'cardMovieDataMap' => []]);
        }

        $channel_data = getSeriesNetworkChannelData($networkId);
        $cardMovieDataMap = [];

        foreach ($channel_data as $item) {
            $mapKey = ($item['type'] ?? 'tvshow') . ':' . $item['id'];
            $cardMovieDataMap[$mapKey] = slimCardMovieData($item);
        }

        $html = '';
        if (!empty($channel_data)) {
            $html = view('frontend::components.section.tv_series_network_row', [
                'category' => $network,
                'channel_data' => $channel_data,
                'omit_movie_data' => true,
            ])->render();
        }

        return response()->json([
            'html' => $html,
            'cardMovieDataMap' => $cardMovieDataMap,
        ]);
    }
}
