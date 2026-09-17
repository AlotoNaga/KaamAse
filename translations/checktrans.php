<?php
/**
 * The check that matters: a translated sentence must carry exactly the
 * same placeholders as the English one. A lost %s prints nothing where a
 * number should be; a %1$s turned into %s by a slip is a PHP warning on a
 * live page.
 */
$po = file_get_contents( $argv[1] );
$blocks = preg_split( "/\n\n+/", $po );
$bad = 0; $checked = 0; $empty = 0;

function ph( $s ) {
	preg_match_all( '/%(?:\d+\$)?[bcdeEfFgGosuxX]|%%/', $s, $m );
	$out = $m[0];
	sort( $out );
	return $out;
}
function unq( $b, $key ) {
	if ( ! preg_match( '/^' . $key . ' ((?:".*"\n?)+)/m', $b, $m ) ) { return null; }
	$s = '';
	foreach ( explode( "\n", $m[1] ) as $l ) {
		if ( preg_match( '/^"(.*)"$/', trim( $l ), $q ) ) { $s .= $q[1]; }
	}
	return $s;
}

foreach ( $blocks as $b ) {
	$id = unq( $b, 'msgid' );
	if ( null === $id || '' === $id ) { continue; }
	$plural = unq( $b, 'msgid_plural' );

	$targets = array();
	if ( null !== $plural ) {
		foreach ( array( 0, 1 ) as $i ) {
			$t = unq( $b, 'msgstr\[' . $i . '\]' );
			if ( null !== $t ) { $targets[] = array( 0 === $i ? $id : $plural, $t ); }
		}
	} else {
		$t = unq( $b, 'msgstr' );
		if ( null !== $t ) { $targets[] = array( $id, $t ); }
	}

	foreach ( $targets as $pair ) {
		list( $src, $dst ) = $pair;
		if ( '' === $dst ) { $empty++; continue; }
		$checked++;
		$a = ph( $src ); $c = ph( $dst );
		if ( $a !== $c ) {
			$bad++;
			echo "  PLACEHOLDER MISMATCH\n    en: $src\n       " . implode( ' ', $a ) . "\n    hi: $dst\n       " . implode( ' ', $c ) . "\n";
		}
	}
}
printf( "  %d translated strings checked, %d untranslated, %d placeholder problems\n", $checked, $empty, $bad );
exit( $bad > 0 ? 1 : 0 );
