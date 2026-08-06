<?php

namespace Modules\Genres\Models;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\EntertainmentGenerMapping;

class Networks extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'series_networks';

    protected $fillable = 
	['order',
	 'parent_id',
	 'name',
	 'image',
	 'banner_image',
	 'slug',
	 'in_home',
	 'footer',
	 'banner',
	 'in_menu',
	 'network_list_active',
	 'created_at',
	 'updated_at',
	 'deleted_at', 
	 'updated_by',
	 'created_by'
	 
	];

/*
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = slug_format(trim($value));

        if (empty($value)) {
            $this->attributes['slug'] = slug_format(trim($this->attributes['name']));
        }
    }


    public function entertainmentGenerMappings()
    {
        return $this->hasMany(EntertainmentGenerMapping::class,'genre_id','id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($genre) {

            if ($genre->isForceDeleting()) {

                $genre->entertainmentGenerMappings()->forcedelete();

            } else {
                $genre->entertainmentGenerMappings()->delete();
             }

        });

        static::restoring(function ($genre) {

            $genre->entertainmentGenerMappings()->withTrashed()->restore();
            
        });
    }

  */  
}