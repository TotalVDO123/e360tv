@extends ('backend.layouts.app')



@section('content')


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
 
    <div class="card">
        <div class="card-body">

<div>&nbsp;</div>
<div>&nbsp;</div>
<div><strong> Episodes List for {{ $seriesdata[0]->name  }}</strong></div>
<div>&nbsp;</div>
<div>&nbsp;</div>
 <x-back-button-component route="backend.networks" />
          <table class="table table-bordered" id="sortableTable">
    <thead>
        <tr>
            <th width="60">Order</th>
            <th>Image</th>
            <th>Name</th>
           
        </tr>
    </thead>

    <tbody id="sortable">
        @foreach($episodes_order as $row)
            <tr data-id="{{ $row->id }}">
                <td>
                    <i class="fa fa-bars"></i>
                </td>
                
                <td>
                <img src="{{ $imageUrl = setBaseUrlWithFileName($row->poster_url , 'image', 'tvshow/episode');  }}"  width="50" height="50">
                </td>
                    
                <td>{{ $row->name }}</td> 
                
               
            </tr>
        @endforeach
    </tbody>
</table>

        
		
		
		</div>
        <div class="card-footer">
           
        </div>
    </div>
    
    
    
    
<script>

new Sortable(document.getElementById('sortable'), {
    animation: 150,
    onEnd: function () {

        let sno_order  = [];

        $('#sortable tr').each(function(index) {
            sno_order.push({
                id: $(this).data('id'),
                sno_order : index + 1
            });
        });

        $.ajax({
            url: "{{ url('app/networks/update-order-episode') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                sno_order : sno_order
            },
            success: function(response) {
                console.log(response);
            }
        });
    }
});



</script>


    
    
@endsection


@push('after-scripts-end')
@endpush

