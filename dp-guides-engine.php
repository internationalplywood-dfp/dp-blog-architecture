<?php
/**
 * Plugin Name: DP Guides Engine
 * Description: Permanent blog architecture for discountplywood.com. (1) Auto-applies the branded guide template to every Post in the Plywood Guides category. (2) Renders the /blog/ hub grid dynamically from BOTH legacy child Pages of 145 and Plywood Guides Posts, so new articles appear with no manual hub edit. (3) Disables comments on guides.
 * Version: 1.0
 * Author: International Plywood & Lumber
 *
 * Design tokens and CSS mirror the existing hand-built guide pages
 * (.blog-page) and the existing hub cards (.bi-card) exactly.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DP_GUIDES_CAT_SLUG', 'plywood-guides' );
define( 'DP_GUIDES_HUB_ID', 145 );
define( 'DP_GUIDES_CF7_ID', 518 );
define( 'DP_GUIDES_PHONE_TEL', '+13058840860' );
define( 'DP_GUIDES_PHONE_DISPLAY', '(305) 884-0860' );

/* ============================================================
 * 0. Helpers
 * ============================================================ */

/** Is this post one of the guide articles? */
function dp_is_guide_post( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || 'post' !== $post->post_type ) { return false; }
	return has_term( DP_GUIDES_CAT_SLUG, 'category', $post );
}

/** Is this the singular guide article view? */
function dp_is_guide_single() {
	return ( is_singular( 'post' ) && dp_is_guide_post( get_queried_object_id() ) );
}

/* ============================================================
 * 1. Body classes — hide Kadence's default title hero, widen content
 * ============================================================ */

add_filter( 'body_class', function ( $classes ) {
	if ( ! dp_is_guide_single() ) { return $classes; }
	$classes[] = 'dp-guide';
	// Swap Kadence's narrow single-post width for the guide width.
	$classes = array_diff( $classes, array( 'content-width-narrow', 'content-title-style-normal' ) );
	$classes[] = 'content-width-normal';
	$classes[] = 'content-title-style-hide';
	return $classes;
}, 20 );

/* ============================================================
 * 2. Comments off on guides
 * ============================================================ */

add_filter( 'comments_open', function ( $open, $post_id ) {
	return dp_is_guide_post( $post_id ) ? false : $open;
}, 20, 2 );

add_filter( 'comments_array', function ( $comments, $post_id ) {
	return dp_is_guide_post( $post_id ) ? array() : $comments;
}, 20, 2 );

/* ============================================================
 * 3. The guide stylesheet (identical to the hand-built pages)
 * ============================================================ */

