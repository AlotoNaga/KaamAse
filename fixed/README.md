# Fixed files

Three production blockers, the same sort fix carried into the app API, and the
Tier 1 security items. Each file below is complete — open it, select all, and
paste over the matching file on your site. Nothing else was touched.

The `.zip` files in the repository root are still the original upload. These are
the patched versions of twenty-seven files taken from inside them, plus three
new translation templates.

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

**New folders to create** (they do not exist on your site yet):

| Copy this file | Into this new folder |
| --- | --- |
| `fixed/kaamase-core/languages/kaamase-core.pot` | `wp-content/plugins/kaamase-core/languages/` |
| `fixed/kaamase/languages/kaamase.pot` | `wp-content/themes/kaamase/languages/` |
| `fixed/kaamase-pay/languages/kaamase-pay.pot` | `wp-content/plugins/kaamase-pay/languages/` |

For fix 7, copy **`throttle.php` first** — the other five call into it.

For fix 16, copy **`taxonomies.php` and `contact.php` first, then
`kaamase-core.php`** — the last one is what triggers the new trades to be created.

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
- **`kaamase/readme.txt`** — changelog still stops at 1.1.0. Cosmetic.
