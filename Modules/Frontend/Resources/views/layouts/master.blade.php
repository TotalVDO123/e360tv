<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark"
    dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}"
    data-bs-theme-color={{ getCustomizationSetting('theme_color') }}>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{ url('/') }}" />
    @php
        $faviconUrl = GetSettingValue('favicon') ? setBaseUrlWithFileName(GetSettingValue('favicon'),'image','logos') : asset('img/logo/favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', GetSettingValue('app_name'))</title>

    @include('frontend::layouts.head')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
     <link rel="stylesheet" href="{{ asset('modules/frontend/custom_style.css') }}">
    
    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">
    @stack('phosphor-extra')


    @include('frontend::components.partials.head.plugins')
    @stack('after-styles')

    @php
        $frontendJsMessages = trans('frontend-js');
        if (!is_array($frontendJsMessages)) {
            $frontendJsMessages = ['dismiss' => __('messages.dismiss')];
        }
    @endphp
    <script>
        window.localMessagesUpdate = {
            messages: @json($frontendJsMessages)
        };
    </script>

    <!-- Slider Fallback CSS - Display content horizontally when slick fails -->
    <style>
        /* Fallback styles for when slick carousel fails to initialize */
        .slick-general:not(.slick-initialized) {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 12px !important;
            padding: 0 !important;
            margin: 0 !important;

        }

        .slick-general:not(.slick-initialized)>* {
            flex: 0 0 260px !important;
            /* desktop card width */
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            display: block !important;
        }

        /* Hide slick arrows and dots when not initialized */
        .slick-general:not(.slick-initialized) .slick-arrow,
        .slick-general:not(.slick-initialized) .slick-dots {
            display: none !important;
        }

        /* Responsive fallback - match slick breakpoints */

        @media (max-width: 768px) {
            .slick-general:not(.slick-initialized)>* {
                flex-basis: 180px !important;
                width: 180px !important;
                min-width: 180px !important;
                max-width: 180px !important;
            }
        }

        @media (max-width: 576px) {
            .slick-general:not(.slick-initialized)>* {
                flex-basis: 160px !important;
                width: 160px !important;
                min-width: 160px !important;
                max-width: 160px !important;
            }
        }

        /* Ensure proper spacing for card-style-slider */
        .card-style-slider .slick-general:not(.slick-initialized) {
            margin-bottom: 3.75rem !important;
            overflow: visible !important;
            /* add bottom space between sections */
        }

        .card-style-slider .slick-general:not(.slick-initialized)>* {
            margin: 0 6px !important;
        }
    </style>

    <style>
        /* ========== Full Page Loader Styles ========== */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bs-body-bg);
            /* matches your dark theme */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loader-wrapper {
            text-align: center;
        }



        .loader-text {
            color: #fff;
            font-family: "Roboto", sans-serif;
            font-size: 18px;
            letter-spacing: 1px;
        }
    </style>

</head>

