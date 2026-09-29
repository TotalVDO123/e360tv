# Content View Count — Common API Specification

**Project:** E360TV (`https://e360tv.com`)  
**Document type:** Implementation specification (no code changes in this phase)  
**Date:** 31 August 2026  
**Status:** Design only — ready for development after approval

---

## 1. Goal

When a user clicks **Watch Now** and starts watching content, the backend must store a **view count** in the database.

**Login is not required.** If the user is **not logged in** (guest), the view must still be counted. Logged-in and guest Watch Now events both write to the same tables, still split by device.

The same common API must work for:


| Platform      | `device_type` value |
| ------------- | ------------------- |
| Website       | `website`           |
| Android phone | `android`           |
| iOS phone     | `ios`               |
| Android TV    | `android_tv`        |


Views must be stored **per content item** and **per device**, so admin and APIs can answer:

- How many times was this show watched? (total views)
- How many unique users watched it? (unique users)
- How many of those views came from website / Android / iOS / Android TV?

This applies to **all playable categories**:

- Movies
- TV shows
- Episodes
- Videos
- Live TV channels
- Networks (rolled up from the shows/episodes that belong to them)
- Live TV categories (rolled up from channels)
- Genres (optional roll-up)

---



## 2. What exists today (current system)

This section describes the live codebase. Nothing below is a proposed change.

### 2.1 Existing table: `entertainment_views`

Created by `Modules/Entertainment/database/migrations/2024_06_26_054413_create_entertainment_views_table.php`.


| Column                                     | Type          | Notes          |
| ------------------------------------------ | ------------- | -------------- |
| `id`                                       | bigint        | Primary key    |
| `entertainment_id`                         | int, nullable | Content id     |
| `user_id`                                  | int, nullable | Logged-in user |
| `profile_id`                               | int, nullable | Profile        |
| `created_by` / `updated_by` / `deleted_by` | int, nullable | Audit          |
| `timestamps`                               | datetime      |                |
| `deleted_at`                               | datetime      | Soft delete    |


**Missing today:** `content_type`, `device_type`, episode id, live TV channel id, network id, unique vs total counters.

### 2.2 Existing API

```
POST /api/save-entertainment-views
Auth: Sanctum (auth:sanctum)
Controller: Modules/Entertainment/Http/Controllers/API/EntertainmentsController.php
Method: saveEntertainmentViews()
```

**Request body (current):**

```json
{
  "entertainment_id": 123,
  "profile_id": 10
}
```

**Current behaviour:**

1. Requires a logged-in user.
2. Looks up one row by `entertainment_id` + `user_id`.
3. If no row exists, it inserts one row.
4. If a row already exists, it does **not** increment. It returns “already added”.
5. Device type is **not** stored.
6. Content type is **not** stored (movie / episode / livetv / network cannot be distinguished).
7. Guest (not logged in) views are **not** stored.



### 2.3 Website Watch Now

Website player: `Modules/Frontend/Resources/assets/js/videoplayer.js`

On Watch Now click, `saveEntertainmentView()` calls `POST /api/save-entertainment-views`.

It currently sends a view only for:

- `movie`
- `tvshow`
- `video`
- `episode` (and even then it often stores the **parent TV show id**, not the episode id)

**Not covered today:** Live TV channel, Network page, Android / iOS / Android TV device split.

Episode Watch Now buttons currently mix IDs, for example in `card_episode.blade.php`:

- `data-entertainment-id` = parent TV show id
- `data-episode-id` = episode id
- `data-contentid` = episode id
- `data-entertainment-type` = `tvshow` (on cards) or `episode` (on detail)

That is why episode views are not stored as true episode-level counts.

### 2.4 Admin Watch Count

Backend lists already show a **Watch Count** column for movies, TV shows, videos, and episodes. That count is `entertainmentView()->count()` from `entertainment_views`.

It is a single total. There is **no per-device breakdown** in admin.

