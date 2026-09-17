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