function dp_guides_css() {
	return <<<CSS
.blog-page{--nav:#1B3A6B;--org:#E8590C;--org-h:#C94A08;--wh:#FFFFFF;--off:#F5F6F8;--lt:#EAECF0;--txt:#1A1F2E;--txt-l:#4A5261;--fd:'Barlow Condensed','Arial Narrow',Arial,sans-serif;--fb:'Inter',Arial,sans-serif;--r:6px;font-family:var(--fb);color:var(--txt);font-size:16px;line-height:1.6;-webkit-font-smoothing:antialiased}
.blog-page *,.blog-page *::before,.blog-page *::after{box-sizing:border-box;margin:0;padding:0}
.blog-page .cnt{max-width:800px;margin:0 auto;padding:0 clamp(20px,4vw,48px)}
.blog-page .cnt--wide{max-width:1100px}
.blog-page .art-header{background:var(--nav);padding:clamp(60px,8vw,100px) 0 clamp(40px,5vw,60px)}
.blog-page .art-cat{display:inline-block;background:var(--org);color:#ffffff!important;font-family:var(--fd);font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:5px 14px;border-radius:4px;margin-bottom:16px}
.blog-page .art-header h1{font-family:var(--fd);font-size:clamp(28px,4vw,52px);font-weight:800;line-height:1.05;letter-spacing:.01em;text-transform:uppercase;color:#ffffff!important;margin-bottom:16px}
.blog-page .art-meta{color:#ffffff!important;font-size:14px}
.blog-page .art-body{padding:clamp(40px,6vw,80px) 0}
.blog-page h2{font-family:var(--fd);font-size:clamp(22px,3vw,32px);font-weight:700;text-transform:uppercase;color:#1B3A6B!important;margin:48px 0 16px;line-height:1.1;letter-spacing:.01em}
.blog-page h2:first-child{margin-top:0}
.blog-page h3{font-family:var(--fd);font-size:clamp(18px,2.5vw,24px);font-weight:700;color:#1B3A6B!important;margin:32px 0 12px}
.blog-page p{color:#4A5261!important;line-height:1.75;margin-bottom:1.2em;max-width:70ch}
.blog-page p:last-child{margin-bottom:0}
.blog-page ul,.blog-page ol{color:#4A5261!important;padding-left:24px;margin-bottom:1.2em}
.blog-page li{color:#4A5261!important;line-height:1.7;margin-bottom:6px}
.blog-page strong{color:#1A1F2E!important;font-weight:600}
.blog-page a{color:#E8590C!important}
.blog-page .callout{background:var(--off);border-left:4px solid var(--org);border-radius:0 var(--r) var(--r) 0;padding:20px 24px;margin:32px 0}
.blog-page .callout p{margin-bottom:0;color:#1A1F2E!important}
.blog-page figure{margin:0}
.blog-page table{width:100%;border-collapse:collapse;margin:32px 0}
.blog-page th{background:var(--nav);color:#ffffff!important;font-family:var(--fd);font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;text-align:left}
.blog-page td{padding:11px 14px;border-bottom:1px solid var(--lt);font-size:15px;color:#4A5261!important}
.blog-page tr:nth-child(even) td{background:var(--off)}
.blog-page .toc{background:var(--off);border:1px solid var(--lt);border-radius:var(--r);padding:24px 28px;margin:0 0 40px}
.blog-page .toc h4{font-family:var(--fd);font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#1B3A6B!important;margin-bottom:12px}
.blog-page .toc ol{padding-left:20px;margin:0}
.blog-page .toc li{margin-bottom:4px;font-size:14px}
.blog-page .toc a{color:#1B3A6B!important;text-decoration:none;font-weight:500}
.blog-page .toc a:hover{color:#E8590C!important;text-decoration:underline}
.blog-page .cta-inline{background:var(--org);padding:24px 28px;border-radius:var(--r);margin:32px 0;text-align:center}
.blog-page .cta-inline p{color:#ffffff!important;margin-bottom:8px;max-width:none}
.blog-page .cta-inline .btn{display:inline-block;background:#ffffff;color:#E8590C!important;font-family:var(--fd);font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:12px 28px;border-radius:var(--r);text-decoration:none!important;margin-top:8px}
.blog-page .related-cta{background:linear-gradient(135deg,#122850,#1B3A6B);padding:clamp(40px,5vw,64px) 0;margin-top:clamp(48px,6vw,80px)}
.blog-page .related-cta h2{color:#ffffff!important;margin-top:0}
.blog-page .related-cta p{color:rgba(255,255,255,.88)!important}
.blog-page .rel-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:28px}
.blog-page .rel-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:var(--r);padding:20px;text-align:center}
.blog-page .rel-card a{color:#ffffff!important;font-family:var(--fd);font-size:17px;font-weight:700;text-decoration:none!important;text-transform:uppercase;letter-spacing:.04em}
.blog-page .rel-card a:hover{color:#E8590C!important}
.blog-page .quote-sec{background:var(--off);padding:clamp(40px,5vw,64px) 0}
.blog-page .quote-sec h2{margin-top:0}
.blog-page .areas-sec{padding:clamp(32px,4vw,48px) 0}
.blog-page .area-pills{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.blog-page .area-pills a{display:inline-block;background:var(--nav);color:#ffffff!important;font-family:var(--fd);font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:9px 16px;border-radius:var(--r);text-decoration:none!important}
.blog-page .area-pills a:hover{background:#122850}
body.dp-guide .entry-hero,body.dp-guide .entry-hero-container,body.dp-guide .post-title,body.dp-guide .entry-header .entry-meta,body.dp-guide #comments,body.dp-guide .comments-area{display:none!important}
body.dp-guide .entry-content-wrap{padding:0!important}
body.dp-guide .content-area,body.dp-guide .site-container{max-width:100%!important}
@media(max-width:768px){.blog-page .cnt{padding:0 16px}.blog-page .art-header{padding:60px 16px 40px}.blog-page h2{margin-top:36px}.blog-page table{display:block;overflow-x:auto}}
CSS;
}

/* ============================================================
 * 4. Auto-apply the guide template to every guide Post
 * ============================================================ */

/**
 * Normalise generated markup:
 *  - <aside> becomes the branded .callout box
 *  - adds id anchors to every h2 so the TOC can link to them
 * Returns array( html, toc_items ).
 */
function dp_guides_prepare_body( $html ) {
	// <aside> -> .callout
	$html = preg_replace( '#<aside\b[^>]*>#i', '<div class="callout">', $html );
	$html = preg_replace( '#</aside>#i', '</div>', $html );

	// Add anchors to h2 headings and collect them for the TOC.
	$toc = array();
	$html = preg_replace_callback(
		'#<h2\b([^>]*)>(.*?)</h2>#is',
		function ( $m ) use ( &$toc ) {
			$attrs = $m[1];
			$inner = $m[2];
			$text  = trim( wp_strip_all_tags( $inner ) );
			if ( '' === $text ) { return $m[0]; }
			if ( preg_match( '#\bid=["\']([^"\']+)#i', $attrs, $idm ) ) {
				$slug = $idm[1];
			} else {
				$slug = 'sec-' . sanitize_title( $text );
				if ( strlen( $slug ) > 60 ) { $slug = substr( $slug, 0, 60 ); }
				$attrs .= ' id="' . esc_attr( $slug ) . '"';
			}
			$toc[] = array( 'slug' => $slug, 'text' => $text );
			return '<h2' . $attrs . '>' . $inner . '</h2>';
		},
		$html
	);

	return array( $html, $toc );
}

/** Insert the orange inline CTA after the Nth h2 section (mid-article). */
function dp_guides_insert_inline_cta( $html, $after_h2 = 2 ) {
	$cta = '<div class="cta-inline">'
		. '<p><strong style="color:#ffffff!important;">Need wholesale pricing on this material?</strong></p>'
		. '<p>Send thickness, quantity, jobsite ZIP, and your required delivery date. Hablamos Espa&ntilde;ol.</p>'
		. '<a class="btn" href="tel:' . DP_GUIDES_PHONE_TEL . '">Call ' . DP_GUIDES_PHONE_DISPLAY . '</a>'
		. '</div>';

	if ( ! preg_match_all( '#<h2\b#i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}
	// Place immediately before the (after_h2 + 1)th h2, if it exists.
	$idx = $after_h2;
	if ( ! isset( $m[0][ $idx ] ) ) { return $html; }
	$pos = $m[0][ $idx ][1];
	return substr( $html, 0, $pos ) . $cta . substr( $html, $pos );
}

/** Build the table-of-contents box. */
function dp_guides_toc_html( $toc ) {
	if ( count( $toc ) < 3 ) { return ''; }
	$out = '<div class="toc"><h4>Table of Contents</h4><ol>';
	foreach ( $toc as $item ) {
		$out .= '<li><a href="#' . esc_attr( $item['slug'] ) . '">' . esc_html( $item['text'] ) . '</a></li>';
	}
	return $out . '</ol></div>';
}

/** Three related guides, newest first, excluding the current one. */
function dp_guides_related_html( $current_id ) {
	$items = dp_guides_collect_items( 3, array( $current_id ) );
	if ( empty( $items ) ) { return ''; }
	$out = '<div class="rel-cards">';
	foreach ( $items as $it ) {
		$out .= '<div class="rel-card"><a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['title'] ) . '</a></div>';
	}
	return $out . '</div>';
}

/** Service-area pills — the standard nine. */
function dp_guides_areas_html() {
	$areas = array(
		'Miami, FL'        => '/miami-plywood/',
		'Doral'            => '/plywood-lumber-doral-fl/',
		'Hialeah'          => '/plywood-lumber-hialeah-fl/',
		'Miami Beach'      => '/plywood-lumber-miami-beach-fl/',
		'Fort Lauderdale'  => '/plywood-lumber-fort-lauderdale-fl/',
		'Coral Gables'     => '/plywood-lumber-coral-gables-fl/',
		'Homestead'        => '/plywood-lumber-homestead-fl/',
		'Broward County'   => '/broward-county-plywood-delivery/',
		'Miami Lakes, FL'  => '/miami-lakes-plywood/',
	);
	$out = '<div class="area-pills">';
	foreach ( $areas as $label => $url ) {
		$out .= '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return $out . '</div>';
}

add_filter( 'the_content', function ( $content ) {
	if ( ! is_main_query() || ! in_the_loop() || ! dp_is_guide_single() ) {
		return $content;
	}
	// Guard: if the article already ships its own .blog-page chrome, leave it alone.
	if ( false !== strpos( $content, 'class="blog-page"' ) || false !== strpos( $content, "class='blog-page'" ) ) {
		return $content;
	}

	$post = get_post();

	list( $body, $toc ) = dp_guides_prepare_body( $content );
	$body = dp_guides_insert_inline_cta( $body, 2 );

	$eyebrow  = 'Plywood Guides';
	$terms    = get_the_terms( $post, 'category' );
	if ( $terms && ! is_wp_error( $terms ) ) { $eyebrow = $terms[0]->name; }

	$meta = 'By International Plywood &amp; Lumber &nbsp;|&nbsp; '
		. esc_html( get_the_date( 'F j, Y', $post ) );
	$mod = get_the_modified_date( 'F j, Y', $post );
	if ( $mod && $mod !== get_the_date( 'F j, Y', $post ) ) {
		$meta .= ' &nbsp;|&nbsp; Updated ' . esc_html( $mod );
	}

	$cf7 = do_shortcode( '[contact-form-7 id="' . DP_GUIDES_CF7_ID . '" title="Quote Request Form"]' );

	$html  = '<div class="blog-page">';
	$html .= '<style id="dp-guides-styles">' . dp_guides_css() . '</style>';

	// Hero
	$html .= '<div class="art-header"><div class="cnt">'
		. '<div class="art-cat">' . esc_html( $eyebrow ) . '</div>'
		. '<h1>' . esc_html( get_the_title( $post ) ) . '</h1>'
		. '<p class="art-meta">' . $meta . '</p>'
		. '</div></div>';

	// Body + TOC
	$html .= '<div class="art-body"><div class="cnt">'
		. dp_guides_toc_html( $toc )
		. $body
		. '</div></div>';

	// Quote form
	$html .= '<div class="quote-sec"><div class="cnt">'
		. '<h2>Get a Quote From Our Team</h2>'
		. '<p>Wholesale pricing. Real inventory. Delivered to your site. Trade professionals only &mdash; Hablamos Espa&ntilde;ol.</p>'
		. $cf7
		. '</div></div>';

	// Related guides band
	$related = dp_guides_related_html( $post->ID );
	if ( $related ) {
		$html .= '<div class="related-cta"><div class="cnt cnt--wide">'
			. '<h2>More Guides for Miami Contractors</h2>'
			. '<p>Specification help, grade comparisons, and buying guides from a wholesale supplier since 1983.</p>'
			. $related
			. '</div></div>';
	}

	// Service areas
	$html .= '<div class="areas-sec"><div class="cnt cnt--wide">'
		. '<h2>Delivery &amp; Service Areas</h2>'
		. dp_guides_areas_html()
		. '</div></div>';

	$html .= '</div>'; // .blog-page

	return $html;
}, 9 );

/* ============================================================
 * 5. Dynamic hub grid — [dp_guides_grid]
 *    Merges legacy child Pages of 145 with Plywood Guides Posts.
 * ============================================================ */

/**
 * Collect every guide, newest first.
 * @param int   $limit   0 = all
 * @param array $exclude post IDs to skip
 */
function dp_guides_collect_items( $limit = 0, $exclude = array() ) {
	$items = array();

	// (a) Guide Posts in the category.
	$posts = get_posts( array(
		'post_type'          => 'post',
		'post_status'        => 'publish',
		'category_name'      => DP_GUIDES_CAT_SLUG,
		'numberposts'        => -1,
		'orderby'            => 'date',
		'order'              => 'DESC',
		'suppress_filters'   => false,
	) );

	// (b) Legacy guide Pages that are children of the hub.
	$pages = get_posts( array(
		'post_type'        => 'page',
		'post_status'      => 'publish',
		'post_parent'      => DP_GUIDES_HUB_ID,
		'numberposts'      => -1,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => false,
	) );

	foreach ( array_merge( $posts, $pages ) as $p ) {
		if ( in_array( (int) $p->ID, array_map( 'intval', $exclude ), true ) ) { continue; }

		$tag = get_post_meta( $p->ID, '_dp_guide_tag', true );
		if ( ! $tag ) {
			if ( 'post' === $p->post_type ) {
				$terms = get_the_terms( $p, 'category' );
				$tag   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Guide';
			} else {
				$tag = 'Guide';
			}
		}

		$excerpt = has_excerpt( $p )
			? wp_strip_all_tags( get_the_excerpt( $p ) )
			: wp_trim_words( wp_strip_all_tags( strip_shortcodes( $p->post_content ) ), 26, '&hellip;' );

		$items[] = array(
			'id'      => (int) $p->ID,
			'title'   => get_the_title( $p ),
			'url'     => get_permalink( $p ),
			'tag'     => $tag,
			'excerpt' => $excerpt,
			'ts'      => (int) get_post_time( 'U', true, $p ),
		);
	}

	usort( $items, function ( $a, $b ) {
		if ( $a['ts'] === $b['ts'] ) { return 0; }
		return ( $a['ts'] < $b['ts'] ) ? 1 : -1;
	} );

	if ( $limit > 0 ) { $items = array_slice( $items, 0, $limit ); }
	return $items;
}

function dp_guides_grid_css() {
	return <<<CSS
.bi-dyn{--nav:#1B3A6B;--org:#E8590C;--off:#F5F6F8;--lt:#EAECF0;--txt-l:#4A5261;--fd:'Barlow Condensed','Arial Narrow',Arial,sans-serif;--fb:'Inter',Arial,sans-serif;--r:6px;font-family:var(--fb)}
.bi-dyn .bi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.bi-dyn .bi-card{background:#ffffff;border:1px solid var(--lt);border-top:4px solid var(--org);border-radius:var(--r);padding:24px;display:flex;flex-direction:column}
.bi-dyn .bi-tag{display:inline-block;align-self:flex-start;background:var(--nav);color:#ffffff!important;font-family:var(--fd);font-size:12px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;padding:4px 12px;border-radius:4px;margin-bottom:12px}
.bi-dyn .bi-card h3{font-family:var(--fd);font-size:21px;font-weight:700;line-height:1.2;margin:0 0 10px;text-transform:none}
.bi-dyn .bi-card h3 a{color:#1B3A6B!important;text-decoration:none!important}
.bi-dyn .bi-card h3 a:hover{color:#E8590C!important}
.bi-dyn .bi-card p{color:#4A5261!important;font-size:15px;line-height:1.65;margin:0 0 16px}
.bi-dyn .bi-read{margin-top:auto;font-family:var(--fd);font-size:15px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#E8590C!important;text-decoration:none!important}
.bi-dyn .bi-read:hover{text-decoration:underline!important}
.bi-dyn .bi-date{font-size:13px;color:#8B93A4!important;margin:0 0 8px}
@media(max-width:768px){.bi-dyn .bi-grid{grid-template-columns:1fr}}
CSS;
}

add_shortcode( 'dp_guides_grid', function ( $atts ) {
	$atts = shortcode_atts( array(
		'limit'   => 0,
		'exclude' => '',
	), $atts, 'dp_guides_grid' );

	$exclude = array_filter( array_map( 'intval', explode( ',', (string) $atts['exclude'] ) ) );
	$items   = dp_guides_collect_items( (int) $atts['limit'], $exclude );

	if ( empty( $items ) ) { return ''; }

	$out  = '<div class="bi-dyn"><style>' . dp_guides_grid_css() . '</style><div class="bi-grid">';
	foreach ( $items as $it ) {
		$out .= '<div class="bi-card">'
			. '<span class="bi-tag">' . esc_html( $it['tag'] ) . '</span>'
			. '<h3><a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['title'] ) . '</a></h3>'
			. '<p>' . esc_html( $it['excerpt'] ) . '</p>'
			. '<a class="bi-read" href="' . esc_url( $it['url'] ) . '">Read Guide &rarr;</a>'
			. '</div>';
	}
	return $out . '</div></div>';
} );

/* ============================================================
 * 6. Cache invalidation — hub must refresh when a guide publishes
 * ============================================================ */

add_action( 'save_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || ! dp_is_guide_post( $post ) ) { return; }
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	} elseif ( function_exists( 'w3tc_pgcache_flush' ) ) {
		w3tc_pgcache_flush();
	}
}, 20, 2 );