Live TV channels and networks have **no watch count** in admin.

### 2.5 Existing device header

Many API transformers already read:

```
Header: device-type
```

Login already stores devices in the `devices` table (`device_id`, `device_name`, `platform`).

That header and table can be reused. Current helper `getDeviceType()` only returns `mobile` / `tv` / `desktop`, which is **not** enough for Android vs iOS vs Android TV vs website.

---



## 3. Gaps to close


| Requirement                                                       | Current state                                        |
| ----------------------------------------------------------------- | ---------------------------------------------------- |
| Store view when user clicks Watch Now                             | Partial (movies / TV shows / videos / some episodes) |
| Episode-level view count                                          | Not reliable (often stored as parent TV show)        |
| Live TV channel view count                                        | Missing                                              |
| Network view count                                                | Missing                                              |
| All content categories                                            | Incomplete                                           |
| Separate counts per device (website / Android / iOS / Android TV) | Missing                                              |
| Unique users vs total views                                       | Missing (one row per user, never incremented)        |
| One common API for website + apps + TV                            | Partial (old endpoint, web-oriented)                 |
| Guest (not logged in) views                                       | Missing — old API requires login, so guests are never counted |


---



## 4. Proposed design (do not implement yet)

Use **one common API** for every platform. Keep the old `save-entertainment-views` endpoint until apps are updated, then deprecate it.

### 4.1 Two-table model

**A. Event log** — every qualified Watch Now  
**B. Aggregated stats** — fast totals per content + device

This avoids recounting millions of rows on every admin page load.

### 4.2 Content types


| `content_type`    | Source table                           | `content_id` is       |
| ----------------- | -------------------------------------- | --------------------- |
| `movie`           | `entertainments` where `type = movie`  | `entertainments.id`   |
| `tvshow`          | `entertainments` where `type = tvshow` | `entertainments.id`   |
| `episode`         | `episodes`                             | `episodes.id`         |
| `video`           | `videos`                               | `videos.id`           |
| `livetv`          | `live_tv_channel`                      | `live_tv_channel.id`  |
| `network`         | `series_networks`                      | `series_networks.id`  |
| `livetv_category` | `live_tv_category`                     | `live_tv_category.id` |
| `genre`           | `genres`                               | `genres.id`           |


`network`, `livetv_category`, and `genre` are **roll-up** types. They are not sent by the player. The server writes them automatically when a related item is watched.

### 4.3 Device types (fixed list)

Clients **must** send one of these values in header `device-type` (and optionally in the JSON body):


| Value        | Meaning               |
| ------------ | --------------------- |
| `website`    | e360tv.com web player |
| `android`    | Android phone app     |
| `ios`        | iOS phone app         |
| `android_tv` | Android TV app        |


If the header is missing, the server should fall back in this order:

1. Body field `device_type`
2. Map from login `platform` if present
3. Default: `website` for browser User-Agent, otherwise reject with validation error

---



## 5. Database design (proposed)



### 5.1 Table: `content_view_logs`

One row per counted view event.

```sql
CREATE TABLE content_view_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(32) NOT NULL,          -- movie, tvshow, episode, video, livetv
    content_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,             -- TV show id for episode; category id for livetv
    network_id VARCHAR(100) NULL,               -- copied from entertainments.network_id when known
    user_id BIGINT UNSIGNED NULL,               -- null = guest
    profile_id BIGINT UNSIGNED NULL,
    device_type VARCHAR(20) NOT NULL,           -- website, android, ios, android_tv
    device_id VARCHAR(191) NULL,                -- from app login / browser fingerprint
    ip_hash VARCHAR(64) NULL,                   -- hashed IP for guest unique-user fallback
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_content (content_type, content_id),
    INDEX idx_user_content (user_id, content_type, content_id),
    INDEX idx_device (device_type),
    INDEX idx_created (created_at)
);
```



### 5.2 Table: `content_view_stats`

