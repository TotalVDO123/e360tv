@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection


@section('content')
    <x-back-button-component route="backend.networks" />
    {{ html()->form('PUT', route('backend.network_update', $network->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-12 col-xl-3 position-relative">
                    {{ html()->label(__('messages.image'), 'Image')->class('form-label') }}
                    <div class="input-group btn-file-upload">
                        {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer1')->attribute('data-hidden-input', 'file_url1') }}

                        {{ html()->text('image_input1')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 1')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer1')->attribute('data-hidden-input', 'file_url1')->attribute('aria-describedby', 'basic-addon1') }}
                    </div>
                    
                       <?php
                                
                                 //$genre->file_url = setBaseUrlWithFileName($network->image,'image','networks');
                                //  $imageUrl = setBaseUrlWithFileName($row->image, 'image', 'series_networks');
                                
                                ?>
                    <div class="mb-3 uploaded-image" id="selectedImageContainer1">
                        @if ($network->image)
                            <img src="{{ setBaseUrlWithFileName($network->image,'image','series_networks') }}" class="img-fluid mb-2 box-preview-image">
                            <span class="remove-media-icon"
                                onclick="removeImage('selectedImageContainer1', 'file_url1', 'remove_image_flag1')">×</span>
                        @endif
                    </div>
                    {{ html()->hidden('file_url')->id('file_url1')->value($network->image) }}
                    {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}

                </div>
                <div class="col-xl-9">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-6">
                            <div class="mb-3">
                                
                                
                             
                                
                                
                                {{ html()->label(('Network Name') . '<span class="text-danger">*</span>', 'name')->class('form-label') }}
                                {{ html()->text('name', $network->name)->class('form-control')->id('name')->placeholder('Network Name')->attribute('required', 'required') }}
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="name-error">{{ __('messages.name_required') }}</div>
                            </div>
                            <div>
                                {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                                <div class="d-flex justify-content-between align-items-center form-control">
                                    {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0') }}
                                    <div class="form-check form-switch">
                                        {{ html()->hidden('status', 0) }}
                                        {{ html()->checkbox('status', $network->network_list_active)->class('form-check-input')->id('status')->value(1) }}
                                    </div>
                                </div>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3">
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>

    {{ html()->form()->close() }}

    @include('components.media-modal')
    <script>
        function removeImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainer1');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }
    </script>
@endsection
