<?php
/**
 * Plugin Name: Interpaste Admin
 */

defined( 'ABSPATH' ) || exit;

const IP_BRAND = 'Interpaste';
const IP_PATHS = array(
	'wp-admin'     => 'admin',
	'wp-login.php' => 'login',
	'wp-content'   => 'content',
	'wp-includes'  => 'includes',
);

function ip_hide_paths( $s ) {
	if ( ! is_string( $s ) ) {
		return $s;
	}
	return preg_replace_callback(
		'#(\\\\?/)(wp-admin|wp-content|wp-includes|wp-login\.php)(?![\w.-])#',
		function ( $m ) {
			return $m[1] . IP_PATHS[ $m[2] ];
		},
		$s
	);
}

if ( defined( 'IP_HIDE_PATHS' ) && IP_HIDE_PATHS ) {
	$ip_base = wp_parse_url( get_option( 'siteurl' ), PHP_URL_PATH );
	$ip_base = rtrim( (string) $ip_base, '/' ) . '/';
	defined( 'ADMIN_COOKIE_PATH' ) || define( 'ADMIN_COOKIE_PATH', $ip_base );
	defined( 'PLUGINS_COOKIE_PATH' ) || define( 'PLUGINS_COOKIE_PATH', $ip_base );

	$ip_path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	if ( preg_match( '#/(wp-admin|wp-login\.php)(/|$)#', $ip_path ) && ! preg_match( '#/wp-admin/(admin-ajax|admin-post)\.php$#', $ip_path ) ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}
	$_SERVER['REQUEST_URI'] = preg_replace(
		array( '#^' . preg_quote( $ip_base, '#' ) . 'admin(/|$)#', '#^' . preg_quote( $ip_base, '#' ) . 'login/?(?=\?|$)#' ),
		array( $ip_base . 'wp-admin$1', $ip_base . 'wp-login.php' ),
		$_SERVER['REQUEST_URI'],
		1
	);

	foreach ( array( 'admin_url', 'network_admin_url', 'site_url', 'includes_url', 'content_url', 'plugins_url', 'login_url', 'logout_url', 'lostpassword_url', 'register_url', 'wp_redirect', 'script_loader_src', 'style_loader_src' ) as $ip_hook ) {
		add_filter( $ip_hook, 'ip_hide_paths', 99 );
	}
	add_filter( 'rest_url_prefix', function () {
		return 'api';
	} );
	if ( ! defined( 'WP_CLI' ) ) {
		ob_start( 'ip_hide_paths' );
	}

	add_filter( 'mod_rewrite_rules', function ( $rules ) {
		$ours = "RewriteRule ^admin$ admin/ [R=301,L]\n"
			. "RewriteRule ^admin/(.*)$ wp-admin/$1 [L,QSA]\n"
			. "RewriteRule ^login/?$ wp-login.php [L,QSA]\n"
			. "RewriteRule ^content/(.*)$ wp-content/$1 [L]\n"
			. "RewriteRule ^includes/(.*)$ wp-includes/$1 [L]\n";
		return preg_replace( '#(RewriteBase [^\n]*\n)#', '$1' . $ours, $rules, 1 );
	} );
	if ( get_option( 'ip_rewrite_v' ) !== '1' ) {
		add_action( 'init', function () {
			flush_rewrite_rules();
			update_option( 'ip_rewrite_v', '1' );
		}, 99 );
	}
}

function ip_t( $en, $pl ) {
	return str_starts_with( get_user_locale(), 'pl' ) ? $pl : $en;
}

add_filter( 'pre_site_transient_available_translations', function () {
	return array(
		'pl_PL' => array(
			'language'     => 'pl_PL',
			'english_name' => 'Polish',
			'native_name'  => 'Polski',
			'iso'          => array( 'pl', 'pol' ),
			'version'      => '',
			'updated'      => '',
			'package'      => '',
			'strings'      => array( 'continue' => 'Kontynuuj' ),
		),
	);
} );

function ip_url( $file ) {
	return content_url( 'mu-plugins/interpaste/' . $file );
}

function ip_rebrand( $text ) {
	return str_replace( array( 'ClassicPress', 'WordPress' ), IP_BRAND, $text );
}
add_filter( 'gettext', 'ip_rebrand' );
add_filter( 'gettext_with_context', 'ip_rebrand' );
add_filter( 'ngettext', 'ip_rebrand' );

add_filter( 'admin_footer_text', '__return_empty_string' );
add_filter( 'update_footer', '__return_empty_string', 11 );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

add_action( 'admin_head', function () {
	$screen = get_current_screen();
	if ( $screen ) {
		$screen->remove_help_tabs();
	}
} );

add_action( 'admin_init', function () {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_action( 'network_admin_notices', 'update_nag', 3 );
	remove_action( 'admin_notices', 'maintenance_nag', 10 );
} );