Pre-aggregated counters. One row per content + device.

```sql
CREATE TABLE content_view_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(32) NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    device_type VARCHAR(20) NOT NULL,           -- website, android, ios, android_tv
    total_views INT UNSIGNED NOT NULL DEFAULT 0,
    unique_users INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY uniq_content_device (content_type, content_id, device_type),
    INDEX idx_content (content_type, content_id)
);
```

A fourth virtual device `all` is **not** stored. Totals are summed in the GET API.

### 5.3 Unique-user helper table: `content_view_uniques`

Used so unique users can be incremented exactly once per user + content + device.

```sql
CREATE TABLE content_view_uniques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(32) NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    device_type VARCHAR(20) NOT NULL,
    user_key VARCHAR(191) NOT NULL,             -- "u:{user_id}" or "g:{device_id|ip_hash}"
    first_viewed_at TIMESTAMP NULL,

    UNIQUE KEY uniq_user_content_device (content_type, content_id, device_type, user_key)
);
```



### 5.4 Optional columns on existing tables (for fast admin lists)

If admin list performance needs it later, add:

- `entertainments.view_count_total`
- `episodes.view_count_total`
- `videos.view_count_total`
- `live_tv_channel.view_count_total`
- `series_networks.view_count_total`

These would be denormalized sums of `content_view_stats.total_views`. They are optional. The stats table is enough for the first version.

**Do not alter** `entertainment_views` **for the new design.** Keep it for backward compatibility until all clients move to the new API.

---



## 6. Counting rules



### 6.1 When to count

A view is stored **only when playback actually starts** after Watch Now (player `play` / first frame), not when the user only opens the details page.

Trigger points:


| Client                     | When to call the API                                                             |
| -------------------------- | -------------------------------------------------------------------------------- |
| Website                    | Existing Watch Now click in `videoplayer.js` after playback is allowed           |
| Android / iOS / Android TV | When the video/live player starts playing (same moment as continue-watch starts) |


Do **not** count:

- Trailer-only play
- Hover preview
- Opening a details page without Watch Now
- Failed / blocked play (no subscription, pay-per-view locked, device not supported)



### 6.2 Total views vs unique users


| Metric         | Rule                                                                                                                                   |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| `total_views`  | Increment on every qualified Watch Now, with a **throttle** of 1 count per user (or guest key) + content + device every **30 minutes** |
| `unique_users` | Increment only the **first** time that user (or guest key) watches that content on that device                                         |


Throttle prevents refresh / double-click spam. Unique users answer “how many people saw this”.

### 6.3 Logged-in vs guest (required)

**Rule: always count the view, whether the user is logged in or not.**

The save API must **never** return `401 Unauthorized` only because there is no token or session. If Watch Now playback starts, store the view.

| Viewer | `user_id` stored | Unique key (`user_key`) | Counts `total_views` | Counts `unique_users` |
| --- | --- | --- | --- | --- |
| Logged in | real user id | `u:{user_id}` | Yes | Yes (once per user + content + device) |
| Guest with `device_id` | `null` | `g:{device_id}` | Yes | Yes (once per device_id + content + device type) |
| Guest without `device_id` | `null` | `g:{sha256(ip + user-agent)}` | Yes | Yes (approximate, once per hashed IP + UA + content + device type) |

Details:

1. **Total views** — guest Watch Now increments the same `total_views` as a logged-in user. Admin “how many times watched” includes guests.
2. **Unique users** — guests are counted as unique using `device_id` when the app sends it, otherwise a hashed IP + User-Agent. This is an estimate, not a registered-user count.
3. **`profile_id`** — omit or send `null` for guests. Do not require it.
4. **Do not skip free / public content.** If the player is allowed to play without login, the view must be saved.
5. If playback is **blocked** (must log in, pay-per-view locked, no access), do **not** count. The trigger is successful Watch Now playback, not a failed click.
6. Website guests still send `X-CSRF-TOKEN` from the page. They do **not** send `Authorization`.
7. Android / iOS / Android TV guests send **no** Bearer token. They should still send `device-type` and `device_id` if the app has one (installation id).

