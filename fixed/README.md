# Fixed files

Three production blockers, the same sort fix carried into the app API, and the
first two security items. Each file below is complete — open it, select all, and
paste over the matching file on your site. Nothing else was touched.

The `.zip` files in the repository root are still the original upload. These are
the patched versions of six files taken from inside them.

## What to copy where

| Copy this file | Over this one on your site |
| --- | --- |
| `fixed/kaamase/inc/enqueue.php` | `wp-content/themes/kaamase/inc/enqueue.php` |
| `fixed/kaamase-core/includes/queries.php` | `wp-content/plugins/kaamase-core/includes/queries.php` |
| `fixed/kaamase/functions.php` | `wp-content/themes/kaamase/functions.php` |
| `fixed/kaamase-core/includes/rest-api.php` | `wp-content/plugins/kaamase-core/includes/rest-api.php` |
| `fixed/kaamase/inc/security.php` | `wp-content/themes/kaamase/inc/security.php` |
| `fixed/kaamase-pay/includes/settings.php` | `wp-content/plugins/kaamase-pay/includes/settings.php` |

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