add_action( 'admin_bar_menu', function ( $bar ) {
	foreach ( array( 'wp-logo', 'cp-logo', 'about', 'cp-debugging', 'updates' ) as $id ) {
		$bar->remove_node( $id );
	}
	$bar->add_node( array(
		'id'    => 'ip-brand',
		'title' => '<img src="' . esc_url( ip_url( 'logo.png' ) ) . '" alt=""><span>' . IP_BRAND . '</span>',
		'href'  => admin_url(),
		'meta'  => array( 'class' => 'ip-brand' ),
	) );
	$account = $bar->get_node( 'my-account' );
	if ( $account ) {
		$user = wp_get_current_user();
		$bar->add_node( array(
			'id'    => 'my-account',
			'title' => esc_html( $user->display_name ) . get_avatar( $user->ID, 28 ),
		) );
	}
}, 999 );

add_action( 'wp_install', function () {
	wp_delete_comment( 1, true );
	update_option( 'WPLANG', 'pl_PL' );
} );

add_filter( 'login_headerurl', 'home_url' );
add_filter( 'login_headertext', function () {
	return get_bloginfo( 'name' );
} );
add_action( 'login_enqueue_scripts', function () {
	wp_enqueue_style( 'ip-login', ip_url( 'login.css' ), array( 'login' ), filemtime( __DIR__ . '/interpaste/login.css' ) );
	wp_add_inline_style( 'ip-login', '.login h1 a{background-image:url(' . esc_url( ip_url( 'logo.png' ) ) . ')!important}' );
} );

// Lucide icons, ISC license.
function ip_icons() {
	return array(
		'dashboard'  => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
		'posts'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8M16 17H8M10 9H8"/>',
		'media'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
		'drafts'     => '<path d="M12 20h9"/><path d="M16.4 3.6a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4Z"/>',
		'pages'      => '<path d="M12.8 2.2a2 2 0 0 0-1.6 0L2.6 6.1a1 1 0 0 0 0 1.8l8.6 3.9a2 2 0 0 0 1.6 0l8.6-3.9a1 1 0 0 0 0-1.8Z"/><path d="m22 12.6-9.2 4.2a2 2 0 0 1-1.6 0L2 12.6"/><path d="m22 17.6-9.2 4.2a2 2 0 0 1-1.6 0L2 17.6"/>',
		'comments'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'appearance' => '<path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.3a1.8 1.8 0 0 0-1.4 2.8l.3.4a1.8 1.8 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/>',
		'plugins'    => '<path d="M12 22v-5M9 8V2M15 8V2"/><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"/>',
		'users'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'tools'      => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
		'settings'   => '<path d="M20 7h-9M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>',
	);
}

function ip_svg_uri( $inner ) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
	return 'url("data:image/svg+xml,' . rawurlencode( $svg ) . '")';
}

add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style( 'ip-admin-bar', ip_url( 'admin-bar.css' ), array( 'admin-bar' ), filemtime( __DIR__ . '/interpaste/admin-bar.css' ) );
	wp_enqueue_style( 'ip-admin', ip_url( 'admin.css' ), array( 'colors' ), filemtime( __DIR__ . '/interpaste/admin.css' ) );
	$css = '';
	foreach ( ip_icons() as $id => $inner ) {
		$css .= "#adminmenu #menu-$id .wp-menu-image:before{content:'';background:currentColor;display:block;width:20px;height:20px;padding:0;margin:10px auto;-webkit-mask:" . ip_svg_uri( $inner ) . ' center/20px no-repeat;mask:' . ip_svg_uri( $inner ) . ' center/20px no-repeat}';
	}
	wp_add_inline_style( 'ip-admin', $css );
}, 99 );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin_bar_showing() ) {
		wp_enqueue_style( 'ip-admin-bar', ip_url( 'admin-bar.css' ), array( 'admin-bar' ), filemtime( __DIR__ . '/interpaste/admin-bar.css' ) );
	}
} );

add_action( 'wp_dashboard_setup', function () {
	global $wp_meta_boxes;
	$wp_meta_boxes['dashboard'] = array();
	wp_add_dashboard_widget( 'ip_overview', ip_t( 'Overview', 'Przegląd' ), 'ip_overview' );
}, 999 );
add_filter( 'get_user_option_screen_layout_dashboard', function () {
	return 1;
} );

