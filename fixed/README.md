# Fixed files

Three production blockers, the same sort fix carried into the app API, and the
Tier 1 security items. Each file below is complete — open it, select all, and
paste over the matching file on your site. Nothing else was touched.

The `.zip` files in the repository root are still the original upload. These are
the patched versions of forty-seven files taken from inside them, seventeen brand new
files, plus three translation templates.

Everything in `fixed/` is meant to be copied up. If a file is in there and not
in the table below, that is a fault in the table rather than a file you may
skip: the table is the whole instruction, and a file missed here is a file that
never reaches the site.

Nothing outside `fixed/` should be touched. The plugin holds around thirty more
files that were never changed, and copying an old copy of one of those over the
live version would undo work that is already running. If a file is not in
`fixed/`, leave it alone.

## What to copy where

| Copy this file | Over this one on your site |
| --- | --- |
| `fixed/kaamase/inc/enqueue.php` | `wp-content/themes/kaamase/inc/enqueue.php` |
| `fixed/kaamase-core/includes/queries.php` | `wp-content/plugins/kaamase-core/includes/queries.php` |
| `fixed/kaamase/functions.php` | `wp-content/themes/kaamase/functions.php` |
| `fixed/kaamase-core/includes/rest-api.php` | `wp-content/plugins/kaamase-core/includes/rest-api.php` |
| `fixed/kaamase/inc/security.php` | `wp-content/themes/kaamase/inc/security.php` |
| `fixed/kaamase-pay/includes/settings.php` | `wp-content/plugins/kaamase-pay/includes/settings.php` |
| `fixed/kaamase-pay/includes/access.php` | `wp-content/plugins/kaamase-pay/includes/access.php` |
| `fixed/kaamase-pay/includes/account.php` | `wp-content/plugins/kaamase-pay/includes/account.php` |
| `fixed/kaamase-pay/includes/store-webhook.php` | `wp-content/plugins/kaamase-pay/includes/store-webhook.php` |
| `fixed/kaamase-pay/includes/notices.php` | `wp-content/plugins/kaamase-pay/includes/` **(new file)** |
| `fixed/kaamase-core/includes/throttle.php` | `wp-content/plugins/kaamase-core/includes/throttle.php` |
| `fixed/kaamase-core/includes/contact.php` | `wp-content/plugins/kaamase-core/includes/contact.php` |
| `fixed/kaamase-core/includes/rest-auth.php` | `wp-content/plugins/kaamase-core/includes/rest-auth.php` |
| `fixed/kaamase-core/includes/registration.php` | `wp-content/plugins/kaamase-core/includes/registration.php` |
| `fixed/kaamase-core/includes/reports.php` | `wp-content/plugins/kaamase-core/includes/reports.php` |
| `fixed/kaamase-core/includes/post-job.php` | `wp-content/plugins/kaamase-core/includes/post-job.php` |
| `fixed/kaamase-core/includes/fields.php` | `wp-content/plugins/kaamase-core/includes/fields.php` |
| `fixed/kaamase-core/includes/post-types.php` | `wp-content/plugins/kaamase-core/includes/post-types.php` |
| `fixed/kaamase-core/includes/taxonomies.php` | `wp-content/plugins/kaamase-core/includes/taxonomies.php` |
| `fixed/kaamase-core/includes/roles.php` | `wp-content/plugins/kaamase-core/includes/roles.php` |
| `fixed/kaamase-pay/includes/webhook.php` | `wp-content/plugins/kaamase-pay/includes/webhook.php` |
| `fixed/kaamase/assets/js/app.js` | `wp-content/themes/kaamase/assets/js/app.js` |
| `fixed/kaamase-core/includes/install.php` | `wp-content/plugins/kaamase-core/includes/install.php` |
| `fixed/kaamase/inc/performance.php` | `wp-content/themes/kaamase/inc/performance.php` |
| `fixed/kaamase-core/includes/districts.php` | `wp-content/plugins/kaamase-core/includes/districts.php` |
| `fixed/kaamase-pay/kaamase-pay.php` | `wp-content/plugins/kaamase-pay/kaamase-pay.php` |
| `fixed/kaamase-core/kaamase-core.php` | `wp-content/plugins/kaamase-core/kaamase-core.php` |
| `fixed/kaamase/front-page.php` | `wp-content/themes/kaamase/front-page.php` |
| `fixed/kaamase/inc/app-banner.php` | `wp-content/themes/kaamase/inc/` **(new file)** |
| `fixed/kaamase-core/includes/more-trades.php` | `wp-content/plugins/kaamase-core/includes/more-trades.php` |
| `fixed/kaamase-core/includes/employer-index.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/not-confirmed.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/views.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/privacy.php` | `wp-content/plugins/kaamase-core/includes/privacy.php` |
| `fixed/kaamase/inc/template-tags.php` | `wp-content/themes/kaamase/inc/template-tags.php` |
| `fixed/kaamase/inc/setup.php` | `wp-content/themes/kaamase/inc/setup.php` |
| `fixed/kaamase/footer.php` | `wp-content/themes/kaamase/footer.php` |
| `fixed/kaamase/style.css` | `wp-content/themes/kaamase/style.css` |
| `fixed/kaamase-core/includes/dashboard.php` | `wp-content/plugins/kaamase-core/includes/dashboard.php` |
| `fixed/kaamase-core/includes/ratings.php` | `wp-content/plugins/kaamase-core/includes/ratings.php` |
| `fixed/kaamase-core/includes/rest-shape.php` | `wp-content/plugins/kaamase-core/includes/rest-shape.php` |
| `fixed/kaamase-core/includes/standing.php` | `wp-content/plugins/kaamase-core/includes/standing.php` |
| `fixed/kaamase/single-kaamase_worker.php` | `wp-content/themes/kaamase/single-kaamase_worker.php` |
| `fixed/kaamase/single-kaamase_gang.php` | `wp-content/themes/kaamase/single-kaamase_gang.php` |
| `fixed/kaamase-core/includes/who-looked.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/google-signin.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/number-requests.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/push.php` | `wp-content/plugins/kaamase-core/includes/push.php` |
| `fixed/kaamase-core/includes/saved.php` | `wp-content/plugins/kaamase-core/includes/saved.php` |
| `fixed/kaamase-core/includes/apple-signin.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/account-password.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/account-providers.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/insights.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/indexing-api.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/services.php` | `wp-content/plugins/kaamase-core/includes/services.php` |
| `fixed/kaamase/single-kaamase_job.php` | `wp-content/themes/kaamase/single-kaamase_job.php` |
| `fixed/kaamase-core/includes/app-version.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/hire-claims.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/views-api.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/promote.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/email-typos.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase-core/includes/sharing.php` | `wp-content/plugins/kaamase-core/includes/sharing.php` |
| `fixed/kaamase-core/includes/verified-mark.php` | `wp-content/plugins/kaamase-core/includes/verified-mark.php` |
| `fixed/kaamase-core/includes/seo.php` | `wp-content/plugins/kaamase-core/includes/` **(new file)** |
| `fixed/kaamase/assets/images/hero/` (14 photos) | `wp-content/themes/kaamase/assets/images/hero/` **(new folder)** |
| `fixed/kaamase-core/includes/job-photos.php` | `wp-content/plugins/kaamase-core/includes/job-photos.php` |

**Translation templates** (create the `languages/` folder if it is not there yet):

| Copy this file | Into this new folder |
| --- | --- |
| `fixed/kaamase-core/languages/kaamase-core.pot` | `wp-content/plugins/kaamase-core/languages/` |
| `fixed/kaamase/languages/kaamase.pot` | `wp-content/themes/kaamase/languages/` |
| `fixed/kaamase-pay/languages/kaamase-pay.pot` | `wp-content/plugins/kaamase-pay/languages/` |

For fix 7, copy **`throttle.php` first** — the other five call into it.

For fix 16, copy **`taxonomies.php`, `more-trades.php` and `contact.php` first,
then `kaamase-core.php`** — the last one is what triggers the new trades to be
created. See fix 18 before you start: `more-trades.php` matters.

Do them one at a time and check the site after each.

---

## 1. `kaamase/inc/enqueue.php` — front end forms reach their handler again

`kaamase_block_admin_access()` runs on `admin_init`. WordPress fires `admin_init`
from `admin-post.php` **before** it fires `admin_post_{action}`, so for anyone
without `edit_posts` the redirect ran first and the handler never ran. The
submission was thrown away with no error and the person landed on the dashboard.

This is the fault noted in `checkout.php` and `account.php` as something on the
host sitting in front of `admin-post.php`. It was the theme.

Fixed by treating `admin-post.php` and `admin-ajax.php` as form handlers rather
than as dashboard screens. wp-admin itself is still blocked for non-editing users
exactly as before.

**Restores:** job alerts, verification requests, hiring requests, adding the
working side, and posting on behalf of somebody else.

**Test as a worker or employer, not as an admin** — admins were never affected.

## 2. `kaamase-core/includes/queries.php` — new workers appear in the sorts

Setting `meta_key` and ordering by `meta_value_num` makes `WP_Query` INNER JOIN
`postmeta`. A profile with no row for that key was not sorted last, it was
removed from the results.

Registration writes only a phone number and a district. The `'default' => 0` in
the field schema is a read-time fallback and never writes a row. So every worker
who had registered but not yet completed their profile was missing from **Best
rated**, **Most experienced** and **Lowest day rate** — and on a site where
nobody had been rated yet, Best rated was empty.

Replaced with a LEFT JOIN built in `posts_join` and `posts_orderby`, the same
pattern the availability rotation already uses in this file. Everybody is
returned, real values sort first, blanks sort last in both directions.

A stored zero counts as no answer for Lowest day rate, so a blank rate no longer
ranks as the cheapest worker on the platform.

The job sorts (Highest pay, Urgent) had the same fault and are fixed too.

**Test:** register a worker, fill in nothing, then open Best rated. They should
appear at the bottom instead of vanishing.

> **Copy `queries.php` again if you already took an earlier copy.** It changed
> once more in fix 4, to share the ordering rule with the app API.

## 3. `kaamase/functions.php` — returning visitors get the current stylesheet

`KAAMASE_VERSION` was a hand-written constant that had drifted to `1.2.0` while
`style.css` said `1.5.0`. It is what `KAAMASE_ASSET_VERSION` falls back to in
production, so every release after 1.2.0 went out behind an unchanged `?ver=`
string and returning visitors kept the CSS and JS they already had.

The version is now read from the `Version:` header in `style.css`, which is what
WordPress already treats as the version of record.

**From now on, bumping `style.css` is the only step.** Do not add the number
anywhere else.

**Test:** view source on the front end. The stylesheet should be
`style.css?ver=1.5.0`.

## 4. `kaamase-core/includes/rest-api.php` — the phone app gets the same fix

`rest-api.php` carried its own copy of the sort logic and never called
`kaamase_apply_sort()`, so fixing the website left the app with the original
fault. In the app, Best rated / Most experienced / Lowest day rate still hid
every newly registered worker, and on a site where nobody had been rated yet
the app's Best rated list came back empty.

The ordering now comes from `kaamase_number_sort_args()` in `queries.php`, which
`kaamase_sort_by_number()` also uses. The website sets it on a query in
`pre_get_posts`; the API merges it into the args it hands `WP_Query`. Both
produce byte-identical SQL.

**No app release is involved.** No route, parameter, response shape or auth
changed, so the installed iOS and Android builds pick this up on their next API
call. No EAS Update, no EAS Build, no store submission, no version bump.

**Test:** `GET /wp-json/kaamase/v1/workers?sort=rated` — a worker who has filled
in nothing must appear in the list, at the bottom.

## 5. `kaamase/inc/security.php` — GPS on WebP/PNG, and user enumeration

Two security items in one file.

**GPS metadata.** The strip list was JPEG and TIFF only, because the code
assumed phones do not write EXIF to PNG or WebP. They do — WebP carries a
standard EXIF chunk including GPS, and PNG has supported `eXIf` since 1.5. WebP
is on this same file's allowed-upload list, so a GPS-tagged WebP reached the
public uploads folder untouched while the readme promised otherwise.

⚠️ **Behaviour change to know about before deploying.** If the server has no
image editor for a format, the upload is now **refused and the file deleted**,
rather than stored with coordinates intact. Your server has GD with JPEG, PNG
and WebP support, so this should never trigger — but if you ever see "This photo
could not be processed", that is why. To fall back to the old permissive
behaviour: `add_filter( 'kaamase_require_metadata_strip', '__return_false' );`

The master copy is now saved at quality 90 instead of 74. Thumbnails are still
generated at 74, so nothing a visitor downloads got heavier — the master was
just being compressed twice. Uploads use somewhat more disk.

**User enumeration.** `?author=1`, `/author/x/` and `/wp-json/wp/v2/users` were
all gated on `is_user_logged_in()`. Registration is free and open, so that
stopped a stranger and nobody else. Now gated on capability: administrators,
editors and field agents can still enumerate; workers and employers cannot.

**Test:** upload a photo as a worker (JPEG, and a PNG or WebP if you can). Then,
signed in as a worker, open `/wp-json/wp/v2/users` — it must return 403. As an
admin it must still return the user list.

## 6. `kaamase-pay/includes/settings.php` — Razorpay secrets out of autoload

The settings option is stored through the Options API, which autoloads by
default, so the live key secret and both webhook secrets were read into memory
on every request including every front-end page view. They now migrate to
`autoload=no` once, automatically.

You can also now put the credentials in `wp-config.php`, which keeps them out of
the database, its backups, and any option dump:

```php
define( 'KAAMASE_PAY_KEY_ID',         'rzp_live_xxxxxxxx' );
define( 'KAAMASE_PAY_KEY_SECRET',     '...' );
define( 'KAAMASE_PAY_WEBHOOK_SECRET', '...' );
define( 'KAAMASE_PAY_STORE_SECRET',   '...' );
```

A constant wins over the stored value, the settings screen disables that field
and says where it is set, and the sanitiser will not persist a value a constant
overrides. **Optional** — with no constants defined, everything behaves exactly
as before.

Signature verification is untouched: `razorpay.php`, `webhook.php`,
`checkout.php`, `access.php`, `account.php`, `store-webhook.php`,
`subscribers.php` and `plans.php` are all byte-identical.

**Test:** open Kaam Ase → Payments. Without constants, the secrets still work and
the screen looks the same. Make a test payment to confirm. If you add the
constants, the fields grey out and say "Set in wp-config.php".

## 7. Durable rate limits — six files

Every abuse control was kept in a transient. With no persistent object cache
that is fine, because a transient with an expiry is written to the options
table. With Redis or Memcached in front — most managed Indian hosting — a
transient lives *only* in that cache. Flush it, let it evict under memory
pressure, or press the "Clear Transients" button nearly every caching plugin
offers, and every counter on the site resets to zero.

**Seven controls were affected:** the daily contact-reveal cap (your
anti-scraping defence), the daily job posting cap, the registration limit, the
verification-email resend cooldown, the report limit, the sign-in lockout, and
the shared throttle behind app registration and forgot-password.

They now use a durable windowed store in `throttle.php`, backed by options with
autoload off, garbage-collected on the `kaamase_daily` task that already exists.
`get_option()` falls back to the database on a cache miss, so a count survives a
flush. **No new database table**, so there is nothing to install.

Two behaviour improvements came with it:

- The window is measured from the **first** attempt in it rather than the last,
  so somebody who keeps hammering cannot hold the window open and then get a
  fresh allowance the moment it lapses.
- **Daily limits now roll over at local midnight**, not 05:30 IST. They used to
  key on `gmdate`. Expect quotas to reset once on the day you deploy.

Limits allow exactly the same number of attempts as before.

Form values and error messages stay in transients deliberately — losing one
costs a retyped form, not a security control. The caches in `indexes.php`,
`admin.php`, `how-seen.php`, the REST reference data and the three payment
notices are unchanged.

**Test:** exhaust a limit (e.g. submit six reports in an hour), then clear your
object cache or press Clear Transients, and confirm you are *still* blocked.
Before this change you would have been let straight back in.

## 8. `kaamase-core/includes/fields.php` — phone numbers written as 0091

The `0091` branch in `kaamase_sanitize_phone()` tested for a length of **13**.
`0091` is four digits and an Indian mobile is ten, so the string it was meant to
catch is **fourteen** long — the branch never matched once.

Anyone who wrote their number the way it is printed on a visiting card
(`0091 98560 12345`) fell through every branch, failed the ten-digit check, and
was told at registration *"That phone number does not look right."* They were
turned away, not stored wrong.

Prefixes are now peeled in order instead of matched against fixed lengths, so
`0091`, `+91`, `91` and a plain trunk zero all reduce to the same ten digits. A
real ten-digit number that happens to start `91` is still left alone.

Also fixed `kaamase_save_field()`, which compared with `===` against a value
WordPress stores as a string — so it reported failure for every int, float and
bool field. Nothing checks the return today; it was a trap for whoever checked
it first.

**Test:** register with `0091 98560 12345`. It should be accepted and stored as
`9856012345`.

## 9. `kaamase-core/includes/post-types.php` — jobs that never closed

Three faults in the daily task:

- It said "Batched" but took 100 jobs and returned. The task runs once a day, so
  the first day more than 100 expired began a backlog that **could never clear**
  — and every job in it stayed in front of workers as though it were open. It
  now runs until done, with a 20-second guard for cheap hosting.
- **A job with no `expires` row was immortal.** The closing query only matched
  rows that exist and are in the past. Those now get an expiry dated from when
  the job was *published*, so an old one closes on the next pass rather than
  being handed another three weeks.
- `kaamase_clear_profile_cache()` called `get_post()` on `deleted_post`, which
  fires *after* the row is gone — so it returned null and cleared nothing.

**Test:** if you have jobs sitting open past their date, they should clear within
a day of deploying. Check Jobs in wp-admin for anything old still marked
Published.

## 10. `kaamase-core/includes/queries.php` — the fair exposure rule was dead code

`exposure.php` and `queries.php` both answer the `kaamase_rotate` sentinel, both
sit on `posts_orderby` at priority 10, and both return a complete clause that
discards whatever came in. `exposure.php` loads first alphabetically, so it built
its clause and `kaamase_rotation_orderby()` then threw it away — every request.

So the rule that stops the same few workers being shown all day while everybody
else waits **had never once taken effect**, on the website or in the app. Your
default worker sort was the simpler rotation the whole time.

Rotation now stands down when `exposure.php` is present, and stays as the
fallback without it.

⚠️ **Copy `queries.php` again** — this is its third revision.

**Test:** open `/workers/` on the default sort on two consecutive days. Workers
who were shown a lot yesterday should sit lower today.

## 11. The rest of the correctness list — six files

Six small independent faults.

**`contact.php`** — `kaamase_may_contact_home_worker()` takes a user id, but the
verifier bypass asked `current_user_can()`, i.e. whoever was making the request.
Correct today because the only caller passes the current user; wrong the moment
cron or the API asks on someone else's behalf. ⚠️ *Third revision — copy again.*

**`roles.php`** — the media-library restriction ran on **every** query on the
site, front end included, forcing `author = you` onto anything asking for
attachments. A signed-in worker opening someone else's job photos was quietly
shown only their own uploads. Now limited to the media library and the REST media
endpoint, and it steps aside for a query that names its parent.

**`taxonomies.php`** — trade matching returned the first term matching either
exactly *or* partially, in whatever order `get_terms()` gave them, so "driver"
landing on Driver rather than Tractor driver was alphabetical luck. Exact matches
are now tried across every term before any partial one.

The seed list has always claimed Mistri was carried as an alias for Mason. No
description was ever written and nothing ever read one — so **typing "mistri"
found nothing**. That alias list now exists, with the words people actually use:
mistri, beldar, chowkidar, kamwali, darzi, mali, bar bender, ayah and so on.

**`app.js`** — all ten enhancements sat inside one `try` block, which defeats the
point of having one: a throw in the first skipped the other nine. The first is
`setUpMenus`, which collapses the menus *before* attaching the buttons that
reopen them — so a throw there produced exactly the state its own comment said
must never happen. Each now fails alone.

**`enqueue.php`** — enqueued the stylesheet with no dependencies, then tried to
re-enqueue it with one. `wp_enqueue_style()` does not re-register an existing
handle, so the second call was discarded and a child theme's overrides could load
*before* the design system. ⚠️ *Second revision — copy again.*

**`webhook.php`** — decided whether an insert failed on a duplicate key by
searching the MySQL error text for the word "duplicate". That is the server's
language, not an interface: another locale or a differently-worded MariaDB
release turns every duplicate into an unrecognised failure, which falls through
to the permissive branch and lets Razorpay's retries be processed as new. It now
asks whether the row is there. **Signature verification untouched.**

**Test:** search for "mistri" — it should find masons. Open another worker's job
photos while signed in as a worker; all photos should show.

## 12. Performance, and the field agent fix — three files

**`install.php`** — `kaamase_page_url()` rebuilt every page definition on every
call, and four of those definitions run the starter-content builders, which
assemble ~4.8KB of block markup through dozens of translation calls. It is called
from **72 places, 20 on the dashboard alone** — so one dashboard load built that
content twenty times over, to read twenty slugs.

Definitions are now built once per request, resolved URLs are memoised (and
cleared when pages are rebuilt), and `kaamase_pages` is autoloaded rather than
fetched separately each time. **~95% less work on a dashboard load.**

**`functions.php`** ⚠️ *2nd revision* — `kaamase_svg()` did a disk read, a regex
and a full `wp_kses()` pass on every call. A worker card carries a pin and a
phone, so twenty cards paid for forty of them. Read and sanitising are now cached
per icon per request; only the attributes are injected per call. **Output is
byte-identical for all ten icons.** ~95% less work, and more in practice since
real `wp_kses()` is dearer than the stand-in used to measure it.

**`fields.php`** ⚠️ *2nd revision* — **field agents can now see phone numbers.**
`kaamase_can_see_private()` was owner-or-administrator, and an agent is neither.
Your launch plan has agents registering workers in person at a labour point — they
could create and edit a profile but could not see the number on it, **not even
the one they had just typed in**, so they could not check their own work.

Scoped to workers and teams only, using the capability they already hold, so it
grants nothing they could not already change. Employers and jobs unaffected. The
rest of the privacy layer is byte-identical.

**Test:** sign in as a field agent, open a worker profile, and confirm the phone
number is visible. Then confirm an *employer* profile still hides its number from
that same agent.

## 13. The rest of performance, and failed-payment warnings — five files

**`performance.php`** — Heartbeat was deregistered on `init`, and deregistering a
script instantiates `wp_scripts()`, which fires `wp_default_scripts` and
registers every script WordPress ships. That happened on **every request**,
including REST, cron and admin-ajax — none of which was ever going to print a
script tag. Moved to `wp_enqueue_scripts`.

**`enqueue.php`** ⚠️ *3rd revision* — the stylesheet preload is gone. It printed a
few tags above the stylesheet link, in the same head, so the browser discovered
both in the same parse; the gain was nil, as its own note admitted. The risk was
not nil: it built its own URL while the link got one from `WP_Styles`, so
anything rewriting one and not the other made the browser **fetch the stylesheet
twice** — the most expensive possible mistake on a 2G connection.

**`districts.php`** — matching walked all seventeen districts calling
`remove_accents()` and a regex over every name, alias and town, on every lookup.
Now built once into a flattened map: **98% less work** over 500 lookups.

Every district also now answers to "X Town". Only Mon, Phek, Wokha and Meluri
carried that alias — yet `kaamase_sanitize_district`'s own note has always used
*"somebody wrote Kohima Town instead of Kohima"* as its example of what must not
cost a registration. Kohima was one of the thirteen where it didn't work.

**`taxonomies.php`** ⚠️ *2nd revision* — trade terms were refetched and
reflattened on every match. Cached per request.

**`webhook.php`** ⚠️ *2nd revision* — **failed payments are now handled.** Nothing
listened for them, so a subscriber whose card expired simply stopped being
charged, ran to the date the last payment bought, and lapsed — the first anyone
knew was the customer asking where their allowance had gone.

`payment.failed` and `subscription.pending` are now recorded and the person is
emailed, **once a day at most** (Razorpay retries, and four identical worrying
emails is the fastest way to make someone cancel out of confusion). Nothing is
taken away on a failure — the existing `halted` handler still deals with a
subscription that gives up.

The `failed` status was already in the account screen's vocabulary as "did not go
through". Nothing had ever written it.

**Test:** in Razorpay test mode, trigger a failed subscription charge. The
customer should get one email, and the payment should show as "did not go
through" on their account screen.

## 14. Translation templates, and a spelling that must not be "fixed"

Every user-facing string in all three packages already goes through a translation
function — rare discipline, and clearly done on purpose for the Nagamese build.
But **no `.pot` file shipped anywhere**, so nobody could start translating: there
was nothing to open.

Generated now, **1,699 strings**: 1,303 core, 228 theme, 166 payments. Contexts
and plural forms carried through. Escaping was checked — two core strings contain
literal double quotes and would have made the file unreadable to every
translation tool had they gone in raw.

**`kaamase-pay` never loaded its text domain.** It declared `Text Domain:
kaamase-pay` but had no `Domain Path` header and no `load_plugin_textdomain()`
call, so its 166 strings could never have been translated whatever anyone put in
a `.po` file. The other two packages both did this; this one was missed. Fixed.

**`Aquqhnaqua` is now marked in `districts.php` as correct and not to be
changed.** It is a circle of Dimapur district and that is the local spelling. It
looks like a typo from outside Nagaland and has already been queried once, so the
note is there to stop the next person quietly correcting it into something wrong.

**To start translating:** open the `.pot` in Poedit, save as
`kaamase-core-nag.po` (or your chosen locale code) in the same folder, and Poedit
writes the `.mo` WordPress actually loads.

### Rebuilt, because the code moved and the template did not

`kaamase-core.pot` was last built on **1 September** and everything added since
was missing from it — Sign in with Apple, Google sign in, setting a password, and
connecting providers were all absent, so none of it could have been translated.

Rebuilt from the plugin as it now ships: **1,556 strings**, up from 1,460. The 102
newly captured strings come from `number-requests.php` (39), `google-signin.php`
(25), `account-password.php` (15), `apple-signin.php` (15),
`account-providers.php` (11) and a handful elsewhere.

**Six entries were wrong before and could never have worked.** They are the long
email bodies written as double-quoted PHP strings, where `\$` escapes the dollar
so it is not read as a variable. The old template stored the backslash, so the
msgid read `%1\$s` while WordPress looks up `%1$s` at run time. A translator could
have translated those six perfectly and not one word would ever have appeared.
They now match what the code actually asks for. Nothing was lost in the rebuild —
every one of the six has a corrected entry.

**Translator comments are now carried through**, 101 of them. These are the
`/* translators: %1$s: district */` notes already written through the codebase,
and they were absent from the old template. Without them somebody translating
`%1$s %2$s needed in %3$s` is guessing which placeholder is the trade and which
is the town, and word order is exactly what changes between English, Nagamese and
Hindi.

**Keep it in step.** Any change that adds or edits a user-facing string needs this
file rebuilt, or the new wording silently cannot be translated. It is a generated
file — never hand-edit it.

## 15. `kaamase-core/includes/rest-auth.php` — sign a lost phone out

⚠️ *2nd revision — copy again.*

An account can hold ten app tokens and **nothing ever showed them**. The device
label, creation time and last-used time have all been stored since tokens were
added; none of it was ever shown to the person it belongs to.

So somebody whose phone was stolen had two options: change their password —
which signs out every device *including the one in their hand* — or do nothing.

The dashboard now lists the phones the app is signed in on, most recently used
first, with a button to sign each one out and a second to sign out everywhere.
Expired tokens are left out rather than shown as dead rows.

**The credential never reaches the browser.** The handle printed into the page
and posted back is a hash *of* the stored hash, so the value in the form cannot
be turned back into a working token. Compared with `hash_equals`.

The panel only appears for an account that has actually opened the app — a worker
who never has gets no heading about phones.

Token issuing, hashing, revocation, lookup and bearer authentication are all
byte-identical. **No app release** — this is a website screen that revokes
tokens the app already uses; a signed-out phone simply gets a 401 on its next
call and asks the person to sign in again.

**Test:** sign in on the app, then open your dashboard on the website. The phone
should be listed. Sign it out, and the app should ask you to sign in again on its
next action.

## 16. Many more trades — three files

⚠️ *`taxonomies.php` is a 3rd revision and `contact.php` a 2nd — copy both
again. `kaamase-core.php` is new to the list.*

**From 5 categories and 41 trades to 13 categories and 98 trades.** The site was
built around construction, home services, repair and farm work. A teacher, a
receptionist, a nurse, a shop assistant, a delivery rider or a graphic designer
had nothing to pick.

New headings: **Teaching and childcare**, **Office work**, **Shop and sales**,
**Health**, **Hotel and food**, **Driving and transport**, **Computer and
design**, **Craft and making**, and **Other work**.

### Why not simply add every trade on the list

Three kinds of entry would have split one trade's results across two terms, which
is the exact failure this file's own header warns about:

- **Gendered pairs** — Waiter and Waitress are one job. Two terms means an
  employer browsing Waiter never sees half the people who can do it.
- **Seniority as a trade** — Store Manager, Office Manager, Hotel Manager. That
  is a level, not a trade, and it belongs in the job description.
