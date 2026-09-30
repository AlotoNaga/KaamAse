<?php
/**
 * Turn a .pot plus a JSON map of translations into .po and .mo.
 *
 * Stands in for msgfmt, which is not installed here. The .mo layout is
 * the GNU one: a magic number, two tables of (length, offset) pairs, and
 * the strings themselves. A context is joined to its msgid with \x04 and
 * a plural to its singular with \0, which is how gettext keys them.
 *
 * usage: mkpo.php <in.pot> <translations.json> <out-basename> <locale> <project>
 */

list( , $potPath, $jsonPath, $outBase, $locale, $project ) = array_pad( $argv, 6, '' );

if ( ! is_file( $potPath ) || ! is_file( $jsonPath ) ) {
	exit( "usage: mkpo.php <in.pot> <translations.json> <out-basename> <locale> <project>\n" );
}

function po_unquote( $block ) {
	$out = '';
	foreach ( explode( "\n", $block ) as $line ) {
		if ( preg_match( '/^"(.*)"$/', trim( $line ), $m ) ) {
			$out .= $m[1];
		}
	}
	return $out;
}
function po_unescape( $s ) {
	return str_replace(
		array( '\\n', '\\t', '\\"', '\\\\' ),
		array( "\n", "\t", '"', '\\' ),
		$s
	);
}
function po_escape( $s ) {
	return str_replace(
		array( '\\', '"', "\n", "\t" ),
		array( '\\\\', '\\"', '\\n', '\\t' ),
		$s
	);
}

/* ---- read the template ---- */
$blocks  = preg_split( "/\n\n+/", file_get_contents( $potPath ) );
$entries = array();

foreach ( $blocks as $b ) {

	if ( ! preg_match( '/^msgid ((?:".*"\n?)+)/m', $b, $m ) ) {
		continue;
	}

	$id = po_unescape( po_unquote( $m[1] ) );

	if ( '' === $id ) {
		continue;                      // the header
	}

	$plural = preg_match( '/^msgid_plural ((?:".*"\n?)+)/m', $b, $p ) ? po_unescape( po_unquote( $p[1] ) ) : null;
	$ctx    = preg_match( '/^msgctxt ((?:".*"\n?)+)/m', $b, $c ) ? po_unescape( po_unquote( $c[1] ) ) : null;

	preg_match_all( '/^#\. (.+)$/m', $b, $cm );
	preg_match_all( '/^#: (.+)$/m', $b, $rf );

	$entries[] = array(
		'id'      => $id,
		'plural'  => $plural,
		'ctx'     => $ctx,
		'notes'   => $cm[1],
		'refs'    => $rf[1],
	);
}

/* ---- the translations ---- */
$map  = json_decode( file_get_contents( $jsonPath ), true );
$done = 0;
$miss = array();

/* ---- write the .po ---- */
$po  = "# Kaam Ase — " . $locale . "\n";
$po .= "# This file is distributed under the GPL-2.0-or-later license.\n";
$po .= "msgid \"\"\nmsgstr \"\"\n";
$po .= '"Project-Id-Version: ' . $project . "\\n\"\n";
$po .= "\"Report-Msgid-Bugs-To: https://kaamase.com\\n\"\n";
$po .= '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:i' ) . "+0000\\n\"\n";
$po .= '"PO-Revision-Date: ' . gmdate( 'Y-m-d H:i' ) . "+0000\\n\"\n";
$po .= '"Language: ' . $locale . "\\n\"\n";
$po .= "\"MIME-Version: 1.0\\n\"\n";
$po .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$po .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$po .= "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n";
$po .= '"X-Domain: ' . basename( $outBase, '-' . $locale ) . "\\n\"\n";

$mo = array();   // key => value, both already \0/\x04 joined

foreach ( $entries as $e ) {

	$key = ( null === $e['ctx'] ? '' : $e['ctx'] ) . '|' . $e['id'];
	$t   = isset( $map[ $key ] ) ? $map[ $key ] : ( isset( $map[ $e['id'] ] ) ? $map[ $e['id'] ] : null );

	$po .= "\n";
	foreach ( $e['notes'] as $n ) { $po .= '#. ' . $n . "\n"; }
	foreach ( $e['refs'] as $r )  { $po .= '#: ' . $r . "\n"; }
	if ( null !== $e['ctx'] ) { $po .= 'msgctxt "' . po_escape( $e['ctx'] ) . "\"\n"; }
	$po .= 'msgid "' . po_escape( $e['id'] ) . "\"\n";

	if ( null !== $e['plural'] ) {

		$po .= 'msgid_plural "' . po_escape( $e['plural'] ) . "\"\n";

		$one = ( is_array( $t ) && isset( $t[0] ) ) ? $t[0] : '';
		$many = ( is_array( $t ) && isset( $t[1] ) ) ? $t[1] : '';

		$po .= 'msgstr[0] "' . po_escape( $one ) . "\"\n";
		$po .= 'msgstr[1] "' . po_escape( $many ) . "\"\n";

		if ( '' !== $one ) {
			$done++;
			$k = ( null === $e['ctx'] ? '' : $e['ctx'] . "\x04" ) . $e['id'] . "\0" . $e['plural'];
			$mo[ $k ] = $one . "\0" . $many;
		} else {
			$miss[] = $e['id'];
		}

		continue;
	}

	$str = is_array( $t ) ? '' : (string) $t;
	$po .= 'msgstr "' . po_escape( $str ) . "\"\n";

	if ( '' !== $str ) {
		$done++;
		$k = ( null === $e['ctx'] ? '' : $e['ctx'] . "\x04" ) . $e['id'];
		$mo[ $k ] = $str;
	} else {
		$miss[] = $e['id'];
	}
}

file_put_contents( $outBase . '.po', $po );

/* ---- compile the .mo ---- */
$headers = "Project-Id-Version: {$project}\nLanguage: {$locale}\nMIME-Version: 1.0\n"
	. "Content-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n"
	. "Plural-Forms: nplurals=2; plural=(n != 1);\n";

$all = array( '' => $headers ) + $mo;
ksort( $all, SORT_STRING );          // gettext requires the keys sorted

$n       = count( $all );
$keys    = array_keys( $all );
$vals    = array_values( $all );
$hdrSize = 28;
$oTable  = $hdrSize;
$tTable  = $oTable + ( $n * 8 );
$strBase = $tTable + ( $n * 8 );

$oEntries = '';
$tEntries = '';
$blob     = '';
$off      = $strBase;

foreach ( $keys as $k ) {
	$oEntries .= pack( 'VV', strlen( $k ), $off );
	$blob     .= $k . "\0";
	$off      += strlen( $k ) + 1;
}
foreach ( $vals as $v ) {
	$tEntries .= pack( 'VV', strlen( $v ), $off );
	$blob     .= $v . "\0";
	$off      += strlen( $v ) + 1;
}

file_put_contents(
	$outBase . '.mo',
	pack( 'V', 0x950412de ) . pack( 'V', 0 ) . pack( 'V', $n )
	. pack( 'V', $oTable ) . pack( 'V', $tTable )
	. pack( 'V', 0 ) . pack( 'V', $strBase )
	. $oEntries . $tEntries . $blob
);

fwrite( STDERR, sprintf( "  %s: %d of %d translated, %d left\n", basename( $outBase ), $done, count( $entries ), count( $miss ) ) );

if ( getenv( 'SHOW_MISSING' ) && $miss ) {
	foreach ( array_slice( $miss, 0, 60 ) as $m ) { fwrite( STDERR, "    - $m\n" ); }
}
