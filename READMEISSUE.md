1. Stop loading backend/admin translations on the public site
Where: Modules/Frontend/Resources/views/layouts/master.blade.php (the loop around window.localMessagesUpdate).

Today every PHP file in lang/{locale}/ is dumped into the HTML as its own <script> block. That is most of the ~106 translation objects and a large share of the ~70 script tags. Files such as dashboard.php, sidebar.php, settings.php, filemanager.php, backup.php, and admin setting pages are not needed on the homepage.

What to do:

Whitelist only frontend files: frontend.php, messages.php, and maybe placeholder.php / auth.php.
Merge them into one window.localMessagesUpdate = {...} object, not one script per file.
Confirm JS only uses window.localMessagesUpdate.messages.dismiss (snackbars in app.js / entertainment.js). Blade already uses __() on the server, so most of this dump is unused.
Do not load the full messages.php if it is huge; copy the few keys JS needs into a small frontend-js.php.
Expected win: tens of KB and many script tags gone.

2. Slim data-movie-data (this is the ~187 KB)
Where:

Cards: card_movie.blade.php, card_tvshow.blade.php, card_video.blade.php, card_episode.blade.php, etc. — they do json_encode($value) of the full object.
Network carousels: tv_series_shows_network.blade.php selects description, trailer_url, duration, language, etc.
API shape: CommonContentResourceV3 also returns description, video_url_input, full genres, etc.
Card attributes should be only:

id, name, slug, type
poster_image / thumbnail_url
trailer_url_type (and only a trailer URL if you still preview on hover without an API)
flags the card itself paints: imdb_rating, is_pay_per_view, is_purchased, show_premium_badge
Remove from the HTML: description, video_url_input, video_upload_type, plan_id, plan_level, full genres, duration, language, release_date, movie_access, watchlist, etc.

Hover modal today (openHoverModal in master.blade.php) reads all of that from the attribute. After slimming:

Add something like GET /api/frontend/hover/{type}/{id} that returns hover fields only (title, short description, genres, duration, language, trailer, watchlist, badges).
On mouseenter, show the poster immediately, then fetch (cache in a JS Map so the same title is not requested twice).
Debounce ~300ms as you already do so a fast scroll does not fire 20 requests.
Do not put trailer_url on every card if trailers are long HLS URLs; load them with the hover API.

Expected win: most of that 187 KB gone.

3. Remove duplicate Chromecast
Where: master.blade.php, two tags:

cast_sender.js?loadCastFramework=1
cast_sender.js again
Keep only the ?loadCastFramework=1 one. Load it only on player/detail pages, not the homepage (Cast is unused until something is playing).

Also avoid loading HLS.js on the homepage until a trailer actually plays (it is already lazy in places; keep it that way).

4. Why the same titles appear ~128 times with only ~81 IDs
This is mostly by design, not a render bug.

tv_series_shows_network.blade.php loops each series network and queries shows with FIND_IN_SET(network_id). A show in several networks is rendered several times, each time with a full data-movie-data blob.
Banner / live / personality can repeat titles that also sit in a network row.
What to do:

Do not hide the same show from every row (Netflix-style rows overlap). That is product, not a bug.
Do stop duplicating the heavy JSON: slim cards (step 2), or keep one JSON map of unique IDs and let cards store only data-movie-id.
Cap each network row (you already limit(8)).
Confirm FIND_IN_SET is not matching junk (comma-separated network_id is hard to index; consider a pivot table later).
5. Lazy-render below-the-fold carousels (not only images)
loading="lazy" on images is not enough. All carousel HTML is in the first response.

section-hidden does not lazy-load: on DOMContentLoaded, index.blade.php immediately adds section-visible to every section. The HTML was already sent.

What to do:

Above the fold only in the first HTML: banner + first 1–2 rows (live stream, first network).
Below the fold: empty placeholders (<div data-lazy-section="network-5">) and load via IntersectionObserver from an endpoint that returns that row’s HTML or JSON.
Do not initialize Slick on a row until it is in (or near) the viewport.
Personality and extra networks should not be in the first paint.
That cuts first HTML, parse time, and image work.

6. TTFB / Laravel / database (check after HTML cleanup)
HTML size is download + parse. TTFB is server time before the first byte. Several homepage paths are expensive even after the big movie blocks were commented in the view.

A. Controller still builds unused dashboard data

FrontendController::index fills dashboard_detail_data_v3 (top 10, latest/popular movies, TV, pay-per-view, ads, recommendations, dynamic_data, …) even when index.blade.php does not render those sections.

If those rows stay commented, stop querying them (or split cache: “slim home” vs full dashboard). Cache does not help the first miss or logged-in users (user_id is in the cache key).

B. Queries inside Blade (every request, uncached)

Live stream block in index.blade.php: join live_tv_channel + live_tv_stream_content_mapping, then PHP classify/sort.
tv_series_shows_network.blade.php: one query per network (FIND_IN_SET + whereExists episodes). 15 networks ≈ 15 extra queries on the request thread.
Move both into the controller (or a dedicated home service), cache 1–5 minutes.

C. N+1 in resources

CommonContentResourceV3 can run a Watchlist exists() per item. For homepage cards, skip watchlist or batch it. Same for Entertainment::isPurchased.

D. How to measure

After HTML work, compare:

Chrome Network: document TTFB vs content download.
Laravel Debugbar / Telescope: query count and slow queries.
FIND_IN_SET on entertainments.network_id — cannot use a normal index; a entertainment_network pivot is the real DB fix.
If TTFB stays high after unused queries are removed, the remaining cost is live-stream logic + per-network FIND_IN_SET.

7. Extra homepage cuts (same layout)
Worth doing because they sit on every public page via master.blade.php:

Issue	Action
backend-custom.js on the public layout
Load only in admin
Hover JS copied in master.blade.php and hover-modal-scripts.blade.php
One include
SweetAlert2 on every page
Load only when a modal is needed
Google Fonts CSS
preconnect + display=swap (already) or self-host
Phosphor (regular + fill + bold)
One subset
~285 <img>
Lazy sections (step 5) plus width/height (TV cards already have some)
~70 scripts
Translation whitelist + one messages object + drop homepage Chromecast/HLS/SweetAlert
Suggested order
Priority	Work	Main effect
1
Whitelist frontend translations, one JS object
HTML + script count
2
Slim data-movie-data; hover API
~187 KB
3
Drop duplicate Chromecast (homepage optional)
Extra JS
4
Stop building unused cachedResult sections
TTFB
5
Move live-stream + network queries out of Blade; cache
TTFB
6
Lazy-load network rows below the fold
HTML, images, Slick
7
Measure TTFB again; then pivot/FIND_IN_SET if still slow
Remaining server time