- **Near-synonyms** — Sales Executive and Salesperson, Delivery Rider and
  Delivery Agent, Teacher and Assistant Teacher.

Each of those is now **one trade carrying the other spellings as aliases**, so
searching either word still lands on the right term. 94 trades carry 284 alias
spellings between them.

Local vocabulary went in the same way, so the word actually used here finds the
trade: *motor mechanic*, *ANM* and *GNM*, *ASHA* and *anganwadi*, *ward boy*,
*DEO*, *peon*, *telecaller*, *KG teacher*, *PT teacher*, *saloon*, *JCB
operator*, *sumo driver*, *handloom*, *flex printing*, *AutoCAD*, *tally
operator*, *piggery*.

### A matching bug found while testing

Trade matching ran its alias list **before** it compared against real trade
names. So once **Taxi driver** and **Furniture maker** existed as trades of their
own — and were *also* listed as words for Driver and Carpenter — a spelling that
missed the exact-name check landed on the wrong one. Typing `taxi  driver` with
two spaces gave you Driver.

Real trade names are now compared first, aliases second, loose matching last. The
two colliding aliases are gone, but the ordering is what stops this recurring the
next time a trade is added whose name is already somebody's alias elsewhere.

`Salon` was also listed under both Beautician and Barber, so which one you got
depended on map order. It now goes to Barber, which is what the word means on a
signboard here; the parlour side keeps `Parlour`, `Beauty parlour` and `Beauty
salon`.

### Nothing existing is renamed or deleted

Trades already on your site are left exactly as they are. The only change to an
existing term is **which heading it appears under**, where the new categories
give it a better home — Tutor moving from Other work to Teaching, for example.
Nothing attached to a term moves with it, so no worker's profile changes.

### Why `kaamase-core.php` has to be copied too

`KAAMASE_CORE_SCHEMA` goes **2 → 3**. That constant is what tells the plugin to
re-run its seeding on the next load. Without it the new trades sit in the code
and **never appear on your site**. `KAAMASE_CORE_VERSION` and the plugin header
are aligned to `1.4.0` in the same file — they disagreed before (`1.3.0` against
`1.3.1`).

### The safety gate — one addition, and one thing for you to decide

`kaamase_protected_trades()` in `contact.php` gates **maid, house cleaner, cook,
babysitter and caregiver** behind the extra safety check, because that is work
done alone in a stranger's home. Adding new trades of that kind without putting
them on the list would be a hole in the gate rather than a decision, so:

- **Home nurse or attendant — added to the gate.** It sits in Home services
  beside all five of the above and is the same situation: one person, alone, at
  an address a stranger gave them.
- **Housekeeping staff — deliberately not added.** It sits under Hotel and food
  and means hotel housekeeping: a workplace with colleagues and a manager. That
  is not what this gate is for, and gating it would slow those jobs down for no
  safety gain.

**Your call, not mine:** **Nurse**, **Physiotherapist**, **Medical assistant**
and **Tutor** are *not* gated. Each is usually clinic-, hospital- or
school-based, but each is sometimes a home visit. Adding them makes those jobs
slower to post; leaving them off means the protection does not apply on the
occasions the work *is* at somebody's house. Say the word and it is one slug each
on that same line.

**Test:** after copying both files, load any page once. Open **Workers → Trades**
in the admin — you should see 13 headings and 98 trades. Then post a job and
check the trade dropdown groups them under the new headings. Search the worker
list for `saloon`, `ward boy` and `telecaller`; each should return the right
trade rather than nothing.

## 17. `kaamase/front-page.php` — "See all" moved under the cards

⚠️ *`kaamase.pot` is a 2nd revision — copy it again with this one.*

Both front page sections put their **See all** link level with the heading, at
the top. On a phone the card grid is one column, so six cards is a long scroll
and that link has been off the screen since the second card. Somebody who read
the whole list and wanted more had to scroll back up to find it.

It now sits **under the last card**, which is where that person already is. Both
sections: *Work posted recently* and *Available now*.

Two smaller changes came with it:

- **The links are named.** `See all` became **See all jobs** and **See all
  workers**. Beside a heading the word *all* borrowed its meaning from the
  heading; under the cards it no longer does. It also reads correctly to
  somebody using a screen reader, who hears links out of order.
- **Outline instead of ghost.** The ghost style is deliberately low-contrast,
  which suits a small link tucked beside a heading and does not suit the one
  thing you want tapped at the end of a list.

No new CSS. `ka-btn--outline`, `ka-center` and `ka-mt-6` were already in
`style.css`, so `style.css` is **not** in this change and does not need copying.

**This is the website only.** If the app's home screen has the same pattern it is
a separate change on that side — the app does not read this template.

**Test:** open the front page on a phone. Scroll to the bottom of *Available
now*; the button should be there, and it should go to the workers listing. Same
for *Work posted recently*.

## 18. `more-trades.php` — a clash that would have swallowed 16 of the new trades

⚠️ *`taxonomies.php` is a 4th revision and `kaamase-core.pot` a 2nd — copy both
again. `more-trades.php` is new to the list. **Read this one before deploying fix
16.***

**This was my mistake, caught before you copied anything.** `more-trades.php` was
already on your site and I did not account for it when I expanded the trade list.

It adds trades through a filter that runs **after** the main list. Three of its
lines assign a whole category instead of adding to one:

```php
$seed['office'] = array( ... );   // replaces everything under 'office'
```

Once the main list grew its own **Office work**, **Hotel and food** and
**Computer and design** headings, that filter emptied all three and put its own
two or three trades there instead. Deploying fix 16 as it stood would have given
you:

- **16 of the new trades never created** — 4 office, 4 hotel and food, 8 computer
  and design, including Receptionist, Data entry, Waiter, Chef, Graphic designer
  and Web developer.
- **16 headings instead of 13**, with **two called Health** and a **Teaching**
  sitting beside **Teaching and childcare**.
- **Teacher, Nurse, Health worker and Heavy vehicle driver filed under the wrong
  one of each pair.**

### What changed

**The filter is gone from `more-trades.php`.** Its whole list is now in
`taxonomies.php` by name, so nothing it created is lost.

**Four trades it had that the new list did not are now in the list**, marked
*(general)*: **Shop and sales**, **Mechanic**, **Design and video**, **Computers
and websites**. Profiles on your site are attached to these, so they are not mine
to drop. That takes the count to **102 trades in 13 categories**.

**The rest of `more-trades.php` stays and still matters.** It writes a "what this
covers" description onto each trade, and — see below — sends those to the app.

**A safety net in `taxonomies.php`.** It detaches that old filter itself before
building the list. These files are copied by hand, one at a time; if
`taxonomies.php` arrives and `more-trades.php` does not, the site must still be
correct. Both orders are tested.

### The leftover headings

Your site will still have empty **Teaching**, **Health** and **Vehicles**
categories after this, because seeding never deletes anything. They will **not
appear anywhere** — a category with no trades under it is skipped by every
dropdown. You can delete them by hand in **Workers → Trades** whenever you like,
or leave them.

`kaamase-core.pot` was also regenerated: it was built before the trade expansion
and was missing every new trade name. 1370 strings now.

**Test:** after copying, **Workers → Trades** should show 13 headings, and the
job form dropdown should list Receptionist, Waiter and Graphic designer — the
three that would have gone missing.

## 19. Who is hiring — the employer directory

⚠️ *`install.php`, `rest-api.php`, `kaamase-core.php` and both `.pot` files are
new revisions — copy again. `employer-index.php` is a **brand new file**.
`template-tags.php`, `setup.php` and `style.css` are new to the list.*

⚠️ **If you already copied `rest-shape.php` from an earlier version of this
section, put the original back — see fix 21.** It should not have been in the
list at all.*

Workers could see jobs, and could see other workers. They could **not** see
employers. So the only thing a worker knew about whoever posted a job was the one
line in the advert — and the question every worker actually asks first, *has this
person hired anybody before and did they pay*, had nowhere to be answered.

Every employer profile has stored **how many workers they have hired** and **what
those workers rated them** since the beginning. None of it was ever shown. This
is the page that shows it.

**A new page at `/employers/`, titled "Who is hiring".** Filter by district and
by kind (individual, contractor, company); sort by newest, most workers hired,
best rated, or verified only.

### Who can see it, and why not paid-only

**Signed in with a confirmed email. Not restricted to paying accounts.**

You asked for this as a paid perk and I have built it so you can make it one in a
single line (below) — but I would not start there, for a reason worth a paragraph:

An employer is listed here *so that workers approach them*. That exposure is the
thing they are paying you for. Putting the directory behind a paywall hides the
employers from the workers they want to reach, which is backwards — you would be
charging employers for a listing and then charging workers to look at it. The
thing worth selling is being **prominent in** this list, not the list itself.

It is kept off the open internet because a public page listing every business on
the platform, with district and hiring history, is a scrapeable directory of
local businesses that no employer agreed to when they registered.

**If you want it paid-only anyway**, it is one filter in your theme's
`functions.php` — no file in this list changes:

```php
add_filter( 'kaamase_may_browse_employers', function ( $may, $user_id ) {
    return $may && kaamase_has_plan( $user_id );
}, 10, 2 );
```

### No phone numbers on it

Contact still runs through `kaamase_can_contact()` one profile at a time, counted
against the daily reveal cap your code calls *the main protection against somebody
harvesting phone numbers*. A directory that printed numbers would be a way around
that cap rather than a feature of it. Verified twice: no phone field reaches the
page or the API response.

### The sort uses the LEFT JOIN helper

Sorting by hires or rating goes through `kaamase_number_sort_args()` from fix 2.
Sorting the ordinary way would have hidden **every employer who has not hired
anybody yet** — on a directory whose whole point is showing who is here, that is
the worst possible failure.

### The app

`GET /employers` and `GET /employers/{id}` are added, using the same filter and
sort function as the page so the two cannot drift apart. **The app needs a screen
built for it** — see the note I gave you separately. Until then the website has
it and the app does not; nothing in the app breaks.

### The menu — nothing for you to do

**You do not create the page and you do not touch the menu.** The plugin creates
the page on the schema bump, and the link is added in all three places a menu
appears, by two paths that cover both ways a WordPress site can be set up:

- **No menu assigned in Appearance → Menus** (which is how your site runs now —
  Find workers, Find work and Post a job come from the theme's fallback): the
  fallback itself now carries **Who is hiring**. Desktop header and mobile
  drawer both.
- **A menu assigned later**: the fallback stops running the moment you assign
  one, so a `wp_nav_menu_items` filter adds the item to the header and drawer
  menus as well. It checks for the link first, so if you ever add it by hand it
  is not added twice.
- **Footer**, Browse column: added to the theme's own list, beside Hire a team.

All three are shown to **signed-in visitors only**. Signed out, the link is
hidden rather than leading to a sign-in wall — a header link that always ends in
a wall teaches people the header is not worth reading.

**Test:** load any page once so the page gets created. Sign out and visit
`/employers/` — you should get a sign-in card, not a list. Sign in with a
confirmed account and the list should appear. Check that a brand new employer who
has hired nobody still shows when you sort by "Most workers hired".

## 20. The page that never got created — read this if `/employers/` 404s

⚠️ *`install.php`, `kaamase-core.php` and `kaamase-core.pot` are new revisions —
copy again. **This supersedes the schema bump in fix 19.***

**This was my mistake, and it bit on the live site.**

Fix 19 created the Who is hiring page inside `kaamase_upgrade()`, which runs once
— when the schema number stored in the database is lower than the one in
`kaamase-core.php`. On a site whose files are replaced **one at a time, by hand,
while visitors are on it**, that is a race:

1. `kaamase-core.php` lands, carrying schema 4.
2. A visitor loads any page. The upgrade runs — using the **old** `install.php`,
   whose page list has no employers page. It creates nothing, and writes 4.
3. The new `install.php` lands a minute later. Its page list is never read,
   because the schema already matches and the upgrade never runs again.

The page is then missing **permanently**, re-copying the files does not fix it,
and the only symptom is a 404 on a URL the menu is already linking to.

### The fix, in two parts

**Schema 4 → 5**, so the upgrade runs once more on your site and creates the page.
That alone unblocks you.

**Page creation no longer depends on a version number.** A new
`kaamase_heal_pages()` runs on admin screen loads, compares the defined pages
against what exists, and creates anything missing. The definitions are the
authority now, so a page can never again go missing because of the order files
were copied in. It skips AJAX and cron, so nothing on the front end pays for it,
and it stops as soon as everything is present.

### What to do

Copy these three, `kaamase-core.php` **last**:

1. `fixed/kaamase-core/includes/install.php`
2. `fixed/kaamase-core/languages/kaamase-core.pot`
3. `fixed/kaamase-core/kaamase-core.php`

Then **open wp-admin** — any screen. That is what triggers the heal. Then load
`/employers/`.

If it still 404s, the page exists but its URL rule is stale: **Settings →
Permalinks → Save Changes**, without changing anything.

## 21. `rest-shape.php` — put the original back

⚠️ **`rest-shape.php` has been removed from the copy list. If you already copied
it, restore the original from your `.zip`.** Nothing else in fix 19 changes.

**My mistake, and this one would have shown up in the app rather than on the
website.**

`kaamase_shape_employer()` **already existed** in the plugin. I did not check, and
wrote a second copy of it for the new directory. Both are wrapped in
`if ( ! function_exists( ... ) )`, so the one defined first wins — and mine was
first. Every existing use of that function silently got my version instead of the
real one:

- **`kind` was renamed to `employer_type`.** Anywhere the app reads an employer's
  kind, it would have found nothing.
- **`gst` was dropped** from the detailed response.
- A post-type check I added could return nothing where the original returned a
  profile.

That function has **five callers in `rest-api.php`** and **two filters hooked onto
it** (`rich-manu.php`, `verified-mark.php`), so this reached far more than the new
page.

The original does everything the employer directory needs. My copy is deleted,
and `rest-shape.php` is now **byte-identical to your original upload** — verified
with `cmp`. That is why it has been taken off the list rather than given a new
revision.

**If you already copied it:** take `rest-shape.php` from the original
`kaamase-core` `.zip` and put it back. If you have not copied it yet, do nothing.

**What the app should read:** an employer's kind is **`kind`**, not
`employer_type`, and the detail response includes **`gst`**.

## 22. Subscriptions — saying the same thing on both screens

⚠️ *`kaamase-pay.pot` is a 2nd revision — copy again. `access.php` and
`account.php` are new to the list.*

Four separate faults, all of them about a person not being able to tell what they
had paid for or how to stop paying.

### The app had nowhere to send anybody

`manage_url` was built as `$offer ? kaamase_pay_plans_url() : ''`, and `$offer`
requires the platform to be one we may sell on — which is the website only. So on
both phones it came through **empty**, and the app's "you can manage it from your
account page" had nothing to tap.

That treated two different permissions as one. An app store forbids sending
somebody out to a website to **buy**. It does not forbid showing somebody where to
look at what they already bought — and a customer who cannot find that has a worse
problem than one who cannot buy.

**`manage_url` is unchanged**, including staying empty on both phones, because it
is the *buying* link and that rule has not moved. A separate **`account_url`** now
carries the dashboard, and only ever for an account that already has a plan, so it
can never become a route to a purchase page.

### A plan bought for life was shown as expiring in 2126

A lifetime purchase stores its expiry 36,500 days out. The website called
`kaamase_pay_is_endless()` and said *"This does not end."* The API sent only the
raw timestamp, so the app formatted it as a date: *"Runs until 1 August 2126, then
stops on its own."* One purchase, two front doors, opposite answers.

There is now one function, `kaamase_pay_plan_state()`, that decides which of four
states an account is in — **none**, **endless**, **renewing**, **ending** — and
writes the sentence. The website prints it and the app receives it. They cannot
disagree again.

### No Stop button, and no explanation

The Stop renewing control only renders when there is a live subscription, which is
correct — a one-off purchase has nothing to stop. But the screen simply ended
there, and somebody hunting for a way to stop being charged concluded the control
was broken or hidden rather than absent. Both screens now say **"There is nothing
to cancel and nothing more will be charged."**

### "I cancelled and it still says Premium"

That is correct behaviour that was never explained. Cancelling ends the
**subscription** and deliberately leaves the plan and its expiry alone — you keep
what you already paid for. With a lifetime expiry, cancelling therefore looks like
it did nothing. The new sentence and note make the distinction visible.

### Backwards compatible on purpose

Every key the app already reads — `name`, `active`, `expires`, `renews`,
`charging`, `can_buy_here`, `can_buy_in_app`, `manage_url` — keeps its exact
meaning and value. The six new ones are additive, so **your current app build
keeps working unchanged** and can adopt them whenever you ship.

### Not touched

`checkout.php`, `webhook.php`, `razorpay.php`, `plans.php`, `settings.php`,
`store-webhook.php`, `subscribers.php`. **All three Razorpay signature verifiers
are byte-identical**, checked again after this change.

**Still open, deliberately:** the server does not record *where* a plan was bought,
so web and App Store purchases are indistinguishable afterwards and the app cannot
yet say "cancel this in your App Store settings". That needs `store-webhook.php`
and only matters once in-app purchases are switched on.

**Test:** open your dashboard. The plan line should read the same as before, with a
new line under it saying there is nothing to cancel. Then check `/wp-json/kaamase/v1/me`
— `plan.status` should be `endless`, `plan.account_url` should be your dashboard,
and `plan.manage_url` should still be the plans page on the website.

## 23. Where a plan was bought, and where it gets cancelled

⚠️ *`access.php`, `account.php` and `kaamase-pay.pot` are new revisions — copy
again. `store-webhook.php` is new to the list. **Copy `access.php` before
`store-webhook.php`.***

### The live bug this found

`kaamase_pay_grant()` writes a subscription marker **only when handed a Razorpay
subscription id**, and `store-webhook.php` called it without one. So an Apple or
Google **auto-renewing subscription left the server believing nothing renewed**.

Combined with fix 22, an iPhone subscriber was shown:

> Runs until 3 September, then stops on its own. Nothing renews.

…on the morning Apple charged them again — and `can_cancel` was false, telling
them there was nothing to cancel when Apple was billing them monthly. That was
live from the moment in-app purchases were switched on.

### How store billing actually works, for the record

RevenueCat is the middleman. The app buys through the store, RevenueCat verifies
the receipt, then POSTs to `/wp-json/kaamase-pay/v1/store`. A shared secret in the
`Authorization` header is the authentication; `event.app_user_id` is the WordPress
user id; `event.id` is claimed once so retries cannot double-grant. All three
payment routes end at `kaamase_pay_grant()`.

**Nothing about that verification changed.** The secret check is untouched.

### What is recorded now

RevenueCat sends `event.store` on every event, and `store-webhook.php` was already
writing it into the payment row's `note` — so **the origin of every store purchase
you have taken so far is already in your database**. It just was not anywhere the
plan screen could reach.

- **`kaamase_pay_grant()` takes an optional `$origin`, defaulting to `razorpay`.**
  The default is what makes `checkout.php` and `webhook.php` need no edit: all
  three of their calls are Razorpay payments. The store webhook passes its own.
- **A store subscription now records that it renews**, in a key of its own —
  deliberately **not** the Razorpay one, because the website's Stop button feeds
  that key straight to Razorpay's cancel API. An Apple id in there would have
  produced a button that fails, which is worse than no button.
- **Anyone who bought before this shipped** gets their origin worked out once from
  their payment history and written down, so nothing needs migrating by hand.

### What each person is now told

| Bought via | Website | App |
| --- | --- | --- |
| Razorpay subscription | Stop renewing button, as before | `cancel_where: web`, open `account_url` |
| App Store | No button, and says why | `cancel_where: app_store` |
| Google Play | No button, and says why | `cancel_where: play_store` |
| Lifetime, any route | Nothing to cancel | `can_cancel: false` |

The website's Stop button is now gated on `cancel_where === 'web'`, so it can never
fire a Razorpay cancel against a store subscription.

An unrecognised store value returns empty rather than a guess — a wrong answer here
sends somebody to the wrong place to cancel, which is worse than admitting we do
not know.

### Not touched

`checkout.php`, `webhook.php`, `razorpay.php`, `plans.php`, `settings.php`,
`subscribers.php`. All three Razorpay signature verifiers byte-identical, checked
again.

### Cancelling in Apple's settings

The same bug pointed the other way, found while checking the finished response.

RevenueCat sends `CANCELLATION` when somebody turns the subscription off in their
Apple or Google settings. This file deliberately does nothing then, because access
is still owed until the date they paid to — that part is right and is unchanged.
But the renewal flag was left set, so after cancelling, the app would still have
said **"Renews on 3 September"** when the third is the day it *ends*.

`CANCELLATION` and `EXPIRATION` now clear the renewal flag and nothing else.
`BILLING_ISSUE` deliberately does not: that is a card that failed during a grace
period, and the subscription is still meant to renew once it is paid.

**Test:** on your dashboard the plan line should read as before. Then check
`/wp-json/kaamase/v1/me` — `plan.origin` and `plan.cancel_where` should be present.
If you have a test App Store subscription, confirm it says *Renews on…* rather than
*stops on its own*; then cancel it in iPhone Settings and confirm it flips to
*Runs until…* while you keep access.

## 24. Phones signed in — now reachable from the app

⚠️ *`rest-api.php` and `kaamase-core.pot` are new revisions — copy again. Nothing
else changes.*

Fix 15 gave the website a list of the phones an account is signed in on, with a
button to sign each one out. I said at the time that it needed no app release,
which was true and also the wrong way round: **the person whose phone was stolen
is holding a different phone, and the thing in their hand is far more likely to be
the app than a browser.** The functions existed; nothing ever exposed them.

Two endpoints, in one file:

- **`GET /devices`** — the same list the website shows.
- **`POST /devices/revoke`** — takes `device`: a handle from the list, or
  **`others`** (everything except the phone asking — what somebody whose phone was
  stolen actually wants) or **`all`** (everything, matching the website's Sign out
  everywhere).

### The bit that makes it usable

Every row is called "Kaam Ase app". Four identical rows is useless for the job
this exists to do — nobody dares sign anything out when they cannot tell which
one is in their hand. So each row carries **`is_current`**, worked out by the
server, because the phone knows its own token but not which stored entry that
token became.

The handle compared is a hash *of* the stored hash, so nothing that could be
replayed as a credential is computed or returned.

### On letting the app do this at all

Whoever holds a stolen phone holds a valid token, so they could already read
everything the account can see. Letting them sign the owner out is a delay rather
than a new power, and the owner's recovery — a password reset on the website —
revokes every token anyway.

It is rate limited to 20 sign-outs an hour per account, which is far more than any
honest person needs and not enough to grind through anything. Refusals come back
as `429` with a message that points at the website.

`others` revokes each other handle rather than revoking everything and re-issuing.
Re-issuing would hand back a new token the app would have to notice and store, and
a phone that missed that reply would be signed out by the very action meant to keep
it signed in.

### Not touched

`rest-auth.php` is unchanged. Token issuing, hashing, lookup and bearer
authentication are all byte-identical, checked again.

**Test:** sign in on two phones, open the list on one, and confirm exactly one row
says it is this phone. Sign the other out and confirm it is asked to sign in again
on its next action.

## 25. "Not confirmed" — a list you can ring

⚠️ *`not-confirmed.php` is a **brand new file**. `kaamase-core.pot` is a new
revision. **Nothing existing is edited** — not one line.*

An account that never confirmed its email has a profile nobody can find. The
person registered, so they wanted the work; they just never opened the link. Some
do not use email at all and gave an address because a form asked for one. Sending
them another email saying "check your email" is not a plan.

There was no way to reach them. A phone number could only be read one profile at a
time, by opening that profile on the website and pressing reveal, and **nothing
anywhere said which accounts were unconfirmed** — so the people most in need of a
phone call were the hardest to find.

**Users → Not confirmed** now lists them: name, worker or team or employer, phone
as a `tel:` link, district and town, email, and when they registered. Newest first,
50 to a page. The name links to their profile.

### What it deliberately is not

**Not a directory of everybody's number.** Only accounts that have not confirmed,
because that is the job it exists for, and a list of every phone number on the
platform sitting in wp-admin is a liability nobody asked for. A confirmed worker's
number is still read the way it always was.

### How it reads a number

Through **`kaamase_field()`** — the filtered reader with the privacy rule attached,
which answers for administrators because `kaamase_can_see_private()` already says
so. Going to post meta directly would have worked and would have been wrong: it
would put a second, unguarded route to a phone number into the codebase, and the
whole design of `fields.php` is that there is exactly one. If those rights are ever
narrowed, this screen empties out on its own.

**No quota is spent.** It does not go through `kaamase_can_contact()`, because that
counts reveals to guard against harvesting by strangers, and this is the platform's
own support work on its own accounts.

`manage_options` only, checked when the menu is added **and again when the page
renders**, because the first only hides a link and this page prints phone numbers.

### Not changed

Nothing. No existing file is edited, no filter is added, and the only hook is
`admin_menu`. `kaamase_can_see_private()` is byte-identical.

**Test:** open **Users → Not confirmed**. You should see the accounts you have been
chasing, with numbers. Ring one; when they confirm, they drop off the list on the
next page load.

## 26. `kaamase.com/app` — why the store link kept stopping

**Not a file to copy.** `fixed/wpcode/app-redirect.php` replaces the snippet in
**WPCode → Code Snippets**. Open the existing one, select all, delete, paste.
Do not run both.

### It was a page cache, not the code

The redirect is PHP, and PHP only runs when a request actually reaches WordPress.
A page cache saves a finished copy of the HTML and serves it from disk, so
WordPress is never asked and `template_redirect` never fires.

That is the whole pattern:

1. You save the page. The cache is emptied.
2. You open the link on your phone. Nothing is cached, PHP runs, you land in the
   store. It works.
3. **Somebody on a desktop opens it** — or a bot crawls it, or you check it on
   your laptop. PHP runs, decides correctly not to redirect, and the cache saves
   *that* page: the one with two buttons.
4. Every visitor after that, phone or not, is handed the saved copy. No PHP, no
   redirect.

One desktop visit poisons it for everybody, which is why it took minutes or hours
and felt random.

**The JavaScript did not save it either.** WordPress strips `<script>` from page
content for anybody without `unfiltered_html`, so visitors never received it. You
did, because administrators keep that capability — which is exactly why it looked
fine when you tested it yourself.

### What the new snippet does differently

- **Tells every cache never to store `/app`** — `DONOTCACHEPAGE` (LiteSpeed, which
  is what Hostinger runs, plus WP Rocket, W3TC and the rest), LiteSpeed's own
  switch, `nocache_headers()`, and `Vary: User-Agent` for anything in between.
  This is the actual fix.
- **Prints the JavaScript from the snippet**, not from page content, so nothing
  can strip it. If a cached copy is ever served anyway, the script still sends
  people to the right store.

The server redirect stays the fast path — no flash of the page, works with
JavaScript off. The script is the safety net.

### After pasting it

**Purge your cache once.** The poisoned copy is still sitting there, and until it
is cleared the old page keeps being served no matter what the snippet says.
Hostinger → hPanel → Performance → Clear cache, and LiteSpeed Cache → Toolbox →
Purge All if that plugin is installed.

### Your page content needs no change

The two buttons stay exactly as they are and are now the fallback for desktop.
The `<script>` block still in the page is harmless — it is stripped for visitors
and duplicated harmlessly for you.

**Test:** on a phone, open `kaamase.com/app`. Then open it on a laptop — you
should see the buttons. Then go straight back to the phone: **that is the test
that used to fail.** It should still go to the store.

## 27. View counting — phase 1 of 3, and nothing shows yet

⚠️ *`views.php` is a **brand new file**. `privacy.php` and `kaamase-core.pot` are
new revisions.*

The counting half of the views feature. **Nothing appears anywhere on the site or
in the app** — that is deliberate. Let it run for a few days so the numbers have
something in them before the first person sees one. A profile that says *0 views*
on the day the feature arrives is worse than no number at all.

Full plan, including phases 2 and 3: the build plan page.

### After copying, open wp-admin once

The table is created on an admin page load, not by a version number. **Nothing is
counted until you have loaded any wp-admin screen once.** That is not an
oversight — tying setup to a version number is exactly what lost the employers
page in fix 20, and making a table from whatever request happens to arrive first
is worse. An admin load is rare, unhurried, and somewhere a failure is visible to
somebody who can act on it.

### What counts as a view

- **Total, not unique.** Somebody coming back that evening counts again.
- **At most once per viewer per 30 minutes.** Refresh-spam counts once. Proved
  against a real database, not asserted.
- **Never your own page.** An owner checking their profile all day would
  otherwise be most of their own audience.
- **Never staff.** Moderating a hundred profiles would put a view on each.
- **Never crawlers.** Same list the store redirect uses.
- **Strangers count**, and cannot block each other.

### One row per viewer per day

Not a row per view. A busy profile is a handful of rows a day rather than
hundreds, and both numbers come from one query: `SUM(hits)` is how many views,
`COUNT(DISTINCT viewer)` is how many people. In testing, 8 views were held in 6
rows.

### People who are not signed in

They count and there is nobody to name. Telling one stranger from another needs
something per visitor, and **an IP address is not worth keeping**: `visitor_key`
is a one-way hash of the address, the browser, and **today**, salted with the
site's own key. It cannot be reversed to an address, it is a different value for
the same person tomorrow, and the daily prune removes it entirely. Verified: the
key is hex only and contains no fragment of the address.

### Forgetting

Three ways a row stops being wanted, all handled:

- **A year passes** — pruned on the existing `kaamase_daily` cron, in batches of
  1000 so one statement cannot hold a lock long enough for the site to notice.
- **The profile or job is deleted** — its views go with it.
- **Somebody erases their account** — every record of **what they looked at** is
  deleted. This is the one that matters: views *of* their profile vanish with the
  profile, but the record of *their browsing* sits on other people's profiles and
  would otherwise survive them entirely. Other people's counts fall by whatever
  that person contributed, which is correct — those views were theirs.

### Not changed

Only `privacy.php` is edited, and only to add the erasure call. No template, no
query, no listing, no API. `exposure.php` is untouched.

**Test:** copy both files, load any wp-admin screen once. Then open a worker
profile from a signed-out browser. Nothing visible changes — that is correct.
To confirm it is working, in phpMyAdmin check that `wp_kaamase_views` exists and
has a row in it.

## 28. "Get the app" on a phone

⚠️ *`app-banner.php` is a **brand new file**. `functions.php`, `style.css` and
`kaamase.pot` are new revisions.*

### 28b. And a block on the home page, above the two doors

⚠️ *`app-banner.php` and `front-page.php` and `style.css` are new revisions.*

The two offers above each need a particular browser. Apple's banner is drawn by
Safari and nothing else; the strip is Android only. The owner's report from
Nagaland is that **almost nobody there opens Safari by choice**, so on an iPhone in
Chrome the app was invisible. A block in the page itself has no such condition: it
is on every device, in every browser, and — not a small thing after an evening of
hunting — the owner can look at their own home page and see it.

Placement above the two doors was the owner's call. My advice was below them,
because those two doors are the only two things on that page that grow the
business and an app link above them sends somebody to a shop before they have
made a profile or posted a job. The block is therefore kept to one low row rather
than a banner, so it costs the doors as little height as possible.

**Two links, one per store, not one link to `/app`.** `/app` has to work out which
phone it is talking to from the user agent, and that guess has already been wrong
once (fix 26). Somebody who can see both shops cannot be sent to the wrong one.
The iOS address is built from `kaamase_app_store_id()`, so the App Store number is
still written down in exactly one place.

**The small line on each button names the phone, not the shop** — *iPhone* over
*App Store*, *Android* over *Google Play*. Somebody choosing between the two knows
what is in their hand and may never have heard of an App Store.

The two shop marks are drawn as inline SVG rather than fetched, because rule 2 of
the design system is no external requests and a village connection should not wait
on two logos to read the page. These are our own buttons, not facsimiles of
Apple's and Google's official badges. If the official artwork is ever wanted
instead, `kaamase_app_store_links` and the markup are the only two places to touch.

The icon is built from the uploads folder rather than written out as a full
address, so moving the site to another domain does not leave a broken image, and
it falls back to the site icon if the file is ever cleared out.


A quiet strip along the bottom of the screen on Android, and Apple's own banner on
iPhone. Both point at `/app`, so all the store routing from fix 26 does the work
and no store URL is repeated anywhere.

### A strip, not a pop-up

Google demotes a mobile page that covers its own content with a box you must
dismiss before reading. `indexes.php` says the trade and district pages are *"the
only pages on the platform whose job is to be found by somebody who has never
heard of Kaam Ase"* — a pop-up there works directly against the reason those pages
exist. The other reason is the connection: somebody on 2G who has waited for a job
to load should not have to fight a box before reading it.

### iPhone gets Apple's banner instead of ours

One meta tag, drawn by Safari itself. Worth having in place of our own strip
because **Safari knows whether the app is already installed and says OPEN rather
than GET** — nothing on a web page can know that. Our strip returns immediately on
anything that is not Android, so the two can never both appear.

### The browser decides, not PHP

This is the part that matters, and it is the lesson from fix 26 applied before it
could bite again. The site sits behind a page cache: a cached copy is one finished
page handed to everybody, so **anything PHP decides about this particular visitor
gets frozen into it**. The first desktop visitor after a purge would have cached
the no-strip version, and every phone after that would be handed it — exactly what
stopped `/app` redirecting for hours at a time.

So the strip ships to everybody, hidden, and JavaScript reveals it. A cached page
cannot be wrong about who is looking at it. Verified in the tests: no user agent,
no IP, no `wp_is_mobile`, no login check anywhere in the PHP.

### The rest

- Dismissible, remembered for **30 days** in `localStorage`, wrapped in try/catch
  so a private window does not break it.
- Never on `/app`, never on a 404, never in wp-admin.
- Hidden entirely above 900px — there is nothing to install on a desktop.
- Sits clear of the home indicator on a phone with no buttons.
- `style.css` gains one block and **no existing rule changes**.
- `functions.php` gains **one line** in the module list.

**Test:** open the site on an Android phone. The strip should appear at the
bottom; tapping **Get it** should land you in Play. Dismiss it and reload — it
should stay away. On an iPhone you should see Apple's banner at the top instead,
and never both.

---

## 29. Signed-in pages could still be stored by the cache

⚠️ *`kaamase/inc/security.php` is a new revision. Upload it and then purge the
whole cache — this stops new pages being stored, it does not remove ones already
in there.*

**Reported symptom: people signing in and finding somebody else's account.**

`kaamase_no_cache_when_signed_in()` exists for exactly this. Its own note says a
dashboard holds one person's data and that if a shared cache stores it, *"the next
visitor through that cache can be served somebody else's profile"*. It called
`nocache_headers()` and set a `Cache-Control` header, and nothing else.

That is the method fix 26 had already proved does not work on this server. The
`/app` page sent those same headers and LiteSpeed went on handing out a stored
copy for hours at a time; what actually fixed it was `DONOTCACHEPAGE` plus
LiteSpeed's own `litespeed_control_set_nocache`. Response headers ask politely.
Those two are the switch.

So the one function guarding every signed-in page was asking politely, on a server
already known to ignore it.

Now it defines `DONOTCACHEPAGE`, fires LiteSpeed's switch, and then sends the
headers. The constant and the action are also moved above the `headers_sent()`
check: neither needs headers, and skipping both because output had already begun
left the most damaging case with no protection at all.

Proved with the real function source under three conditions — a stranger (nothing
fires, anonymous pages stay fast), signed in (all four fire), and signed in with
headers already sent (the two that matter still fire, where the old code did
nothing).

**This is containment, not a confirmed diagnosis.** It closes a real hole that
produces this exact symptom. Whether it is the hole these particular users fell
through is not something the code can answer on its own — see the checks sent with
this change.

## 30. Why the app saw other people's accounts, and the website never did

⚠️ *`kaamase-core/includes/rest-auth.php` is a new revision. Upload it, then purge
the whole cache again.*

Fix 29 stopped signed-in **pages** being stored. It could not have helped the app,
and the sequence of events says so plainly: app 2.0.6 shipped with the offline
cache fix and the mix-ups continued; uploading `security.php` and purging stopped
them the same day. Something on the server was holding the wrong answer, and the
purge is what let go of it.

### What a page cache thinks "the same request" means

The address, and the cookies. Nothing else.

A browser carries the WordPress sign in cookie, so the cache sees two different
people and steps aside. **The app carries no cookies at all.** It signs in with an
`Authorization: Bearer` header, which the cache does not read and does not know
exists.

So every phone asking `GET /wp-json/kaamase/v1/me` looks identical to the cache:
same address, no cookies. It stored the first answer and handed that one person's
name, telephone number and profile to every phone that asked afterwards.

That accounts for every fact — app only, website clean, purge fixed it instantly,
and the app release did not.

### Why fix 29 could not reach it

WordPress serves a REST route and stops inside `parse_request`. `send_headers`,
where the theme's guard lives, runs after that and never fires. An answer to the
app passes none of the page protections, so it needs its own.

### And why the purge alone was not the end of it

Nothing stopped the next answer being stored the same way. The cache was empty,
so the first app request after the purge became the new stored copy. It was going
to come back.

`rest_api_init` now sets `DONOTCACHEPAGE`, fires LiteSpeed's switch, and sends the
headers for any request carrying a bearer token or a sign in cookie. Public
answers are deliberately left cacheable — a trade listing is the same for
everybody and worth caching on a village connection.

An invalid or expired credential counts as personal too. Being wrong that way
costs one answer not cached. Being wrong the other way hands somebody's telephone
number to a stranger.

Proved against the real function on four requests: no token (cacheable), bearer
token (all five protections), sign in cookie (all five), and a stranger carrying
analytics cookies (cacheable).

### Worth checking in LiteSpeed

**Cache → Cache REST API.** If it is on, turn it off. This change makes the site
correct either way, but that setting is what made a private answer storable in the
first place.

## 31. Turning away an app version that has a fault in it

⚠️ *`kaamase-core/includes/app-version.php` is a **brand new file**. Upload it.
Nothing changes until you set a number: with the boxes empty the app is not told
anything and nobody is stopped.*

The app now reads two optional fields from `/reference` and shows an Update screen
to anybody below the version named there. This is the site's half. The app's half
ships in its next store build, so it has no effect on anyone running today's
2.0.6 — that binary has no gate in it to trigger.

**Settings → App version.** Two boxes and a message. Empty means off.

### Why it is a screen and not a constant

It gets reached for when something has gone wrong, which is exactly the moment
nobody should be editing PHP. The owner sets it and lifts it without help.

### Why it is never cached

`/reference` is held in a transient for six hours. A floor written into that
payload would take six hours to arrive, which is bad, and six hours to **remove**,
which is far worse: one mistyped number would lock every phone out until the
afternoon with no way back. So the two fields are merged into the answer after the
cache is read, and are always live.

### Off means absent, not empty

With nothing set the keys are left out of the answer altogether rather than sent
empty. The app treats a missing field as "let everybody in", and an absent key
cannot be misread as a floor of zero.

### The button that matters

"Turn the gate off" is its own button, not "clear both boxes and save". It gets
pressed by somebody who has just shut out every user by mistake, and at that
moment one obvious button beats two fields and a save.

Version strings take digits and dots only. `2.0.7-beta`, `v2.0.7` and anything
else are refused and nothing is set, because a gate acting on a typo shuts out
people it should not. Proved on twelve inputs, and on six shapes of request:
off, off-with-empty-strings, one platform, both platforms with a message, a
message with no floor, and a different route entirely.

### What leaves this site is a version or nothing

The values are cleaned again *after* the filter runs, not only before it. A filter
written carelessly could otherwise put `"Array"`, `"1"` or `"2.0.7-beta"` on the
wire and leave the app to defend against our output. Only a string is accepted;
an integer, a boolean, an array or markup omits the key, which the app reads as
"let everybody in". Wrong in the safe direction. The message is stripped of markup
and capped at 200 characters, because it is drawn on a screen somebody is stuck
behind, on the smallest phone we build for.

### The one thing it cannot check

Whether the version you type actually exists in the stores. Type one that has not
been released and every user is locked out with nothing to update to. The screen
says so in red above the boxes, and the off button is the way back.

## 32. View counts on cards — phase 2, part one

⚠️ *`views.php`, `template-tags.php` and `style.css` are new revisions. Upload
them.*

The numbers are now shown on worker cards, team cards and job cards. Two days of
counting gave 93 rows, 96 views across 53 different profiles and jobs, so the
mechanism is proved on live traffic before anything was drawn.

### Shown from one, never from zero

The owner asked for no threshold, and that is what this does — a profile with one
view says one view. Zero is the exception and renders nothing at all: a profile
announcing "0 views" tells a worker who joined this morning that nobody wants
them, which is worse than saying nothing.

### One query for a page, not one per card

A listing draws twenty cards. Twenty counts would be twenty queries on shared
hosting for a number nobody came to the page to read. `the_posts` now primes the
whole page in a single grouped query and the count function answers from that.
Posts with no views are remembered as zero too, otherwise every empty profile on
the page would fall through and ask again on its own.

### Two densities

A card already carries a rating, a wage and a button, so there it is the eye and
the figure. A profile page has room for the word. The eye is stroked to the same
weight as every other mark in the theme, nudged half a pixel down because a circle
inside an almond reads high against a line of text, and the figures are tabular so
they do not shuffle sideways as they grow — on a listing the eyes then line up
down the column instead of drifting.

The full density sets its own word spacing. One flex gap for both put
"247&nbsp;&nbsp;views" a whole space apart and broke the phrase in half.

Every count carries the sentence "Looked at 24 times" for a screen reader, in the
theme's existing `.ka-sr`, because an eye and a figure alone are not a sentence.

### What followed

The count on the profile and job pages themselves, in fix 33 below, and the "who
looked at you" list in fix 34. The templates for both turned out to be in
`kaamase.zip` and `kaamase-core.zip` at the root of this repository, which is
where anything missing from this folder should be looked for first.

## 33. Who looked at you — phase 2, part two

⚠️ *`who-looked.php` is a **brand new file**. `install.php`, `dashboard.php` and
`kaamase-core.pot` are new revisions, plus `style.css` in the theme. The page
builds itself the next time any wp-admin screen is loaded — see fix 21.*

The other half of view counting. The number on a profile says how much interest
there is; this says where it came from. **Settings are not needed and no menu has
to be edited:** the page appears at `/who-looked/` and a button for it appears on
the dashboard.

### A week free, a year paid, and why they are different questions

A worker looked at by four employers has something to act on **this week**, so
holding that back entirely would make the counter on their profile a tease, and a
number nobody can act on is worse than no number.

Hiring here is seasonal, and that is what the paid window answers. The employer
who looked in March is the one to ring in March next year. **The longer window is
not a bigger version of the free one.** The offer says so in those words rather
than selling more of the same.

### The offer is silent when there is nothing to sell

An empty week is the one moment the year is worth mentioning, so the question is
asked — once, and only for a free account with an empty list: is there anything in
the longer window? Then:

- **Nothing this week, nothing before it** → no offer at all. Asking somebody to
  pay for a longer view of an empty list is the kind of offer that teaches people
  to distrust every later one.
- **Nothing this week, four people before it** → *"4 people looked at you before
  this week."* Specific, true, and the best moment it will ever have.
- **Already paying** → never shown.

The heading changes with it. "Nobody yet" means never, and when somebody did look
— just further back than a free account sees — that heading is simply untrue, so
it becomes "Nobody this week".

### Names, and what is never shown

Signed in viewers only; `kaamase_views_of_mine` already refuses to return
strangers, because a line reading "somebody looked at you" tells nobody anything
they can act on. Name and public profile link only — **never a telephone number
and never an email.** Contact runs through the same per-profile reveal as
everywhere else, and a list that printed numbers would be a way around the daily
cap rather than a feature of the list.

### Two queries for a list, not two per line

A busy week is two hundred rows. Names and profile links are fetched for the whole
list in two queries, and the titles primed in one more. Four hundred queries to
print names is not something to put on this hosting.

### Built out of what was already there

The initials block is the theme's own `.ka-avatar--empty`, not a second one of the
same thing. The dashboard button is guarded on the page actually existing rather
than on `kaamase_page_url()` being non-empty — that function falls back to a
guessed address, so testing it for emptiness proves nothing and would have put a
button to a 404 on the dashboard before the pages were built. Checked on four
states: nothing stored, a zero id, a trashed page, and a live one.

### One thing for the owner to decide

`/privacy` does not currently say that opening somebody's profile shows them your
name. It should.

## 34. Review findings

⚠️ *`rest-api.php` and `kaamase-core.pot` are new revisions.*

Two faults found reviewing everything above, neither of them from today's work.

### The employers endpoint was registered twice, with different locks

`GET /employers/{id}` was registered at two places in `rest-api.php`. One carried
`kaamase_rest_require_employer_browse`, the paid and confirmed check. The other
carried the open one. `kaamase_rest_employer` was likewise defined twice, once at
`@since 1.4.1` and once at `@since 1.3.0`.

**Nothing was open.** `register_rest_route` merges rather than replaces, and
dispatch takes the first handler matching the method, so the gated one won. That
was proved by simulating the merge and the selection rather than trusting a
reading of WordPress, and `function_exists` meant the newer definition won too.

But it was one edit away from being open. Anybody reordering those two blocks —
or adding a route between them — would have made the paid employer directory free
to every signed-in account, silently, with both versions still sitting in the file
looking deliberate. This is the same shape as the duplicate `kaamase_shape_employer`
caught earlier in this programme.

The dead registration and the dead definition are gone. What survives is the gated
one, which is what was already running.

### Sixteen strings could not be translated

`app-version.php` never had its strings added to `kaamase-core.pot`. Added, and
both templates now check clean: every `__()` and `esc_html_e()` in either the
plugin or the theme has an entry, and every entry has a `msgstr`.

### What the review checked

42 PHP files parse. No duplicate function definitions anywhere in the tree. The
nine privacy, authentication and payment functions are each defined exactly once
and none were touched. CSS braces balance. Every file imported from the two zips —
`dashboard.php` and the three single templates — differs from its original by
**additions only, zero removed lines**. `who-looked.php` mentions a telephone
number and an email in one comment and reads neither.

## 35. View counts and who looked at you, for the app — phase 3

⚠️ *`views-api.php` is a **brand new file**. Nothing else changes. Upload it and
the app can see both.*

### rest-shape.php is not touched

Every shape in that file already ends with `apply_filters`, so `views` is added
through the filter and that file is not edited at all. This matters here
specifically: a duplicate of a shape function was written once before in this
programme and quietly replaced the real one, renaming a field and dropping
another, with five callers still calling it. The safest edit to that file is none.

### What every profile and job now carries

```
"views": { "total": 247, "people": 89 }
```

Both numbers, because one without the other misleads: four hundred views from
three people is a different thing from four hundred views from four hundred
people, and only the second is what somebody reading "400 views" assumes.

On the short shape as well as the full one, because the app draws cards from the
short one and the website shows the count on its cards. `/teams` shapes teams with
`kaamase_shape_worker`, so teams are covered by the same filter.

**A list costs no extra queries.** `the_posts` already primes every count on the
page in one grouped query, and the REST list callbacks use `WP_Query`, so that
fires for the app exactly as it does for the website.

### GET /me/looked

The same list the website draws, in the same window, decided in the same place.
Two clients working out separately whether somebody has paid is two chances to
disagree, and the one that disagrees quietly is the one nobody finds. So the
server sends the window, whether they are paying, and **whether to show the
offer** — the app does not recompute any of it.

```
window_days, is_paid, people,
viewers[]: id, name, url, initials,
           subject{ id, type, title, url }, hits, last_seen
upgrade:   show, older_people
```

`upgrade.show` is false for a paying account, false for somebody nobody has ever
looked at, and true otherwise. `older_people` is the number waiting behind the
longer window when this week is empty — the one moment that offer is worth making,
and the server has already asked the question so the app does not have to.

Checked on four states, matching the website's four exactly: free with visitors,
paid with visitors, an empty week with people behind it, and nobody ever.

## 36. The app had never counted a single view

⚠️ *`views-api.php` is a new revision. Upload it. **Nothing changes in the app** —
this is counted entirely on the server.*

`kaamase_view_catch_singular` asks `is_singular()`, which is only ever true for a
page of the website, and a REST request stops inside `parse_request` long before
that hook runs. So the counter had never seen the app at all — not few views,
none. `exposure.php` says in its own note that **most of the traffic is the app**,
so every count on every profile has been missing the majority of real openings
since the day counting started.

That is the whole explanation for the numbers looking dead. The 73 worker views
recorded so far are website-only.

### The same act, so the same rules

Opening a profile in the app is opening a profile. The route hands the id to the
website's own pending slot and the `shutdown` handler that already exists does the
writing — one recording path, one set of rules. Nothing new decides who counts:
`kaamase_record_view` still refuses the owner, refuses staff, refuses robots, and
holds the thirty minute cooldown, exactly as it does for the website.

### What counts as an opening

Only a successful `GET` of one record: `/workers/{id}`, `/jobs/{id}`,
`/employers/{id}`, and the two slug routes — `/workers/slug/{slug}` and
`/jobs/slug/{slug}`, which are how a `kaamase.com` link opens a profile inside the
app and therefore how a shared link becomes a view.

A list is not somebody opening a profile. Nor is a failed request, a POST, a job
action, a contact reveal, the saved list, `/me`, or another plugin's route.
Checked on all fourteen.

## 37. The views the cache was swallowing

⚠️ *`views-api.php` is a new revision. Upload it. Nothing changes in the app.*

A view is recorded on `shutdown`, and `shutdown` only happens if PHP ran. When
LiteSpeed answers from store PHP never starts, so nothing is counted.

Since fix 29 a signed-in visitor always gets an uncached page and is always
counted. A signed-out one usually is not. So the count leans towards people with
accounts and misses the visitor arriving from Google — which is precisely who the
trade and district pages exist to reach.

The page now asks to be counted after it has loaded, from an address the cache
cannot answer.

### Why asking twice cannot inflate anything

It goes through `kaamase_record_view` like everything else, and that upsert only
adds a hit when `last_hit` is older than the cooldown:

```sql
hits = hits + IF(last_hit < %d, 1, 0)
```

So on a cache **miss**, where PHP ran and already counted the view, the beacon
that follows adds nothing. It is the cooldown, not any cleverness in the new code,
that makes this safe. The owner, staff and robots are refused in the same place,
and a stranger is still one `visitor_key`, so the most any single browser can add
is one hit per profile per thirty minutes.

On top of that, one browser may ask at most 120 times an hour. The cooldown
already stops one profile being counted twice; the ceiling stops one script
walking every profile on the site. Somebody opening sixty profiles in an hour is
doing a hard day's looking and is still under it.

### A POST, and after load

A POST because it changes something, and because a GET that changes something gets
fired by link scanners and browser prefetch — which would inflate counts with
nobody having looked at anything.

`sendBeacon` where there is one, so the browser sends it in its own time after the
page is done and it survives the reader tapping a link immediately. It is the one
request in the theme allowed to happen after the page is usable, and it blocks
nothing. 811 bytes of inline script, no extra file to fetch.

The id is written into the page, and the page it is written into is that same
profile, so a cached copy carries the right number for everybody handed it.

### What it does not do

Somebody with JavaScript switched off is not counted — but they were only ever
counted on a cache miss anyway, so nothing is lost that was working before.

Printed only on a singular worker, team, employer or job. Checked: silent on an
ordinary page, silent on a listing, silent when there is no id, and the endpoint
refuses a missing id, a non-numeric id, and anything past the hourly ceiling.

## 38. Counted for being seen, not only for being opened

⚠️ *Four files: `kaamase-core/includes/views.php` and `views-api.php`,
`kaamase/inc/template-tags.php` and `kaamase/style.css`. All four, and
`views.php` first.*

⚠️ **After uploading `views.php`, open wp-admin once.** The table is being
changed, and by design that only happens on an admin page load — never from
whatever visitor request happens to arrive first. Until you do, nothing at all
is counted, openings included. It is one page load and it fixes itself.

Somebody scrolling a list of workers has genuinely seen those workers. Until
now none of that was counted: a view meant an opening, so a worker who appeared
on the front page four hundred times and was opened twelve times had a profile
that said 12.

Both are now counted, and they are kept apart, because they are not the same
claim.

### The two numbers

- **seen** — a card for this worker, team, employer or job came onto somebody's
  screen in a list. Scrolling counts. Signing in is not needed.
- **opened** — somebody went to the page itself. This is what was already being
  counted, and it has not changed.

A profile page says **`340 seen · 12 opened`**. A card in a listing has room for
one figure, and it is the same one the profile page leads with — 340 — so a
worker who sees 340 on their card does not open their page and find 12.

Nothing is invented and nothing is rounded. Every showing is a card that was
actually on somebody's screen, for at least a second, half of it visible.

### What counts as being seen

A card has to be **half visible and still there a second later**. A card that
flicks past during a fast scroll was not read by anybody and is not counted —
tested: five cards shown for three hundred milliseconds and then scrolled off
count zero.

Then the same cooldown idea as before, at ten minutes rather than thirty.
Scrolling up and down the same list for five minutes is one showing, not sixty
— tested, sixty showings in five minutes came to 1. Ten minutes later it is a
fresh showing. Shorter than the thirty minutes on an opening because a list is
scrolled through in a way a profile page is not, and because being shown is a
smaller claim than being opened.

Everything else is unchanged and is decided in one place: the owner is not
counted looking at themselves, staff are not counted, and robots are not
counted.

### Why it cannot be inflated

The same upsert that has always guarded this:

```sql
hits = hits + IF(last_hit < %d, 1, 0)
```

so a repeat inside the cooldown adds nothing, whatever asks. On top of that one
browser may report at most 600 showings an hour — ten pages of twenty cards,
looked at three times over, is 600, so a real person scrolling all afternoon is
under it and a script walking the whole site is not. Openings keep their own
separate ceiling of 120.

Tested: forty batches of thirty were sent and exactly 600 were counted, the
window did not slide, and openings were unaffected by the showings window being
spent.

### One database write per screen, not thirty

The obvious way to enforce a ceiling is to bump the counter once per card, which
on a listing is thirty `update_option` calls — thirty writes for one screen, on
every listing, for every visitor. On shared hosting that is the feature turning
into a load problem.

The allowance is read once and written once per request instead, whatever the
size of the batch. Measured: forty batches of thirty cards, twenty database
writes. The window still starts at the first request in it and does not slide.

The counts themselves cost nothing extra to draw. A listing was already fetching
every count on the page in one query; it now fetches both kinds in that same
query.

### If the table cannot be changed

The unique key is what keeps an opening and a showing of the same profile, by
the same person, on the same day, from colliding. `dbDelta` will add the new
column but will **not** rebuild a key that already exists under the same name,
so the rebuild is done by hand — and then **checked**, not assumed.

If that rebuild does not take, counting stays switched off and the next admin
page load tries again. It would otherwise have carried on, with every showing
quietly bumping that day's opened count instead. Numbers that are wrong and look
right are worse than numbers that have stopped.

### For the app

`views` in the API grew a third field:

```json
"views": { "total": 12, "people": 9, "shown": 340 }
```

`total` and `people` are openings and mean exactly what they meant before, so an
app build that has never heard of `shown` is unaffected. The `/seen` endpoint now
takes `ids` and `kind` as well, so the app can report what its own lists put on
screen. There is a message for the app side below the upload list.

### What it does not do

Somebody with JavaScript switched off is not counted, and neither is a browser
too old for `IntersectionObserver` — on those the page still counts openings
exactly as it did. Nothing is printed at all on a page with neither an opening
nor a card, so terms and privacy carry no counter for something they have
nothing to count. The whole script is 2.6 KB inline, with no extra file to
fetch, and it runs after the page is usable.

## 39. Every time it comes past, not once every ten minutes

⚠️ *Three files: `kaamase-core/includes/views.php` and `views-api.php`, and
`kaamase/single-kaamase_job.php`. No database change this time, and nothing to
do in wp-admin afterwards.*

Two things. A showing now means what the word means, and the job page says the
same thing the worker page says.

### A card is counted every time it comes past

The rule was one showing per profile per person per ten minutes. Somebody
scrolling a district up and down for five minutes counted as **one**. That is
not an impression, it is a visit.

The cooldown on a showing is now zero:

| | before | now |
| --- | --- | --- |
| Scrolling one worker past 20 times in 5 minutes | 1 | **20** |
| Two reports in the same second (a retry, a doubled beacon) | 1 | 1 |
| Opening the same profile twice in 20 minutes | 1 | 1 |

Zero is not "no guard". The counting statement only adds a hit when `last_hit`
is older than the cooldown, so at zero two reports landing in the *same second*
still collapse into one. That is the whole of what is left, and it is the part
worth keeping: it stops a retry counting twice, and it can never suppress an
honest showing, because a card has to be half visible for a full second before
it is reported at all.

**Openings are untouched.** Still thirty minutes. Opening the same worker twice
in an afternoon is one person making one decision, and that has not changed.

### The browser had its own, stricter rule

The server was not the only thing suppressing this. The script marked each card
finished for the whole page load and stopped watching it, so scrolling back up
never counted at all, whatever the server would have allowed. Both had to go.

A card is now watched for as long as the page is open. It counts once each time
it **arrives** on the screen — not once per observer event, of which a slow
scroll produces several. Scroll off, scroll back, and it counts again.

Tested against a stubbed browser: three cards over three passes gave nine
showings, the same eight events during one slow arrival gave one, a card that
flicked past for 300ms gave none, and nothing was ever unwatched.

### It costs nothing to store

Worth being plain about, because "count everything" usually means a bigger
table. It does not here. A row is one person, one profile, one day, with a
counter on it. Three people scrolling the same worker ten times each is **three
rows and thirty hits** — the same three rows it was before.

The ceiling per browser went from 600 an hour to 3,000, because the old figure
was set when a card could only count six times an hour. Three thousand is about
fifty a minute sustained for a full hour, which is more looking than a person
does and far less than a script would want.

### The job page now says what the worker page says

A worker page read `7 seen · 3 opened`. A job page read `19`. Three templates,
and one of them asked for the compact card version:

```
single-kaamase_job.php     kaamase_views( $id );          ← the fault
single-kaamase_worker.php  kaamase_views( $id, 'full' );
single-kaamase_gang.php    kaamase_views( $id, 'full' );
```

Fixed, and moved onto a line of its own under the place and the date, which is
where the app puts it. Two numbers and the words that tell them apart do not fit
on the end of a line that already carries a place and a date.

It still says nothing at all when there is nothing to say, and still never
announces a nought: `340 seen` on its own until something is opened, and the
old `7 views` wording on a site that has not started counting showings yet.

## 40. Urgent jobs were running three weeks, not three days

⚠️ *One file: `kaamase-core/includes/services.php`. It has not been touched
before this, so what you are uploading is the original with one block added.*

Urgent is supposed to mean today. The plugin's own note says why:

> *Urgent is limited to one open job at a time. If every listing is urgent,
> urgent means nothing within a fortnight, and the tag stops working for the
> person who actually needs somebody today.*

A job gets 3 days if it is urgent and 21 if it is not. **Every urgent job was
getting 21** — the same as an ordinary one — so for as long as the site has been
running, the tag has meant nothing at all.

### Why

An ordering mistake in the one function that saves a job, used by the website
form and the app alike:

```
1. wp_insert_post()                       creates the job
2.   → fires kaamase_set_job_expiry
3.     → asks kaamase_job_lifespan: 3 days or 21?
4.       → reads the urgent flag           ← not written yet. Reads false. Stamps 21.
5. kaamase_save_field( 'urgent', true )   the flag is written here, too late
6. the expiry is never re-stamped         ("do not move an expiry the employer extended")
```

Step 4 asks a question that step 5 answers. The flag cannot be written earlier
because there is no job to attach it to until step 1 has run.

Run against the real `kaamase_job_lifespan` and `kaamase_set_job_expiry` in
that order: an urgent job comes out at **21 days**. That is the fault, and it
matches a live listing carrying the Urgent badge and saying *18d left* — 21
minus the three days it had been up.

### The fix

The expiry is stamped once more at the end of the save, where the flag is
stored and lifespan can read the truth. Written directly, because
`kaamase_set_job_expiry` refuses to move an expiry that already exists — the
right rule for an employer who extended one, the wrong rule for the placeholder
it wrote itself moments earlier on a guess.

| | before | now |
| --- | --- | --- |
| New urgent job | 21 days | **3 days** |
| New ordinary job | 21 days | 21 days |
| Ticking urgent on a job already running | no change | no change |

**New jobs only, deliberately.** Ticking urgent on a listing that is already
running still leaves its expiry alone. Three days from now would be *shorter*
than a listing with six days left and *longer* than one with two, and neither is
what the employer asked for.

### Two things that were already right

Reposting a closed job clears the expiry first, so it is stamped fresh — and by
then the urgent flag is stored, so a reposted urgent job correctly gets 3 days.
That path was never broken.

And the website form and the app both post through the same `kaamase_save_job`,
so this is one fix for both.

### The jobs already running

They keep the 21 days they were given. Nothing closes early because of this
change, which is the safe answer — but it does mean urgent listings with two
weeks left will be around until they run out. This finds them:

```sql
SELECT p.ID, p.post_title,
       ROUND((e.meta_value - UNIX_TIMESTAMP()) / 86400) AS days_left
FROM wp_posts p
JOIN wp_postmeta u ON u.post_id = p.ID AND u.meta_key = '_kaamase_urgent'  AND u.meta_value = '1'
JOIN wp_postmeta e ON e.post_id = p.ID AND e.meta_key = '_kaamase_expires'
WHERE p.post_type = 'kaamase_job' AND p.post_status = 'publish'
ORDER BY days_left DESC;
```

## 41. One allowance each, not one between a whole village

⚠️ *One file: `kaamase-core/includes/views-api.php`.*

The hourly ceiling on showings was keyed on the visitor hash — a day, an IP
address and a user agent — **even for somebody signed in**. That is fine on a
website, where browsers and versions differ enough to tell people apart. It is
wrong through a phone app, where every install of the same build sends the same
user agent and a mobile carrier puts a great many people behind one address.

Ten people on one carrier were sharing one allowance. Measured, with the ceiling
at 3,000:

| | counted before being cut off |
| --- | --- |
| Ten signed-in people, ceiling keyed on the address | 300 each |
| Ten signed-in people, ceiling keyed on the account | 3,000 each |

Three hundred showings is an afternoon's scrolling, and after that the tenth
person in a village silently stops counting for everyone — suppressing exactly
the number the last two changes existed to raise.

The ceiling is now keyed on the account when the request carries one, and only
falls back to the visitor hash when it does not.

### Which makes signing in worth carrying

An unauthenticated request is anonymous in two ways that matter, and neither is
about the ceiling:

- **The owner and staff checks never run.** Both are guarded on there being a
  viewer id, so with no account attached a worker scrolling past their own card
  counts as a showing of themselves, and staff scrolling a list inflate every
  profile they pass.
- **Everybody behind one address is one person** in the table, so the count of
  distinct people looking is far too low.

None of that is a reason for the endpoint to *require* a token — it must keep
working for a signed-out visitor, and it does. It is a reason for a client that
has one to send it.

## 42. The employer's poster, on the job card

⚠️ *Two files: `kaamase/inc/template-tags.php` and `kaamase/style.css`. Bump the
theme version in `style.css` so the new rules reach people who have visited
before.*

Worker cards have always carried a photograph. Job cards never did — not a
broken one, an absent one. The app shows the poster on every job card, and those
posters are doing real work for the employer: a training course, a hiring drive,
a phone number, a venue.

### Read from the featured image, not the photo list

`job-photos.php` keeps the featured image pointing at the first picture on every
save, and says why: *"The featured image matters beyond the theme. It is what a
share card, a search engine and any future plugin will look for."*

So the card reads that, through plain `has_post_thumbnail` and
`get_the_post_thumbnail`. One source of truth, the same one the app draws from,
and no dependence on the photo feature being installed at all.

### A thumbnail, not a band across the top

These are printed flyers and they run tall. Given the full width of a card, one
of them would be most of a phone screen, and a listing exists to be scrolled past
several jobs at a time.

So: 64px square, cropped from the middle where a flyer puts its name, drawn from
the already-registered 128px file so it stays sharp on a phone without fetching
the poster at full size on a village connection. Beside the title, which is where
the app puts it.

The head is the same `.ka-card__head` flex row the worker and employer cards use,
so a long title wraps beside the picture instead of under it, and the thumbnail
stays at the top.

### A job with no picture is untouched

Most jobs have none, so this was the case to get right. The anchor is not printed
at all, the row collapses to a single column, and the card renders exactly as it
did before — same title, same spacing, no gap where a picture would have been.
Checked at 360px alongside a card that has one.

The photograph is skipped by the keyboard and hidden from screen readers, because
the heading beside it is the same link and two links to one job is one thing to
tab past for nothing. Same treatment as the worker card.

## 43. Opening the ratings up

⚠️ *Four files. `kaamase-core/includes/hire-claims.php` is **brand new**;
`ratings.php` and `kaamase-core/languages/kaamase-core.pot` are new revisions;
`kaamase/inc/template-tags.php` is a new revision of the theme file. **`hires.php`
is deliberately not in this list** — the existing hire flow is untouched.*

Every rating depends on a recorded hire, and until now exactly one thing could
record one: an employer revealing a worker's number, then answering *yes I hired
them* two days later on their dashboard.

That is a narrow door. Hiring here happens on the phone, on WhatsApp, through a
cousin, at the work site. None of it goes through a contact reveal, so none of it
produced a hire, so none of those people could ever rate each other — and no
screen told them why. One mis-tap on **No** ended it permanently, for both of
them, and the worker never knew the question had been asked.

### 1. The form used to disappear without a word

`kaamase_rating_form()` returned an empty string to anybody who could not rate.
No heading, no message, nothing. The function already knew the reason and threw
it away.

It now says so — but only where saying so helps:

- **only** for a missing hire. Telling a signed-out reader to sign in repeats the
  button already on the page, and telling somebody they cannot rate themselves is
  noise on the one page they look at most.
- **only** to somebody on the other side of the market. A worker reading another
  worker's page is not waiting on a hire, and a message about confirming one
  would only confuse them.

### 2. A second door, which does not touch the first

`hire-claims.php` is new and `hires.php` is unchanged. The reveal flow works
exactly as it did; this adds a way in beside it.

A button appears inside that new notice — **I hired this person** on a worker or
team, **I have worked for them** on an employer. It reaches all three profile
pages through a filter, so not one line of any template changed.

**The other person has to agree.** The existing answer is one-sided on purpose:
the employer paid a contact lookup, and the reveal is evidence that a real
approach happened. A claim made here has no evidence behind it, and letting it
stand alone would let anybody manufacture a hire with a stranger — and with it
the right to rate them. On a platform where a bad score costs somebody work, that
is not a small thing. So it goes to the other person, in both directions, as a
question on their dashboard: **Did you work together?**

That question reaches workers as well as employers, because the dashboard fires
its prompt hook above everything type-specific.

### Nothing here is permanent

Which is the other half of the "No" problem:

| | |
| --- | --- |
| Refused | can be asked again after **7 days** |
| Never answered | dropped after **30 days**, so it can be made afresh |
| Already connected | refused — there is nothing to confirm |
| Same claim twice | refused while it is still with the other person |
| Ceiling | **10 claims per person per day** |

Tested against the real functions: an employer claiming a worker, a worker
claiming an employer, both resolving to the same pair the other way round;
somebody else's reference refused; answering twice refused; a refusal blocked on
the same day and accepted eight days later; and the ceiling stopping at exactly
ten.

### It works in the app too

Three addresses, all requiring a signed-in account:

```
GET  /kaamase/v1/hire-claims          what this account has been asked to agree to
POST /kaamase/v1/hire-claims          { "profile": 123 }        say a hire happened
POST /kaamase/v1/hire-claims/answer   { "ref": "...", "confirm": true }
```

### 3. Teams finally get their score and their marks

The one card in the theme with neither, while the app has shown both for teams
since it was written — teams travel through the same shape as a worker there.
Now identical to the worker card, which means a team with no ratings reads
**New**, exactly as a worker with none does.

### What was deliberately left alone

The **three-rating threshold stays at three**. The stars are not missing; there
are almost no ratings, because until now almost nobody could leave one. Once the
door is open three will arrive quickly and the scores appear on their own. The
reason for the threshold has not changed:

> In a market this small a single angry employer could otherwise end somebody's
> livelihood with one click on a bad afternoon.

## 44. Signing in with Google

⚠️ *One file. `kaamase-core/includes/google-signin.php` is **brand new**. Nothing
else was touched: email and password still work exactly as they did, on the
website and in the app.*

A worker registering on a phone at a labour point has to invent a password,
remember it, and type it again next time. Most do not, and the ones who cannot
get back in do not send a support email — they stop using the platform. A Google
account is the one credential nearly every Android phone here already has.

**The token is checked here, not by asking Google on every sign in.** Google
hands over a signed ID token; this verifies the RS256 signature against Google's
published keys, then the issuer, then that the audience is one of our own client
IDs, then the expiry. Sending each worker's sign in to a third party and waiting
on the reply is a round trip this connection cannot afford. The same reasoning
that kept a JWT plugin out of `rest-auth.php` applies.

**Everything rests on one flag.** Google says whether it has confirmed the
mailbox. An account here is matched by email address, so an unconfirmed one would
let somebody claim an account by typing its address into a Google profile. A
token without `email_verified` is refused outright.

**Two doors, one account.** Somebody who registered with a password and later
taps Google on that same address is signed into the account they already have.
Refusing would wall them out over a password they have forgotten, which is the
problem this solves.

**Google supplies a name and an email and nothing else,** so a new account still
answers the questions the platform cannot work without: which side of the market,
district, trade, phone. The app asks on a second call and the website on a short
form, and both re-verify the same token, so nothing half made is held on this end
between the two steps.

Off until a client ID is set, under **Settings → Google sign in**. With the
settings empty no button renders, the endpoints say the door is closed, and the
site behaves exactly as it did before this file existed.

---

## 45. Keeping a phone number back, and asking for it

⚠️ *Three files. `kaamase-core/includes/number-requests.php` is **brand new**;
`contact.php` and `privacy.php` are new revisions.*

A worker may choose not to show their number. Anybody who wants it asks, the
worker is told on their phone, and the worker decides. Nothing is revealed until
they say yes.

**Off by default, and worded to stay that way.** A number anybody can ring is the
point of this platform. An employer standing in a hardware shop with a job
starting tomorrow rings the three numbers they can see, not the one they have to
apply for. The dashboard says that plainly where somebody is about to make the
choice, rather than burying it in help they will read afterwards.

**Paid plans see every number regardless.** They have bought reach on the hiring
side, a hidden number defeats exactly that, and selling access and then
withholding it is selling something you do not have.

**A job post can never be hidden.** Somebody answering an advert has to be able to
ring it; an advert you cannot reply to is not an advert.

**Enforced in one place.** Every route to a number — the website screen and the
app endpoint alike — goes through `kaamase_can_contact()`, so that is where the
check lives, through a new `kaamase_contact_veto` filter. It sits *after* the
platform's own rules, so an unconfirmed account is still told to confirm rather
than sent down a path ending in the same place, and *before* the daily cap, so a
refusal never costs somebody a lookup they did not get.

Three faults were found reviewing it before it shipped, all fixed:

- **The wrong profile.** Every endpoint read `kaamase_profile_id`, which is
  whichever profile an account made first. An account can hold a worker profile
  and an employer profile at once, so somebody who registered to hire and later
  added a working profile had all of this pointed at the wrong post: requests
  would never have appeared and the setting saved somewhere it does nothing.
- **The safety gate.** Somebody who cannot be given a maid's or a cook's number
  until they hold a finished employer profile could still ask for one. Nothing
  leaked, but the worker would have agreed to share and watched the platform
  refuse the person she had just said yes to.
- **Erasure.** A request carries who asked and sits on somebody else's profile,
  so deleting the account left it behind with their name on it.

---

## 46. Push notifications — a daily task that starved most of the platform

⚠️ *One file. `kaamase-core/includes/push.php` is a **new revision of an existing
file**, not a new one. Every function, hook and notification body from the live
version is unchanged.*

**The bug.** `kaamase_push_hire_questions()` asked `get_users` for a hundred
accounts holding the meta and gave it no order. `get_users` sorts by login when
nothing says otherwise, so it took the same hundred logins every night, for ever.
Anybody sorting below them was never asked — not asked late, *never*. Their hire
question sat on the dashboard waiting to be stumbled on, and the ratings that
hang on the answer never came.

It would have surfaced only as ratings quietly drying up for everybody with a
late alphabet, once the platform passed a hundred outstanding questions. A cursor
now walks the list a batch a night, ordered by ID because a login can change and
an ID cannot, and wraps at the end. Simulated over 250 accounts: all 250 reached
in three nights.

**`kaamase_notify_user()`** sends by phone when there is one and by email when
there is not, never both. Half this platform registered on the website and never
installed anything, and a notification layer that only speaks to app users
quietly decides those people need not be told.

**Tokens are checked** against the shape `/push/register` accepts. A malformed
value stored by an older build cost a slot in every batch and could never be
delivered to. **Titles and bodies are cut** to what a phone shows — a sentence
chopped by the notification shade mid word reads like a broken app. **A filter
runs before each send,** so a per person mute can be added later without touching
this file.

---

## 47. The contact screen — markup on the page, and a number nobody can read

⚠️ *Three files. `contact.php`, `rest-api.php` and `rest-shape.php` are new
revisions.*

Two faults on the same block, both visible only to particular people, which is
why they came and went.

**Tag soup in the name.** `verified-mark.php` hooks `the_title` to append the tick
as a span and an svg, but only when the title asked for belongs to the profile
being viewed. This block is printed by `the_content` on that very profile, so
every condition of that filter is met and the name arrives carrying markup.
Escaping it then does exactly what escaping is for and prints the tags as words —
in the heading and again in the sentence underneath. **It shows on verified
profiles and nowhere else,** which is what made it look intermittent.

Unescaping would have been the wrong repair. A name is whatever somebody typed
into their profile, and printing it raw puts that straight into the page. The
stored title is asked for instead, so the tick still renders where the theme
means it to and this block gets a plain name it can safely escape.

**The unreadable number.** Staff and field agents come back from the allowance
layer as `PHP_INT_MAX`, which is how that layer says *not metered*. Today's
lookups were subtracted from it and the result printed, so the page offered
**9,223,372,036,854,775,806 lookups left today**. Those accounts are now told they
have no daily limit.

The same value went to the app as `quota_left`. JSON carries integers, JavaScript
reads them as doubles, and anything above 9,007,199,254,740,991 loses precision on
the way in — so the app was not being given a large number, it was being given
arithmetic noise. **`quota_unlimited`** now travels alongside it and is the truth;
`quota_left` stays a number an older build can still print, capped so it survives
the journey. Nothing changes for anybody who is actually metered.

The limit is what gets tested, never what is left. What is left has already had
today's lookups taken off it, so it stops equalling the sentinel the moment
somebody uses the page.

---

## 48. The two doors on the register page

⚠️ *Three files. `kaamase/style.css`, `kaamase-core/includes/registration.php` and
`google-signin.php` are new revisions.*

**The underlines were a bug, not a style.** A card that is itself a link had every
line inside it underlined edge to edge, heading and sentence alike, which reads as
broken rather than as clickable. An underline set on an anchor is inherited by
everything inside and cannot be switched off further down: only the anchor can
remove it, and nothing did. The buttons escaped it only because `inline-flex`
makes an atomic box the underline cannot cross, which is why half of each card
looked wrong and half looked fine.

Fixed on `.ka-card--link` itself, so the prev and next cards on a post are mended
by the same line. Colour is deliberately left alone there: most of those cards are
an `article` or a list item with nothing to change.

The rest is the rework. **The buttons now fill their card** instead of sitting at
whatever width their text happened to be, and **the two cards end at the same
height** with their buttons on one line — side by side, an uneven pair reads as
one being the lesser option. **A stripe along the top** says which door is which
in the colours those paths already use everywhere else: green for working, amber
for hiring.

**Somebody already registered had no way off this screen.** The form on the next
one has offered a sign in link since the beginning, but the chooser never did, so
anybody who landed here by mistake had to pick a side they did not want in order
to find it.

**The Google button moves under the choice rather than over it.** This screen is
one question with two answers, and a third way in above the question answers it
before it has been asked. On the form itself it stays where it was: somebody
looking at a dozen fields wants to know there is a shorter way before they start,
not after.

Checked in Chromium at phone and desktop widths.

---

## 49. Unsaving from the saved list

⚠️ *Four files. `kaamase-core/includes/saved.php` is a **new revision of an
existing file** that was not previously in `fixed/`; `rest-api.php` and
`kaamase/style.css` are new revisions. **`worked-with.php` and the rehire list
are deliberately untouched** — see the end of this section.*

Removing something you had saved was only possible from the thing's own page.
You opened a profile in order to say you did not want to keep it, which is the
opposite of what the saved list is for.

### The closed items already had the button

`kaamase_saved_section()` rendered `kaamase_save_button()` on the **No longer
available** list at the bottom and on nothing else. So the items nobody revisits
could be removed in one tap, and the live ones — the entire point of the page —
could not. The button now sits under every card.

Under the card rather than over it, and quiet rather than loud. Somebody opening
this page is looking for the thing they saved, not for the way to throw it away,
and on a phone a stray tap should land on the card and open it.

It also says **Remove** here instead of **Saved**. On a profile, *Saved* is
reporting a state and pressing it undoes that. On a page where everything is
saved by definition, a column of buttons all saying *Saved* says nothing at all.

### Employers were saveable but not savable

`kaamase_post_types()` has always included `kaamase_employer`, so the toggle
handler and the app endpoint both accepted one happily. Two places disagreed:

- `kaamase_append_save_button()` listed job, worker and team and left employers
  out, so no employer profile on the website ever showed the button. A worker who
  ended up with a saved employer through the app had no way to remove it from a
  browser.
- The card `switch` on the saved page had no `kaamase_employer` case and fell
  through to `default`, so a saved employer was drawn as a **worker**: a day rate,
  a trade and an availability light, none of which an employer has.
  `kaamase_employer_card()` already existed a few functions above and was only
  ever called from the worked with list.

`/saved` in the app had the same fault from the other end — it asked only whether
the thing was a job and shaped everything else as a worker.

### A toggle is the wrong verb for Remove

`POST /saved/{id}` toggles, which is right for a button on a profile that shows
the current state. It is wrong for **Remove** on a list: a tap that times out and
is retried, or a double tap on a slow phone, sends the same toggle twice and puts
the thing back. The person watches an item they removed reappear.

The endpoint now accepts an optional `saved` boolean and sets that state however
many times it is asked. **Omitting the field keeps the old behaviour exactly**, so
nothing already shipped changes.

### The employer shape had no `saved` flag

Found while the app was being built. The worker and job shapes have carried
`saved` from the start; the employer shape never did, and the omission was not a
decision — nothing had needed it while employers were being shaped as workers.

The consequence was one-sided. The website could offer a Save button on an
employer profile, because it asks `kaamase_is_saved()` directly. The app could
not: with no flag in the response it had no way to know whether to draw *Save* or
*Saved*, so it left the button off employer profiles rather than guess at the
state. A saved employer still appeared in the list and could still be removed
there; only the profile button was missing.

`kaamase_shape_employer()` now carries `saved`, set exactly as the other two set
it. **Purely additive** — a field appearing in a response breaks no client, so
this one is safe to upload at any point, in any order, unlike the employer
shaping change above.

### What was left alone

The **worked with** list on the same page is not a saved list and has no remove
button. It is built from the hire record — for a worker it is the record of who
has actually paid them, which is the most valuable thing they own here. A button
that quietly deletes evidence of a hire is not a convenience.

---

## 50. Removing somebody from the worked with list

⚠️ *Two files. `kaamase-core/includes/saved.php` and `rest-api.php` are new
revisions. **`kaamase_worked_with()` itself is byte for byte unchanged** — that
matters, and the reason is below.*

The saved list could be pruned from section 49 onwards. The list above it could
not, and it is the one that grows on its own: work for a hundred different people
and a hundred cards arrive whether you want them or not.

### What it is not

Not a saved list. Nothing was ever saved. `kaamase_worked_with()` reads the hire
record on the worker's profile and rebuilds the list on every page load, so there
was no stored entry to remove — the only thing that could have been deleted was
the hire.

Worth knowing before anybody is tempted: **repeat work with one person is already
one card.** That function deduplicates by profile and keeps the most recent date,
so being hired fifty times by the same employer has always been a single row. The
list grows with the number of *people*, never the number of jobs.

### Why this hides rather than deletes

`kaamase_can_rate()` gates on `kaamase_hire_exists_between()`, which reads the
same `_kaamase_hires` meta the list is built from. Deleting an entry would
therefore end both sides' ability to rate each other, permanently, and silently:
one person tidying their screen would strip the other of a rating they were owed.
For a worker that record is also the evidence of who has actually paid them.

So a note is written against the person doing the hiding and the hire stays
exactly where it is. The other side's list does not change.

### Why the filter is not inside `kaamase_worked_with()`

Because `hires.php` reads it too. `kaamase_pending_ratings()` walks that same list
to decide who somebody still owes a rating, and filtering at the source would have
meant that tidying a name off a screen also stopped the platform ever asking about
them again.

`kaamase_worked_with_visible()` is a separate function used only by the two places
that draw the list. Somebody may still be asked, once, to rate a person they have
hidden. That is the right way round: the list is theirs to tidy, the rating is
owed to somebody else.

### Twelve at a time, and that is still fine

The website has always shown twelve. Removing one lets the thirteenth up, so a
long history can be worked through in full without the cap being lifted. The app
shows the whole list and now filters it the same way.

`POST /worked-with/{id}` takes `{hidden: true｜false}` and defaults to hiding.
Explicit, for the reason the saved list is: a tap that times out and is retried
must not put the row back.

---

## 51. Sign in with Apple, and a password for accounts that never had one

⚠️ *Three files. `kaamase-core/includes/apple-signin.php` and
`account-password.php` are **brand new**; `google-signin.php` is a new revision
whose diff is **seven added lines and nothing removed**.*

Apple refused version 2.0.9 under guideline 4.8: an app offering a third party
sign in must also offer one that collects only a name and an email, lets somebody
keep that email private, and does not follow them for advertising. Email and
password does not qualify — it cannot hide the address from us. Sign in with
Apple is the one Apple names.

### The shortcut that was not taken

Google on Android, Apple on iPhone would have satisfied the rule and was the
wrong shape for these users. People here share and borrow phones, and employers
look at profiles on somebody else's desktop. Splitting the providers makes an
account belong to the family of device that made it, and would have locked
anybody who signed up on an iPhone out of the website entirely.

So Apple is **added** and Google stays everywhere. Nothing was removed.

### Three things about Apple that are not true of Google

**The name arrives once and never again.** Apple hands it to the app on the first
authorisation, not in the token, and never afterwards. The account is built from
what the app sends, and a missing name is a plain validation error rather than
something the server can paper over — there is nothing anywhere to fall back to.

**The email may be a forwarding address.** Somebody may hide theirs and what
arrives is a `privaterelay.appleid.com` address. It is real and it forwards, so
it is accepted as real.

**The email may not arrive at all.** Apple sends it on first authorisation and is
not obliged to send it again, so a returning person is matched on the Apple
account id, which never changes. A token with no email is accepted rather than
refused; the address is only required when there is nobody to match yet, and
section 52 covers what happens then. The first version of this refused, and that
was wrong.

### The crypto is copied on purpose

The base64url, DER and key rebuilding are the same shape as the ones in
`google-signin.php` and were deliberately not shared into a common file. That
code is live, verified against OpenSSL, and holds up every Google sign in on the
platform. Rewriting it to serve two callers would risk a working door to save a
hundred lines. A third provider would be the moment to lift it out.

### Closing the password gap

An account made with Google or Apple gets a random thirty two character password
its owner is never shown. Correct — nobody should be handed a password they did
not choose. The consequence was not: signing in on the website, or on a phone
where that provider is not offered, meant a **Forgot password** link for a
password that never existed.

`account-password.php` lets somebody set one from inside, while signed in, and it
then works everywhere. Three decisions in it are worth knowing:

- **It uses `wp_set_password()`, not `wp_update_user()`.** The latter fires
  `profile_update`, which `rest-auth.php` answers by revoking every token on the
  account. Right for a password *reset*; wrong here, where being signed out at the
  moment of success reads as failure.
- **The old password is still required where there is one.** A stolen bearer token
  should not be able to become a permanent credential. Where there has never been
  a password there is nothing to ask for, and demanding it would lock out exactly
  the people this exists for.
- **An unmarked account counts as having a password.** Only accounts built by a
  provider are marked, so the default is the safe one: being wrong costs somebody
  one trip through Forgot password rather than opening a way past the check.

Either way the account is told by email that a password was set — by email
specifically, because a notification on a stolen handset is read by whoever stole
it.

The seven lines added to `google-signin.php` mark new Google accounts the same
way. Google accounts made before this still use Forgot password, which works.

---

## 52. When Apple never sends the email again

⚠️ *One file. `kaamase-core/includes/apple-signin.php` replaces the copy from
section 51. Nothing else changed.*

### The dead end

Apple hands over an email address on the **first** authorisation for an app and
never again. Section 51 knew that and still got the consequence wrong.

Somebody taps the Apple button. The server has never seen their Apple id, so it
answers `needs_profile: true` with their address and the app shows the form. They
look at it, get interrupted, and close the app. Nothing has been written
anywhere — no account, no Apple id on record. But Apple has now recorded the
authorisation, so **the address never comes again**, and every later tap reached a
409 saying:

> Apple did not send an email address this time, so a new account cannot be made.
> Open Settings, Apple Account, Sign in with Apple, remove Kaam Ase, then try
> again.

That reads like an instruction and behaves like a wall. Closing a form is not a
mistake somebody should be locked out of an account for, and this was one
interrupted signup away for every iPhone user.

It also surfaced first as a bug report from TestFlight, where a tester had
authorised during an earlier build and then been permanently unable to sign up.
There was no bug in the verification at all. The lockout *was* the design.

### What happens now

An empty address is part of the answer instead of a refusal. `/auth/apple`
returns `needs_profile: true` with `email: ""`, plus `email_needed: true` for
anything that would rather read a flag than test a string, and the app asks for an
address in the same form that already asks for district and trade.

`/auth/apple/complete` then takes the typed address and treats it exactly as one
typed into the ordinary registration form:

- The account is created **unconfirmed**, its profile stays a **draft**.
- The confirmation email is sent and does its ordinary job. On the token path it
  is still suppressed, because Apple already proved that address and a mail asking
  somebody to confirm what was confirmed a second ago is noise.
- `kaamase_apple_mark_verified()` is called **only** when the address came out of
  the token.

So an unconfirmed Apple account is the same object as an unconfirmed password
account, and lands on the `not-confirmed.php` list with a phone number, where
somebody can ring them. That was already the platform's answer for people who
never open a link, and it did not need a second one.

### Why a typed address is never trusted

Arriving beside a valid Apple token proves the person holds that **Apple
account**. It proves nothing whatever about the address they typed next to it.
Trusting it would be an account takeover: authorise with your own Apple id, type
somebody else's address, and be handed their account.

Three separate things stop that, and only the first is a check:

1. **`kaamase_apple_signup_errors()`** refuses an address that already belongs to
   an account, in the same deliberately vague words used by `rest-api.php` and
   `registration.php` — *"We could not create an account with those details. If
   you already have one, try signing in."* Vague on purpose: a reply that says
   plainly whether an address is registered turns the endpoint into a way to find
   out who is on the platform.

2. **`wp_insert_user()` refuses it too**, whatever the check above concluded, so
   two requests arriving together still cannot both get through. Its own wording
   says outright that the address is taken, so it is replaced with the vague one.

3. **The typed address is never written into `$claims`.** This is the one that
   matters. `$claims` is what the token said, and `kaamase_apple_find_user()`
   matches an account by the address in it. Keeping the typed address in `$data`
   means an unproven address cannot reach the code that finds accounts — not
   because a rule forbids it, but because it is not in the variable that function
   reads.

`kaamase_apple_attach()` runs on both paths, which is safe because the account is
new either way: `kaamase_create_account()` has just made it, and refused outright
if the address belonged to anybody.

### The fourth thing, which the first version of this got wrong

Letting an account exist with a typed address broke an assumption the rest of the
file was quietly resting on. Before this section, every Apple-linked account
carried an address out of a token, so confirming an account the moment its Apple
id was recognised was always right. It is not right any more.

An account made through the completion form carries an address somebody typed and
nobody checked, with the Apple id attached to it. Signing in with Apple again — or
simply sending the completion form a second time — finds that account by its Apple
id. Confirming it there would confirm an unproven address on the strength of the
Apple token, publish the profile carrying it, and hand anybody a one-tap way to
put somebody else's address on a confirmed public profile. Sending the form twice
was enough.

`kaamase_apple_proved_address()` is now the single rule, and every call to
`kaamase_apple_mark_verified()` goes through it. It confirms only when Apple sent
an address on **this** request, marked it verified, and that address is the one
the account actually carries. Being found by an Apple id proves who authorised,
and nothing about what they typed. The link in the inbox settles a typed address,
here exactly as in the ordinary registration form.

The address comparison matters as much as the presence check. An account made
with a typed address keeps its Apple id, so if that Apple id is later revoked and
re-authorised — which is what happens all day while this is being tested — Apple
can hand over a relay address for an account whose stored address is something
else entirely. Confirming on that would settle one address on Apple's word about
a different one.

### Absent and unusable are different answers

A second, quieter fault in the same file:

```php
$email = isset( $claims['email'] ) ? sanitize_email( (string) $claims['email'] ) : '';

if ( '' !== $email && ! is_email( $email ) ) {
	return $fail;
}
```

Sanitise first, reject only what survives. So a claim Apple *did* send but
`sanitize_email()` emptied came out identical to no claim at all, and the caller
reported it as "Apple sent no email address" — sending somebody off to remove the
app from their Apple settings to fix a malformed token. A claim that arrived and
will not parse is a bad token; only a claim that never arrived is missing. Now
they return different things.

### What the app does

The field appears only when the address is empty, sits under the name, and says
why:

> Apple only shares your address the first time you sign in, so we need it again
> here. We will send you a message to confirm it.

That wording is load-bearing. Somebody who has just signed in with Apple and is
then asked to type an email will otherwise conclude the sign-in failed and start
over, which is the one thing that cannot help them.

### The prerequisite this created, since settled

Mail to a `privaterelay.appleid.com` address bounces until the sending domain is
registered under **Apple Developer → Certificates, Identifiers & Profiles → the
App ID → Sign in with Apple → Configure → Email Communication**. On the token
path that never mattered, because those accounts are confirmed on the spot and no
mail is sent. On this path the confirmation email is the whole mechanism, so for
anybody who chose **Hide My Email** it became a prerequisite rather than a nice to
have. The domain is now registered and SPF verified.

### The four outcomes when Apple sends no address

Worth having in one place, because only two of them were ever obvious.

1. **The Apple id is known.** Matched on `kaamase_apple_sub`, signed in, nothing
   asked. This is the ordinary case for everybody who has used the app before.
2. **The Apple id is unknown and the token carries a verified address that
   matches an account.** Joined to that account by address. Deliberate, and the
   same rule Google has always followed.
3. **The Apple id is unknown and the token carries an address nobody holds.** A
   new account, confirmed on the spot, no mail sent.
4. **The Apple id is unknown and there is no address at all.** The completion
   form asks for one. The account is made unconfirmed exactly like a registration,
   and section 52 above is entirely about this case.

Rows 2 and 4 each had a gap that could not be closed from inside the sign in
routes, and both are closed by section 53.

## 53. Connecting Apple and Google to an account that already exists

`account-providers.php` is a new file. It adds six routes and changes nothing that
was already there.

### The two gaps

Both come from one cause: a provider could only ever be joined to an account at
the moment the account was made.

**Somebody quietly ends up with two accounts.** They register with a password
using `raj@gmail.com`, then tap Sign in with Google on a new phone. If Google
hands over the same address the two meet. If it hands over a different one — a
work address, or nothing at all, which is what Apple does on every authorisation
after the first — they get a second account with an empty profile and none of
their saved work, and nobody is told, because from the platform's side nothing
went wrong.

**Somebody is stuck behind an address they cannot use.** Finishing an Apple sign
in, they type an address that already belongs to their own older account. They are
refused, correctly and vaguely, and there is nowhere to go from there.

Connecting answers both, and it is the same act each time: prove you hold the
provider account while already signed in here, and the two are joined.

### The routes

```
GET  /me/sign-in              what the account screen draws
POST /me/apple/connect        { id_token }
POST /me/apple/disconnect
POST /me/google/connect       { id_token }
POST /me/google/disconnect
POST /me/email                { email }   replace an unconfirmed address
```

Every one requires a signed-in caller. All five provider routes answer with the
same `/me/sign-in` body, so the app never has to make a second call to redraw.

`since` is a unix timestamp, matching `expires_at` elsewhere in the API rather
than introducing a date string that would need a timezone decided for it.
`available` says whether the provider is switched on for the site at all, so the
screen knows not to draw a Connect button it cannot honour. `private_email` is
sent for Apple only, and only while connected — Google has no equivalent, and
sending `false` there would read as Google having said something it never said.

### Why the token is verified in full

Being signed in proves who is asking. It proves nothing about which Apple account
they are claiming. So connect runs the identical verification the sign in route
runs — signature, issuer, audience, expiry — with no shortcut for a caller holding
a session. A route that took a provider id on trust from a signed-in caller would
let anybody attach anybody else's identity to their own account and then sign in
as them.

A provider id already held by a different account is refused with **409**. Already
held by *this* account is a success, not an error: a second tap, a retry after a
dropped reply, or two phones at once are none of them mistakes.

### Why nothing here touches the address

Connecting writes one thing: the provider's account id. It never reads the address
out of the token, never writes it to the account, and never marks the account
confirmed. Marking an account confirmed because a provider token arrived is
exactly the hole closed in section 52, and it is not reopened here.

### Why the last way in will not come off

Disconnecting the only thing you can sign in with locks you out, and the app
cannot know it has happened until the person is already outside. So the count is
kept on this side — a password the person **chose**, plus every provider currently
connected — and `kaamase_provider_can_disconnect()` is the one guard all of it
goes through. Counting across every provider rather than against the password
alone means the guard still holds the day a third one is added.

Disconnecting deliberately does not require the provider to be switched on.
Turning Apple off in wp-admin must not trap the accounts already holding it.

### Changing an address that was typed wrong

Only while it is unconfirmed. An account created with a mistyped address is
invisible and cannot be mended from the app: *send the link again* resends to the
same wrong address, and connecting a provider does not help. `POST /me/email`
replaces it and sends a fresh link, which also invalidates any link already sent
to the wrong address.

The previous address is **not** notified. WordPress does that by default, and it is
right for an account whose owner proved that address — but this one is unconfirmed,
was very probably a typing mistake, and may belong to a stranger who should not be
told anything about an account that is not theirs. `send_email_change_email` is
filtered off for the one call.

Changing a **confirmed** address is out of scope, and refused with 409.

### Two things the brief got wrong, both harmless

`wp_update_user()` was avoided on the grounds that `profile_update` revokes every
token. It does not: `kaamase_revoke_tokens_on_password_change()` is the only
listener in the plugin and it returns early unless `user_pass` actually moved. So
`/me/email` uses `wp_update_user()`, which gets cache invalidation and the
authoritative uniqueness check for free. The caution was right; the reason was not.

The taken-address reply reuses the `kaamase_invalid_registration` code so existing
app handling works, but not the registration wording — *"We could not create an
account"* is wrong for somebody who plainly has one. It is equally vague.

## 54. How long a stolen Apple token stays useful

One check added to `kaamase_apple_verify()`. Nothing else in that file moved.

Apple sets `exp` a full day ahead — the live logs show `exp_in=86399s` — and the
app sends no nonce. Expiry alone therefore left an identity token good for
**twenty four hours** to anybody who obtained one, and a token is a whole session
here, not a step towards one. Anything that could read one in transit or out of a
log had a day to use it.

`iat` is when Apple minted the token, so bounding that closes the window without
needing anything from the app. **Thirty minutes**, rather than the seconds a sign
in really takes, because `/auth/apple/complete` sends the same token back after
somebody has typed a name, district, trade and phone number on a phone, on a
connection that may be poor, possibly having been interrupted. That is the case
the window is sized for, not the sign in itself.

Being refused is recoverable: the answer is the existing `kaamase_apple_expired`,
whose message already says to tap the Apple button again, so the app needs no
change to handle it.

The check runs only when Apple sends `iat`, which it always does. If that ever
changes this returns quietly to expiry alone rather than refusing every sign in on
the platform. A **future** `iat` is deliberately not refused either — shared
hosting clocks drift, the signature is what proves the token genuine, and
rejecting on a fast server clock would lock people out for a fault that is ours.

Google was left alone. Its tokens expire in an hour rather than a day, twenty four
times tighter to begin with, and its website flow deliberately holds a token in a
transient for fifteen minutes between the redirect and the finish, so the same
bound would need sizing around a different constraint for a much smaller gain.

## 55. Insights: the shape of the platform, and how to reach anybody on it

`insights.php` is a new file, on the Kaam Ase menu. Nothing existing is edited.

### The gap

Six hundred people had registered and never confirmed. The only screen that
showed them was `not-confirmed.php`, which has no search, no filter, no date
range, and no way to get anything out of it. Ringing six hundred people from a
paginated HTML table is not work anybody can actually do. Neither is answering
"are we growing, and is it getting better?" by counting rows.

### What it shows

**The numbers.** Accounts, confirmed, not confirmed, the confirmation rate, and
the split between workers, teams and employers. Plain figures, not charts — a
total is one value and a chart of one value is decoration.

**How people signed up** — email and password, Google, or Apple. This is the
number that answers whether the social sign-in work actually fixed the wrong
address problem, because a Google account is confirmed on the spot and cannot
mistype anything.

**Who signed up each day for the last 30**, stacked by whether they confirmed. If
the confirmation rate is improving, this is where it shows. The same figures sit
in a table underneath the chart, so the chart is never the only way to read them.

**Where they are**, busiest district first, with how many of each confirmed.

### Getting them out

The list filters on search (name, email or phone), confirmed or not, worker or
employer, district, how they signed up, and a date range. Then:

- **A spreadsheet** of every match, not just the page — name, phone, phone in
  international form, email, district, confirmed, sign-up method, date.
- **A box of phone numbers** for the current page, already carrying `+91`, which
  is the form WhatsApp and every broadcast tool wants. Deliberately the page and
  not the whole match: pasting six hundred numbers into a broadcast list is not
  something to do by accident, and the spreadsheet is the right tool when that
  really is the job.

### Why this screen is locked harder than the rest of the menu

`not-confirmed.php` makes the argument and it holds here: a list of everybody's
number sitting in wp-admin is a liability. Two rules follow.

Every number is read through `kaamase_field()`, never the raw meta, so the
privacy rule in `fields.php` stays the only gate there is. If those rights are
narrowed this screen empties out on its own.

And the capability is **`manage_options`**, not the `edit_others_kaamase_workers`
the rest of the Kaam Ase menu runs on. Somebody trusted to edit a worker's trade
is not automatically somebody trusted to export every phone number on the
platform. The export checks it again on its own and carries a nonce, because a
menu that hides a link is not a permission check.

### Two details that would otherwise be quietly wrong

**The day is worked out in the site's timezone.** WordPress stores
`user_registered` in UTC. Grouped by UTC day, everybody who signs up after half
past five in the evening lands on tomorrow — which makes "how many today" wrong
by exactly the amount that matters. The offset comes from `wp_timezone()` rather
than a hardcoded `+05:30`, so it follows the setting rather than assuming it.

**One profile per account.** Somebody holding both a worker and an employer
profile is one row, not two. Done as a derived table picking the lowest post id
rather than a `GROUP BY` on the outer query, so it stays correct under
`ONLY_FULL_GROUP_BY`. An account with no profile at all is left out entirely,
which keeps staff accounts off the list without this file naming a role.

### Sending the confirmation email again, in batches

At the bottom of the screen. This is the part that actually clears the backlog,
and what it does is not what it first looks like.

**It is not a reminder about the old email.** `kaamase_send_verification()` gives
each link seven days, and most of the six hundred are older than that, so the
link they were originally sent is dead. A reminder would send them to an expired
page, which is worse than saying nothing. This calls the same function again:
a new token is written, the old one is killed, and a fresh link goes out.

**It exists because the self-service route cannot reach these people.** Pressing
"Send it again" on the dashboard needs an active session. Somebody who registered
on a borrowed phone a fortnight ago has no session anywhere, and there is no
signed-out way to ask for a new link. Without this, the only people who could
rescue those accounts were the people who already could not be reached.

**A batch at a click, on purpose.** Six hundred messages handed to a shared mail
server in one breath is how a domain's sending reputation gets destroyed, and one
PHP request could not finish them anyway. The batch size is the rate limit.

**It stops dead on the first refusal.** If the mail server rejects one, the loop
breaks, that person is *not* marked as done, and the screen says so. The useful
outcome of a sending limit being hit is finding out after one failure with
everybody else still queued — not after six hundred silent ones with every
account marked as handled and nothing actually sent.

Three things stop it mailing the wrong people:

1. **Unconfirmed is forced, not read.** The screen's own state filter cannot widen
   this. There is no reading of the job in which somebody who already confirmed
   should be asked to confirm again.
2. **Seven days between messages to the same person.** Clicking twice, or working
   through the districts and losing your place, cannot mail anybody twice in a
   day. Recorded as `kaamase_confirm_nudge_at` on the account.
3. **The ten minute self-service lock is set too**, the same one `/auth/resend`
   uses, so somebody who presses "Send it again" just after receiving this does
   not trigger a second.

The rest of the filter bar still applies, so a batch can be one district at a
time — which is the sensible way to do it, since it matches how the calls would
be made anyway.

### The chart's two colours were checked, not chosen

Blue `#2a78d6` and orange `#eb6834`. The whole point of that chart is telling
confirmed from not confirmed apart, so the pair was run through a contrast and
colour-blindness check rather than picked by eye: worst-case separation 24.7 for
the commonest colour blindness and 33.6 for normal vision, both well clear of
their floors, and both above 3:1 against the background. There is a legend, the
figures are in a table underneath, and the bars carry hover labels, so the colour
is never the only thing carrying the meaning.

## 56. The subscription audit: three things money was getting wrong

Four files in `kaamase-pay`, one of them new. Found by reading the payment
plugin end to end rather than by anybody reporting it, which is the point: all
three are the kind that a customer notices before you do.

One finding from the same audit — a failed card being silent — turned out to be
**already fixed**, so nothing was done to it.

### A paying customer could lose access for a day

`kaamase_pay_days()` handed out a flat **30 days** for monthly and **365** for
yearly. Razorpay bills on the **calendar month**: the 15th of January, the 15th
of February, the 15th of March. In a 31 day month the thirty days ran out on the
14th and the next charge did not arrive until the 15th, so somebody who was
paying had a day with no allowance. Yearly did the same across a leap year.

It was not only Razorpay. Apple and Google renewals come through the same
`kaamase_pay_grant()`, and a store subscription is not reliably a month at all —
it can be a trial, a period the store extended while retrying a card, or an
upgrade mid-cycle. Thirty days was a guess against every one of those.

Three changes, in order of authority:

1. **The provider's own cycle end is used when it sends one.** Razorpay puts
   `current_end` on every `subscription.charged`; RevenueCat puts
   `expiration_at_ms` on every store event. Both were being **thrown away** —
   neither string appeared anywhere in the plugin. They are the billing
   calendar rather than a guess at it, so they win.
2. **Failing that, whole calendar months.** PHP's own month arithmetic could not
   be used directly: `31 January +1 month` is **3 March**, because February has
   no 31st, which would have handed out three free days every time a long month
   rolled into a short one. `kaamase_pay_add_months()` lands on the first of the
   target month and then clamps the day to that month's length, so 31 January
   becomes 28 February, and 29 February becomes 28 February a year later.
3. **Two days of grace on top.** A webhook is not instant and is retried when it
   fails. With the expiry landing exactly on the charge date, any of that
   lateness is a window with no access. The grace does not compound into free
   months, because the next renewal counts from the date already held.

Two guards came with it: a cycle end **in the past** is ignored rather than
honoured, so a replayed event cannot drag a live subscriber backwards; and the
new date is never allowed to be **shorter than what is already held**, so
somebody who bought a year and then renewed a month keeps the year.

### A refund gave the money back and kept the access

`payment.refunded` was not in the webhook's event list at all. Refund somebody
and they kept every day they had paid for. That is not a generous policy, it is
a hole: the one person guaranteed to know about it is the one who asked for the
money back, and nothing stopped them doing it again next month.

Now handled, under all three names Razorpay uses for it, with the existing event
id guard stopping the same refund counting twice. The row is marked refunded and
`kaamase_pay_revoke_period()` takes the time back using the same calendar
arithmetic that granted it — so a refunded month removes a month, and a refund
straight after a payment lands where it started. It is floored at now, so the
worst it can do is end access today; it can never invent a debt.

**A part refund takes nothing back.** Refunding two hundred of a thousand is a
correction or a goodwill gesture, and ending somebody's month over it would turn
a partial refund into a total one. It is written into the record and left.

### Nobody was ever told anything about money

There was exactly one email in the whole payment plugin — the one that goes out
when a card is declined, which is good and stays. Nothing else. Somebody paid
and got a screen: no record of what they bought, no price, no date it runs to,
and nothing at all before a plan that does not renew stopped working one
morning.

`notices.php` adds two messages and deliberately only two. Anything more is a
mailing list, and this platform's promise to the people on it rests on not being
one.

**A receipt**, on every grant including renewals — a renewal is the charge
somebody is most likely to have forgotten was coming. The amount is read from the
row that was just written rather than from the plan's current price, because a
price that went up last month is not what this person was charged.

**A warning**, three days before access ends. Only for people **nobody is going
to charge again**: warning somebody that a subscription is ending, when it is
about to renew instead, invites them to cancel something they meant to keep.
`kaamase_pay_renews()` is the shared answer to that, covering both a Razorpay
subscription id and the store's own renewal flag.

The reminder is marked against **the expiry date it was about**, not a yes/no
flag, so renewing re-arms it with no code needing to remember to clear anything.
It is marked only when the mail actually went, and the daily run stops on the
first refusal, so a mail server having a bad morning means people are warned late
rather than never.

### Access still has no scheduled task

`access.php` is deliberate about this and it is untouched: paid access is a
**date**, so it lapses correctly on the next request whether or not any cron ran.
The daily job added here is purely the reminder email. If it never fires, nobody
is warned and every other part of the system behaves exactly as before.

### One trap worth recording

`kaamase-pay.php` loads its includes from an **explicit list**, not a `glob()`
like `kaamase-core` does. A new file dropped into `includes/` is silently never
loaded. `notices.php` is in the list.

## 57. Telling Google about a job the minute it is posted

`indexing-api.php` is a new file, on the Kaam Ase menu as **Google indexing**.
Nothing existing is edited.

### Why this one is worth having

Google's Indexing API works for exactly **two** kinds of page in the world:
`JobPosting` and `BroadcastEvent`. That restriction is the reason it is worth
building. Almost every site that would like faster indexing is not allowed to
use it. A job board is.

Without it a new job waits for a crawler, which on a young site is days or
weeks. A labour job posted on Monday for work on Wednesday is worthless by the
time it is found. With it, the URL is handed to Google within minutes.

It is **not** a ranking trick and does not force anything into the jobs box. It
says "this page changed, come and look." Whether Google then shows it is decided
on the structured data in `schema.php` and everything else it weighs.

Using it for pages that are not job postings breaks Google's terms and is
enforced when quota is requested, so this only ever sends a URL whose post type
is `kaamase_job`. There is deliberately no filter to widen that.

### The details that decide whether it works at all

**The endpoint has a colon, not a slash** —
`https://indexing.googleapis.com/v3/urlNotifications:publish`. That is how
Google spells custom methods. The slash form returns a 404 that reads like a
routing fault on your own site, and is the single easiest way to build this and
have it silently send nothing.

**`aud` in the signed assertion is the token endpoint, not the API.** A JWT
naming the Indexing API as its audience is refused, and the message does not say
why.

**`exp` may be at most one hour after `iat`.** Anything longer is rejected
outright.

The assertion is RS256, signed with the service account's private key, and
exchanged at `oauth2.googleapis.com/token` with
`grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer`. The access token is
cached until five minutes before it expires, so a run of twenty URLs signs in
once rather than twenty times.

### Nothing happens while somebody is waiting

Posting a job must not wait on two HTTPS round trips to Google. If Google is
slow the employer watches a spinner; if Google is down the post fails for a
reason that has nothing to do with posting. So publishing writes one line into a
queue and returns, and a five minute schedule empties it. Anything that fails
stays queued and is tried again.

The run **stops on the first refusal** rather than working through the rest.
Whatever is wrong — a revoked key, a wrong server clock, the daily allowance —
is wrong for every entry, and burning the queue against it just loses the URLs.

The same URL twice is one queue entry holding whichever answer is newer. A job
posted and closed within the hour should tell Google it is gone, not tell it
twice that it exists and then argue.

### One hook, not several

`transition_post_status` sees every way a job can start or stop being public —
published, closed, filled, expired, unpublished, binned — so nothing has to be
chased separately and a route added later cannot be missed. `before_delete_post`
covers a permanent delete, caught before it goes because the permalink cannot be
built afterwards.

A draft moving to pending sends nothing. It was never a page Google could have,
and telling it to drop a URL it has never seen wastes one of two hundred.

### The daily allowance

Google's default is **200 publishes a day**, counted here as well as there.
Going over returns an error for every remaining call of the day rather than
queueing, and a queue that empties itself into a wall is worse than one that
waits.

### The private key

The service account JSON holds an RSA private key. Stored with **autoload off**,
for the same reason the Razorpay keys are (section 6): an autoloaded option is
read into memory on every request on the site, including the ones with nothing
to do with it. It is never printed back to the screen — the settings form shows
only which account is connected, and an empty key box means "unchanged" rather
than "delete", so saving the on switch cannot quietly throw the credentials away.

### The test is the point of the screen

Everything in this file can be correct and still send nothing, because the half
that decides lives in a Google Cloud console and a Search Console property,
neither of which this code can see.

So the test does the real thing: it publishes your most recent **real** job URL —
Google refuses any URL with no job posting on it, so a test against the homepage
would prove nothing — and then calls `urlNotifications/metadata` to ask Google to
read back what it just received. That read back is the difference between "the
message was accepted" and "Google has it on record."

It reports which of four steps it reached, so "it did not work" is never the
answer when there are four distinct reasons and three of them are settings in
somebody else's console.

## 58. Telling somebody their profile passed a round number

Two files: `push.php`, which already existed and is edited rather than replaced,
and the rebuilt `kaamase-core.pot`. **`views.php` is not touched**, and that is
deliberate — the counting works and nothing here can put it at risk.

Views have been counted since section 27 and displayed since 32. Almost nobody
goes and looks at the number. This sends it to them instead, once, when it passes
a figure — the thing a photo app does at a hundred likes, and it works for the
same reason: it is news about them rather than about the platform. It is also the
only notification on the platform that is purely good news, which is worth
something on its own.

### The figures

10, 50, 100, 160, 200, 300, 400, 500, then every hundred to a thousand, every
five hundred to five thousand, then every thousand up to fifty thousand.

Close together at the start and further apart later, because that is the only
shape that works. The first ten opens are what prove to a worker that the
platform is doing something. By the time somebody is at five thousand, another
hundred is not news, and a notification about it is noise.

Worker profiles, teams and jobs. **Not employer pages** — telling somebody their
own company page is popular is flattery rather than information.

Openings only, never showings. The two have been counted separately since
section 38, and 500 cards scrolled past is not the same claim as 500 people
opening you.

### Your first upload sends nothing at all

This is the part worth reading twice.

The site has a year of view history. A first run that simply looked at the table
would push several hundred people at once about figures they passed months ago —
the worst possible first impression of a notification meant to feel like good
news, and the fastest way to teach people to turn them off.

So the first run that is able to send anything writes down where everybody
already is and sends nothing. A worker sitting on 1,000 opens gets their next
notification at 1,500. Nobody hears about history.

After that, a profile with nothing written down is genuinely new, so its first
ten opens are a real milestone and do go out. New profiles are not quietly
skipped by the same mechanism that protects the old ones.

### Read from the table, never hooked onto the counter

Counting a view sits on the critical path of somebody loading a page. Turning
that into a `SUM` over a year of rows, on shared hosting, to find out whether
this particular view happened to be the hundredth, would make every profile page
slower for a notification that fires once in a thousand views.

An hourly task reads the table instead. It asks which profiles and jobs were
opened in the last two days, totals only those, and compares each against what
its owner was last told. Two indexed queries, and on most runs nothing crossed
anything and nothing is written.

The figure is stored **on the post**, not on the account, because one employer
can have twenty jobs and each passes a hundred on its own day. That store is also
what survives the yearly prune in `views.php` taking old rows away: a total that
dips back under a figure and climbs through it again cannot fire twice.

### The highest figure, not every one it went past

A job that goes from nothing to 250 overnight gets one notification saying 200 —
not four saying 10, 50, 100 and 160. Four notifications in a row for one job is a
chore, and a chore gets the app muted.

### Never at night, and never in a blast

Quiet hours from nine at night to eight in the morning in site time, which is now
Asia/Kolkata. Nothing is lost by skipping those runs: the window looks back two
days, so whatever crossed at one in the morning is still found at eight.

At most 200 in one run. Anything over the cap is **left for the next hour rather
than thrown away**, and it cannot starve anybody: what was sent is written down
and drops out of the set, so the queue is 200 shorter every time round.

### Push only, and no phone number

Never email. A view count is a pleasure to see on a phone and an annoyance to
find in an inbox, and the fallback in `kaamase_notify_user()` would have put it
there.

No phone number, and nothing about who looked. A count is not a list, and this is
deliberately not one — section 45 is where asking for somebody's number lives,
and it stays there.

A closed job, a draft profile and an owner with no app are all written down and
not sent. Publishing a draft later does not replay its history.

### Nothing for the app to do

The payload carries `type: view_milestone`, the post id and the figure, alongside
the shapes the app already handles. No app release is needed for this, and none
is waiting on it.

### Off means off

The whole feature is silent while push is switched off in settings — including
the writing-down. A site that has never sent a notification should not be
carrying a record of figures nobody was told, and switching push on later starts
everybody from where they actually are on that day.

### The language file

`kaamase-core.pot` is rebuilt from the merged source, so it is current again:
**94 new strings, none removed**. Six of them are these notifications; the other
88 are the Google indexing screen (section 57) and the Insights resend work
(section 55), both added after the template was last generated. Ready for
Nagamese and Hindi without anything being chased down first.

### What it does not do

No digest, no weekly summary, no "you are trending". One sentence, one figure,
once. The list at the top of `push.php` has one more item on it and no more than
that, and every item on it still earns its place — which is the whole argument
that file has been making since it was written.

## 59. Promoted listings — phase 1 of 3, and nothing shows yet

One new file, `promote.php`. Nothing existing is edited. After copying it up
there is a new screen at **Kaam Ase → Promotions**, and a new card on the
dashboard for anybody with a live profile or an open job.

**Nothing appears on any listing yet, by design.** This phase is the machinery:
the request, the queue, the call, the button, the expiry and the book. The Ad
badge comes next on the website, then in the app. `views.php` was built in three
phases for the same reason — the counting should be right and running before the
first person sees a number.

### What is being sold, and what is not

A slot. Not a position. `exposure.php` has said this since it was written:

> *"It is not ranking. Nobody is ranked above anybody on merit here, and nothing
> about paying moves a profile up. A hiring platform that sells position tells an
> employer that the worker who paid is the better worker, and the employer finds
> out on the site that this is false."*

So a promotion will never reorder a list. Phase 2 puts one marked card **above**
the list; the queue underneath stays exactly as `exposure.php` decided it.
Nobody loses a place because somebody else paid.

That is also what makes it sellable to several agencies at once, which is the
whole point of building it. Nobody can buy the top of Kaam Ase, because the top
of Kaam Ase is not for sale. What is for sale is a marked slot, shared between
whoever bought it.

### The money does not come through the site

Deliberately. Somebody asks, you telephone them, they pay you on Google Pay, and
you start it by hand. There is no checkout, no gateway, and **no price anywhere
in the code** — the price is settled on the call, where an agency and a worker
can be quoted differently without either reading the other's number off a page.

The call is not an inconvenience, it is the product. It is what stops a placement
racket buying a promoted slot with a card at two in the morning. `job-screening.php`
opens by naming trafficking as a live risk in this region, and **taking money to
promote a job is a louder act than hosting one**. The screen says so above the
first table, with the four things to ask.

### What to do on the screen

1. **Waiting to be rung** — who asked, what for, their number, and when they said
   to ring. You ring them.
2. Enter **days**, **₹**, and the **Google Pay reference**, then Start it.
3. **Running now** shows what is live and how long is left, with **Stop now** on
   every row.
4. **Just finished** is the renewal list. Somebody whose week ended yesterday is
   the easiest call of the day.

### Three things that would otherwise go wrong

**It stops on its day whether or not anything ran.** `kaamase_promo_is_live()`
reads the clock, not the stored word. The daily sweep only tidies state up and
sends the message, so a night the task failed costs a notification rather than a
week of free advertising. This is the same lesson as section 56: the date is
authoritative, the bookkeeping follows it.

**Days are whole days, in Nagaland's clock.** A week bought at nine at night runs
to the last second of the seventh day here, not to nine at night. That is what
anybody would mean saying it out loud, and the only version that survives being
explained on the phone.

**A job that closes halfway through stops being advertised immediately**, and the
run is left standing and flagged in red on your screen rather than silently
cancelled — because the employer has paid for days they are no longer getting,
and that is a conversation, not a database change.

### The Google Pay reference is not decoration

Every payment on this platform happens between two phones. Without the
transaction number there is nothing to point at, and *"but I paid you"* becomes
an argument with no facts in it. The box is on the Start form for that reason.

### The book

Every run ever started is written to one option, with the date, what was sold, to
whom, for how many days, for how much, and the reference. **Download the book**
gives you it as a spreadsheet.

It is written from the ledger rather than from the listings on purpose: a job
binned in May was still paid for in April, and the line has to survive the
listing being deleted.

The heading counts **from 1 April**, because that is the year an accountant asks
about. Worth knowing before it arrives: Nagaland is a special category state, so
a business there must register for GST once service turnover passes **ten lakh**
in a year — not the twenty lakh that most Indian guidance, written for elsewhere,
will tell you. Advertising is a service. Ask an accountant before you are near
that line rather than after.

### Not promoted

**Employer pages.** Nobody is searching for employers, and a promoted slot
pointing at a company profile is an advertisement for a company rather than a
job — which is the one thing Google's job posting rules say a listing must never
be. Workers, teams and jobs only.

### Nothing here touches Google

`schema.php` is not edited and must not be. Google's job content policy prohibits
ads disguised as job listings and promotional content marked up as `JobPosting`,
and Google for Jobs has no paid placement at all. The Ad badge and the slot live
only in your own screens, and a promotion may only ever boost **a real job that
already exists** — never become a way to buy a listing that is not a job. That
is what keeps section 57 safe.

### Nothing here touches the tick either

`verify-requests.php` is emphatic that the mark means a telephone call happened.
An advertisement must never grant or imply it. Different word, different badge,
no overlap, ever.

### One thing found while building this

`insights.php` calls `fputcsv()` without the escape argument, which PHP 8.4
deprecates. On a host with `display_errors` on, that notice is printed **into the
download**, so the spreadsheet arrives with a line of PHP at the top and will not
open. `promote.php` passes all the arguments and its export was tested with
notices switched on to prove it. The same one-line fix is worth making in
`insights.php` — say the word and it will be in the next upload.

## 60. The Ad mark, and the slot — phase 2 of 3

Four files. `promote.php` again, the theme's `template-tags.php` and `style.css`,
and a one-line fault in `insights.php` that is described at the end.

**This is the phase where something appears.** After copying these up, a
promotion bought on the Promotions screen is visible on the website.

### The mark

The word **Ad**. Short because a worker's name is already being cut off on a
narrow phone, and because India's advertising code names it as an acceptable
label — the shortest true word available happens to be the approved one.

**On a job card** it sits on the top row, right-aligned, beside Urgent. Not after
the title: a job title wraps to two lines as often as not, and a mark tacked onto
the end of one lands somewhere different on every card. That row is one line on
every job there is.

**On a worker or team card** it goes first in the badge row, where Vouched and
Verified already are. It cannot go beside the name — `verified-mark.php` claims
that spot by an explicit decision, and there is no room next to a name that
truncates. First in the row, because a disclosure printed after two awards has
already been read as a third award.

### Why it is drawn quietly

Every other badge on the site is a block of colour. This one is a grey outline.

Vouched and Verified were **earned**. Ad was **bought**. An advertisement dressed
as an achievement is the thing an advertising code exists to prevent, and here it
would cost more than a rebuke: an employer who works out that the bright badge is
for sale stops believing the other two as well.

Quiet is not faint. The text sits at about 9:1 against the card, because a
disclosure nobody can read is not a disclosure. It is also square-cornered and
badge-sized so it cannot be mistaken for a trade chip, which is a pill and is
also an outline — on a job card the two share a row, and a disclosure that reads
as a trade name is worse than none.

### The slot

One promoted card at the top of a listing. Which one rotates by the hour, so five
advertisers in one district get a fifth of it each rather than the first one
getting all of it. That rotation is the thing that lets the slot be sold to
several agencies at once, which was the whole reason for building this.

**Nothing is reordered to make room.** No filter here touches `posts_orderby` or
`the_posts`, nothing is removed from the results, and no page count changes. One
card is drawn at the top of the grid and the grid is exactly what `exposure.php`
decided. Everybody keeps their place. The tests check this by comparing the
query's results before and after.

Rules it follows:

- **First page only.** An advertisement on page four is one nobody sees, and a
  copy on every page is why people mute a site.
- **Never twice in a request**, even if a template runs the loop twice.
- **A worker list advertises workers**, a job list jobs. A team belongs on the
  worker list.
- **A district bought is a district shown.** Somebody who bought Dimapur does not
  appear on the Mon list.
- **Nothing that is already on the page.** A promoted listing sitting in the
  results carries its own mark where it stands; a second copy of one card on one
  screen reads as a fault, not an advertisement.
- **Listings only.** Not the front page, not a single profile, not the blog, not
  a feed, not a secondary query.

The promoted card is the ordinary card with no wrapper around it. The listing
grid styles its own children, and slipping an extra element between the two would
be a layout change made for the sake of an advertisement, which is the wrong way
round.

### The advertiser gets a real number

The slot card is drawn by the same function as every other card, so it carries
`data-ka-seen` and is counted by the same beacon. A week in the slot produces a
figure you can quote on the next call — measured, not estimated. Very little else
being sold locally can prove that.

### One thing to know about the page cache

The website sits behind a page cache (sections 29 and 30). A cached listing holds
whoever was chosen when it was built until it is rebuilt, so the rotation on the
website is coarser than hourly. Across a day it still comes out even, and the app
— which is most of the traffic and is not cached — will rotate exactly when phase
3 ships.

### The off switch

`kaamase_promo_slot_enabled` turns the slot off without editing a file. The mark
still shows where a promoted listing stands, runs still end on their day, and the
takings are unaffected. Only the extra card at the top stops.

### The theme still works without the plugin

Both calls are guarded with `function_exists`. With `kaamase-core` switched off
the cards render exactly as they did before, no mark and no fatal. There is a
test that loads the theme in a process with no plugin in it and checks precisely
that.

### `insights.php` — a download that would not open

Found while building the promotions export, and fixed here.

`fputcsv()` was being called without its escape argument, which PHP 8.4
deprecates. On a host with `display_errors` on, that notice is printed **into the
download**, so the spreadsheet arrives with a line of PHP above the header row
and will not open in Excel.

Both calls now pass the separator, the enclosure and the escape. An empty escape
is the value PHP is moving to and the one the CSV standard describes. Nothing
else in the file is touched — same columns, same rows, same order. The promotions
export was tested with notices switched on to prove the output is clean.

⚠️ **Copy `insights.php` again.** This is its third revision.

## 61. The app's half of the promotions — phase 3, server side

One file, `promote.php` again. The app release is a separate piece of work in the
app's own repository; this is everything the server owes it, and it is safe to
upload before the app is ready.

**Nothing here changes what an app that has not been updated sees.** Both
additions are additive: an older build ignores a key it does not know, and it
never calls a route it has never heard of.

### The mark, in the listings the app already reads

- **Worker and team:** `badges.promoted`, a boolean, beside `vouched` and
  `verified` — a third key in an object the app is already reading.
- **Job:** `promoted`, a boolean, at the top level beside `urgent`.

The two shapes keep their flags in different places and that difference is older
than this feature. Matching each one is less surprising to somebody who already
knows these payloads than inventing a third arrangement for the sake of symmetry.

Both are always present, never absent, and always a real boolean.

### `GET /kaamase/v1/promoted`

```
GET /wp-json/kaamase/v1/promoted?kind=worker|job&district=<slug>
→ { "item": { …a list-shaped card… } }   or   { "item": null }
```

A route of its own rather than another key on the listing responses, for three
reasons. The list envelope in `rest-api.php` is shared by every list on the
platform, and adding to it would touch screens that have nothing to do with
advertising. An app that has not been updated never calls this, so nothing
changes for it. And a slot that is slow or failing must never be able to stop a
worker seeing a list of jobs, which is exactly what it could do if it travelled
with them.

Open to anybody, signed in or not. An advertisement shown only to signed-in
people is one the buyer is not getting what they paid for, and there is nothing
in the answer a listing page does not already show.

`item` is `null` rather than missing when there is nothing to show. A key that
comes and goes is a key somebody forgets to check for.

The card is shaped by the same function the lists use, so the app draws it with
the component it already has.

### What the app has to do that the server cannot

**Not show it twice.** The server does not know what is on the phone's screen. If
the id in `item` is already in the list the app just fetched, the app skips the
slot — the card carries its own mark where it stands, and two copies of one card
on one screen reads as a fault rather than an advertisement.

**Count it.** The slot card should be reported to the views endpoint as `shown`,
exactly like any other card. That is what turns a week in the slot into a number
the advertiser can be told on the next call.

### Rotation is exact here

The website sits behind a page cache, so its slot rotates coarsely (section 60).
The app does not, so a phone asking at eleven and again at twelve gets two
different advertisers when two have bought the same district. Since the app is
most of the traffic, this is where the fairness actually lands.

### Asking, from the app

`POST /kaamase/v1/promote` and the `promote` block on `/me` have been there since
phase 1 (section 59) and are unchanged.

### The promotion notification says which kind of listing it means

Added after the app side reported the gap. The push now carries:

```json
{ "type": "promotion", "id": 601, "kind": "job" | "profile", "what": "started" | "ended" | "stopped" }
```

The id alone never said whether it pointed at a profile or a job, and the app
cannot tell from the number, so the first build had to open the promotions screen
and let the person work out which listing was meant. With `kind` a tap can open
the listing itself. Additive: a build that ignores it behaves exactly as before.

### A note on the wording, for whoever builds the screen

`copy.live` is **"This is being promoted now."** — there is no placeholder in it
for a date. The end date is `ends` on each row of `promote.mine`, a unix
timestamp in seconds, and it belongs on its own line.

That split is deliberate rather than an omission. A date formatted on the server
is formatted in the site's locale; the same timestamp formatted on the phone is
formatted in the reader's. The phone should win.

## 62. Two faults found by testing, and the slot made sellable

Two files: `promote.php`, and `verified-mark.php` — which has not been touched
before, so what you are uploading is the original with one block added.

⚠️ **`verified-mark.php` is new to this list.** It was never in `fixed/` before.

### The Ad mark never showed on website worker cards

Section 60 put the mark in the theme's `kaamase_badges()`. **That function never
runs on this site.**

`verified-mark.php` says so itself, at the top of its second section:

> *"Defining it here first puts the mark in all of them at once: plugins load
> before themes, and the theme's copy is wrapped in a check for whether the name
> is already taken."*

So the plugin takes the name and the theme's copy is dead. The job card was fine
because `kaamase_job_card()` really is the theme's; the badge row never was.

The result was the worst possible shape of this bug: a promoted worker showed
**Ad** in the app and nothing at all on the website — so the person who had just
paid could see it working everywhere except the place they were most likely to
look.

The mark now goes in the plugin's copy, first in the row, same wording and same
quiet outline. The theme's copy keeps its version, which is harmless: with the
plugin off there are no promotions either, and the guard returns nothing.

A test loads the plugin and then the theme, in that order, and checks the Ad mark
survives **alongside the call mark** — which only the plugin's copy draws, and so
proves which function answered.

### A paid listing now goes to the top, always

Before this, the slot was only drawn when the promoted listing was **not** on
page one. If it was already there it stayed where the queue had put it, wearing
an Ad mark, and no slot was drawn at all.

That was wrong, and testing showed it plainly: on the Workers tab the advertiser
was first, and on the Jobs tab at the same moment the advertiser was third. Since
`exposure.php` rotates daily, the same buyer would be third today and fortieth
tomorrow. What was being sold was "the top, unless you happened to be doing well
anyway, in which case somewhere" — which nobody can sell and no buyer can check.

It is hoisted now. The card is lifted out of the page and drawn above it.

**This still does not reorder the list.** Everybody below keeps the order
`exposure.php` gave them and keeps it relative to each other. One marked card
moved; nobody was shuffled. `posts_orderby` is untouched, and so are `posts_where`
and `posts_join` — there is a test for each. The one filter now used is
`the_posts`, and only to lift the one paid card out so it is not shown twice.

One case is left alone: when the promoted listing is the **only** result. Taking
it out would empty the page, and an empty page runs no loop, so the advertisement
would vanish along with the result. It is already at the top and already marked.

### The slot turns over every five minutes

Was an hour. Five minutes is better for the same reason it sounds better: an hour
each means the same advertiser gets nine in the morning every single day, and
nine in the morning is not worth the same as six in the evening. At five minutes
everybody gets a share of every part of the day.

With **one** advertiser nothing rotates at all — a single candidate is chosen
every time whatever the clock says, so a sole buyer holds the slot without a
break until their run ends. That needs no special case in the code and it is
worth saying out loud on the telephone, because it is what makes a sole buyer
worth charging more.

`KAAMASE_PROMO_ROTATE` is the constant if it ever needs changing.

### Taken out of every page, not only the first

The advertisement is drawn once, at the top of page one. The paid listing is
removed from **every** page it would otherwise appear on.

Removing it only from page one would have moved the duplicate rather than fixed
it: a buyer sitting at number thirty-five organically would be at the top of page
one and again halfway down page two, wearing the same mark both times. Removing
on a later page and drawing nothing there is the right pair — the card was
already shown at the top and the reader has passed it.

The one exception is unchanged: when the paid listing is the only thing on the
page, it is left alone, because taking it out would leave an empty page.

This came from the app side, which had reached the same conclusion
independently. Both now behave identically.

### The app and the server agree on which five minutes it is

Both floor an absolute clock rather than counting from whenever they started:
`floor( time() / 300 )` here, `Math.floor(Date.now() / 300000)` there. Those are
the same number. So at any given moment a phone and the website pick the same
advertiser, and a buyer's turn starts and ends on both at once. A rotation
counted from first fetch would have drifted apart within the hour.

### What the app did differently, and was right to

The per-window cache key suggested from here would have leaked storage. The
app's cache only evicts an entry when it is read again, so a key containing the
window number creates an entry that nothing ever reads — on Android, where the
store is capped, that grows without bound.

It keeps one entry per list instead and shows a cached answer only if it was
saved inside the current window. Same guarantee, no leak. Worth recording
because the mistake was in the advice, not in the app.

## 63. The other two translation templates, brought up to date

`kaamase.pot` and `kaamase-pay.pot`. **No PHP in the theme or the payment plugin
was touched** — only the two templates, rebuilt from the code as it stands.

### The payment one was missing fourteen strings

All fourteen are from `includes/notices.php` — the receipt and the expiry warning
added in section 56. That file was new and its strings never reached the
template, so every word of the two emails a paying customer receives was
untranslatable. That is the worst place on the platform to have a gap: they are
the only messages about money anybody gets.

Among them: *"Your payment to %s"*, *"Your plan on %s is ending"*, *"It renews on
its own, so you do not need to do anything."* and *"Nothing will be charged. When
it ends your account stays exactly as it is."*

### The theme one had every string, and the wrong line numbers

Nothing missing and nothing stale — the words were all there. But **171 reference
lines were pointing at the wrong place**, because the template was last built on
1 September and the theme has moved since. A reference is how a translator finds
the sentence in context; one that is off by forty lines sends them to somebody
else's code.

### Both gained translator comments

The old templates had **none at all**, in either file. The new ones carry 16 and
24.

This matters more than it sounds. A translator handed `"%1$s %2$s"` with no note
has no way to know which is the number and which is the trade, and in a language
that orders them differently they have to guess. Every one of those is now
labelled.

### How they were built

The same generator that builds the core template, with the domain and project
name passed in rather than fixed. Before using it on anything, it was run against
the core plugin and its output compared to the core template already shipped:
identical. So the two files below were produced by a tool proven not to change
anything on a package it had already done correctly.

⚠️ **Copy both again.** No code change accompanies them.

## 64. Hindi — the theme, and how the files have to be named

The website's own text, all 251 strings, in Hindi. **No code changed anywhere.**

### Two files

| Copy this file | Into this folder |
| --- | --- |
| `fixed/kaamase/languages/hi_IN.mo` | `wp-content/themes/kaamase/languages/` |
| `fixed/kaamase/languages/hi_IN.po` | `wp-content/themes/kaamase/languages/` |

The `.mo` is the one WordPress reads. The `.po` is the readable source, kept
beside it so the next person can correct a word without starting again.

### The name is not a detail

A theme and a plugin look for translations under **different names**, and getting
it wrong fails silently — no error, no warning, just English.

```
load_theme_textdomain( 'kaamase', … )   ->  languages/hi_IN.mo
load_plugin_textdomain( 'kaamase-core' ) ->  languages/kaamase-core-hi_IN.mo
```

`inc/setup.php` uses the theme form, so the file is `hi_IN.mo`, not
`kaamase-hi_IN.mo`. The plugins, when their turn comes, take the longer name.

### What sort of Hindi

Plain spoken Hindi, the kind a mason from Bihar working in Dimapur actually uses.
Not the formal register. *मज़दूर* rather than *श्रमिक*; *काम डालें* rather than
*कार्य प्रकाशित करें*.

The audience is real and visible in the job listings already on the platform:
recruitment for Gujarat, Rajasthan and "Pan India", and the migrant workers those
posts are aimed at. Most Nagas do not speak Hindi first — Nagamese does that job,
and it comes next.

**Kaam Ase stays Kaam Ase.** It is the name of the business and it is already
Nagamese, so translating it would be translating a name.

### What was checked, and how

**Every placeholder survives.** A translated sentence that loses a `%s` prints a
blank where a number should be, and a `%1$s` mistyped as `%s` is a PHP warning on
a live page. 264 translated strings (plurals counted separately) were compared
against their English placeholders: **0 mismatches**.

**The `.mo` is a real `.mo`.** There is no `msgfmt` on the build machine, so the
compiler was written by hand and the output parsed back the way gettext reads it:
magic number correct, 252 entries, **keys sorted** (gettext binary-searches them,
so an unsorted file half works and is horrible to diagnose), header entry present
carrying the plural rule, plurals joined with a null byte, every entry valid
UTF-8.

### Hindi plurals are English plurals, here

`nplurals=2; plural=(n != 1);` — the same rule. So *%s दिन* covers one day and
five days, which is also how the language behaves.

### This does not switch the site to Hindi on its own

Worth being plain about. The file makes Hindi **available**; something still has
to decide to use it. WordPress serves the site language to everybody unless a
logged-in account has its own language set, or a switcher is added. Until then
these translations sit unused.

Deciding how the site chooses a language is its own piece of work and is not
started here.

## 65. Hindi, finished

Every string on Kaam Ase that a worker or an employer can see is now in Hindi.

| Copy this file | Into this folder |
| --- | --- |
| `fixed/kaamase/languages/hi_IN.mo` and `.po` | `wp-content/themes/kaamase/languages/` |
| `fixed/kaamase-pay/languages/kaamase-pay-hi_IN.mo` and `.po` | `wp-content/plugins/kaamase-pay/languages/` |
| `fixed/kaamase-core/languages/kaamase-core-hi_IN.mo` and `.po` | `wp-content/plugins/kaamase-core/languages/` |

### The count

| | Translated | Deliberately left |
| --- | --- | --- |
| Theme, the website's own text | 251 of 251 | — |
| Payment plugin | 121 | 62 owner-only |
| Core plugin | 851 of 851 that reach a person | 861 owner-only |

**1,223 strings.** The 923 left are the Insights, Promotions, Google indexing,
Limits and registry screens, which only the owner opens and which he reads in
English. Translating them would be effort with no reader, and it is a decision
rather than an omission.

### What was checked on every batch

**Placeholders.** A translation that loses a `%s` prints a blank where a number
belongs; a `%1$s` mistyped as `%s` is a PHP warning on a live page. Every
translated string was compared against its English placeholders across all three
files: **no mismatches**.

Hindi reorders sentences, so `Your %1$s plan on %2$s ends on %3$s` becomes
`%2$s पर आपका %1$s प्लान %3$s को खत्म हो रहा है`. That is what numbered
placeholders exist for, and the checker compares the set rather than the
sequence, which is why a correct reordering passes and a dropped argument does
not.

**The files themselves.** There is no `msgfmt` on the build machine, so the
compiler was written by hand and every output parsed back the way gettext reads
it: magic number, keys sorted for the binary search, header entry carrying the
plural rule, plurals joined with a null byte, valid UTF-8 throughout. All three
clean.

**Newlines in the emails.** Several job emails are multi-line. The template
writes them escaped and gettext stores them raw, which is easy to get wrong.
Read back out of the compiled file: five newlines in the English, five in the
Hindi, in the same places.

### The Hindi itself

Plain spoken Hindi, the kind a mason from Bihar working in Dimapur uses, not the
formal register. *मज़दूर* rather than *श्रमिक*. *काम डालें* rather than
*कार्य प्रकाशित करें*.

**Kaam Ase stays Kaam Ase**, and so does **Rich Manu**. Both are names.

**DELETE stays DELETE.** It is what somebody types to confirm their account is
going, and it has to be typed exactly. A Devanagari word there is a trap on a
phone keyboard set to English.

### It still does not switch the site to Hindi

Said in section 64 and worth repeating now the work is done. These files make
Hindi **available**. WordPress serves the site language to everybody unless a
signed-in account has its own language set, so until there is a switcher, or the
app asks for a locale, almost nobody will see any of it.

That is the next piece of work, and it is small next to this one.

### Nagamese next

The tooling is built and the method is proven, so Nagamese is the same shape of
job without the groundwork. `translations/` holds the JSON each language is built
from, the `.po` and `.mo` writer, and the placeholder checker.

One thing will be different and should be decided first: Nagamese has no settled
written standard, and it is normally written in Roman letters here rather than in
a script. Somebody who speaks it will need to read what is produced before it
goes anywhere near the site.

## 66. Nagamese

The same job as Hindi, in the language two Nagas from different villages
actually use to talk to each other. Same coverage, same count, same checks.

| Copy this file | Into this folder |
| --- | --- |
| `fixed/kaamase/languages/nag.mo` and `.po` | `wp-content/themes/kaamase/languages/` |
| `fixed/kaamase-pay/languages/kaamase-pay-nag.mo` and `.po` | `wp-content/plugins/kaamase-pay/languages/` |
| `fixed/kaamase-core/languages/kaamase-core-nag.mo` and `.po` | `wp-content/plugins/kaamase-core/languages/` |

### `nag`, and why WordPress will never offer it

`nag` is ISO 639-3 for Naga Pidgin, which is what Nagamese is called in the
standards. It is not a locale WordPress ships, so **it does not appear in
Settings → Site Language and never will.** That is not a fault to be worked
around; section 67 simply stops asking WordPress which language to serve.

### Written the way it is written here

Roman letters, not a script, because that is how Nagamese is written in
Nagaland — on shop boards, in WhatsApp, in the newspaper. The grammar follows a
real Nagamese news article rather than a grammar book: `pra` marks who did it,
`ke` who it was done to, `te` where, `laka` whose, `khan` makes a plural, `-shey`
puts it in the past.

**English words stay English.** *Profile*, *district*, *password*, *employer*,
*register* are the words people actually use in a Nagamese sentence, and
inventing replacements would produce something nobody says.

> `Kaam Ase te employer khan pra apni laka profile 100 bar khulishey.`

That is a real string out of the compiled file — the milestone notification at
100 opens.

### The same checks as Hindi

**1,228 strings**, same three files, **zero placeholder mismatches**, every `.mo`
parsed back the way gettext reads it. The 923 owner-only strings are left in
English for the same reason as before.

### It needs a Nagamese reader

Nagamese has no settled written standard, and this was built by following one
sample closely rather than by speaking it. The wording will need going over by
somebody who does. The `.po` files are plain text and each string sits next to
its English, so correcting one is editing one line — and `translations/nag-*.json`
is where a correction should go so it survives the next rebuild.

## 67. Letting people choose the language, and the notifications that exposed

Sections 64 to 66 put 2,456 translated strings on the server. Nobody had seen a
single one of them, because there was no way to ask for a language.

This is that way, plus a fault it uncovered that had been there since the first
notification was ever sent.

Section 65 called this "the next piece of work, and it is small next to this
one". The switcher itself was. What it turned over was not, and that half is
below first because it is the half that reaches people who never open the
website.

### The fault first, because it is the bigger half

**Nothing on this platform had ever switched language per person.**
`switch_to_locale()` appeared nowhere in the theme, the core plugin or the
payment plugin. Every email and every push went out in whatever language the
request that triggered it happened to be in.

That sounds harmless while everybody is in English. It is not, and the shape of
the bug is worth stating plainly, because it is the kind that survives testing:

> An employer reading in Hindi looks up a worker's number. `push.php` writes
> *"Somebody has your number"* and sends it to the worker. The sentence is built
> in the **employer's** language. The one person who will read it is the only
> one whose language was never consulted.

Same for anything sent from a scheduled task, where there is no person in the
request at all, and anything the owner triggers from the admin, where the
request is in English by definition. That covers: the number request and its
answer, the profile-live notice, the hire question, the milestone notice, the
promotion notices, the retention notice, the job-went-live email, the hiring
approval, both Rich Manu emails, the payment receipt, the expiry warning and the
failed-payment notice.

Sixteen send sites across ten files, all now built inside
`kaamase_locale_write_to()` or between a `kaamase_locale_switch_to_user()` and
its restore.

**A reader who has never chosen gets the SITE's language, never the language of
whoever triggered it.** This was found by a test, and it matters: standing down
and using the request's language means the same worker gets Hindi on Monday and
Nagamese on Tuesday depending on who looked them up, which reads as a broken
platform rather than a multilingual one.

`date_i18n()` and `number_format_i18n()` sit inside the switch with the words.
Which digits a figure is written in, and how a date is spelled, are part of a
language too — and the promotion notice's end date is the fact somebody who has
paid will hold us to.

### `includes/locale.php`

One new file, eight sections, no new plugin. The whole decision is a
`determine_locale` filter, in this order:

| | What | Why it wins |
| --- | --- | --- |
| 1 | `?kaamase_lang=` in the address | Somebody just clicked |
| 2 | `X-Kaamase-Locale` header | What is on that phone's screen right now |
| 3 | The account | Follows them between devices, and an email can read it |
| 4 | The cookie | This browser, signed out |
| 5 | The site's language | Nobody has chosen |

It stands down in two places on purpose: **admin screens**, because the owner
reads English and the admin strings were deliberately never translated, and
**during a `switch_to_locale()`**, because that is somebody writing to one named
person and overriding it would quietly undo the whole repair above.

### Three traps, and what was done about each

**The page cache would have broken it silently.** LiteSpeed answers without
starting PHP, so the first visitor to a page decides what everybody else gets.
One person reading in Hindi and the front page is Hindi for the whole state.
Nothing in the error log, and invisible to the owner, who is signed in and never
served from store.

The first version refused to let a translated page be stored at all. Correct,
and far too expensive — see "What the slowness was" below, which is what it cost
and how it is done now.

**No language in the address.** No `/hi/`, no `hreflang`, one address per job for
ever. Google is explicit that translating only the template is legitimate and
wants `hreflang` for it — and it is still wrong here. Google decides a page's
language by reading it, and the readable part of a job page is the employer's own
words, so a Hindi address would hold an English job and be judged an English page
anyway. What it would certainly do is turn every job into three addresses, and
section 57 has two hundred submissions a day to spend. Sixty-six jobs a day
instead of two hundred, to buy nothing.

**The switcher links carry `rel="nofollow"` and redirect back to a clean
address.** Without the redirect, the first person to paste a Hindi link into a
WhatsApp group sets the language for everybody who taps it.

### Where somebody finds it

Top right of the header on a wide screen, as a globe and two letters. In the menu
drawer on a phone, spelled out, because the header control is hidden below the
large breakpoint and the drawer is where most of this platform actually is. In
the footer. And on the dashboard, where there is room to say the thing nobody
would guess — that it changes what we write to you, not only what you read.

No JavaScript in any of them. A `<details>` element and three links.

**The language names are never translated.** English, हिन्दी, Nagamese, each in
its own script with its own `lang` attribute, in every one of the three
languages. Somebody who reads only Hindi is looking at a page of English; the
word they can find is हिन्दी. A language menu is the only menu on a website that
must not be translated.

### What the app gets

`GET /kaamase/v1/locale` — open to anybody, so the app can draw a language
screen before somebody has an account. `POST /kaamase/v1/locale` — signed in,
saves the choice to the account. And `locale` plus `locales` on `/me`, so the
language screen costs no extra request on a cold start.

**Without that header there is no fallback, and that is deliberate.** A signed-in
app request carries a bearer token, not a cookie, and the language layer
specifically does not resolve that token — resolving it at line 581 is the
second fault listed further down. So an app request with no `X-Kaamase-Locale`
answers in the site's language even when that account has Nagamese saved.
Notifications to that same person are still Nagamese, because they read the
account directly and never go near the request.

One useful consequence: because the registration email is the one send that
keeps the request's language, an app that sends the header on the *registration*
call gets the confirmation email in the right language, before there is an
account for anybody to have chosen on.

**`Accept-Language` is deliberately ignored.** The app has its own language menu
and its own translated screens; if the server read the phone's system language
while the app read its own setting, somebody who set the app to English on a
Hindi phone would get English screens with Hindi sentences inside them. One of
the two has to be in charge and it has to be the one the person chose. The same
reasoning is why a Hindi phone does not silently get a Hindi website.

### The app's screens are not translated by any of this

Worth saying loudly. A `.mo` file is read by PHP on this server and the phone
never sees one. The 2,456 strings cover the website and everything the server
*writes* — notifications, emails, the text it hands over ready to display. Every
button, tab and error message inside the app is a string in the app's own code
and needs its own Hindi and Nagamese there.

### Tested

156 assertions across thirty-three scenarios, each in its own process because the
resolver caches its answer in a static and `DONOTCACHEPAGE` is a constant — two
scenarios in one process would be a lie. The real `.mo` files are loaded, so the
tests prove sentences come out in Nagamese rather than that a flag was set.

The one that matters is end to end: a Hindi employer looks up a Nagamese worker,
the real `kaamase_push_contact_revealed()` runs, and what the phone is handed is
`Kunba logote apni laka number ase` — while the employer's own request is still
Hindi afterwards and nothing is left switched.

### The one string a .po file could never reach

Found by the app side after the first upload: `/reference` was returning
`update_message` in Nagamese whatever language the request asked for.

Their diagnosis was right, and the reason is worth writing down because it is
the boundary of everything sections 64 to 67 built. `update_message` is not a
string in the source code. It is a sentence the owner types into an admin box at
runtime, so there is no `msgid` for a translation to attach to. **Text a person
enters can only be translated by a person.** No amount of `.po` work reaches it.
One box, typed once, sent to everybody — so whichever language it happened to be
written in was the language every user read it in.

It is also the worst string on the platform to get wrong. It is drawn on a
screen somebody is stuck behind with no way past it.

So `app-version.php` now asks for it **once per language**, and underneath the
three boxes sits a built-in sentence that *is* in the source, and therefore is in
the `.po` files like everything else. A box left empty falls through to that,
translated, rather than falling back to another language: nothing in Nagamese is
better than something in Nagamese for somebody reading English, which was the
whole fault.

The message from before is not sent to anybody any more, and nothing deletes it.
It is shown on the screen, once, until one of the boxes has something in it, so
it gets moved rather than lost.

Two smaller things came out of the same change. `mb_substr` now names UTF-8
explicitly, because a Devanagari character cut in half at 200 bytes is not a
shorter sentence but a broken one. And `kaamase_app_update_message( $locale )`
takes a language, so the built-in fallback is read *in that language* rather
than in whatever language the request happens to be running in — otherwise the
argument would have been a lie, and the admin screen would have shown the
English hint under all three boxes.

`kaamase_app_update_message` is the only owner-typed prose that reaches the app.
The only other free text box on the platform is the extra emergency numbers on
the safety page, which is place names and telephone numbers on the website.

### What the slowness was

Reported a day after upload: everything slow to open for the first time, and the
app saying "no connection" on a good wifi. Measured rather than guessed, because
the obvious suspect was wrong.

**It was not the translation files.** Loading all three `.mo` files costs **0.64 ms**
for a Nagamese reader and **0.69 ms** for Hindi, and nothing at all for English,
where no `.mo` exists. That is not what anybody was feeling, and knowing it
stopped a pointless afternoon generating faster translation formats.

**It was the cache, and it was this file's own doing.** Refusing to store a
translated page meant that the moment somebody chose Hindi or Nagamese, every
page and every API answer they asked for was a full PHP render, for ever. The
people this whole feature was built for were the only people on the platform
with no cache. A cached page is tens of milliseconds and an uncached one is
hundreds; on a village connection with an app timeout on the other end, that is
the difference between a screen and "no connection".

The fix is to let it be cached under its own key, and the mechanism matters:

| | `litespeed_vary_cookies` | `litespeed_vary` |
| --- | --- | --- |
| What it does | names a cookie for the server to watch | adds a value to LiteSpeed's own `_lscache_vary` |
| How it takes effect | **by writing rewrite rules** | the server varies on that cookie already, with nothing to configure |
| When it does nothing | until `.htaccess` is regenerated, silently | — |

The first version used the first one. It now uses the second, and a translated
page is cached normally. Nothing is added to the vary for the site's own
language, so an empty vary means LiteSpeed sets no cookie at all for a guest and
the English pages — nearly all the traffic — are cached exactly as they were
before any of this existed.

`DONOTCACHEPAGE` is still there for the two cases where that vary cannot be
trusted: no LiteSpeed at all, and LiteSpeed Guest Mode, which hands every logged
out visitor one prebuilt page and skips vary entirely.

**And `Vary: Cookie` came off every page.** It was being sent on all of them as
belt and braces. Nearly every visitor carries some cookie, so that header on a
page everybody gets is how a CDN is switched off by accident. It now goes only
on the answers that really do depend on a cookie.

### Why one worker profile took 200 to 400 milliseconds

The app side measured it and handed over the number that made it findable:
**629 bytes, 200–400 ms.** Six hundred bytes cannot take that long to send, so
none of it was the network.

Three things, all in the same place.

**No index the counting could use.** Both view counts ask
`WHERE subject_id = ? AND kind = ?`, and there was no key with `kind` in it. The
`subject` key stops at `(subject_id,seen_on)`, so the database narrowed to the
profile and then walked every row it had — of *both* kinds — checking each one.
That is nothing for a profile nobody has opened. It is not nothing for a busy
one: a *showing* is recorded every time a card comes onto somebody's screen, so
the rows being walked past to count openings are mostly showings, and there are
far more of those. There is now a `kindly (subject_id,kind,seen_on)` key, which
also lets the listing query do its `GROUP BY` without sorting a temporary table.

It is added on its own rather than by raising the schema number. Raising it
would make `kaamase_views_ready()` answer false until somebody opened an admin
page, and nothing would be counted in between. An index is worth having; it is
not worth a day of missing views to get.

**The single profile route never primed anything.** `the_posts` hands a whole
listing to `kaamase_views_prime()` in one query, and `/workers/{id}` has no
listing — it is one `get_post()`. So it fell through and asked twice, once for
openings and once for showings, on the one request where somebody is watching a
blank screen. Measured against the previous version: **2 queries, now 1**, and
shaping the same profile a second time in one request went from 2 more queries
to none.

**And its terms.** Same cause. A listing primes the term cache for the page
before anything is drawn, and the slug route goes through `get_posts()` so it is
covered too, but `/workers/{id}` and `/jobs/{id}` are single `get_post()` calls,
so the trade lookup and the language lookup each went to the database alone.
One `update_object_term_cache()` does both.

What is *not* fixable here is that the answer cannot be cached at all: it
carries `is_mine` and `saved`, and `rest-auth.php` refuses to store anything
written for one account. That is correct and it stays. It just means the render
is the whole cost, which is why the render is what got cheaper.

### What it came to, measured from the app

Five profiles, eight runs each, connection time separated from server
processing, on the four most-viewed profiles plus the one measured before so
the comparison is like for like.

| | Before | After |
| --- | --- | --- |
| Worker profile | 200–400 ms | **132–148 ms**, median 142 |
| Workers list | ~270 ms | 138 ms |
| Jobs list | ~270 ms | 174 ms |
| Reference | ~270 ms | 170 ms |

The number that says it is really the index is not the median. It is the
spread: repeat requests used to bounce between 200 and 400 ms and now sit
between 132 and 148. A payload problem or a slow network does not tighten like
that. A query that stopped walking rows it did not need does.

### The one in twenty that still takes a second and a half

Left in the open deliberately, because it is not fixed and it is the kind of
thing that gets reported later as "sometimes it hangs".

Occasional responses of ~1.4 s against a flat ~140 s either side. The shape —
rare, enormous, unrelated to which profile — does not look like a query. It
looks like a request that had to wait for a PHP worker, and this site has an
obvious candidate for taking one: **WP-Cron is spawned by whichever visitor
request happens to arrive when a job is due.** There are three scheduled hooks,
and the daily one submits to the Google Indexing API over **blocking HTTP with a
fifteen second timeout**. On shared hosting with a handful of PHP workers, one
of those is enough to make everything behind it queue.

The fix is configuration rather than code: `DISABLE_WP_CRON` in `wp-config.php`
and a real cron job at the host calling `wp-cron.php`. Nothing scheduled
changes, it simply stops being paid for by a visitor.

### The cached answer that had the same fault as the update message

Found while looking for the slowness, and worth its own paragraph because it is
the third time this shape of bug has appeared.

`/reference` is held in a transient for six hours under **one key**. Almost
everything in it comes out of the database and is the same in every language —
trades, districts, wage floors. `kaamase_emergency_numbers()` is not. Its labels
are translated strings: *Police and emergency*, *Women helpline*.

So the first person to warm that cache after it expired chose what language the
emergency labels were in, for every user of the platform, for six hours. On the
emergency numbers, of all the things to get wrong. The transient is now kept per
language, and clearing it clears all of them.

### Three things the first version got wrong

Written down because two of them are traps anybody adding a `determine_locale`
filter to any WordPress site will walk into, and the first one is a white screen.

**A fatal on every page.** The filter opened with `is_locale_switched()`, which
is correct-looking and reads well. It is also a method call on a global object:

```php
function is_locale_switched() {
	global $wp_locale_switcher;
	return $wp_locale_switcher->is_switched();   // core, verbatim
}
```

In `wp-settings.php`, `load_default_textdomain()` — the first thing that ever
asks for a locale — is **line 581**. `$wp_locale_switcher` is created on **line
605**. For those twenty-four lines the object is `null`, so the very first call
to the filter would have been
`Call to a member function is_switched() on null`. Every page, every request,
from the moment the file was uploaded. It is now behind an `isset`.

It got that far because the test double for `is_locale_switched()` answered from
an array instead of dereferencing an object — a stub kinder than the thing it
stood in for, which is the one way a stub can hide a crash rather than reveal
one. The stub is now faithful, and there is a test that walks the real boot
order with the switcher deliberately absent.

**Resolving the reader too early.** The filter asked `get_current_user_id()`.
WordPress works out who the caller is lazily, the first time anything asks, by
running `determine_current_user` — which on this platform is
`rest-auth.php:363`, where the app's bearer token is checked. Asking at line 581
would have made the language layer the thing that triggered app authentication,
before the theme had loaded, with the answer cached for the rest of the request.
It now uses the answer only if WordPress already has one and otherwise reads the
sign-in cookie directly, which is what core does for a browser and has no side
effects. There is a re-entrancy guard around it as well, covering the meta read
too: either step can run a filter, any filter can translate a word, and
translating a word comes straight back here.

**The first email somebody ever gets.** Making an unchosen reader fall back to
the site's language is right for notifications and wrong for exactly one
message. Somebody reads the site in Hindi for a week and then registers; the
confirmation email is written inside their own request, and the account is
seconds old so it has no language saved. The fix put that one email back into
English. `kaamase_locale_switch_to_user()` now takes `'request'` for that single
case: use what they have saved if they have saved anything, otherwise leave the
request in the language it is already in.

## 68. Keeping the tick true

Reported from the live site: accounts that had paid, been telephoned and been
given the tick were afterwards changing to a false name and a stranger's
photograph, and keeping the mark.

That is worse than having no mark. An unmarked platform asks people to use their
own judgement. A platform whose mark can be quietly falsified spends its own
credibility vouching for whoever is willing to abuse it, and the person who
finds out is a worker who travelled to a job that was not real.

### What the call actually established

`verified-mark.php` is careful about the claim: **we telephoned this person.**
Not that their work is good, not that they are recommended. So what that call
established was three facts and only three — **this name, this number, this
face** — and when one of them changes the mark has stopped being true.

Which is why the list of watched fields is short, and why keeping it short is
the most important decision in the file:

| Changing this | Tick | Because |
| --- | --- | --- |
| Name | **comes off** | the call verified this name |
| Phone number | **comes off** | the call was made *to* this number |
| Photograph | **comes off** | the reported abuse |
| Day rate, about, trades, district, town, experience, travel radius | stays | the call never vouched for any of it |
| A job posting's title | stays | a job is not an identity, and its title changes all the time |

On three post types, not two. **Teams carry the mark as well**, which is easy to
miss: `kaamase_mark_in_title()` puts the tick beside a `kaamase_gang` title on
the website, and the app shapes teams through `kaamase_shape_worker()`. A team
has its own name, its own number and its own photograph, so leaving it out would
have left the whole hole open one post type along — rename the team instead of
yourself, and the tick stays. Found on a review pass after the first build, not
by the tests.

`contact_name` on an employer and `leader_name` on a team are deliberately *not*
watched. They are second names rather than the one the tick sits beside, and
they change for ordinary reasons — staff leave. `kaamase_mark_watches` is a
filter if that judgement ever needs reversing.

Taking somebody's tick away for correcting their day rate would teach everybody
that the mark is arbitrary, and that costs more trust than the abuse it was
meant to stop.

### Why not lock the fields

It was the first idea and it is the wrong one.

Locking makes the owner the bottleneck for every honest correction: a misspelled
name, a marriage, a better photograph. Worse, somebody whose number really had
changed could not fix it, so employers would ring a dead number and conclude the
platform is full of ghosts — a bigger reputation problem than the one being
solved, and one that grows with every person who gets the tick.

Letting them edit and taking the mark off puts the cost where it belongs. Nobody
who paid for a tick swaps to a false name on a whim if it costs them the tick,
and nobody honest is stopped from fixing their own details.

### The subscription is never touched

Stated plainly because it was the first thing asked about. `rich-manu.php` keeps
the paid plan and the tick as two separate things on purpose — paying buys a
place in the queue and never the mark — and that separation is exactly what
makes this safe to do automatically. The tick goes; every day of the plan stays.
The test asserts it by snapshotting the whole account and checking that nothing
except the two tick keys moved.

### Watching the post, not the save function

`kaamase_save_profile()` is the front door for the website and the app both, and
it is not the only door: the photograph is written by `set_post_thumbnail()`
from two different places. So the guard hooks `post_updated` for the name and
the meta actions for the number and the photograph, which catches every route
into those three fields by construction, including routes that do not exist yet.

WordPress only fires the meta action when a value genuinely changes, so
re-saving a form without touching anything costs nobody their mark.

**It only counts when the owner does it.** The platform owner fixing a
misspelling from the admin has falsified nothing, and a change with nobody
signed in — an import, a scheduled task — has no author to hold responsible.

### Said beforehand, and answered afterwards

Nobody should lose the mark by surprise: told in advance it is a choice they
made, found out afterwards it is a platform that took something off them, and
that is how a fair rule becomes a grievance.

**In two places on the website, and the second is not redundant.** A notice at
the top of the form explains the rule once. A short amber line then sits under
each of the three fields it applies to, because the form is long — photograph,
name, trades, rates, experience, district, town, telephone — and somebody who
opens it to change their photograph scrolls straight past the top, while
somebody changing their number is a long way below anything they read on the
way in. The notice explains; the field line catches them at the moment they are
actually deciding.

`/me` carries both of them for the app — `mark_warning` and
`mark_field_note`, **finished sentences in the language that person reads**
rather than flags — so the app shows exactly what the website shows and the two
cannot drift apart. Both are empty for anybody without a call on record, so the
app prints them without asking any further question.

The short one matters more on a phone than on a desktop, not less: the screen is
smaller and the form is just as long, so a notice at the top is gone before
somebody reaches their number.

Afterwards: the person is told once, in their own language, naming everything
that changed and saying the plan is safe, because that is the first thing
anybody who paid will worry about. They go straight back on the call list
without paying again — but **not** through `kaamase_join_call_queue()`, which
sends the message somebody gets when they have just paid, and "Thank you, the
plan is on your account" would be a strange thing to read for having corrected
your own telephone number.

The owner gets an email the same day, in English, with the old value beside the
new one. And the call list now shows what changed since the call, because that
decides what kind of call this is: somebody who went from Imliakum to Imliakum
Jamir needs thirty seconds, and somebody who went to the name of a company that
exists needs a different conversation.

### The hole a lapsed plan left

Found when the owner asked why no warning appeared, which turned out to be two
different things at once.

`kaamase_has_been_called()` answers "is the tick showing", and that requires the
plan to still be running. rich-manu.php is explicit about what happens when it
is not: *"Nothing is deleted when a plan lapses. The record of the call stays,
so renewing brings the mark straight back with no second phone call."*

Which made the whole rule avoidable by the easiest route there is:

1. let the plan lapse, and the tick goes quiet on its own
2. change to a false name while nothing is watching, because there is no tick
   to lose
3. renew — and the mark comes straight back, onto the new name, with nobody
   having ever rung it

The guard now asks `kaamase_mark_has_record()` instead: is there a call on
record that this change would make untrue. A lapsed plan does not make the call
less true and must not make the change less consequential. The warning follows
the same rule, so somebody whose plan has lapsed is told before they edit —
they have the most to lose, since their mark is coming back on renewal and this
is what stops it.

### Both sides of one account

Asked directly, and the answer is yes. An account can hold a worker profile and
an employer profile — on this platform many do, the same people in different
weeks — and both carry the same tick because the tick is on the account.

Nothing special was needed. The guard compares the signed-in person against
`$post->post_author`, which is the same account for both profiles, so changing
either costs the same mark. Both website edit routes go through
`kaamase_profile_form()` as well: the dedicated page for the profile somebody
registered with, and `?edit=` on the dashboard for the other one. Tested from
both sides.

### The bug the tests found

The first version recorded **one** change when three had happened.

After the first change took the tick off, the second and third looked at an
account with no tick, decided there was nothing to lose, and were never written
down. A worker who changed their name, number and photograph in one save would
have appeared on the call list as having changed their name.

The guard now remembers what it has already taken off in the current request, so
the rest of that same save is still recorded. It is why the "all three at once"
test exists, and it would not have been found by testing the three separately.

### Tested

85 assertions, twenty scenarios, each in its own process because the guard
remembers what it has dropped in a request.

The negatives carry as much weight as the positives: the rate, experience, town,
travel radius and description all keep the tick; the owner editing from the
admin does not cost anybody theirs; a change with nobody signed in does not
count; somebody with no tick is not watched at all; and re-saving a form without
changing anything records nothing.

The one that matters most is end to end with the real `.mo` files: a worker who
reads Nagamese changes their name, and what reaches their phone is
`Apni pra apni laka naam bodli kuri shey...` while the owner's copy of the same
event stays in English.

## 69. The promote card that widened the whole page

Reported from a phone: on the dashboard, below **The tick on a profile**, the
page could be zoomed out and slid sideways, with every card squeezed into the
left half of the screen.

**One file:** `fixed/kaamase-core/includes/promote.php` (1.2.0). Nothing in the
theme changed, and no words changed, so no translation files need copying.

### The cause

The **Promote a job or your profile** form was the only form on the public site
built from bare browser controls rather than the theme's field classes. A bare
`<select>` is as wide as its longest option, and a job title is often a whole
sentence: *Quickserv is Hiring Nursing Assistants/ General Duty Assistants and
Staff Nurses / GNM*. That one control was wider than the phone, so the phone
laid the whole page out wider to fit it, which is the zoomed-out, sliding page.

Measured in Chromium at phone size with the theme's real stylesheet: a 390px
phone was laying the dashboard out **689px** wide. After the fix it is 390px,
and 360px on a small Android phone.

### What the form is now

It uses the same pieces as every other form on the site, so it looks like it
belongs:

| Field | Before | Now |
| --- | --- | --- |
| Which one? | bare dropdown, as wide as the longest title | `.ka-select`, the width of the card; a long title is shortened inside the box. With **one** listing there is nothing to choose, so its name is shown in full instead |
| How long? | a dropdown of two | two tap targets, **One week** already chosen |
| Best time to ring you | narrow bare box | full-width `.ka-input` |
| Anything we should know | narrow bare box | full-width `.ka-textarea` |
| Button | outline | the green primary button, full width |

The form still sends exactly the same four fields (`post`, `want`, `when`,
`note`) to the same handler, so nothing on the receiving side changed and the
phone app is not affected.

### Checked across the rest of the site

Every other bare dropdown or text box in the plugins and theme is on an
**admin** screen (posted-for, gift, job alert hour, verification length, SMTP,
media), which is only ever opened on a computer. The public site already wraps
long words (`overflow-wrap: break-word` on the page), and nothing else in the
theme has a fixed width wider than a phone.

### Tested

The existing promotion suite, 139 assertions, passes unchanged. (Its WordPress
stand-in gained `checked()`, which real WordPress always has.) The card was
rendered through the real `kaamase_promo_card()` for one listing and for several,
and measured at 390px and 360px, with the longest title selected.

## 70. Catching a mistyped email address before the account is made

Reported from the live site: many people were registering with a misspelt Gmail
address and then never receiving the confirmation link. The mail itself is
arriving (in Inbox, even under Important); the addresses were simply wrong.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase-core/includes/email-typos.php` | **new** |
| `fixed/kaamase-core/includes/registration.php` | 1.2.0 |
| `fixed/kaamase-core/includes/rest-api.php` | 1.7.0 |
| `fixed/kaamase-core/includes/account-providers.php` | 1.1.0 |
| `fixed/kaamase-core/languages/kaamase-core.pot` | four new lines |
| `fixed/kaamase-core/languages/kaamase-core-hi_IN.po` and `.mo` | Hindi |
| `fixed/kaamase-core/languages/kaamase-core-nag.po` and `.mo` | Nagamese |

Nothing needs switching on. The plugin loads every file in `includes/` by itself.

### What is caught

**1. The provider spelt wrong.** The reply names the address they meant.

| Typed | Suggested |
| --- | --- |
| gmial.com, gamil.com, gmai.com, gmaill.com, gnail.com, gmal.com | gmail.com |
| gmail.co, gmail.cm, gmail.con, gmail.in, gmail.co.in, gmail.com.com | gmail.com |
| gmail..com, gmail,com, just `gmail` with no ending | gmail.com |
| yaho.com, yahooo.com, yahoo.co / yahoo.com.in | yahoo.com / yahoo.co.in |
| hotmial.com, outlok.com, redifmail.com, iclod.com | the real one |
| anything ending `.con`, `.cmo`, `.comm` | the same with `.com` |

**2. An address that cannot be Gmail.** Gmail names are 6 to 30 letters,
numbers and dots, so `raju@gmail.com` (4) or `raju_k@gmail.com` (underscore)
cannot receive anything.

**Left alone:** every real address that happens to look close. mail.com,
email.com, ymail.com, rediff.com, yahoo.co.in, yahoo.ca, hotmail.co.uk,
outlook.in, nic.in, gov.in and college addresses, company addresses. Forty of
these are in the test, and none is questioned.

### What the person sees

On the **website**, the form comes back with:

> Check your email address. Did you mean raju.kumar@gmail.com?
> We have put that address in the box for you. If what you typed was right,
> type it again exactly and we will use it.

and the corrected address is already in the email box. Everything else they
typed is kept; only the password has to be typed again, as with any error.

**Never a locked door.** A list like this will one day meet a real address it
thinks is wrong. So the same address typed a second time, exactly, is accepted.
Insisting cannot push something that is not an address at all through: that
still gets the old "Please enter a working email address."

In the **app**, the same message arrives as the error (in the person's
language), because the server now checks what the app sends. The app can do
better with two small additions, in the message for the app side:

- the reply carries `email_suggestion` with the corrected address, for a
  one-tap "Use raju.kumar@gmail.com" button;
- sending `email_as_typed: true` means "yes, what I typed is right".

Until the app adds those, an app user who really does have an unusual address
can still register on the website. Both apply to app registration and to
correcting an unconfirmed address from the app, and, since fix 72, to the address
typed on the Apple finish screen. An address that Google or Apple itself supplies
is never checked: that one is proven.

### Tested

110 cases against the detector (46 typos with the exact suggestion expected,
40 real addresses that must pass untouched, 10 on Gmail's own rules), and 37 through
the real handlers lifted out of the plugin files: a typo then the suggestion, the
same typo insisted on, a different typo the second time, an impossible Gmail
name, a double dot insisted on, the app's reply shape, `email_as_typed` sent as
the text "false", and every other error reply unchanged. The Hindi and
Nagamese lines were read back out of the compiled `.mo` files.

## 71. Share on WhatsApp, with the post's own photo in the chat

### Upload

| File | |
| --- | --- |
| `fixed/kaamase-core/includes/sharing.php` | 1.1.0, replaces the live one |
| `fixed/kaamase-core/languages/kaamase-core.pot` | one new line |
| `fixed/kaamase-core/languages/kaamase-core-hi_IN.po` and `.mo` | Hindi |
| `fixed/kaamase-core/languages/kaamase-core-nag.po` and `.mo` | Nagamese |

The language files already include fix 70's lines, so upload these once for
both. After uploading, **LiteSpeed Cache → Toolbox → Purge All**, so cached job
and profile pages pick up the button and the corrected share picture.

### The button

**Share on WhatsApp** now sits under Save on every open job, published worker
profile, team and employer page. It is shown to everybody, owners included:
the person most likely to share a job is whoever posted it, into the groups
where the workers already are. It is not shown on a closed job (it would send
people to work that has gone) or on a profile still waiting for its email to be
confirmed (nobody else can open it yet).

One tap opens WhatsApp, on a phone or on a computer through WhatsApp Web, with
the message already written: the job or person's name, and the link. Nothing
else is written into the message, because WhatsApp builds the preview itself
from the link.

In Hindi it reads **WhatsApp पर भेजें** and in Nagamese **WhatsApp te pathai
dibi**, the wording the site already uses for sending a link on WhatsApp. It
matches the theme's own outline button, with WhatsApp's mark in its green; no
theme file changed.

### The photo in the preview

The picture in a WhatsApp preview comes from the page's share tags, which
already used a job's first photo. What was wrong was profiles.

A profile photo is square, so the site never makes the 960 by 540 wide copy
that the share card asked for. WordPress does not refuse a size that was never
made: it quietly hands back the full original, up to 1600 pixels, and a file
that heavy is often not shown by WhatsApp at all. So a shared profile could
arrive with no picture, or with the logo.

Now each kind of page asks for a size it really has:

| Page | Picture in the chat |
| --- | --- |
| Job with photos | its first photo, 960 by 540, the large picture across the top |
| Worker, team, employer with a photo | their own 320 square, the small picture beside the name |
| Older photo with no square | the 300 medium copy |
| Very small upload | the upload itself, which is small too |
| Nothing usable | the Kaam Ase logo, as before |

Only a copy that really exists as its own file is used, never the original
dressed up with a smaller size's name.

WhatsApp remembers a link's preview for a while, so a link shared before this
change may keep its old preview for some days. New shares get the new one.

### Tested

22 assertions: each row of the table above, with `wp_get_attachment_image_src()`
behaving as core's `image_downsize()` does for a size that was never made; the
message written into the link (a job name with `&` and an apostrophe arrives as
real characters, not `&#038;`); no button on a closed job, a draft profile or an
ordinary page; and the button appended only to the page's own content, not to a
list or an excerpt. The button was rendered with the theme's stylesheet at phone
width: 48 pixels tall, and the page stays phone width.

### Checked on a real WordPress before upload

Fixes 69, 70 and 71 were then run on a real WordPress 6.5 site (SQLite, PHP
8.4) with this theme and both plugins exactly as they will be after upload: the
original ZIPs with every file in `fixed/` copied over.

That run found one real fault, now fixed. WordPress's `esc_url()` deletes an
encoded line break, so the WhatsApp message arrived as
`...GNMhttps://kaamase.com/job/...`, the name glued to the link, which WhatsApp
does not recognise as a link. The stand-in `esc_url()` in the first test did not
do that, which is why it passed; it does now, and fails on the old code. The
button now escapes the address with `esc_attr()`, and the message on the real
site reads the name, a line break, then the link.

Everything else passed on the real site:

- **Pages:** 19 pages and routes in English, Hindi and Nagamese, signed in and
  out, 114 requests, with not one PHP warning, notice or error in `debug.log`.
  The button reads *Share on WhatsApp*, *WhatsApp पर भेजें* and *WhatsApp te
  pathai dibi* in the three languages, and older translations are unchanged.
- **Share pictures:** WordPress really made a 960x540 copy of the job photo and
  a 320 square, and no wide copy, of the profile photo, and the pages carry
  exactly those in `og:image`. No button on the closed job.
- **Website registration (18 checks):** a typo comes back with the suggestion
  in the box and the name kept; the same typo typed again makes the account as
  typed; the suggestion accepted makes it at the corrected address; an
  impossible Gmail name; the message in Nagamese; a right address and nonsense
  behave as before.
- **App routes (15 checks):** `/auth/register` and `/me/email` over HTTP, with
  `email_suggestion`, `email_as_typed`, Hindi and Nagamese via
  `X-Kaamase-Locale`, and the old reply shape on every other error.
- **Promote card:** signed in as an employer at phone width with the long
  nursing title in the list, the page is exactly 390 wide, and sending the form
  records the request with the chosen length and time.

## 72. The address typed on the Apple finish screen

Reported by the app side, and it corrects a line in fix 70, which said Apple and
Google sign ups need no typo check because the provider supplies the address.
That is not true when **Apple withholds the address**. The person then types one
by hand on the finish screen, it goes to `/auth/apple/complete`, and that route
never reached the check. It is the address where a slip is most likely, because
nobody copied it from anywhere.

**One file:** `fixed/kaamase-core/includes/apple-signin.php` (1.1.0). No new
words, so no language files.

- The typed address now gets exactly the same check as `/auth/register`: the
  same translated message, `email_suggestion` in the reply, and `email_as_typed:
  true` as the way through for an unusual address that really is right.
- An address **Apple supplied** is never questioned, and the typed box is still
  ignored when Apple sent one, as before.
- A returning Apple id is still signed straight in with no address involved.

**Google needs nothing.** `/auth/google/complete` has no email field at all: the
address always comes from Google's own token, so there is nothing typed to check.
If the app's finish screen shows an email box for Google, whatever is typed there
is ignored by the server.

### Tested on the real WordPress site

Apple's signature check needs a token Apple signed, so on the test site only, a
must-use plugin stood in for `kaamase_apple_verify()` (a token `test|id|email`);
everything after the token check was the plugin's real code. 15 checks over HTTP:
the typo caught with `email_suggestion`, accepted with `email_as_typed`, the text
"false" not taken as yes, a right address through, an impossible Gmail name,
Nagamese and Hindi, the name error listed alongside, the old messages for an
empty box and for nonsense, Apple's own address untouched, and a returning id
signed in. The database then showed the insisted typo saved exactly as typed and
no account made for the refused one. Nothing in `debug.log`.

## 73. Wording fixes from the app, kept apart from reports

The app now sends translation corrections to `POST /reports` with `mode:
"report"`, `kind: "wording"`, and `details` as five lines: Language, Key,
English, Now, Should be. Anonymous, like any report.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase-core/includes/reports.php` | 1.1.0 |
| `fixed/kaamase-core/includes/services.php` | 1.3.0 |
| `fixed/kaamase-core/includes/rest-api.php` | 1.8.0 (includes fix 70) |

The new words are on admin screens only, which stay in English, so the language
files do not need uploading for this one.

### An unknown kind was never refused

Checked before changing anything: `kaamase_submit_report()` refuses only an
empty kind and details under 20 characters. A kind the website form does not
list was always accepted and filed as an ordinary open report titled
"Report" and its reference. It still is. So the app's corrections were already
arriving; they were simply landing among the abuse reports.

### What changed

- **Their own place.** A wording fix is filed under a new status, **Wording
  fixes**, not Open. WordPress then keeps it out of *All*, gives it its own
  link at the top of *Kaam Ase → Reports*, and keeps every count right, because
  it counts per status.
- **A title you can read at a glance:** `Wording fix ABC123 (nag)`, with the
  language taken from the first of the five lines.
- **Opening one** shows *Type: Wording fix from the app* in the details box.
  Every report now shows its type there.
- **Its own allowance.** Reports were limited to 5 an hour per connection,
  shared by every kind. Somebody who had just sent five corrections to the
  Nagamese would have been told to wait an hour before they could report a job
  asking for money. Wording fixes now have their own limit of 20 an hour, and the
  5 for real reports is untouched.
- The email to you still goes out for each one, with the same readable title in
  the subject, so it never looks like an abuse report.

### Tested on the real WordPress site

16 checks over HTTP and in the admin: the five lines accepted anonymously and
kept exactly, line breaks included; filed under Wording fixes, not urgent, titled
with reference and language; an unknown kind still accepted as an open report
and an empty kind still refused; seven fixes in a row accepted and a real report
from the same phone straight after still going through; wording capped at 20; and
in the owner's Reports list, the Wording fixes link present, none of them in the
main list, *All* counting exactly what it shows, and the details box reading
*Wording fix from the app*. Nothing from these files in `debug.log`.

## 74. Being found for "jobs in Nagaland" and "workers in Nagaland"

The site ranked for its own name and for words already on its pages, and not
for what people actually type into Google or ask an AI assistant: *jobs in
Nagaland*, *workers in Nagaland*, *how to find a job in Nagaland*. The homepage
was titled "Kaam Ase – There is work." and headed "Kaam ase.", which says
nothing to somebody who has never heard of it, and no page had a description,
so Google wrote its own.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase-core/includes/seo.php` | **new file**, 1.0.0. Loaded like every other file in `includes/` |
| `fixed/kaamase/front-page.php` | 1.2.0, the new homepage |
| `fixed/kaamase/style.css` | 1.8.0 |
| `fixed/kaamase-core/languages/kaamase-core.pot`, `kaamase-core-hi_IN.po` and `.mo`, `kaamase-core-nag.po` and `.mo` | the questions and the page titles |
| `fixed/kaamase/languages/kaamase.pot`, `hi_IN.po` and `.mo`, `nag.po` and `.mo` | the homepage |

Upload `seo.php` before or with `front-page.php`. The homepage asks for its
questions with `function_exists()`, so the other order does no harm, but the
questions only appear once `seo.php` is there.

### After uploading

1. **LiteSpeed Cache → Toolbox → Purge All.**
2. **Google Search Console → Sitemaps:** submit `https://kaamase.com/wp-sitemap.xml`
   again. If `wp-sitemap-users-1.xml` is listed there on its own, remove it.
3. **Search Console → URL Inspection:** inspect `https://kaamase.com/` and press
   *Request indexing*, so Google reads the new homepage now rather than on its
   next visit.
4. **Let the AI crawlers in.** If the site sits behind Cloudflare or Hostinger's
   CDN, look for any setting that blocks AI bots or AI crawlers. If it is on,
   ChatGPT, Claude and Perplexity cannot read the site, so they can never
   recommend it. Turn it off.
5. **If Yoast, Rank Math, SEOPress or All in One SEO is installed**, `seo.php`
   leaves titles, descriptions and structured data to it (two sets on one page
   is worse than one). Then set the homepage title in that plugin to
   **Jobs and workers in Nagaland – Kaam Ase**. The sitemap fix, the private
   pages and `/llms.txt` work either way.

Rankings move over weeks, not days. The pages are now right for these searches;
how fast Google re-ranks them is up to Google.

### What was broken: members' email names in the sitemap

WordPress's own sitemap listed every member at `/author/<login>/`. Login names
here are the part of the email address before the @, so
`/wp-sitemap-users-1.xml` published the start of every member's email to anybody
who opened it. `security.php` already refuses author pages, so every one of those
links also answered "not found", and a sitemap full of dead links teaches Google
to distrust the rest. The users list is gone from the sitemap, and its old
address now answers 404 rather than showing the homepage.

The account pages (My account, Saved, My team, Who looked at you) were in the
sitemap too. A crawler sees only a sign in prompt there. They are out of the
sitemap and marked `noindex`.

### Titles and descriptions

| Page | Title in a search result |
| --- | --- |
| Homepage | Jobs and workers in Nagaland – Kaam Ase |
| Jobs | Jobs in Nagaland; AC repair jobs in Kohima, Nagaland, when filtered |
| Workers | Workers in Nagaland; Workers in Dimapur, Nagaland |
| Teams | Teams for hire in Nagaland |
| District | Jobs and workers in Dimapur, Nagaland |
| Trade | AC repair in Nagaland: workers and jobs |
| Worker or team | Raju Kumar – AC repair in Dimapur – Kaam Ase |
| Job | its own title, plus the district when the title does not say it |

The homepage and every list, district and trade page now have a description of
about 150 characters written for them, not left to Google. Profiles and jobs get
one built from their own trade, district and text. The homepage also names its
own address as the canonical one.

Titles follow the reader's language. Descriptions stay in English, which is the
version Google reads, and a Hindi place name inside an English sentence reads
worse than either language.

### For AI assistants

- **Questions on the homepage.** Eight plain questions and answers: what Kaam
  Ase is, how to find a job in Nagaland, where to find workers, is it free,
  which districts (the real list, from the site), is my number shown, is there
  an app, which languages. Every answer is something the site really does. The
  same text goes to Google as FAQ data, from one source, so the two can never
  differ. They are in English, Hindi and Nagamese.
- **`/llms.txt`**, a short plain-text page of facts and links in the format AI
  tools look for: what the site is, that workers never pay, how numbers are
  protected, a link to each district, the jobs, workers and teams lists, the
  20 trades with the most people, and the app.
- **Who runs it.** The organisation data now says Kaam Ase serves Nagaland, in
  English, Hindi and Nagamese, is run by Nagaland Me, and links the two app
  store pages, so an assistant can tie the website and the apps together.

### The homepage

Built around the words people search with, and the answer they want next:

- **Top:** *Jobs and workers in Nagaland* as the main heading, one search with
  trade and district, and two buttons: **Find workers** goes to the worker list
  and **Find work** to the job list, carrying the same choices. It is a plain
  form, so it works with no JavaScript. Under it, four true facts: free for
  workers, all 17 districts, the real number of trades, the three languages.
- **Popular trades**, **Latest jobs in Nagaland** (expired jobs never shown) and
  **Workers available now in Nagaland**.
- **Find jobs and workers by district:** a tile for every district, each one a
  link Google can follow to that district's page.
- The app, the two doors and the trust band, as before, then the questions.

Dark green top with the hills of the state along its lower edge, a white search
card, no web fonts and nothing loaded from another site, so it costs nothing
extra on a slow connection.

### Two layout faults found while testing, fixed

- **Worker cards pushed the page sideways on a 320px phone in Nagamese**, and at
  360px, the most common Android width, they ran 4px past their column. The
  status "Etiya available ase" is longer than "Available now" and was never
  allowed to move. When there is no room beside the name, it now drops to its
  own line under the name. The same change gives the name the full width on a
  small phone in every language: at 360px in English it was squeezed into 88px.
  From 390px up in English and Hindi nothing moves.
- **On a desktop in Nagamese the trade box was cut off** ("Jiman kaam hoileh b")
  because the longer button words took the room. The top of the page is now
  1040px wide rather than 920; the heading and the lead keep their own width.

### Tested on the real WordPress site

- Titles, descriptions and robots tags on the homepage, jobs, workers, teams, a
  district, a trade, a worker and a job, filtered and unfiltered, in English,
  Hindi and Nagamese.
- The FAQ data parses and matches the questions on the page word for word, in
  all three languages; the organisation, website and job data parse.
- Sitemap index with no users list, account pages gone from it, the old users
  sitemap 404, `/dashboard/` marked `noindex, follow`, `robots.txt` naming the
  sitemap, `/llms.txt` answering as plain text.
- In a real browser: both buttons land on the right list with trade and district
  carried (*AC repair jobs in Kohima, Nagaland*), and at 320, 360, 390, 1024,
  1280 and 1440 pixels in all three languages the page never scrolls sideways,
  and no district name breaks mid-word.
- Nothing from these files in `debug.log`. Language files: 0 placeholder
  problems.

## 75. Every kind of job, not only daily work

Kaam Ase is for every job: teachers, nurses, office staff and government
adverts as well as masons and drivers. Fix 74's words leaned on the trades
("hire a mason, electrician, plumber, driver or helper", "118 trades"), which
tells a teacher the site is not for them. Only wording changed; nothing about
how the site works.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase/front-page.php` | 1.3.0 |
| `fixed/kaamase-core/includes/seo.php` | 1.1.0 |
| `fixed/kaamase/languages/kaamase.pot`, `hi_IN.po` and `.mo`, `nag.po` and `.mo` | |
| `fixed/kaamase-core/languages/kaamase-core.pot`, `kaamase-core-hi_IN.po` and `.mo`, `kaamase-core-nag.po` and `.mo` | |

Then **LiteSpeed Cache → Toolbox → Purge All**. `style.css` did not change.

### What reads differently

| Where | Before | Now |
| --- | --- | --- |
| Line under the heading | Find work near you, or hire a mason, electrician, plumber, driver or helper in any district… | Every kind of job, government and private, from teaching and office work to driving and building, in every district. |
| First box | Any trade | All kinds of work |
| Ticks | Free for workers, always · 118 trades | Free for job seekers, always · 118 kinds of work |
| Chips | Popular trades · All trades | Popular categories · All categories |
| Latest jobs | Posted by employers across the state. Workers never pay to apply. | Government and private jobs from across the state. Job seekers never pay to apply. |
| Available workers | See their trade, district, ratings… | See what they do, where they are, their ratings… |

In Hindi and Nagamese too. The heading, the buttons and everything else on the
page are as they were.

**For search engines and AI assistants:** the homepage, job list, worker list
and district descriptions now name government and private jobs, teachers,
nurses and office staff, not only trades. A trade page's title is now
*Teacher jobs and workers in Nagaland* (it was *Teacher in Nagaland: workers and
jobs*), matching what people type. `/llms.txt` and the organisation description
say every kind of job, government and private.

**A new question on the homepage:** *Are there government jobs on Kaam Ase?*,
answered yes, free to read, apply the way each advert says, and nobody can get
you a government job for money. **It is written as fact, so post a few
government adverts when you upload this.** If you would rather wait, say so and
the question comes out until you do.

**No Engineer category.** The words name teachers, nurses, accountants and
office staff because those categories exist. There is no *Engineer*, so an
engineer cannot pick one at sign up. Add it under *Trades* when you want
engineers, and it appears in the search box and the chips on its own.

### Found while testing, fixed

A worker list filtered to a trade (`/workers/?kaamase_trade=teacher`) got the
trade page's description, because WordPress marks a filtered list as a trade
page too. The list is now checked first.

### Tested on the real WordPress site

Homepage in three languages at 320, 360, 390 and 1280 pixels: no sideways
scroll, the line under the heading three lines at 390 in every language, both
buttons carrying trade and district. The nine questions match the FAQ data word
for word in each language. Titles and descriptions checked on the job, worker,
team, trade (Teacher and AC repair), filtered and district pages; the longest
description, Chumoukedima, is 160 characters. Nothing in `debug.log`; language
files 0 placeholder problems.

## 76. Photos behind the heading

The badge, the heading and the line under it now sit on seven photos that fade
from one to the next every five or six seconds. Only that part changed: the
search card, the ticks and the hills stay on the green.

### Upload

This includes fix 75, so if 75 is not up yet, upload once for both.

| File | |
| --- | --- |
| `fixed/kaamase/front-page.php` | 1.4.0 (includes 75) |
| `fixed/kaamase/style.css` | 1.9.0 |
| `fixed/kaamase/assets/images/hero/` | **new folder**, 14 files |
| `fixed/kaamase/languages/kaamase.pot`, `hi_IN.po` and `.mo`, `nag.po` and `.mo` | includes 75 |
| `fixed/kaamase-core/includes/seo.php` and `fixed/kaamase-core/languages/` | from 75, if not up yet |

The folder: in Hostinger's File Manager open `wp-content/themes/kaamase/assets/`,
make a folder `images`, inside it a folder `hero`, and upload the 14 `.webp`
files into `hero`. Then **LiteSpeed Cache → Toolbox → Purge All**.

### The photos, in this order

1. `queue`: job fair, the queue
2. `electrician`: hands at a distribution board (stock photo)
3. `dishes`: washing up (stock photo)
4. `interview`: interviews in an office
5. `building`: building site, Kohima
6. `job-fair`: job fair, waiting
7. `kohima`: Kohima from above

Each is there twice, cropped to the same 16:9 shape: `-s`, at most 800 wide, for
phones, and `-l` for computers. The 14 files are 740 KB together. To change the
order, add or drop a photo, edit the list at the top of the hero in
`front-page.php`; a new photo needs its two files in the folder.

### How it behaves

- **Only the first photo loads with the page.** The other six are fetched after
  the page has finished, so a slow phone gets a working page first. A photo that
  has not arrived is skipped, never shown half loaded.
- **Phones fetch only the small files** (23 to 71 KB each); computers only the
  large ones.
- **A round pause button** sits at the bottom right. It reads *Pause the photos*
  or *Play the photos* to a screen reader, in all three languages.
- **Nothing moves** in a tab nobody is looking at, or for somebody whose phone
  is set to reduce motion. They get the first photo and nothing else downloads.
  With no script at all the first photo shows and the button stays hidden.
- **The words are still text**, on a shade that keeps them readable on a bright
  photo, and the bottom edge fades into the green.
- LiteSpeed's lazy loading is told to leave these images alone
  (`data-no-lazy`), because the script already loads them at the right time.

Most of the photos are about 1000 pixels wide, so on a large computer screen
they are a little soft. Sharper originals can replace them at any time with the
same names.

### Tested on the real WordPress site

20 checks in a real browser: on a phone and a computer, only the first photo
before the page finished and all seven after, phones never fetching a large
file nor computers a small one; the photo changing by itself; pause holding it,
play moving on at once; one photo showing after each fade; reduced motion
fetching one photo with no button; no script showing the first photo with the
button hidden; the button's words in Hindi and Nagamese. No sideways scroll at
320, 360, 390, 768, 1024, 1280 and 1440 pixels in any language. With the photo
list emptied, every measured position of the hero matched the previous version
exactly. The pause button shows the amber focus ring from the keyboard. Nothing
in `debug.log`.

## 77. Photo weight, profile photos, and job pictures shown whole

Three things were checked: advice that PNG photos make heavy thumbnails,
whether very small or very big profile photos look bad, and feedback that job
pictures lose part of what is written on them. The first and third were real,
and checking the second found a weight problem of its own. Each was measured on
the real WordPress copy before anything changed.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase/inc/setup.php` | 1.2.0 |
| `fixed/kaamase/inc/template-tags.php` | 1.2.0 |
| `fixed/kaamase-core/includes/job-photos.php` | 1.3.0, **first time in `fixed/`**, over the live one |
| `fixed/kaamase-core/languages/kaamase-core.pot`, `kaamase-core-hi_IN.po` and `.mo`, `kaamase-core-nag.po` and `.mo` | one new line |

Then **LiteSpeed Cache → Toolbox → Purge All**. No other file changed; the
theme's language files and `style.css` are as they were.

### 1. PNG photos: the advice was right

WordPress makes every smaller copy in the format the photo arrived in. The same
photograph, uploaded both ways:

| | as PNG | as JPEG |
| --- | --- | --- |
| 128 px avatar, on every card | 33.6 KB | 4.8 KB |
| 320 px, profile page | 163.6 KB | 19.6 KB |
| 960 × 540, a job picture | 565.1 KB | 64.1 KB |

Now the copies of a member's PNG are made as JPEG, so a PNG photo weighs what
a JPEG does. Four limits keep it safe:

- **Member photos only**: profile pictures and job pictures, told apart by what
  they belong to. The site's own images are untouched.
- **Nothing see-through**: a logo with a clear background, or a photo cut into
  a circle, keeps PNG copies. JPEG would turn the clear parts black. The file is
  checked, not guessed: a PNG saved with an unused see-through layer, which
  phones often do, is recognised as solid and made JPEG.
- **Only the copies**: the uploaded file keeps its format, so the step that
  removes location data from every upload works exactly as before. Tested with
  a PNG carrying a hidden location note: removed, as before.
- **JPEG, not WebP**: WhatsApp does not reliably show WebP pictures in link
  previews, and these are the pictures a shared job or profile shows.

Photos uploaded before this keep their PNG copies. They become light when
re-uploaded, or all at once with the free *Regenerate Thumbnails* plugin, which
is optional.

### 2. Profile photos, any size: they look right

Every avatar is drawn in a fixed square that is filled, never stretched.
Checked with a 60 × 40 photo, a 4000 × 2776 one, a 3000 × 600 strip and a
600 × 3000 strip: all show as clean squares. A very big photo is shrunk to 1600
and cut square from the middle. A tiny one is still square, just soft, because
there are no more pixels to show. Nothing to change there.

What the check did find: **phones were downloading far more than they show.**
WordPress told the browser an avatar is 128 or 320 wide, but the page draws it
at 56 on a card and 96 on a profile. For a square photo, which is what most
croppers make, the phone then chose the 1024 copy.

| Square profile photo, phone | Before | Now |
| --- | --- | --- |
| Worker list, one face | 107.7 KB | 18.3 KB |
| Profile page | 107.7 KB | 18.3 KB |

Just as sharp: the browser still gets three pixels for every pixel on screen.

### 3. Job pictures: shown whole

The job page showed a **960 by 540 crop**, a band across the middle. A tall
flyer lost its heading at the top and its phone number at the bottom, which are
the two things that matter. Now:

- **One picture** shows whole, as wide as the column and never taller than most
  of the screen, in a light frame.
- **Several pictures** sit in a grid of equal frames, each shown whole inside
  its frame.
- **A tap opens the picture large**: the stored picture, up to 1600 pixels, to
  zoom into the small print. For a PNG, the 1024 copy instead, because the
  stored PNG can be many megabytes.
- The page never downloads the stored original on its own: at most the 1024
  copy, which for the test flyer was 29 KB.

**For the app:** each job photo now also has `whole`, with `whole_width` and
`whole_height`: the same picture uncropped, at most 1024 on its longest side.
`small` and `large` are unchanged crops, so nothing in the app changes by
itself. The job screen should use `whole` to show a flyer in full.

**Job cards** in lists asked for a 128 crop that is only ever made for profile
photos, so they fell back to a big copy for a 64 pixel thumbnail. They now use
the 150 square WordPress already makes for every job photo. On the test list:
41.7 KB of pictures before, 3.0 KB now. The share picture on WhatsApp is still
the 960 by 540 crop, which is what a preview wants.

### Tested on the real WordPress site

- 18 uploads through the site's own path, plus 3 through the app's photo route
  over HTTP: PNG photos, a PNG with an unused see-through layer, palette and
  grey PNGs, a PNG flyer, a round-cut photo, two see-through logos, a PNG with a
  location note, a PNG on an ordinary page, and tiny, huge, wide and tall
  JPEGs. Each gave the result described above; the switch never stayed on
  between uploads.
- Job page with one and with three pictures, on a phone and a computer: whole
  pictures, the right file opened by a tap, the original never in the choice.
  The job data over the REST API carries `whole` at 576 by 1024 for the flyer.
- What a phone really downloads, for the worker list, a profile page and the job
  list, before and after.
- Home, jobs, workers, teams, a job, a worker, a district, a trade and a
  filtered list in English, Hindi and Nagamese: every page complete, the new
  label read out as *पूरी तस्वीर देखें* and *Pura photo sabo*, and nothing from
  these files in `debug.log`. Language files: 0 placeholder problems.

## 78. The nightly "Did you hire them?" arriving at 2 a.m.

The app was sending its *Did you hire them?* notification in the middle of the
night. It should come in the morning.

### Upload

| File | |
| --- | --- |
| `fixed/kaamase-core/includes/post-types.php` | 1.1.0 |

No cache purge needed; nothing about a page changed.

### What was wrong

Two different notifications, scheduled two different ways.

The *opened X times* milestone runs on an **hourly** check that only sends
between 8 a.m. and 9 p.m., so it never wakes anyone. But *Did you hire them?*
rides on the once-a-day maintenance task, and that task was booked for **an hour
after the plugin was first switched on** — no particular time of day, and on
this site it settled at about 2 a.m. The hire question has no daytime guard, so
it went out whenever the task ran.

So there was never a 6:30 in the code; the morning timing anyone remembered was
luck, and the 2 a.m. sends are the task simply running when it was booked.

### The fix

The daily task is now booked for **6:30 in the morning, site time**. The
maintenance it also does (closing expired jobs, tidying old rows) does not care
what hour it runs, so the whole task moves together.

- The hour reads from the site's timezone, like the rest of the plugin. A site
  left on WordPress's default of UTC is read as Kolkata, since this platform is
  only ever Nagaland, so 6:30 is 6:30 IST either way. Setting **Settings →
  General → Timezone** to Kolkata is still worth doing (the dashboard and other
  dates use it), but the notification no longer depends on it.
- Changing the code cannot move a task WordPress has already booked, so every
  page load checks, cheaply, that the booking sits on the 6:30 slot. If it does
  not, every copy of it is cleared and the slot booked. So the old 2 a.m. booking
  moves on the first load after upload, even if it had been booked twice (which
  `uninstall.php` already allows for), and it would mend itself if the time or
  timezone were ever changed. A 6:30 run that is due but has not happened yet,
  because nobody visited since 6:30, counts as on the slot and is left alone, so
  no day's run is lost.
- `6:30` can be changed without editing code, through the `kaamase_daily_hour`
  and `kaamase_daily_minute` filters.

Like any WordPress schedule, it fires on the first visit after 6:30 rather than
to the second; on a site with steady traffic that is a few minutes past 6:30.

### Tested on the real WordPress site

On the real WordPress copy, against WordPress's own scheduler, 18 checks, each
with the site left on UTC and set to Kolkata: the next slot is 6:30 IST and in the
future; an old 2:03 a.m. booking moves to 6:30; two old copies are both cleared
to one; an old copy beside a 6:30 copy leaves one; a fresh install books 6:30; a
6:30 run that is due but not yet run is left alone; four loads in a row keep
exactly one copy. Setting the hour filter to 7:00 moved it and removing the
filter moved it back; a misspelt timezone from the filter does not crash and
falls back to Kolkata; the hire-question sender is still attached.

Then a real run through `wp-cron.php`: the due task ran all of its jobs to the
end, and afterwards exactly one copy was booked, on the next 6:30. Nothing from
this file in `debug.log`.

The other nine jobs on the daily task (closing expired jobs, promotions, rating
release, hire-claim expiry, saved lists, view pruning, rate-limit clean-up,
privacy retention, contact logs) were read for any dependence on the hour: all
work on rolling windows, and a promotion's own end time already decides whether
it shows, so none is affected. The privacy retention notices now go out in the
morning too.

## 79. The owner's Nagamese review, folded in

The Nagamese was machine-drafted and leaned Assamese in places. The owner, who
speaks Nagamese, reviewed all 1,252 lines and returned corrections. This folds
them into the three Nagamese files, exactly as written.

### Upload

Only the Nagamese `.po` and `.mo` files changed. The English templates and the
Hindi files are untouched.

| File | |
| --- | --- |
| `fixed/kaamase-core/languages/kaamase-core-nag.po` and `.mo` | |
| `fixed/kaamase/languages/nag.po` and `.mo` | |
| `fixed/kaamase-pay/languages/kaamase-pay-nag.po` and `.mo` | |

Then **LiteSpeed Cache → Toolbox → Purge All**, so cached Nagamese pages pick up
the new wording. The app reads its own copy from `translations/app-strings.json`,
which was updated in the same pass.

### What changed

573 of the 1,252 lines were improved. The consistent spelling fixes: `laka →
laga` (every one, none left), `bosor → sal`, `pisa → paisa`, most past tenses
from `-shey` to `-she` (e.g. `dishey → dishe`), and some command forms to
`kuribi`. The 29 lines that carry a separate "more than one" form were reviewed
on their own and finalised by the owner, singular and plural both.

Verb forms the owner uses on purpose were left alone. `koribo` ("will do") and
`koribi` ("do") are different words, not two spellings of one, so they were not
levelled.

### Left for the owner to decide

Three spellings appear in two forms across the file. They were **not** touched,
because only a speaker can say which is right or whether the difference is
deliberate: `pra` / `para`, `apni ke` / `apnake`, and about 50 words still
ending `-shey` where most became `-she`. A later pass can settle any of these on
one form in a few minutes once the owner says which.

### Tested on the real WordPress site

`checktrans` on all three Nagamese files: every placeholder (`%s`, `%1$s`, `%d`)
in the English is present in the Nagamese, 0 problems. The homepage rendered in
Nagamese with the new wording (`kisim laga kaam`, not `laka`) and no errors in
`debug.log`. The plural machinery was exercised through `_n()` against the real
compiled files: *%s year* gave "1 sal" and "3 sal"; *Team of %s worker* gave
"1 worker laga team" and "5 worker khan laga team"; *%d person wants your number*
and *Sent to %s person.* each gave the right one-versus-many form.

## Not changed, and why

- **`kaamase-pay`** — payment start, confirmation and cancellation were *not*
  broken. Each already has a `template_redirect` fallback
  (`kaamase_pay_catch_start`, `_catch_verify`, `_catch_cancel`) that was catching
  these on the front end. Fix 1 repairs the `admin-post.php` route they also
  register, so both paths now work. Razorpay signature verification was not
  touched.
- **The privacy layer** — untouched. No change to `kaamase_field`,
  `kaamase_can_see_private`, or private-field handling.
- **`kaamase/readme.txt`** — changelog still stops at 1.1.0. Cosmetic.