### 6.4 Parent roll-ups (automatic on server)

When the client sends **one** view, the server may write extra stats rows:


| Client sends       | Server also increments                                                                          |
| ------------------ | ----------------------------------------------------------------------------------------------- |
| `episode`          | Parent `tvshow` (`episodes.entertainment_id`) and each `network` in `entertainments.network_id` |
| `movie` / `tvshow` | Each `network` in `entertainments.network_id` (if set) and mapped `genre` rows (optional)       |
| `livetv`           | Parent `livetv_category` (`live_tv_channel.category_id`)                                        |
| `video`            | Mapped `genre` rows (optional)                                                                  |
| `network`          | None (clients should not send this; it is roll-up only)                                         |


Example: user watches Episode 5 of a TV show that belongs to Network “E360 Originals”:

1. `episode` + episode id — counted
2. `tvshow` + parent show id — counted
3. `network` + network id — counted

All three get the same `device_type`.

---



## 7. Common API

Base URL: `https://e360tv.com/api`

All new endpoints live under **v3** to match existing app APIs (`/api/v3/...`).

### 7.1 Auth (optional — guests must still count)


| Client | How to authenticate |
| --- | --- |
| Android / iOS / Android TV (logged in) | `Authorization: Bearer {sanctum_token}` |
| Website (logged in) | Session cookie + `X-CSRF-TOKEN` **or** Bearer token |
| **Any platform, not logged in** | **No token.** Call the same save API. Server stores `user_id = null` and still increments counts. |


**Hard rules for `POST /api/v3/save-content-view`:**

- Do **not** put this route inside `auth:sanctum` (that is why the old API never counts guests).
- Middleware: `throttle:api` only. If a Bearer token or web session is present, read `user_id` from it. If not, continue as guest.
- Missing / invalid token must **not** reject the request. Invalid token → treat as guest (or ignore token), still count the view.
- `GET` count APIs are also public.



### 7.2 Common headers (required on every call)

```
Content-Type: application/json
Accept: application/json
device-type: website | android | ios | android_tv
```

Optional (only if logged in):

```
Authorization: Bearer {token}
```

If this header is missing, the view is still saved as a guest.

---



## 8. API 1 — Save view (common)



### `POST /api/v3/save-content-view`

Call this once when Watch Now starts playback.

**Works for logged-in and guest users.** Do not require `Authorization`. If the user is not logged in, still save the view with `user_id = null`.

### 8.1 Request body

Logged-in example:

```json
{
  "content_type": "episode",
  "content_id": 4581,
  "parent_id": 220,
  "profile_id": 10,
  "device_id": "a1b2c3d4-device-uuid",
  "device_type": "android"
}
```

Guest (not logged in) example — same API, no token, no `profile_id`:

```json
{
  "content_type": "movie",
  "content_id": 91,
  "device_type": "website"
}
```


| Field          | Type    | Required                | Description                                     |
| -------------- | ------- | ----------------------- | ----------------------------------------------- |
| `content_type` | string  | Yes                     | `movie`, `tvshow`, `episode`, `video`, `livetv` |
| `content_id`   | integer | Yes                     | Id of that content in its table                 |
| `parent_id`    | integer | Recommended for episode | Parent TV show id (`episodes.entertainment_id`) |
| `profile_id`   | integer | No                      | Current profile. Omit or `null` if not logged in. |
| `device_id`    | string  | Recommended for guests  | App install id / browser id. Used to count unique guests when there is no `user_id`. |
| `device_type`  | string  | No if header is sent    | Must match header if both are sent              |


**Do not send** `network` / `livetv_category` / `genre` from the client.

### 8.2 Validation