function ip_recent( $type ) {
	$q = new WP_Query( array(
		'post_type'      => $type,
		'post_status'    => 'publish',
		'date_query'     => array( array( 'after' => '30 days ago' ) ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	return $q->found_posts;
}

function ip_weekly( $from_where, $col ) {
	global $wpdb;
	$now  = current_time( 'mysql' );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT FLOOR(DATEDIFF(%s, $col) / 7) AS w, COUNT(*) AS n $from_where AND $col > DATE_SUB(%s, INTERVAL 49 DAY) GROUP BY w",
		$now,
		$now
	) );
	$out = array_fill( 0, 7, 0 );
	foreach ( $rows as $r ) {
		if ( $r->w >= 0 && $r->w < 7 ) {
			$out[ 6 - $r->w ] = (int) $r->n;
		}
	}
	return $out;
}

function ip_bars( $weeks ) {
	if ( ! $weeks ) {
		return '';
	}
	$max  = max( 1, max( $weeks ) );
	$html = '<span class="ip-bars" aria-hidden="true">';
	foreach ( $weeks as $i => $n ) {
		$html .= sprintf( '<i style="height:%dpx"%s></i>', 3 + round( 23 * $n / $max ), 6 === $i ? ' class="ip-now"' : '' );
	}
	return $html . '</span>';
}

function ip_overview() {
	$posts     = wp_count_posts();
	$pages     = wp_count_posts( 'page' );
	$comments  = wp_count_comments();
	$users     = count_users();
	$new_users = ( new WP_User_Query( array(
		'date_query'  => array( array( 'after' => '30 days ago' ) ),
		'number'      => 1,
		'fields'      => 'ID',
		'count_total' => true,
	) ) )->get_total();
	$month     = ip_t( 'in 30 days', 'w 30 dni' );
	global $wpdb;
	$posts_sql = function ( $type ) use ( $wpdb ) {
		return $wpdb->prepare( "FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish'", $type );
	};
	$stats     = array(
		array( 'posts', __( 'Posts' ), $posts->publish, 'edit.php', '#5b45e0', ip_weekly( $posts_sql( 'post' ), 'post_date' ), '+' . number_format_i18n( ip_recent( 'post' ) ) . ' ' . $month ),
		array( 'drafts', ip_t( 'Drafts', 'Szkice' ), $posts->draft, 'edit.php?post_status=draft', '#d97706', null, ip_t( 'waiting to publish', 'czeka na publikację' ) ),
		array( 'pages', __( 'Pages' ), $pages->publish, 'edit.php?post_type=page', '#2563eb', ip_weekly( $posts_sql( 'page' ), 'post_date' ), '+' . number_format_i18n( ip_recent( 'page' ) ) . ' ' . $month ),
		array( 'comments', __( 'Comments' ), $comments->approved, 'edit-comments.php', '#059669', ip_weekly( "FROM $wpdb->comments WHERE comment_approved = '1'", 'comment_date' ), number_format_i18n( $comments->moderated ) . ' ' . ip_t( 'awaiting moderation', 'do moderacji' ) ),
		array( 'users', __( 'Users' ), $users['total_users'], 'users.php', '#db2777', ip_weekly( "FROM $wpdb->users WHERE 1=1", 'user_registered' ), '+' . number_format_i18n( $new_users ) . ' ' . $month ),
	);
	echo '<div class="ip-stats">';
	foreach ( $stats as list( $icon, $label, $n, $url, $c, $weeks, $sub ) ) {
		printf(
			'<a class="ip-stat" href="%s" style="--c:%s"><span class="ip-h">%s<i class="ip-i ip-i-%s"></i></span><span class="ip-n"><b>%s</b>%s</span><small>%s</small></a>',
			esc_url( admin_url( $url ) ),
			esc_attr( $c ),
			esc_html( $label ),
			esc_attr( $icon ),
			esc_html( number_format_i18n( $n ) ),
			ip_bars( $weeks ),
			esc_html( $sub )
		);
	}
	echo '</div><div class="ip-cols"><section><h3>' . esc_html__( 'Recently Published' ) . '</h3>';
	$recent = get_posts( array( 'numberposts' => 6, 'post_status' => array( 'publish', 'draft', 'pending', 'future' ) ) );
	foreach ( $recent as $p ) {
		$status = get_post_status_object( $p->post_status );
		printf(
			'<a class="ip-row" href="%s"><span>%s</span><i class="ip-st ip-%s">%s</i><em>%s</em></a>',
			esc_url( get_edit_post_link( $p ) ),
			esc_html( get_the_title( $p ) ?: __( '(no title)' ) ),
			esc_attr( $p->post_status ),
			esc_html( $status ? $status->label : $p->post_status ),
			esc_html( get_the_date( 'j M', $p ) )
		);
	}
	echo '</section><section><h3>' . esc_html__( 'Recent Comments' ) . '</h3>';
	foreach ( get_comments( array( 'number' => 6, 'status' => 'approve' ) ) as $c ) {
		printf(
			'<a class="ip-row ip-c" href="%s"><span><b>%s</b> %s</span><em>%s</em></a>',
			esc_url( admin_url( 'comment.php?action=editcomment&c=' . $c->comment_ID ) ),
			esc_html( $c->comment_author ),
			esc_html( wp_trim_words( $c->comment_content, 10 ) ),
			esc_html( get_comment_date( 'j M', $c ) )
		);
	}
	echo '</section></div>';
	echo '<style>';
	foreach ( array( 'posts', 'drafts', 'pages', 'comments', 'users' ) as $id ) {
		echo '.ip-i-' . $id . ':after{-webkit-mask:' . ip_svg_uri( ip_icons()[ $id ] ) . ' center/20px no-repeat;mask:' . ip_svg_uri( ip_icons()[ $id ] ) . ' center/20px no-repeat}';
	}
	echo '</style>';
}
