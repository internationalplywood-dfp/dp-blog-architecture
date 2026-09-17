<?php
/**
 * QA helper — import the live Plywood Guides articles into staging so the
 * dp-guides-engine template and hub grid can be verified against real content.
 *
 * Run:
 *   wp --path=/var/www/staging --allow-root eval-file /tmp/qa_import_guides.php
 *
 * Remove them again:
 *   wp --path=/var/www/staging --allow-root eval-file /tmp/qa_import_guides.php cleanup
 *
 * Reads production's public REST API. Writes only to the environment WP-CLI
 * points at — always pass --path=/var/www/staging.
 */

if ( ! defined( 'ABSPATH' ) ) { exit( "Run through wp eval-file.\n" ); }

$SOURCE   = 'https://www.discountplywood.com/wp-json/wp/v2/posts?per_page=20&categories=16&_fields=id,slug,title,content,excerpt,date,modified,status';
$CAT_NAME = 'Plywood Guides';
$CAT_SLUG = 'plywood-guides';
$MARKER   = '_dp_qa_import';

$cleanup = in_array( 'cleanup', (array) ( $GLOBALS['argv'] ?? array() ), true );

/* ---------------- cleanup mode ---------------- */
if ( $cleanup ) {
	$found = get_posts( array(
		'post_type'   => 'post',
		'post_status' => 'any',
		'numberposts' => -1,
		'meta_key'    => $MARKER,
		'fields'      => 'ids',
	) );
	if ( ! $found ) {
		echo "No QA-imported posts found.\n";
		return;
	}
	foreach ( $found as $id ) {
		$slug = get_post_field( 'post_name', $id );
		wp_delete_post( $id, true );
		echo "deleted QA post {$id} ({$slug})\n";
	}
	echo "Removed " . count( $found ) . " QA post(s).\n";
	return;
}

/* ---------------- ensure the category exists ---------------- */
$term = get_term_by( 'slug', $CAT_SLUG, 'category' );
if ( ! $term ) {
	$new = wp_insert_term( $CAT_NAME, 'category', array( 'slug' => $CAT_SLUG ) );
	if ( is_wp_error( $new ) ) {
		echo "FAILED creating category: " . $new->get_error_message() . "\n";
		return;
	}
	$term_id = (int) $new['term_id'];
	echo "created category '{$CAT_NAME}' (id {$term_id})\n";
} else {
	$term_id = (int) $term->term_id;
	echo "category '{$CAT_NAME}' already present (id {$term_id})\n";
}

/* ---------------- fetch from production ---------------- */
echo "fetching source articles...\n";
$res = wp_remote_get( $SOURCE, array( 'timeout' => 30 ) );
if ( is_wp_error( $res ) ) {
	echo "FETCH FAILED: " . $res->get_error_message() . "\n";
	return;
}
$code = wp_remote_retrieve_response_code( $res );
if ( 200 !== (int) $code ) {
	echo "FETCH FAILED: HTTP {$code}\n";
	return;
}
$items = json_decode( wp_remote_retrieve_body( $res ), true );
if ( ! is_array( $items ) || ! $items ) {
	echo "FETCH FAILED: no articles decoded\n";
	return;
}
echo "source returned " . count( $items ) . " article(s)\n\n";

/* ---------------- import ---------------- */
$done = array();
foreach ( $items as $it ) {
	$slug = sanitize_title( $it['slug'] ?? '' );
	if ( ! $slug ) { continue; }

	$existing = get_page_by_path( $slug, OBJECT, 'post' );

	$data = array(
		'post_title'   => wp_specialchars_decode( $it['title']['rendered'] ?? $slug, ENT_QUOTES ),
		'post_name'    => $slug,
		'post_content' => (string) ( $it['content']['rendered'] ?? '' ),
		'post_excerpt' => wp_strip_all_tags( (string) ( $it['excerpt']['rendered'] ?? '' ) ),
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_date'    => $it['date'] ?? current_time( 'mysql' ),
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id = wp_update_post( $data, true );
		$verb = 'updated';
	} else {
		$id = wp_insert_post( $data, true );
		$verb = 'created';
	}

	if ( is_wp_error( $id ) ) {
		echo "FAILED {$slug}: " . $id->get_error_message() . "\n";
		continue;
	}

	wp_set_object_terms( $id, array( $term_id ), 'category', false );
	update_post_meta( $id, $MARKER, '1' );

	$url = get_permalink( $id );
	echo "{$verb} {$id}  {$url}\n";
	$done[] = $url;
}

echo "\n" . count( $done ) . " article(s) in place. Flushing cache.\n";
if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }

echo "\nHub: " . home_url( '/blog/' ) . "\n";
foreach ( $done as $u ) { echo "  {$u}\n"; }
echo "\nAll imported posts carry meta '{$MARKER}' so the cleanup mode can find them.\n";
