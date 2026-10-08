<?php
/**
 * Build kaamase-core.pot from the plugin source.
 *
 * Stands in for `wp i18n make-pot`, which is not available here. Matches
 * its output shape: same header, sorted by context then msgid, references
 * wrapped the way gettext wraps them.
 */

$root   = $argv[1] ?? '';
$out    = $argv[2] ?? '';
$domain  = $argv[3] ?? 'kaamase-core';
$project = $argv[4] ?? 'Kaam Ase Core';

if ( ! is_dir( $root ) ) {
	exit( "usage: makepot.php <plugin-root> <out.pot>\n" );
}

/* Which argument holds what, per gettext function. */
$spec = array(
	'__'            => array( 'single' => 0, 'domain' => 1 ),
	'_e'            => array( 'single' => 0, 'domain' => 1 ),
	'esc_html__'    => array( 'single' => 0, 'domain' => 1 ),
	'esc_html_e'    => array( 'single' => 0, 'domain' => 1 ),
	'esc_attr__'    => array( 'single' => 0, 'domain' => 1 ),
	'esc_attr_e'    => array( 'single' => 0, 'domain' => 1 ),
	'_x'            => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'_ex'           => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'esc_html_x'    => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'esc_attr_x'    => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'_n'            => array( 'single' => 0, 'plural' => 1, 'domain' => 3 ),
	'_nx'           => array( 'single' => 0, 'plural' => 1, 'context' => 3, 'domain' => 4 ),
	'_n_noop'       => array( 'single' => 0, 'plural' => 1, 'domain' => 2 ),
	'_nx_noop'      => array( 'single' => 0, 'plural' => 1, 'context' => 2, 'domain' => 3 ),
);

/** Turn a PHP string literal into its value. */
function literal_value( $tokens ) {
	$parts = array();
	foreach ( $tokens as $t ) {
		if ( is_array( $t ) && T_WHITESPACE === $t[0] ) { continue; }
		if ( is_array( $t ) && T_COMMENT === $t[0] ) { continue; }
		if ( is_array( $t ) && T_DOC_COMMENT === $t[0] ) { continue; }
		if ( is_string( $t ) && '.' === $t ) { continue; }
		if ( is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] ) {
			$raw   = $t[1];
			$quote = $raw[0];
			$body  = substr( $raw, 1, -1 );
			if ( "'" === $quote ) {
				$parts[] = strtr( $body, array( "\\'" => "'", '\\\\' => '\\' ) );
			} else {
				$parts[] = stripcslashes( $body );
			}
			continue;
		}
		return null; // not a plain literal
	}
	return empty( $parts ) ? null : implode( '', $parts );
}

/** Collect files. */
$files = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
	if ( $f->isFile() && 'php' === strtolower( $f->getExtension() ) ) {
		$files[] = $f->getPathname();
	}
}
sort( $files );

$entries = array(); // key => ['ctx','single','plural','refs'=>[], 'comments'=>[]]
$skipped = array();

