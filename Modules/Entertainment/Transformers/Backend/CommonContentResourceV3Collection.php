<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\Watchlist;

class CommonContentResourceV3Collection extends ResourceCollection
{
    public $collects = CommonContentResourceV3::class;

    public function toArray($request)
    {
        if (!$request->attributes->get('skip_user_content_flags')) {
            $this->attachUserFlags($request);
        }

        return parent::toArray($request);
    }

    protected function attachUserFlags($request): void
    {
        $models = [];
        foreach ($this->collection as $resource) {
            if (is_object($resource) && isset($resource->resource) && is_object($resource->resource)) {
                $models[] = $resource->resource;
            }
        }

        if ($models === []) {
            return;
        }

        $userId = $request->input('user_id') ?? auth()->id();
        $watchKeys = [];
        $purchasedKeys = [];

        if ($userId) {
            $profileId = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $idsByType = [];
            $ppvPairs = [];

            foreach ($models as $model) {
                $id = $model->id ?? null;
                if (!$id) {
                    continue;
                }
                $type = $model->type ?? 'movie';
                $idsByType[$type][] = $id;
                if (($model->movie_access ?? '') === 'pay-per-view') {
                    $ppvPairs[] = ['id' => $id, 'type' => $type];
                }
            }

            if ($idsByType !== []) {
                $watchQuery = Watchlist::query()
                    ->where('user_id', $userId)
                    ->where('profile_id', $profileId)
                    ->where(function ($q) use ($idsByType) {
                        foreach ($idsByType as $type => $ids) {
                            $ids = array_values(array_unique($ids));
                            $q->orWhere(function ($q2) use ($type, $ids) {
                                $q2->where('type', $type)->whereIn('entertainment_id', $ids);
                            });
                        }
                    });

                foreach ($watchQuery->get(['entertainment_id', 'type']) as $row) {
                    $watchKeys[$row->type.':'.$row->entertainment_id] = true;
                }
            }

            if ($ppvPairs !== []) {
                $purchasedKeys = Entertainment::arePurchased($ppvPairs, $userId);
            }
        }

        foreach ($models as $model) {
            $id = $model->id ?? null;
            $type = $model->type ?? 'movie';
            $key = $type.':'.$id;
            $model->setAttribute('is_watch_list', isset($watchKeys[$key]));
            $isPpv = ($model->movie_access ?? '') === 'pay-per-view';
            $model->setAttribute('is_purchased', $isPpv && !empty($purchasedKeys[$key]));
        }
    }
}
