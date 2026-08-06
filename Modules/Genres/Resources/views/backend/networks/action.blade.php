<div class="d-flex gap-2 align-items-center justify-content-end">

    @if(!$row->trashed())
        @hasPermission('edit_genres')
        <a  class="btn btn-warning-subtle btn-sm fs-4" data-bs-toggle="tooltip" title="{{__('messages.edit')}}" href="{{ route('backend.network_edit', $row->id) }}"> <i class="ph ph-pencil-simple-line align-middle"></i></a>
        @endhasPermission

        <?php /* ?>
    
        @hasPermission('delete_genres')
        <a href="{{route('backend.genres_destroy', $row->id)}}" id="delete-locations-{{$row->id}}" class="btn btn-secondary-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE" data-token="{{csrf_token()}}" data-bs-toggle="tooltip" title="{{__('messages.delete')}}" data-confirm="{{ __('messages.are_you_sure?') }}"> <i class="ph ph-trash align-middle"></i></a>
        @endhasPermission
        
         <?php */ ?>
    @else
    <?php /* ?>
    @hasPermission('restore_genres')
        <a class="btn btn-success-subtle btn-sm fs-4 restore-tax" data-confirm-message="{{__('messages.are_you_sure_restore')}}"
    data-success-message="{{__('messages.restore_form',  ['form' => 'Genres'])}}" data-bs-toggle="tooltip" title="{{__('messages.restore')}}" href="{{ route('backend.genres.restore', $row->id) }}">
            <i class="ph ph-arrow-clockwise align-middle"></i>
        </a>
        @endhasPermission
        @hasPermission('force_delete_genres')
        <a href="{{route('backend.genres.force_delete', $row->id)}}" id="delete-locations-{{$row->id}}" class="btn btn-danger-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE" data-token="{{csrf_token()}}" data-bs-toggle="tooltip" title="{{__('messages.force_delete')}}" data-confirm="{{ __('messages.are_you_sure?') }}"> <i class="ph ph-trash align-middle"></i></a>
        @endhasPermission
         <?php */ ?>
    @endif
</div>