- `content_type` in: `movie`, `tvshow`, `episode`, `video`, `livetv`
- `content_id` must exist in the matching table and `status = 1` (not deleted)
- `device_type` in: `website`, `android`, `ios`, `android_tv`
- For `episode`, if `parent_id` is missing, load it from `episodes.entertainment_id`
- `user_id` / `profile_id` are **not** required
- Do **not** return 401 when the user is not logged in



### 8.3 Success response

```json
{
  "status": true,
  "message": "View saved",
  "data": {
    "content_type": "episode",
    "content_id": 4581,
    "device_type": "android",
    "counted": true,
    "throttled": false,
    "is_unique_user": true,
    "view_count": {
      "total_views": 15420,
      "unique_users": 8310,
      "by_device": {
        "website": { "total_views": 4200, "unique_users": 2100 },
        "android": { "total_views": 7100, "unique_users": 3900 },
        "ios": { "total_views": 2800, "unique_users": 1600 },
        "android_tv": { "total_views": 1320, "unique_users": 710 }
      }
    }
  }
}
```


| `counted`                   | Meaning                                                   |
| --------------------------- | --------------------------------------------------------- |
| `true`                      | This call incremented `total_views`                       |
| `false` + `throttled: true` | Same user already counted within 30 minutes; no increment |


HTTP status: `200`

### 8.4 Error responses

**Invalid content**

```json
{
  "status": false,
  "message": "Invalid content_type or content_id"
}
```

HTTP `422`

**Invalid device**

```json
{
  "status": false,
  "message": "device-type must be one of: website, android, ios, android_tv"
}
```

HTTP `422`

---



## 11. Client integration



### 11.1 Website (`device-type: website`)

Update Watch Now in `videoplayer.js` (later implementation) to call:

```
POST /api/v3/save-content-view
```

Guest website payload (not logged in — still send this):

```json
{
  "content_type": "movie",
  "content_id": 91,
  "device_type": "website"
}
```

Logged-in website payload:

```json
{
  "content_type": "movie",
  "content_id": 91,
  "profile_id": 10,
  "device_type": "website"
}
```

Episode Watch Now must send **episode id**, not parent show id:

```json
{
  "content_type": "episode",
  "content_id": 4581,
  "parent_id": 220,
  "profile_id": 10,
  "device_type": "website"
}
```

Live TV Watch Now:

```json
{
  "content_type": "livetv",
  "content_id": 15,
  "device_type": "website"
}
```

Headers (logged in **or** guest — same call):

```
Content-Type: application/json
X-CSRF-TOKEN: {csrf}
device-type: website
Accept: application/json
```

Website guests have a CSRF token on the page but no login session. The player must still call this API. Do not wrap the call in `if (isAuthenticated)`.



### 11.2 Android phone (`device-type: android`)

Call the same endpoint when ExoPlayer / player starts, **even if the user has not logged in**.

Headers if logged in:

```
Authorization: Bearer {token}
device-type: android
Content-Type: application/json
Accept: application/json
```

Headers if **not** logged in (still count the view):

```
device-type: android
Content-Type: application/json
Accept: application/json
```

Body: include `device_id` (install id) even when there is no login, so unique guests can be counted.

### 11.3 iOS phone (`device-type: ios`)

Same as Android (token optional). Header must be exactly `ios` (not `iOS` or `iphone`). Guest Watch Now with no Bearer token must still be counted.

### 11.4 Android TV (`device-type: android_tv`)

Same as Android (token optional). Header must be exactly `android_tv` (not `tv`). Guest Watch Now with no Bearer token must still be counted.

This is separate from the existing `device-type: tv` used today for poster image selection. Apps should send `android_tv` for view tracking. If an older TV build still sends `tv`, the server should map `tv` → `android_tv` for this API only.

---



## 12. Suggested route registration (later)

In `Modules/Entertainment/routes/api.php` (do not add now):

