    <script>
        (function() {
            let hlsLibraryPromise = null;

            const loadHlsLibrary = () => {
                if (hlsLibraryPromise) return hlsLibraryPromise;
                hlsLibraryPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/hls.js@latest';
                    script.onload = () => resolve(window.Hls);
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
                return hlsLibraryPromise;
            };

            const convertToYouTubeEmbed = (url) => {
                try {
                    const parsedUrl = new URL(url);
                    const videoId = parsedUrl.hostname.includes('youtu.be')
                        ? parsedUrl.pathname.slice(1)
                        : parsedUrl.searchParams.get('v');
                    return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
                } catch {
                    return null;
                }
            };

            const convertToVimeoEmbed = (url) => {
                try {
                    const parsedUrl = new URL(url);
                    if (!parsedUrl.hostname.includes('vimeo.com')) return null;
                    const videoId = parsedUrl.pathname.split('/').filter(Boolean).pop();
                    return videoId ? `https://player.vimeo.com/video/${videoId}` : null;
                } catch {
                    return null;
                }
            };

            const addAutoplayParameters = (src) => {
                if (!src) return src;
                const separator = src.includes('?') ? '&' : '?';
                const baseParams = 'autoplay=1&muted=1&controls=0&playsinline=1&loop=1';
                const lowerSrc = src.toLowerCase();

                if (lowerSrc.includes('youtube.com')) {
                    return `${src}${separator}${baseParams}&enablejsapi=1&mute=1`;
                }
                if (lowerSrc.includes('vimeo.com')) {
                    return `${src}${separator}${baseParams}&api=1`;
                }
                return `${src}${separator}${baseParams}`;
            };

            const commonPlayerStyles = {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                height: '55%',
                objectFit: 'cover',
                pointerEvents: 'none'
            };

            const createIframePlayer = (src) => {
                const iframe = document.createElement('iframe');
                iframe.src = addAutoplayParameters(src);
                iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                iframe.loading = 'lazy';
                Object.assign(iframe.style, commonPlayerStyles, { border: '0' });
                return iframe;
            };

            const createVideoPlayer = (src) => {
                const video = document.createElement('video');
                video.src = src;
                video.autoplay = false;
                video.muted = true;
                video.loop = true;
                video.playsInline = true;
                video.preload = 'metadata';
                Object.assign(video.style, commonPlayerStyles);
                return video;
            };

            const getPlayerForUrl = async (url, type) => {
                const trimmedUrl = (url || '').trim();

                // Handle raw iframe HTML
                if (trimmedUrl.toLowerCase().startsWith('<iframe')) {
                    const srcMatch = trimmedUrl.match(/src=["']([^"']+)/i);
                    if (srcMatch && srcMatch[1]) {
                        return createIframePlayer(srcMatch[1]);
                    }
                }

                const lowerType = (type || '').toLowerCase();

                // YouTube
                if (lowerType === 'youtube') {
                    const embedUrl = convertToYouTubeEmbed(url);
                    return embedUrl ? createIframePlayer(embedUrl) : null;
                }

                // Vimeo
                if (lowerType === 'vimeo') {
                    const embedUrl = convertToVimeoEmbed(url);
                    return embedUrl ? createIframePlayer(embedUrl) : null;
                }

                // Generic embed
                if (lowerType === 'embed' || lowerType === 'embedded') {
                    return createIframePlayer(url);
                }

                // HLS streaming
                if (lowerType === 'hls') {
                    const testVideo = document.createElement('video');
                    const supportsNativeHls = testVideo.canPlayType('application/vnd.apple.mpegURL');

                    if (supportsNativeHls) {
                        return createVideoPlayer(url);
                    }

                    try {
                        const Hls = await loadHlsLibrary();
                        if (Hls?.isSupported()) {
                            const video = createVideoPlayer('');
                            const hlsInstance = new Hls();
                            hlsInstance.loadSource(url);
                            hlsInstance.attachMedia(video);
                            video.hlsInstance = hlsInstance;
                            return video;
                        }
                    } catch (error) {
                        console.warn('HLS.js failed to load:', error);
                    }
                }

                // Default to direct video
                return createVideoPlayer(url);
            };

            const isYouTubeIframe = (iframe) => iframe.src?.toLowerCase().includes('youtube.com/embed/');
            const isVimeoIframe = (iframe) => iframe.src?.toLowerCase().includes('player.vimeo.com');

            const sendMessageToIframe = (iframe, message) => {
                try {
                    iframe.contentWindow?.postMessage(JSON.stringify(message), '*');
                } catch (error) {
                    console.warn('Failed to send message to iframe:', error);
                }
            };

            const controlYouTubePlayer = (iframe, command) => {
                sendMessageToIframe(iframe, { event: 'command', func: command, args: [] });
            };

            const controlVimeoPlayer = (iframe, method, value) => {
                const message = { method };
                if (value !== undefined) message.value = value;
                sendMessageToIframe(iframe, message);
            };

            const createMuteButton = (onToggle) => {
                const button = document.createElement('button');
                button.textContent = 'Unmute';
                Object.assign(button.style, {
                    position: 'absolute',
                    top: '10px',
                    right: '10px',
                    zIndex: '5',
                    padding: '5px 10px',
                    background: 'rgba(0,0,0,0.5)',
                    color: '#fff',
                    border: 'none',
                    cursor: 'pointer',
                    borderRadius: '3px',
                    pointerEvents: 'auto'
                });
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    onToggle(button);
                });
                return button;
            };

            const initializeTrailerHover = () => {
                const cards = document.querySelectorAll('[data-trailer-url][data-trailer-scope="hover-modal"]');


                cards.forEach(card => {
                    const trailerUrl = card.getAttribute('data-trailer-url');
                    const trailerType = card.getAttribute('data-trailer-type');

                    if (!trailerUrl) return;

                    // Get or create preview container
                    let previewContainer = card.querySelector('.trailer-preview');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'trailer-preview position-absolute top-0 start-0 w-100 h-100';
                        Object.assign(previewContainer.style, {
                            display: 'none',
                            zIndex: '3',
                            pointerEvents: 'none'
                        });
                        card.appendChild(previewContainer);
                    }

                    const thumbnailImage = card.querySelector('img') || card.closest('.image-box')?.querySelector('img');
                    if (!thumbnailImage) return;

                    let activePlayer = null;

                    const showPreview = async () => {
                        if (activePlayer) return;

                        activePlayer = await getPlayerForUrl(trailerUrl, trailerType);
                        if (!activePlayer) return;

                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(activePlayer);
                        previewContainer.style.display = 'block';
                        thumbnailImage.style.visibility = 'hidden';

                        // Handle video element
                        if (activePlayer.tagName === 'VIDEO') {
                            activePlayer.play().catch(() => {});

                            const muteButton = createMuteButton((button) => {
                                activePlayer.muted = !activePlayer.muted;
                                button.textContent = activePlayer.muted ? 'Unmute' : 'Mute';
                            });
                            previewContainer.appendChild(muteButton);
                        }
                        // Handle iframe element
                        else if (activePlayer.tagName === 'IFRAME') {
                            if (isYouTubeIframe(activePlayer)) {
                                controlYouTubePlayer(activePlayer, 'playVideo');
                            } else if (isVimeoIframe(activePlayer)) {
                                controlVimeoPlayer(activePlayer, 'play');
                            }

                            // Add mute button for supported platforms
                            if (isYouTubeIframe(activePlayer) || isVimeoIframe(activePlayer)) {
                                let isMuted = true;
                                const muteButton = createMuteButton((button) => {
                                    isMuted = !isMuted;
                                    if (isYouTubeIframe(activePlayer)) {
                                        controlYouTubePlayer(activePlayer, isMuted ? 'mute' : 'unMute');
                                    } else if (isVimeoIframe(activePlayer)) {
                                        controlVimeoPlayer(activePlayer, 'setVolume', isMuted ? 0 : 1);
                                    }
                                    button.textContent = isMuted ? 'Unmute' : 'Mute';
                                });
                                previewContainer.appendChild(muteButton);
                            }
                        }
                    };

                    const hidePreview = () => {
                        if (!activePlayer) return;

                        // Cleanup video
                        if (activePlayer.tagName === 'VIDEO') {
                            activePlayer.pause();
                            activePlayer.src = '';
                            activePlayer.load();
                            if (activePlayer.hlsInstance) {
                                activePlayer.hlsInstance.destroy();
                                activePlayer.hlsInstance = null;
                            }
                        }
                        // Cleanup iframe
                        else if (activePlayer.tagName === 'IFRAME') {
                            if (isYouTubeIframe(activePlayer)) {
                                controlYouTubePlayer(activePlayer, 'pauseVideo');
                            } else if (isVimeoIframe(activePlayer)) {
                                controlVimeoPlayer(activePlayer, 'pause');
                            }
                        }

                        previewContainer.innerHTML = '';
                        previewContainer.style.display = 'none';
                        thumbnailImage.style.visibility = 'visible';
                        activePlayer = null;
                    };

                    // Attach event listeners
                    card.addEventListener('mouseenter', showPreview);
                    card.addEventListener('focusin', showPreview);
                    card.addEventListener('mouseleave', hidePreview);
                    card.addEventListener('focusout', hidePreview);
                });
            };

            // Export to global scope
            window.initTrailerHover = initializeTrailerHover;

            // Auto-initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeTrailerHover);
            } else {
                initializeTrailerHover();
            }
        })();
        </script>

    <!-- Hover Modal Functionality -->
    <script>
        let hoverModal = null;
        let hoverTimeout = null;
        let lastMouseX = 0;
        let lastMouseY = 0;
        document.addEventListener('mousemove', (e) => {
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
        }, {
            passive: true
        });

        // Global hover modal functions
        window.openHoverModal = function(element) {
            clearTimeout(hoverTimeout);

            const movieId = element.getAttribute('data-movie-id');
            const movieData = JSON.parse(element.getAttribute('data-movie-data') || '{}');
            const isSearch = Number(element.getAttribute('data-is-search') || 0);

            if (!movieId || !movieData) return;

            hoverTimeout = setTimeout(() => {
                createHoverModal(movieId, movieData, element, isSearch);
            }, 300); // 300ms delay before showing
        };

        window.closeHoverModal = function(element) {
            clearTimeout(hoverTimeout);

            hoverTimeout = setTimeout(() => {
                if (hoverModal) {
                    hoverModal.remove();
                    hoverModal = null;
                }
            }, 100); // 100ms delay before hiding
        };


        function getContentUrl(movieData, isSearch = 0) {
            const isComingSoon = movieData.release_date && new Date(movieData.release_date) > new Date();
            let baseUrl;
            if (isComingSoon && (movieData.type === 'movie' || movieData.type === 'tvshow')) {
                baseUrl = `{{ route('comming-soon-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'movie') {
                baseUrl = `{{ route('movie-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'tvshow') {
                baseUrl = `{{ route('tvshow-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'video') {
                baseUrl = `{{ route('video-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'episode' || movieData.type === 'season') {
                baseUrl = `{{ route('episode-details', ['id' => '__ID__']) }}`;
            } else {
                baseUrl = `{{ route('movie-details', ['id' => '__ID__']) }}`;
            }
            baseUrl = baseUrl.replace('__ID__', movieData.episode_slug || movieData.slug || movieData.id);
            return Number(isSearch) === 1 ? baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'is_search=1' : baseUrl;
        }

        function createHoverModal(movieId, movieData, element, isSearch = 0) {
            if (hoverModal) {
                hoverModal.remove();
            }

            const rect = element.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const isRTL = (() => {
                const dirAttr = (document.documentElement.getAttribute('dir') || document.body.getAttribute('dir') || '').toLowerCase();
                return dirAttr === 'rtl';
            })();

            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const modalHTML = `
                <div class="movie-hover-modal" style="
                    position: fixed;
                    z-index: 9999;
                    background: rgba(0, 0, 0, 0.95);
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
                    color: white;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                ">
                <div class="iq-card">
                    <div class="block-images position-relative w-100" data-trailer-scope="hover-modal" data-trailer-url="${(movieData.trailer_url || '').replace(/"/g, '&quot;')}" data-trailer-type="${movieData.trailer_url_type || ''}">
                        <div class="image-box w-100 position-relative">
                            <a href="${getContentUrl(movieData, isSearch)}" class="d-block w-100 h-100 position-absolute top-0 start-0" style="z-index: 1;"></a>
                            <img src="${movieData.poster_image || ''}" alt="movie-card"
                                class="img-fluid object-cover w-100 d-block border-0" loading="lazy" >
                                ${ movieData.is_pay_per_view
                                    ? (movieData.is_purchased
                                        ? `<span class="product-rent"><i class="ph ph-film-reel"></i> Rented</span>`
                                        : `<span class="product-rent"><i class="ph ph-film-reel"></i> Rent</span>`
                                    )
                                    : (movieData.show_premium_badge
                                        ? `<button type="button" class="product-premium border-0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Premium"><i class="ph ph-crown-simple"></i></button>`
                                        : ``
                                    )
                                }
                            ${movieData.imdb_rating ?
                                `<span class="ratting-value">
                                     <i class="ph ph-star"></i>
                                        ${movieData.imdb_rating}
                               </span>` : ''
                            }
                        </div>
                        <div class="card-description with-transition">
                            <div class="position-relative w-100">
                                <ul class="genres-list ps-0 mb-2 d-flex align-items-center gap-5">
                                    ${movieData.genres ? movieData.genres.slice(0, 2).map(g =>
                                        `<li class="small">${g.name || (g.resource?.genre?.name || '--')}</li>`
                                    ).join('') : ''}
                                </ul>

                                <h5 class="iq-title text-capitalize line-count-1">${movieData.name || '--'}</h5>
                                <p> ${movieData.tv_show_data || ''} </p>
                                <div class="d-flex align-items-center gap-3">
                                    ${movieData.duration ? `
                                    <div class="movie-time d-flex align-items-center gap-1 font-size-14">
                                        <i class="ph ph-clock"></i>
                                            ${formatDuration(movieData.duration)}
                                        </div>
                                    ` : ''}
                                    ${movieData.language ? `
                                <div class="movie-language d-flex align-items-center gap-1 font-size-14">
                                    <i class="ph ph-translate"></i>
                                        <small>${movieData.language ? movieData.language.charAt(0).toUpperCase() + movieData.language.slice(1) : '--'}</small>
                                </div>
                                ` : ''}
                                </div>

                                <div class="d-flex align-items-center gap-3 mt-3">
                                    ${(movieData.type !== 'episode' && movieData.type !== 'season') ? `
                                <button id="watchlist-btn-${movieId}"
                                    class="action-btn btn ${movieData.is_watch_list ? 'btn-primary' : 'btn-dark'} watch-list-btn"
                                    data-entertainment-id="${movieId}"
                                    data-in-watchlist="${movieData.is_watch_list ? 'true' : 'false'}"
                                    data-entertainment-type=" ${movieData.type}"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="${movieData.is_watch_list ? 'Remove from Watchlist' : 'Add to Watchlist'}"
                                    data-bs-placement="top"
                                    onclick="toggleWatchlist(${movieId})">
                                    <i class="ph ${movieData.is_watch_list ? 'ph-check' : 'ph-plus'}"></i>
                                </button>
                            ` : ''}

                                    <div class="flex-grow-1">
                                        <a href="${getContentUrl(movieData, isSearch)}" class="btn btn-success w-100">
                                            {{ __('frontend.watch_now') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            `;


            hoverModal = document.createElement('div');
            hoverModal.innerHTML = modalHTML;
            hoverModal = hoverModal.firstElementChild;
            hoverModal.style.position = 'fixed';
            hoverModal.style.visibility = 'hidden';
            document.body.appendChild(hoverModal);

            const modalWidth = hoverModal.offsetWidth || rect.width * 1.2; // measure rendered width
            const modalHeight = hoverModal.offsetHeight || rect.height * 1.2; // measure rendered height

            // Center over the card but move slightly upward
            let tentativeLeft = rect.left - (modalWidth - rect.width) / 2;
            let top = rect.top - (modalHeight - rect.height) / 2 - 20;

            // Adjust if modal goes off screen vertically
            if (top < 10) {
                top = 10;
            } else if (top + modalHeight > viewportHeight - 10) {
                top = viewportHeight - modalHeight - 10;
            }

            tentativeLeft = clamp(tentativeLeft, 10, viewportWidth - modalWidth - 10);

            if (isRTL) {
                const rtlRight = clamp(viewportWidth - (tentativeLeft + modalWidth), 10, viewportWidth - modalWidth - 10);
                hoverModal.style.right = rtlRight + 'px';
                hoverModal.style.left = 'auto';
            } else {
                hoverModal.style.left = tentativeLeft + 'px';
                hoverModal.style.right = 'auto';
            }
            hoverModal.style.top = top + 'px';
            hoverModal.style.visibility = '';

            const onScroll = () => {
                // Follow the trigger card while it is visible; hide when the card scrolls out of view
                const newRect = element.getBoundingClientRect();
                const isVisible = (
                    newRect.bottom > 0 &&
                    newRect.top < window.innerHeight &&
                    newRect.right > 0 &&
                    newRect.left < window.innerWidth
                );

                if (!isVisible) {
                    if (hoverTimeout) {
                        clearTimeout(hoverTimeout);
                        hoverTimeout = null;
                    }
                    if (hoverModal) {
                        hoverModal.remove();
                        hoverModal = null;
                    }
                    return;
                }

                let newTop = newRect.top - 50;
                if (newTop < 10) newTop = 10;
                if (newTop + modalHeight > window.innerHeight - 10) {
                    newTop = window.innerHeight - modalHeight - 10;
                }
                let newLeft = clamp(newRect.left + (newRect.width / 2) - (modalWidth / 2), 10, window.innerWidth - modalWidth - 10);
                if (hoverModal) {
                    hoverModal.style.top = newTop + 'px';
                    if (isRTL) {
                        const rtlRight = clamp(window.innerWidth - (newLeft + modalWidth), 10, window.innerWidth - modalWidth - 10);
                        hoverModal.style.right = rtlRight + 'px';
                        hoverModal.style.left = 'auto';
                    } else {
                        hoverModal.style.left = newLeft + 'px';
                        hoverModal.style.right = 'auto';
                    }
                }
            };

            window.addEventListener('scroll', onScroll, {
                passive: true
            });


            try {
                if (window.initTrailerHover) {
                    window.initTrailerHover();
                }
            } catch (e) {
                console.warn('hover trailer init failed', e);
            }
            hoverModal.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
            });

            hoverModal.addEventListener('mouseleave', () => {
                hoverTimeout = setTimeout(() => {
                    if (hoverModal) {
                        window.removeEventListener('scroll', onScroll);
                        hoverModal.remove();
                        hoverModal = null;
                    }
                }, 200);
            });
        }

        // Helper function to format duration
        function formatDuration(duration) {
            if (!duration) return '--';
            
            // Handle "HH:MM" format (e.g., "05:20")
            if (typeof duration === 'string' && duration.includes(':')) {
                const [hours, minutes] = duration.split(':').map(Number);
                const hoursFormatted = String(hours).padStart(2, '0');
                const minutesFormatted = String(minutes).padStart(2, '0');
                return `${hoursFormatted}h ${minutesFormatted}m`;
            }
            
            // Handle seconds (numeric)
            if (typeof duration === 'number' || !isNaN(duration)) {
                const seconds = parseInt(duration);
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                
                if (hours > 0) {
                    const hoursFormatted = String(hours).padStart(2, '0');
                    const minutesFormatted = String(minutes).padStart(2, '0');
                    return `${hoursFormatted}h ${minutesFormatted}m`;
                }
                const minutesFormatted = String(minutes).padStart(2, '0');
                return `${minutesFormatted}m`;
            }
            
            return duration;
        }

        // Watchlist toggle function
        function toggleWatchlist(movieId) {
            const baseUrlMeta = document.querySelector('meta[name="baseUrl"]');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!baseUrlMeta) {
                console.warn('baseUrl meta not found');
                return;
            }
            const baseUrl = baseUrlMeta.getAttribute('content');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            const btn = document.getElementById('watchlist-btn-' + movieId);
            if (!btn) return;
            if (btn.disabled) return;
            btn.disabled = true;

            const isInWatchlist = String(btn.getAttribute('data-in-watchlist')) === 'true';
            const entertainmentType = (btn.getAttribute('data-entertainment-type') || '').trim();

            const saveUrl = baseUrl + '/api/save-watchlist';
            const deleteUrl = baseUrl + '/api/delete-watchlist?is_ajax=1';

            const requestInit = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(
                    isInWatchlist ? {
                        id: [movieId],
                        type: entertainmentType || ''
                    } : {
                        entertainment_id: movieId,
                        type: entertainmentType
                    }
                )
            };

            fetch(isInWatchlist ? deleteUrl : saveUrl, requestInit)
                .then(async (res) => {
                    if (res.status === 401) {
                        window.location.href = baseUrl + '/login';
                        return Promise.reject(new Error('Unauthorized'));
                    }
                    if (!res.ok) {
                        const text = await res.text().catch(() => '');
                        throw new Error(text || 'Request failed');
                    }
                    return res.json().catch(() => ({}));
                })
                .then((data) => {
                    // Toggle UI state on the button
                    const nowIn = !isInWatchlist;
                    btn.setAttribute('data-in-watchlist', nowIn ? 'true' : 'false');
                    try {
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove(nowIn ? 'ph-plus' : 'ph-check');
                            icon.classList.add(nowIn ? 'ph-check' : 'ph-plus');
                        }
                        btn.classList.remove(nowIn ? 'btn-dark' : 'btn-primary');
                        btn.classList.add(nowIn ? 'btn-primary' : 'btn-dark');
                        btn.setAttribute('data-bs-title', nowIn ? 'Remove from Watchlist' : 'Add to Watchlist');
                    } catch {}

                    // Persist state on the originating card so future hovers reflect it
                    const card = document.querySelector('[data-movie-id="' + movieId + '"]');
                    if (card) {
                        try {
                            const raw = card.getAttribute('data-movie-data') || '{}';
                            const obj = JSON.parse(raw);
                            obj.is_watch_list = nowIn ? 1 : 0;
                            card.setAttribute('data-movie-data', JSON.stringify(obj));
                        } catch (e) {
                            console.warn('failed to persist movie data', e);
                        }
                    }

                    // Optional: global snackbar if available
                    try {
                        if (window.successSnackbar && data && data.message) {
                            window.successSnackbar(data.message);
                        }
                    } catch {}
                })
                .catch((err) => {
                    console.error('watchlist toggle failed', err);
                })
                .finally(() => {
                    btn.disabled = false;
                });
        }
    </script>