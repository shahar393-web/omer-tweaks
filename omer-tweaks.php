<?php
/*
Plugin Name: Omer Tweaks
Plugin URI: https://github.com/shahar393-web/omer-tweaks
Description: מתגים להסתרת אלמנטים באתר של עומר (דשבורד, עמוד קורס, מסך שיעור). מזריק ישירות בחזית — לא תלוי בקאש של Elementor. העיצוב נשאר ב-Elementor; התוסף רק מסתיר/מציג.
Version: 1.0.2
Author: Shahar
Update URI: https://github.com/shahar393-web/omer-tweaks
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 *  עדכון אוטומטי מ-GitHub (בטוח — אם הספרייה חסרה, לא קורה כלום)
 * ============================================================ */
$omt_puc = plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $omt_puc ) ) {
	require_once $omt_puc;
	if ( class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		try {
			$omt_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/shahar393-web/omer-tweaks/',
				__FILE__,
				'omer-tweaks'
			);
			$omt_checker->setBranch( 'main' );
			$omt_api = $omt_checker->getVcsApi();
			if ( $omt_api && method_exists( $omt_api, 'enableReleaseAssets' ) ) {
				$omt_api->enableReleaseAssets();
			}
		} catch ( \Throwable $e ) {
			// אם משהו נכשל בבדיקת העדכונים — מתעלמים, שהאתר לא ייפול.
		}
	}
}

/* ============================================================
 *  רשימת המתגים: מפתח => [תווית, קבוצה, ה-CSS שמופעל כשמסמנים]
 *  כל מתג רק מסתיר אלמנט. אין כאן עיצוב.
 * ============================================================ */
function omt_toggles_list() {
	return array(
		// --- דשבורד ---
		'hide_completed' => array(
			'label' => 'להסתיר אריח "הושלם" (דשבורד)',
			'group' => 'דשבורד',
			'css'   => '.tutor-stat-card-completed{display:none!important;}',
		),
		'hide_time_spent' => array(
			'label' => 'להסתיר אריח "זמן שהוקדש" (דשבורד)',
			'group' => 'דשבורד',
			'css'   => '.tutor-stat-card-time-spent{display:none!important;}',
		),
		'hide_mobile_more' => array(
			'label' => 'להסתיר כפתור "עוד" בתפריט הנייד (דשבורד)',
			'group' => 'דשבורד',
			'css'   => '.tutor-dashboard-nav-mobile-list>li:last-child{display:none!important;}',
		),
		'hide_discussions' => array(
			'label' => 'להסתיר לשונית "דיונים" (תפריט הדשבורד)',
			'group' => 'דשבורד',
			'css'   => '.tutor-dashboard-menu-discussions{display:none!important;}',
		),
		'hide_wishlist' => array(
			'label' => 'להסתיר לשונית "רשימת משאלות"',
			'group' => 'דשבורד',
			'css'   => '.tutor-dashboard-menu-wishlist,.tutor-dashboard-menu-items a[href*="wishlist"]{display:none!important;}',
		),
		'hide_quiz_attempts' => array(
			'label' => 'להסתיר לשונית "ניסיונות שאלון"',
			'group' => 'דשבורד',
			'css'   => '.tutor-dashboard-menu-quiz-attempts,.tutor-dashboard-menu-items a[href*="quiz-attempts"]{display:none!important;}',
		),
		// --- עמוד קורס ---
		'hide_share' => array(
			'label' => 'להסתיר כפתור "שיתוף" (עמוד קורס)',
			'group' => 'עמוד קורס',
			'css'   => '.tutor-course-share-btn{display:none!important;}',
		),
		// --- מסך שיעור ---
		'hide_lesson_more' => array(
			'label' => 'להסתיר כפתור "עוד" (מסך שיעור, בנייד)',
			'group' => 'מסך שיעור',
			'css'   => '.tutor-learning-pages-item[x-ref="trigger"]{display:none!important;}',
		),
	);
}

