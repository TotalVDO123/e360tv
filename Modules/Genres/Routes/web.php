<?php

use Illuminate\Support\Facades\Route;
use Modules\Genres\Http\Controllers\GenresController;
use Modules\Genres\Http\Controllers\NetworkController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth','admin']], function () {

    Route::group(['prefix' => '/genres', 'as' => 'genres.'], function () {
        Route::get('/index_list', [GenresController::class, 'index_list'])->name('index_list');
        Route::get('/index_data', [GenresController::class, 'index_data'])->name('index_data');
        Route::get('export', [GenresController::class, 'export'])->name('export');
        Route::get('/trashed', [GenresController::class, 'trashed'])->name('trashed');
        Route::post('bulk-action', [GenresController::class, 'bulk_action'])->name('bulk_action');
        Route::post('update-status/{id}', [GenresController::class, 'update_status'])->name('update_status');
        Route::post('restore/{id}', [GenresController::class, 'restore'])->name('restore');
        Route::delete('force-delete/{id}', [GenresController::class, 'forceDelete'])->name('force_delete');
    });
    Route::resource('genres', GenresController::class)->names('genres');
   
    Route::get('networks', [NetworkController::class,'index'] )->name('networks');
    
      Route::get('/network_index_data', [NetworkController::class, 'network_index_data'])->name('network_index_data');
    
         Route::post('networks/update-order', [NetworkController::class, 'updateOrder'])->name('updateOrder');
         Route::get('network-order-list', [NetworkController::class, 'network_order_list'])->name('network_order_list');
        
         Route::post('network-update-status/{id}', [NetworkController::class, 'network_update_status'])->name('network_update_status');
         
         Route::get('network-create', [NetworkController::class, 'network_create'])->name('network_create');
         Route::post('network-store', [NetworkController::class, 'network_store'])->name('network_store');
          
          
         Route::get('network-edit/{id}', [NetworkController::class, 'network_edit'])->name('network_edit');
         Route::PUT('network-update/{id}', [NetworkController::class, 'network_update'])->name('network_update');
         
         Route::get('network-destroy/{id}', [NetworkController::class, 'network_destroy'])->name('network_destroy');
         
         Route::get('network-series-order/{id}', [NetworkController::class, 'network_series_order'])->name('network_series_order'); 
          Route::post('networks/update-order-series', [NetworkController::class, 'update_order_series'])->name('update_order_series');
          
          
    
        Route::get('network-episode-order/{id}/{seasion_id}', [NetworkController::class, 'network_episode_order'])->name('network_episode_order'); 
         Route::post('networks/update-order-episode', [NetworkController::class, 'update_order_episode'])->name('update_order_episode');
         
         
        Route::get('network-season-order/{id}', [NetworkController::class, 'network_season_order'])->name('network_season_order'); 	
		Route::post('networks/update-order-season', [NetworkController::class, 'update_order_season'])->name('update_order_season');

         
    
    ////Route::get('networks-create', [NetworkController::class,'create'] )->name('networks-create');
});


 
