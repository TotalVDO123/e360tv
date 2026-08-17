@extends('frontend::layouts.guest')

@section('content')
<div class="list-page">
    <div class="movie-lists section-spacing-bottom">
        <div class="container-fluid">
            <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="entertainment-list"></div>
            <div class="card-style-slider shimmer-container">
                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                    @for ($i = 0; $i < 12; $i++)
                        <div class="shimmer-container col mb-3">
                            @include('components.card_shimmer_movieList')
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

@include('frontend::components.partials.hover-modal-scripts')

<script src="{{ asset('js/entertainment.min.js') }}" defer></script>

<script>
    const noDataImageSrc = '{{ asset('img/NoData.png') }}';
    const shimmerContainer = document.querySelector('.shimmer-container');
    const EntertainmentList = document.getElementById('entertainment-list');
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    const per_page = 12;
    const networkId = {{ $networkId }};
    const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

    const apiUrl = `${baseUrl}/api/v3/embed/tvshow-list?per_page=${per_page}&networkId=${networkId}`;

    const showNoDataImage = () => {
        shimmerContainer.innerHTML = '';
        const noDataImage = document.createElement('img');
        noDataImage.src = noDataImageSrc;
        noDataImage.alt = 'No Data Found';
        noDataImage.style.display = 'block';
        noDataImage.style.margin = '0 auto';
        shimmerContainer.appendChild(noDataImage);
    };

    const loadData = async () => {
        if (!hasMore || isLoading) return;
        isLoading = true;
        shimmerContainer.style.display = '';
        try {
            const response = await fetch(`${apiUrl}&page=${currentPage}`);
            const data = await response.json();
            if (data?.html) {
                EntertainmentList.insertAdjacentHTML(currentPage === 1 ? 'afterbegin' : 'beforeend', data.html);
                if (window.initTrailerHover) window.initTrailerHover();
                hasMore = !!data.hasMore;
                if (hasMore) currentPage++;
                shimmerContainer.style.display = 'none';
            } else {
                showNoDataImage();
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showNoDataImage();
        } finally {
            isLoading = false;
        }
    };

    const handleScroll = () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && hasMore) {
            loadData();
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadData();
        window.addEventListener('scroll', handleScroll);
    });
</script>
@endsection