/* ברירת מחדל בהפעלה ראשונה: מסתיר "הושלם", "זמן שהוקדש", "עוד" בנייד */
register_activation_hook( __FILE__, 'omt_activate' );
function omt_activate() {
	if ( get_option( 'omt_toggles', null ) === null ) {
		add_option( 'omt_toggles', array(
			'hide_completed'   => 1,
			'hide_time_spent'  => 1,
			'hide_mobile_more' => 1,
		) );
	}
}

/* בניית ה-CSS מהמתגים המסומנים */
function omt_build_css() {
	$opts    = (array) get_option( 'omt_toggles', array() );
	$toggles = omt_toggles_list();
	$css     = '';
	foreach ( $toggles as $key => $data ) {
		if ( ! empty( $opts[ $key ] ) ) {
			$css .= $data['css'] . "\n";
		}
	}
	// אם הוסתרו גם "הושלם" וגם "זמן שהוקדש" — מותחים את 2 האריחים שנשארו על פני השורה
	if ( ! empty( $opts['hide_completed'] ) && ! empty( $opts['hide_time_spent'] ) ) {
		$css .= ".tutor-stat-card-enrolled,.tutor-stat-card-active{grid-column:span 2!important;}\n";
	}
	// CSS נוסף להסתרות (אופציונלי, מתקדם)
	$extra = (string) get_option( 'omt_extra_css', '' );
	$extra = str_replace( array( '</style', '<script', '</script' ), '', $extra );
	if ( trim( $extra ) !== '' ) {
		$css .= "\n" . $extra . "\n";
	}
	return $css;
}

/* הזרקה בחזית בלבד */
add_action( 'wp_head', 'omt_print_css', 99 );
function omt_print_css() {
	if ( is_admin() ) { return; }
	$css = omt_build_css();
	if ( trim( $css ) !== '' ) {
		echo "\n<style id=\"omer-tweaks-css\">\n" . $css . "</style>\n";
	}
}

/* ============================================================
 *  עמוד ההגדרות בממשק הניהול
 * ============================================================ */
add_action( 'admin_menu', 'omt_menu' );
function omt_menu() {
	add_menu_page(
		'Omer Tweaks',
		'Omer Tweaks',
		'manage_options',
		'omer-tweaks',
		'omt_settings_page',
		'dashicons-visibility',
		80
	);
}

add_action( 'admin_init', 'omt_register' );
function omt_register() {
	register_setting( 'omt_group', 'omt_toggles', array(
		'type'              => 'array',
		'sanitize_callback' => 'omt_sanitize_toggles',
		'default'           => array(),
	) );
	register_setting( 'omt_group', 'omt_extra_css', array(
		'type'              => 'string',
		'sanitize_callback' => function ( $v ) { return (string) $v; },
		'default'           => '',
	) );
}

function omt_sanitize_toggles( $v ) {
	$out  = array();
	$keys = array_keys( omt_toggles_list() );
	foreach ( $keys as $k ) {
		$out[ $k ] = ! empty( $v[ $k ] ) ? 1 : 0;
	}
	return $out;
}

