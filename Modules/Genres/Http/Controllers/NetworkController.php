<?php

namespace Modules\Genres\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Genres\Http\Requests\GenresRequest;
//use Modules\Genres\Services\GenreService;
use Yajra\DataTables\DataTables;
use Modules\Genres\Models\Networks;
use App\Trait\ModuleTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
//use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    protected string $exportClass = '\App\Exports\GenresExport';
    protected $genreService;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
      
    }
    
    
    
    
    //  public function network_list(Request $request)
    //{networks
    //}

    
    

    public function index(Request $request)
    {
        $module_action = 'List';
        $export_import = true;
        $module_title='Networks';
        $module_name='network';

        $filter = [
            'status' => $request->status,
        ];

        $export_columns = [
            [
                'value' => 'name',
                'text' => __('messages.name'),
            ],
            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ],
        ];
        $export_url = route('backend.genres.export');

         


        return view('genres::backend.networks.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','module_title','module_name'));
    }


    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Networks';
        Cache::flush();
        return $this->performBulkAction(Genres::class, $ids, $actionType, $moduleName);
    }
    
    
    
    public function network_index_data(Datatables $datatable, Request $request)
{
    $filter = $request->filter;

   $query = Networks::query()
    ->select([
        'id',
        'order',

        'name',
        'image',
        'banner_image',
        'slug',
        
        'network_list_active',
        'created_at',
        'updated_at',
    ])
    ->orderBy('order', 'ASC');

    return $datatable->eloquent($query)

        ->addColumn('check', function ($row) {
            return "
        <input type='checkbox'
            class='form-check-input select-table-row'
            id='datatable-row-{$row->id}'
            name='datatable_ids[]'
            value='{$row->id}'
            data-type='series_networks'
            onclick='dataTableRowCheck({$row->id}, this)'>
    ";
        })

        ->editColumn('image', function ($row) {

            $imageUrl = setBaseUrlWithFileName($row->image, 'image', 'series_networks');

            return view('components.image-name', [
                'image' => $imageUrl,
                'name'  => $row->name,
            ])->render();
        })

       
  
       

        ->editColumn('updated_at', function ($row) {

            $diff = Carbon::now()->diffInHours($row->updated_at);

            return $diff < 25
                ? $row->updated_at->diffForHumans()
                : $row->updated_at->isoFormat('llll');
        })
        
        
        /*
        ->editColumn('status', function ($row) {
            return $row->network_list_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>';
        })
*/


        ->editColumn('network_list_active', function ($row) {
                $checked = $row->network_list_active ? 'checked="checked"' : '';
                $disabled = $row->trashed() ? 'disabled' : '';
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . url('app/network-update-status/'.$row->id)  . '"
                            data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                            id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                ';
            })

        
        
        
        
          ->editColumn('order_your_shows', function ($row) {
             
               return '
                        <a href="' . url('app/network-series-order/' . $row->id) . '"
                            class="d-flex align-items-center gap-1">
                            Order Your Shows
                        </a>
                    ';
            })

        
        
        
        
         ->addColumn('action', function ($row) {
            return view('genres::backend.networks.action', compact('row'));
        })

        ->orderColumns(['id'], '-:column $1')

        ->rawColumns([
            'check',
            'image',
            'network_list_active',
            'order_your_shows',
            'action',
         
        ])

        ->toJson();
}
    

  

    public function network_update_status(Request $request, $id)
    {
        
        //echo "===========";
        
        //echo "===========".$request->status;
        
       /// exit;
        
        
        ///$this->genreService->updateGenre($id, ['status' => $request->status]);
        
        
         $network = Networks::findOrFail($id);

        $network->update([
            'network_list_active' => $request->status,
        ]);
        
        
        
        
        
        return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
    }

    public function network_create(Request $request)
    {
        $module_title ='Networks';
        $page_type='network';

        return view('genres::backend.networks.create', compact('module_title','page_type'));


    }

    public function network_store(Request $request)
    {
        $data = $request->all();
       $file_url = extractFileNameFromUrl($data['file_url'],'networks');
       
       ///echo "===============". $data['file_url'];
       ///exit;
       
       $user = auth()->user();

        $userId = $user->id;
       
       ///echo "=============".$userId;
       
      /// print_r($data);
      /// exit;
       
        ////$this->genreService->createGenre($data);
        
        
        $network = new Networks();

    $network->order =0;
    $network->parent_id = 0;
    $network->name = $request->name;
    $network->image = $file_url;
    $network->banner_image =$file_url;
    $network->slug = Str::slug( $request->name);
    $network->network_list_active = 1;
    $network->updated_by= $userId; 
    $network->created_at = date('Y-m-d H:i:s');
    $network->updated_at = date('Y-m-d H:i:s');
    //$network->in_home = $request->in_home ?? 0;
    //$network->footer = $request->footer ?? 0;
    //$network->banner = $request->banner ?? 0;
    //$network->in_menu = $request->in_menu ?? 0;
    //$network->network_list_active = $request->network_list_active ?? 1;

    $network->save();
        $message ='Networks Successfully create';
        return redirect()->route('backend.networks')->with('success', $message);
    }

    public function show($id)
    {
        return view('genres::show');
    }

    public function network_edit($id)
    {
        //$genre = $this->genreService->getGenreById($id);
        
         $network = Networks::findOrFail($id);
        
        $module_title = 'Edit Network';
        $page_type='networks';
        return view('genres::backend.networks.edit', compact('network','module_title','page_type'));
    }

    public function network_update(Request $request, $id)
    {
        $data = $request->all();

        $file_url = extractFileNameFromUrl($data['file_url'],'networks');

        ///$genre = $this->genreService->getGenreById($id);
        ///$this->genreService->updateGenre($id, $data);
        
        
         $network = Networks::findOrFail($id);

    
    
    $network->name = $request->name;
      $network->image = $file_url;
    $network->banner_image =$file_url;
    $network->slug = $request->slug
        ? Str::slug($request->slug)
        : Str::slug($request->name);

    $network->network_list_active = $request->status ?? 0;
    $network->updated_by = auth()->id();
     $network->created_at = date('Y-m-d H:i:s');
    $network->updated_at = date('Y-m-d H:i:s');
     $network->save();
        
        $message = 'Networks Successfully Updated';
        return redirect()->route('backend.networks')->with('success', $message);
    }

    public function destroy($id)
    {
        $this->genreService->deleteGenre($id);
        $message = __('messages.delete_form_genre', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $this->genreService->restoreGenre($id);
        $message = __('messages.restore_form_genre', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete($id)
    {
        $this->genreService->forceDeleteGenre($id);
        $message = __('messages.permanent_delete_form_genre', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }
    
    
     public function network_order_list()
    {
       
       
        $module_title='Network Order Update';
        $module_name='network';
       
            $networks = Networks::select(
            'id',
            'order',
            'name',
            'image',
            'slug',
            'network_list_active',
          
        )
        ->orderBy('order', 'ASC')
        ->get();
       
       
     /// print_r($networks);
       
        return view('genres::backend.networks.network_order_list', compact('networks','module_title'));
       
    }
    
    
    
    
   public function updateOrder(Request $request)
    {
        DB::transaction(function () use ($request) {
    
            foreach ($request->order as $item) {
    
                Networks::where('id', $item['id'])
                    ->update([
                        'order' => $item['order']
                    ]);
    
            }
    
        });
    
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.'
        ]);
    }
    
    
    
    
    
    
    
    
       public function network_series_order($id=0)
    {
       
     
       
        //$network_name='network';
            $network_details = Networks::select(
            'id',
            'order',
            'name',
            'image',
            'slug',
            'network_list_active',
          
        )
         ->where('id',$id)
        ->get();

       
     
       
      $module_title='Series Order';  
      $series_order = DB::table('entertainments')
    ->whereRaw('FIND_IN_SET(?, network_id)', [$id])
    ->where('status', 1)
    ->orderBy('sno_order', 'ASC')
    ->get();

       
       
       
   
       
        return view('genres::backend.networks.network_series_order', compact('network_details','series_order','module_title'));
       
    }
    
  
    
    
    
     public function update_order_series(Request $request)
    {
        foreach ($request->sno_order as $item) {

    DB::table('entertainments')
        ->where('id', $item['id'])
        ->update([
            'sno_order' => $item['sno_order']
        ]);
    }
    
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.'
        ]);
    }
    
    
    
    
    
    public function network_episode_order($id=0, $seasion_id=0)
    {
       
     
        $seriesdata = DB::table('entertainments')
    ->where('id', $id)
    ->where('status', 1)
    ->get();
      
      
     $network_id=$id;
       
      $module_title='Episodes Order';  
      $episodes_order = DB::table('episodes')
    ->where('entertainment_id', $id)
    ->where('season_id', $seasion_id)
    ->where('status', 1)
    ->orderBy('episode_number', 'ASC')
    ->get();


    


        return view('genres::backend.networks.network_episodes_order', compact('episodes_order','seriesdata','module_title','network_id'));
       
    }
    
  
      public function update_order_episode(Request $request)
    {
        foreach ($request->sno_order as $item) {

    DB::table('episodes')
        ->where('id', $item['id'])
        ->update([
            'episode_number' => $item['sno_order']
        ]);
    }
    
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.'
        ]);
    }
    
    
    
    	public function network_season_order($id=0)
    {
       
      $module_title='Season Order';  
       $seasons_order = DB::table('seasons')
    ->where('entertainment_id', $id)
	 ->where('status', 1)
    ->orderBy('season_index', 'ASC')
    ->get();

        return view('genres::backend.networks.network_season_order', compact('seasons_order','module_title'));
       
    }
    
	
	
	  public function update_order_season(Request $request)
    {
        foreach ($request->sno as $item) {

			DB::table('seasons')
			->where('id', $item['id'])
			->update([
				'season_index' => $item['sno']
			]);
		}
    
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.'
        ]);
    }

    
    
    
    

}