<body class="d-flex flex-column min-vh-100 {{ Route::currentRouteName() == 'search' ? 'search-page' : '' }}">

    <div id="page-loader">
        <div class="loader-wrapper">
            @php
                $loader_gif = GetSettingValue('loader_gif') ? setBaseUrlWithFileName(GetSettingValue('loader_gif'), 'image', 'logos') : asset('img/logo/loader.gif');
            @endphp
            <img src="{{ $loader_gif }}" alt="Loading..."
                class="loader-gif" width="100" height="100">
            {{-- <div class="loader-text">Loading...</div> --}}
        </div>
    </div>
    @include('frontend::layouts.header')

    <main class="flex-fill">
        @yield('content')
    </main>
    {{-- @include('frontend::components.card.card_detail') --}}

    @include('frontend::layouts.footer')

    @include('frontend::components.partials.scripts.plugins')


    @include('frontend::components.partials.back-to-top')
    <script src="{{ mix('modules/frontend/script.js') }}" defer></script>




    <script>
        document.addEventListener("readystatechange", () => {
            if (document.readyState === "complete") {
                hideLoader();
            }
        });

        window.addEventListener("load", () => {
            hideLoader();
        });
        // Hide loader smoothly
        function hideLoader() {
            const loader = document.getElementById("page-loader");
            if (!loader) return;
            loader.classList.add("hidden");
            setTimeout(() => loader.remove(), 600);
        }

        setTimeout(() => {
            const loader = document.getElementById("page-loader");
            if (loader) hideLoader();
        }, 8000);
    </script>




    @if (session('success') || session('purchase_success'))
        @include('frontend::components.partials.sweetalert')
    @endif
    @stack('sweetalert')

    @if (session('success'))
        <script>
            const messages = {
                logout_all_title: "{{ __('messages.logout_all_title') }}",
                logout_all_text: "{{ __('messages.logout_all_text') }}",
                logout_all_button: "{{ __('messages.logout_all_button') }}",
                continue_button: "{{ __('messages.continue_button') }}",
                lbl_plan: "{{ __('messages.lbl_plan') }}",
                lbl_amount: "{{ __('messages.lbl_amount') }}",
                lbl_valid_until: "{{ __('messages.lbl_valid_until') }}",
            };
            document.addEventListener('DOMContentLoaded', function() {
                document.body.setAttribute('data-swal2-theme', 'dark');
                Swal.fire({
                    icon: 'success',
                    title: "{{ __('messages.payment_successful_title') }}",
                    html: `
            <div class="text-center">
                <p>{{ __('messages.subscription_activated_message') }}</p>
                <div class="mt-3">
                    <p><strong>${messages.lbl_plan}:</strong> {{ session('success.plan_name') }}</p>
                    <p><strong>${messages.lbl_amount}:</strong> {{ session('success.amount') }}</p>
                    <p><strong>${messages.lbl_valid_until}:</strong> {{ session('success.valid_until') }}</p>
                </div>
            </div>
        `,
                    showConfirmButton: true,
                    confirmButtonText: messages.continue_button,
                    confirmButtonColor: '#e50914',
                    iconColor: '#e50914',
                    customClass: {
                        icon: 'swal2-icon-red'
                    }
                }).then(function() {
                    Swal.fire({
                        title: messages.logout_all_title,
                        text: messages.logout_all_text,
                        icon: 'question',
                        showCancelButton: false,
                        confirmButtonText: messages.logout_all_button,
                        confirmButtonColor: '#e50914'
                    }).then(function(result) {
                        const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
                        fetch(baseUrl + '/api/logout-all-data', {
                                method: 'GET',
                                credentials: 'same-origin'
                            })
                            .then(function() {
                                window.location.reload();
                            })
                            .catch(function() {
                                console.error('Error:', error);
                            });
                    });
                });
            });
        </script>

        <style>
            .swal2-icon-red {
                border-color: var(--bs-primary) !important;
                color: var(--bs-primary) !important;
            }
        </style>
    @endif



    @if (session('purchase_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const purchaseMessages = {
                    purchase_successful_title: "{{ __('messages.purchase_successful_title') }}",
                    purchase_successful_message: "{{ __('messages.purchase_successful_message') }}",
                    enjoy_until: "{{ __('messages.enjoy_until') }}",
                    begin_watching: "{{ __('messages.begin_watching') }}",
                };
                document.body.setAttribute('data-swal2-theme', 'dark');
                Swal.fire({
                    icon: 'success',
                    html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px;"></div>
                    <h2 class="text-heading" style="margin: 15px 0 10px; font-size: 21px;">${purchaseMessages.purchase_successful_title}</h2>
                    <p class="text-body" style="font-size: 16px;">${purchaseMessages.purchase_successful_message} {{ session('movie_name') }}.</p>
                    <p class="text-body" style="font-size: 14px;">${purchaseMessages.enjoy_until} {{ session('view_expiry') }}.</p>
                </div>
            `,
                    showConfirmButton: true,
                    confirmButtonText: purchaseMessages.begin_watching,
                    confirmButtonColor: 'var(--bs-primary)',
                    iconColor: 'var(--bs-primary)', // Added to make the success icon match the primary color
                    customClass: {
                        icon: 'swal2-icon-red' // Added custom class for icon color
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('unlock.videos') }}";
                    }
                });
            });
        </script>
    @endif



    @if (request()->routeIs([
            'movie-details',
            'tvshow-details',
            'episode-details',
            'video-details',
            'video-detail',
        ]))
        <script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
        <script src="{{ asset('js/script.js') }}" defer></script>
    @endif
    @stack('after-scripts')

    <script>
        const currencyFormat = (amount) => {
            const DEFAULT_CURRENCY = JSON.parse(@json(json_encode(Currency::getDefaultCurrency(true))))
            const noOfDecimal = DEFAULT_CURRENCY.no_of_decimal
            const decimalSeparator = DEFAULT_CURRENCY.decimal_separator
            const thousandSeparator = DEFAULT_CURRENCY.thousand_separator
            const currencyPosition = DEFAULT_CURRENCY.currency_position
            const currencySymbol = DEFAULT_CURRENCY.currency_symbol
            return formatCurrency(amount, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition,
                currencySymbol)
        }

        window.currencyFormat = currencyFormat
        window.defaultCurrencySymbol = @json(Currency::defaultSymbol())
    </script>
    <script>
        window.translations = {
            otp_send_success: @json(__('frontend.otp_send_success')),
            otp_send_error: @json(__('frontend.otp_send_error')),
            send_otp: @json(__('Send OTP')),
            sending: @json(__('frontend.sending')),
            send_otp: @json(__('frontend.send_otp')),
        }
    </script>

    @include('frontend::components.partials.hover-modal-scripts')
</body>

</html>
