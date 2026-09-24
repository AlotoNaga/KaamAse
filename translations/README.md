# Translation working files

Not for uploading. `fixed/` holds what goes on the site; this holds what
produced it, so the next batch does not start from nothing and a wrong word can
be corrected without hand-editing a binary.

    hi-theme.json   the website's own text
    hi-pay.json     the payment plugin
    hi-core.json    the core plugin, filled in batches

    mkpo.php        JSON + .pot  ->  .po and .mo   (there is no msgfmt here)
    checktrans.php  every placeholder in a translation matches the English
    makepot2.php    rebuilds a .pot, with the domain and project passed in

Rebuild any language file with:

    php mkpo.php <in.pot> <hi-xxx.json> <out-basename> hi_IN "<Project>"

The out-basename decides whether WordPress finds it:

    theme   ->  languages/hi_IN
    plugin  ->  languages/<textdomain>-hi_IN

Admin-only strings are deliberately left untranslated. The owner reads those
screens in English and translating them is effort with no reader.

## app-strings.json — for the app side, not for upload

Every English string this platform has already translated, with its Hindi and
Nagamese, in one flat file. 1,240 of them, 29 carrying plural forms.

It exists because the app was re-translating strings that were already done.
The app cannot read a `.mo` file — that is PHP on the server, and the phone
never sees one — so the app needs its own copies. It does not need to think
about them twice.

```json
"Post a job": { "hi_IN": "काम डालें", "nag": "Kaam post koribo" }
```

Plural forms come through as arrays, `[singular, plural]`, in the same order
gettext stores them.

Matching is an exact lookup on the English source text. Anything that comes back
is a word already decided and already used on the website, in an email and in a
push notification — so taking it keeps the app saying the same thing as
everything else, which matters more than the time it saves. Anything that does
not match is genuinely the app's own wording.

This file is generated. Correct a translation in `hi-*.json` or `nag-*.json` and
rebuild, never here.