```php
Route::prefix('v3')->middleware(['throttle:api'])->group(function () {
    Route::post('save-content-view', [ContentViewController::class, 'store']);
    Route::get('content-view-summary', [ContentViewController::class, 'summary']);
});
```

Suggested new files (later, not now):


| File                                                                            | Role                  |
| ------------------------------------------------------------------------------- | --------------------- |
| `Modules/Entertainment/Http/Controllers/API/ContentViewController.php`          | Common API            |
| `Modules/Entertainment/Services/ContentViewService.php`                         | Count + roll-up logic |
| `Modules/Entertainment/Models/ContentViewLog.php`                               | Model                 |
| `Modules/Entertainment/Models/ContentViewStat.php`                              | Model                 |
| `Modules/Entertainment/Models/ContentViewUnique.php`                            | Model                 |
| `Modules/Entertainment/database/migrations/xxxx_create_content_view_tables.php` | Schema                |
| `Modules/Entertainment/Http/Requests/SaveContentViewRequest.php`                | Validation            |


Keep `saveEntertainmentViews()` working so old app builds do not break.

---



## 13. Mapping existing Watch Now buttons


| Screen                                 | Current `data-entertainment-type` | New `content_type` | New `content_id`                            |
| -------------------------------------- | --------------------------------- | ------------------ | ------------------------------------------- |
| Movie details Watch Now                | `movie`                           | `movie`            | movie id                                    |
| TV show Watch Now (play first episode) | `tvshow`                          | `episode`          | first episode id + `parent_id` = show id    |
| Episode card / episode details         | mixed `tvshow` / `episode`        | `episode`          | episode id (`data-episode-id`)              |
| Video details                          | `video`                           | `video`            | video id                                    |
| Live TV channel                        | not sent today                    | `livetv`           | channel id                                  |
| Network page (user only browses)       | n/a                               | do not send        | roll-up only when a show/episode is watched |


---



## 14. Admin / reporting (later)

Add a Watch Count breakdown on:

- Movies list
- TV shows list
- Episodes list
- Videos list
- Live TV channels list
- Networks list

Suggested display:

```
Total: 15,420
Website: 4,200 | Android: 7,100 | iOS: 2,800 | Android TV: 1,320
Unique users: 8,310
```

Optional filters:

- Date range (from `content_view_logs.created_at`)
- Device type
- Content type

Dashboard cards can reuse `content_view_stats` grouped by `device_type`.

---



## 15. Backward compatibility


| Item                                 | Plan                                                                                                                                              |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `POST /api/save-entertainment-views` | Keep. Optionally also write into the new stats tables using `content_type = movie/tvshow/video` and `device_type` from header (default `website`) |
| `entertainment_views` table          | Keep. Do not delete. New counts live in `content_view_stats`                                                                                      |
| Old Android / iOS builds             | Continue to work. They will not have per-device accuracy until they call the v3 API                                                               |
| Existing `device-type: tv` header    | Map to `android_tv` inside the new API only                                                                                                       |


---



## 16. Security and performance

1. **Throttle** save API: 100 requests / minute / user or IP (existing `throttle:api`).
2. **Duplicate window:** 30 minutes per user_key + content + device.
3. **Do not trust** client-sent `total_views`. Server always increments.
4. **Hash guest IP** (`sha256`) before storing. Do not store raw IP in `content_view_logs` if privacy policy forbids it.
5. **Indexes** on `(content_type, content_id, device_type)` are mandatory.
6. Increment `content_view_stats` in the same DB transaction as the log insert.
7. Do not `Cache::flush()` on every view (current `saveEntertainmentViews` flushes the entire cache). That is too expensive. Cache only view-count keys, e.g. `content_view:{type}:{id}`.

---



## 17. Worked examples



### Example A — Android user watches an episode

1. User taps Watch Now on Episode 5 (id `4581`) of TV show `220`.
2. Show `220` has `network_id = "3"`.
3. App sends:

```http
POST /api/v3/save-content-view
Authorization: Bearer ...
device-type: android

{
  "content_type": "episode",
  "content_id": 4581,
  "parent_id": 220,
  "profile_id": 10,
  "device_id": "pixel-uuid-1"
}
```

1. Server writes:

- log row for episode 4581 / android
- stats +1 for `episode:4581:android`
- stats +1 for `tvshow:220:android`
- stats +1 for `network:3:android`
- unique user rows if first time



### Example B — Website user watches Live TV

```http
POST /api/v3/save-content-view
device-type: website
X-CSRF-TOKEN: ...

{
  "content_type": "livetv",
  "content_id": 15
}
```

If channel 15 belongs to live TV category 4, server also increments `livetv_category:4:website`.

This example is valid for a **logged-out website visitor**. No `Authorization` header. `user_id` stored as `null`. `total_views` still +1.

### Example C — Guest (not logged in) watches a movie on website

```http
POST /api/v3/save-content-view
device-type: website
X-CSRF-TOKEN: {csrf from page}
Content-Type: application/json
Accept: application/json

{
  "content_type": "movie",
  "content_id": 91,
  "device_type": "website"
}
```

Server result:

- `user_id` = `null`
- `profile_id` = `null`
- `user_key` = `g:{sha256(ip + user-agent)}` (no `device_id` sent)
- `total_views` for `movie:91:website` **increments**
- `unique_users` increments only if this guest key has not watched movie 91 on website before
- HTTP **200** — never 401

### Example D — Guest Android phone (app opened without login)

```http
POST /api/v3/save-content-view
device-type: android
Content-Type: application/json
Accept: application/json

{
  "content_type": "episode",
  "content_id": 4581,
  "parent_id": 220,
  "device_id": "install-uuid-without-login",
  "device_type": "android"
}
```

No `Authorization` header. View is still stored. Unique guest uses `g:install-uuid-without-login`.

---



## 18. Implementation checklist (for a later development task)

Do **not** do these until this document is approved.

1. Create migration for `content_view_logs`, `content_view_stats`, `content_view_uniques`.
2. Add `ContentViewService` with throttle, unique-user, and roll-up logic.
3. Add `ContentViewController` + v3 routes. **Do not** use `auth:sanctum` on the save route so guests can call it.
4. Validate `content_type` against the correct table. Never require `user_id`.
5. Map header `tv` → `android_tv`.
6. Website: change Watch Now to call v3 API with correct episode / livetv ids. Call it for **guests too** (do not check `isAuthenticated` before saving the view).
7. Android, iOS, Android TV: call v3 API on playback start with the correct `device-type`, with or without a login token.
8. Optionally bridge old `save-entertainment-views` into the new tables.
9. Admin: show totals + per-device breakdown.
10. Load-test save endpoint (Watch Now is a hot path).

---



## 19. Out of scope for this document

- Changing any PHP, JS, Blade, or migration files (this phase is documentation only)
- Replacing continue-watch (`POST /api/save-continuewatch`) — that remains a separate feature
- Analytics dashboards beyond view totals
- Changing subscription / pay-per-view access rules

---



## 20. Decision summary


| Topic                     | Decision                                  |
| ------------------------- | ----------------------------------------- |
| One API for all platforms | Yes — `POST /api/v3/save-content-view`    |
| When to count             | Watch Now playback start, not page open   |
| Devices                   | `website`, `android`, `ios`, `android_tv` |
| Unique vs total           | Both stored                               |
| Episode                   | Count episode + parent TV show + network  |
| Live TV                   | Count channel + live TV category          |
| Guest / not logged in     | **Must count.** Save API is public. `user_id` may be null. Never return 401 for missing login. |
| Old API                   | Keep until apps migrate. Old API still requires login, so guests only count on the new v3 API. |


When development starts, implement exactly this contract so website, Android, iOS, and Android TV all write the same database in the same way.