function omt_settings_page() {
	$opts    = (array) get_option( 'omt_toggles', array() );
	$toggles = omt_toggles_list();

	// קיבוץ לפי אזור
	$groups = array();
	foreach ( $toggles as $key => $data ) {
		$groups[ $data['group'] ][ $key ] = $data;
	}
	?>
	<div class="wrap omt-wrap">
		<div class="omt-header">
			<h1>Omer Tweaks</h1>
			<p>מתגים להסתרת אלמנטים באתר. סמני מה להסתיר ולחצי <strong>שמירה</strong> — השינוי נכנס מיד (רק לרענן את הדף).</p>
			<p class="omt-note">התוסף רק <strong>מסתיר/מציג</strong>. כל העיצוב נשאר ב-Elementor, כך שגם אם התוסף מכובה — העיצוב נשמר.</p>
		</div>

		<form method="post" action="options.php" class="omt-card">
			<?php settings_fields( 'omt_group' ); ?>

			<?php foreach ( $groups as $group_name => $items ) : ?>
				<div class="omt-section">
					<h2 class="omt-section-title"><?php echo esc_html( $group_name ); ?></h2>
					<?php foreach ( $items as $key => $data ) : ?>
						<label class="omt-row">
							<span class="omt-row-label"><?php echo esc_html( $data['label'] ); ?></span>
							<span class="omt-switch">
								<input type="checkbox" name="omt_toggles[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $opts[ $key ] ) ); ?> />
								<span class="omt-slider"></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<details class="omt-advanced">
				<summary>הסתרות נוספות (מתקדם — CSS)</summary>
				<p class="omt-note">מקום להוסיף כללי הסתרה נוספים (למשל <code>.selector{display:none!important;}</code>). לא חובה.</p>
				<textarea name="omt_extra_css" spellcheck="false" placeholder=".selector{display:none!important;}"><?php echo esc_textarea( get_option( 'omt_extra_css', '' ) ); ?></textarea>
			</details>

			<?php submit_button( 'שמירה', 'primary', 'submit', true, array( 'class' => 'omt-save' ) ); ?>
		</form>
	</div>

	<style>
		.omt-wrap{max-width:760px;direction:rtl;font-family:inherit;}
		.omt-wrap h1{font-size:26px;}
		.omt-header{background:#3C5343;color:#FBF8F1;border-radius:16px;padding:24px 26px;margin:16px 0 22px;box-shadow:0 6px 18px rgba(44,53,46,.12);}
		.omt-header h1{color:#FBF8F1;margin:0 0 8px;font-weight:700;}
		.omt-header p{margin:6px 0;font-size:14px;color:#EAE3D3;line-height:1.6;}
		.omt-header .omt-note{color:#C79A4B;font-weight:600;}
		.omt-card{background:#FBF8F1;border:1px solid #E7DFCB;border-radius:16px;padding:10px 26px 26px;box-shadow:0 2px 10px rgba(44,53,46,.06);}
		.omt-section{padding:18px 0;border-bottom:1px solid #ECE4D2;}
		.omt-section:last-of-type{border-bottom:0;}
		.omt-section-title{color:#3C5343;font-size:16px;font-weight:700;margin:0 0 6px;padding-bottom:8px;border-bottom:2px solid #C79A4B;display:inline-block;}
		.omt-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 4px;cursor:pointer;}
		.omt-row:hover{background:rgba(199,154,75,.06);border-radius:10px;}
		.omt-row-label{font-size:15px;color:#2C352E;}
		.omt-switch{position:relative;display:inline-block;width:52px;height:30px;flex:0 0 auto;}
		.omt-switch input{position:absolute;opacity:0;width:0;height:0;}
		.omt-slider{position:absolute;inset:0;background:#CFC7B4;border-radius:30px;transition:background .2s ease;}
		.omt-slider:before{content:"";position:absolute;top:3px;right:3px;width:24px;height:24px;background:#fff;border-radius:50%;transition:transform .2s ease;box-shadow:0 1px 3px rgba(0,0,0,.25);}
		.omt-switch input:checked + .omt-slider{background:#3C5343;}
		.omt-switch input:checked + .omt-slider:before{transform:translateX(-22px);}
		.omt-advanced{margin-top:20px;background:#F4EEDF;border:1px dashed #D9CFB6;border-radius:12px;padding:14px 18px;}
		.omt-advanced summary{cursor:pointer;font-weight:600;color:#3C5343;}
		.omt-advanced textarea{width:100%;height:150px;margin-top:10px;font-family:Consolas,Menlo,monospace;font-size:13px;direction:ltr;text-align:left;line-height:1.5;border-radius:10px;border:1px solid #D9CFB6;padding:10px;}
		.omt-note{color:#6b6455;font-size:13px;}
		.omt-save.button-primary{background:#3C5343!important;border-color:#3C5343!important;box-shadow:none!important;border-radius:10px!important;padding:6px 28px!important;height:auto!important;font-size:15px!important;margin-top:20px!important;}
		.omt-save.button-primary:hover{background:#2C352E!important;}
	</style>
	<?php
}