foreach ( $files as $file ) {

	$rel    = ltrim( str_replace( realpath( $root ), '', realpath( $file ) ), '/' );
	$tokens = token_get_all( file_get_contents( $file ) );
	$n      = count( $tokens );

	for ( $i = 0; $i < $n; $i++ ) {

		if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] ) { continue; }

		$fn = $tokens[ $i ][1];
		if ( ! isset( $spec[ $fn ] ) ) { continue; }

		// Not a method or a definition.
		$p = $i - 1;
		while ( $p >= 0 && is_array( $tokens[ $p ] ) && T_WHITESPACE === $tokens[ $p ][0] ) { $p--; }
		if ( $p >= 0 && is_array( $tokens[ $p ] ) && in_array( $tokens[ $p ][0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) { continue; }

		// Must be followed by (
		$q = $i + 1;
		while ( $q < $n && is_array( $tokens[ $q ] ) && T_WHITESPACE === $tokens[ $q ][0] ) { $q++; }
		if ( $q >= $n || ! is_string( $tokens[ $q ] ) || '(' !== $tokens[ $q ] ) { continue; }

		$line = $tokens[ $i ][2];

		// Split the argument list at depth 1.
		$args  = array();
		$cur   = array();
		$depth = 0;
		for ( $j = $q; $j < $n; $j++ ) {
			$t = $tokens[ $j ];
			if ( is_string( $t ) && in_array( $t, array( '(', '[' ), true ) ) { $depth++; if ( 1 === $depth ) { continue; } }
			elseif ( is_string( $t ) && in_array( $t, array( ')', ']' ), true ) ) { $depth--; if ( 0 === $depth ) { $args[] = $cur; break; } }
			elseif ( is_array( $t ) && in_array( $t[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) { $depth++; }
			if ( 1 === $depth && is_string( $t ) && ',' === $t ) { $args[] = $cur; $cur = array(); continue; }
			if ( $depth >= 1 ) { $cur[] = $t; }
		}

		$s = $spec[ $fn ];

		// Domain must match.
		$dom = isset( $args[ $s['domain'] ] ) ? literal_value( $args[ $s['domain'] ] ) : null;
		if ( $dom !== $domain ) { continue; }

		$single = isset( $args[ $s['single'] ] ) ? literal_value( $args[ $s['single'] ] ) : null;
		if ( null === $single ) { $skipped[] = "$rel:$line ($fn) non-literal text"; continue; }

		$plural = null;
		if ( isset( $s['plural'] ) ) {
			$plural = isset( $args[ $s['plural'] ] ) ? literal_value( $args[ $s['plural'] ] ) : null;
			if ( null === $plural ) { $skipped[] = "$rel:$line ($fn) non-literal plural"; continue; }
		}

		$ctx = null;
		if ( isset( $s['context'] ) ) {
			$ctx = isset( $args[ $s['context'] ] ) ? literal_value( $args[ $s['context'] ] ) : null;
			if ( null === $ctx ) { $skipped[] = "$rel:$line ($fn) non-literal context"; continue; }
		}

		// A translators: comment sitting just before the call.
		$comment = null;
		for ( $b = $i - 1; $b >= 0 && $b > $i - 40; $b-- ) {
			$t = $tokens[ $b ];
			if ( is_array( $t ) && in_array( $t[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				if ( stripos( $t[1], 'translators:' ) !== false ) {
					$txt = preg_replace( '#^/\*+|\*+/$|^//|^\##', '', trim( $t[1] ) );
					$txt = trim( preg_replace( '#^\s*\*\s?#m', '', $txt ) );
					$comment = preg_replace( '/\s+/', ' ', $txt );
				}
				break;
			}
			if ( is_array( $t ) && T_WHITESPACE === $t[0] ) { continue; }
			break; // anything else means no attached comment
		}

		$key = ( null === $ctx ? "\4" : $ctx . "\4" ) . $single . ( null === $plural ? '' : "\0" . $plural );

		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = array(
				'ctx'      => $ctx,
				'single'   => $single,
				'plural'   => $plural,
				'refs'     => array(),
				'comments' => array(),
			);
		}
		$entries[ $key ]['refs'][] = "$rel:$line";
		if ( $comment ) { $entries[ $key ]['comments'][ $comment ] = true; }
	}
}

/* Sort: no context first, then by context, then by msgid. */
uasort(
	$entries,
	function ( $a, $b ) {
		$ca = (string) $a['ctx'];
		$cb = (string) $b['ctx'];
		if ( $ca !== $cb ) { return strcmp( $ca, $cb ); }
		return strcmp( $a['single'], $b['single'] );
	}
);

function po_escape( $s ) {
	return str_replace(
		array( '\\', '"', "\n", "\t", "\r" ),
		array( '\\\\', '\\"', '\\n', '\\t', '\\r' ),
		$s
	);
}

/** gettext wraps a reference line once it has reached 79 columns. */
function wrap_refs( $refs ) {
	$lines = array();
	$cur   = '#:';
	foreach ( $refs as $r ) {
		if ( '#:' !== $cur && strlen( $cur ) >= 79 ) {
			$lines[] = $cur;
			$cur     = '#:';
		}
		$cur .= ' ' . $r;
	}
	if ( '#:' !== $cur ) { $lines[] = $cur; }
	return $lines;
}

$po  = "# Copyright (C) Nagaland Me\n";
$po .= "# This file is distributed under the GPL-2.0-or-later license.\n";
$po .= "msgid \"\"\n";
$po .= "msgstr \"\"\n";
$po .= '"Project-Id-Version: ' . $project . "\\n\"\n";
$po .= "\"Report-Msgid-Bugs-To: https://kaamase.com\\n\"\n";
$po .= '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:i' ) . "+0000\\n\"\n";
$po .= "\"MIME-Version: 1.0\\n\"\n";
$po .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$po .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$po .= "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n";
$po .= '"X-Domain: ' . $domain . "\\n\"\n";

foreach ( $entries as $e ) {

	$po .= "\n";

	foreach ( array_keys( $e['comments'] ) as $c ) {
		$po .= '#. ' . $c . "\n";
	}

	$refs = array_values( array_unique( $e['refs'] ) );
	sort( $refs );
	foreach ( wrap_refs( $refs ) as $l ) { $po .= $l . "\n"; }

	if ( null !== $e['ctx'] ) {
		$po .= 'msgctxt "' . po_escape( $e['ctx'] ) . "\"\n";
	}

	$po .= 'msgid "' . po_escape( $e['single'] ) . "\"\n";

	if ( null !== $e['plural'] ) {
		$po .= 'msgid_plural "' . po_escape( $e['plural'] ) . "\"\n";
		$po .= "msgstr[0] \"\"\n";
		$po .= "msgstr[1] \"\"\n";
	} else {
		$po .= "msgstr \"\"\n";
	}
}

file_put_contents( $out, $po );

fwrite( STDERR, 'files scanned: ' . count( $files ) . "\n" );
fwrite( STDERR, 'entries: ' . count( $entries ) . "\n" );
fwrite( STDERR, 'skipped (non-literal, cannot be extracted): ' . count( $skipped ) . "\n" );
foreach ( array_slice( $skipped, 0, 20 ) as $s ) { fwrite( STDERR, "  $s\n" ); }
