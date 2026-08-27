# Fixed files

Three production blockers, the same sort fix carried into the app API, and the
Tier 1 security items. Each file below is complete — open it, select all, and
paste over the matching file on your site. Nothing else was touched.

The `.zip` files in the repository root are still the original upload. These are
the patched versions of fourteen files taken from inside them.

## What to copy where

| Copy this file | Over this one on your site |
| --- | --- |
| `fixed/kaamase/inc/enqueue.php` | `wp-content/themes/kaamase/inc/enqueue.php` |
| `fixed/kaamase-core/includes/queries.php` | `wp-content/plugins/kaamase-core/includes/queries.php` |
| `fixed/kaamase/functions.php` | `wp-content/themes/kaamase/functions.php` |
| `fixed/kaamase-core/includes/rest-api.php` | `wp-content/plugins/kaamase-core/includes/rest-api.php` |
| `fixed/kaamase/inc/security.php` | `wp-content/themes/kaamase/inc/security.php` |
| `fixed/kaamase-pay/includes/settings.php` | `wp-content/plugins/kaamase-pay/includes/settings.php` |
| `fixed/kaamase-core/includes/throttle.php` | `wp-content/plugins/kaamase-core/includes/throttle.php` |
| `fixed/kaamase-core/includes/contact.php` | `wp-content/plugins/kaamase-core/includes/contact.php` |
| `fixed/kaamase-core/includes/rest-auth.php` | `wp-content/plugins/kaamase-core/includes/rest-auth.php` |
| `fixed/kaamase-core/includes/registration.php` | `wp-content/plugins/kaamase-core/includes/registration.php` |
| `fixed/kaamase-core/includes/reports.php` | `wp-content/plugins/kaamase-core/includes/reports.php` |
| `fixed/kaamase-core/includes/post-job.php` | `wp-content/plugins/kaamase-core/includes/post-job.php` |
| `fixed/kaamase-core/includes/fields.php` | `wp-content/plugins/kaamase-core/includes/fields.php` |
| `fixed/kaamase-core/includes/post-types.php` | `wp-content/plugins/kaamase-core/includes/post-types.php` |

For fix 7, copy **`throttle.php` first** — the other five call into it.

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

---

## Not changed, and why

- **`kaamase-pay`** — payment start, confirmation and cancellation were *not*
  broken. Each already has a `template_redirect` fallback
  (`kaamase_pay_catch_start`, `_catch_verify`, `_catch_cancel`) that was catching
  these on the front end. Fix 1 repairs the `admin-post.php` route they also
  register, so both paths now work. Razorpay signature verification was not
  touched.
- **The privacy layer** — untouched. No change to `kaamase_field`,
  `kaamase_can_see_private`, or private-field handling.
- **`KAAMASE_CORE_VERSION`** (says `1.3.0`, header says `1.3.1`) — defined but
  never used anywhere, so it affects nothing. Worth tidying later.
- **`kaamase/readme.txt`** — changelog still stops at 1.1.0. Cosmetic.
