<?php
/**
 * Plugin Name:       Panchang & Festival Dates by Life Kundali
 * Plugin URI:        https://lifekundali.com/developers.html
 * Description:       Free daily panchang, city-accurate Hindu, Sikh and Jain festival dates, the Gurughar calendar (Sangrand, Puranmashi, Masya, Gurpurabs), a muhurta clock, a birth chart and kundali matching, as shortcodes and a widget. Dates are worked out for your own city, not India's.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Life Kundali (ITERNITY RESEARCH CORP.)
 * Author URI:        https://lifekundali.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lifekundali-panchang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LK_Panchang {

	const OPT = 'lk_panchang_options';

	/** kind => [embed file, default height, extra query] */
	private static $kinds = array(
		'panchang'    => array( 'panchang.html', 510, '' ),
		'festivals'   => array( 'festivals.html', 600, '' ),
		'gurdwara'    => array( 'festivals.html', 600, 'mode=sikh' ),
		'gurughar'    => array( 'gurughar.html', 640, '' ),
		'muhurta'     => array( 'muhurta.html', 520, '' ),
		'birth-chart' => array( 'birth-chart.html', 800, '' ),
		'matching'    => array( 'compatibility.html', 900, '' ),
	);

	public static function init() {
		add_shortcode( 'lk_panchang', function ( $a ) { return LK_Panchang::render( 'panchang', $a ); } );
		add_shortcode( 'lk_festivals', function ( $a ) { return LK_Panchang::render( 'festivals', $a ); } );
		add_shortcode( 'lk_gurdwara', function ( $a ) { return LK_Panchang::render( 'gurdwara', $a ); } );
		add_shortcode( 'lk_gurughar', function ( $a ) { return LK_Panchang::render( 'gurughar', $a ); } );
		add_shortcode( 'lk_muhurta', function ( $a ) { return LK_Panchang::render( 'muhurta', $a ); } );
		add_shortcode( 'lk_birth_chart', function ( $a ) { return LK_Panchang::render( 'birth-chart', $a ); } );
		add_shortcode( 'lk_matching', function ( $a ) { return LK_Panchang::render( 'matching', $a ); } );
		add_action( 'widgets_init', function () { register_widget( 'LK_Panchang_Widget' ); } );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
	}

	public static function opts() {
		return wp_parse_args( get_option( self::OPT, array() ), array( 'city' => 'Brampton', 'theme' => 'light', 'credit' => 0 ) );
	}

	public static function render( $kind, $atts = array() ) {
		if ( ! isset( self::$kinds[ $kind ] ) ) {
			return '';
		}
		$o    = self::opts();
		$atts = shortcode_atts( array( 'city' => $o['city'], 'theme' => $o['theme'], 'n' => '', 'height' => '' ), $atts );
		$k    = self::$kinds[ $kind ];
		$q    = array();
		if ( $atts['city'] ) {
			$q[] = 'city=' . rawurlencode( $atts['city'] );
		}
		if ( in_array( $atts['theme'], array( 'light', 'dark' ), true ) ) {
			$q[] = 'theme=' . $atts['theme'];
		}
		if ( $atts['n'] !== '' ) {
			$q[] = 'n=' . absint( $atts['n'] );
		}
		if ( $k[2] ) {
			$q[] = $k[2];
		}
		$src = 'https://lifekundali.com/embed/' . $k[0] . ( $q ? '?' . implode( '&', $q ) : '' );
		$h   = $atts['height'] !== '' ? absint( $atts['height'] ) : $k[1];
		$w   = $k[1] > 700 ? 560 : 520;
		$out = sprintf(
			'<iframe src="%s" title="%s" loading="lazy" style="width:100%%;max-width:%dpx;height:%dpx;border:0;display:block"></iframe>',
			esc_url( $src ),
			esc_attr( 'Life Kundali ' . $kind ),
			$w,
			$h
		);
		if ( ! empty( $o['credit'] ) ) {   // off by default; the site owner opts in on the settings page
			$out .= '<p style="font-size:13px;margin:4px 0"><a href="https://lifekundali.com/">' . esc_html__( 'Panchang and festival dates by Life Kundali', 'lifekundali-panchang' ) . '</a></p>';
		}
		return '<div class="lk-panchang lk-' . esc_attr( $kind ) . '">' . $out . '</div>';
	}

	public static function menu() {
		add_options_page( 'Panchang & Festival Dates', 'Panchang & Festivals', 'manage_options', 'lk-panchang', array( __CLASS__, 'page' ) );
	}

	public static function settings() {
		register_setting( 'lk_panchang', self::OPT, array( 'sanitize_callback' => array( __CLASS__, 'clean' ) ) );
	}

	public static function clean( $in ) {
		return array(
			'city'   => sanitize_text_field( isset( $in['city'] ) ? $in['city'] : 'Brampton' ),
			'theme'  => ( isset( $in['theme'] ) && 'dark' === $in['theme'] ) ? 'dark' : 'light',
			'credit' => empty( $in['credit'] ) ? 0 : 1,
		);
	}

	public static function page() {
		$o = self::opts();
		?>
		<div class="wrap">
			<h1>Panchang &amp; Festival Dates</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'lk_panchang' ); ?>
				<table class="form-table">
					<tr><th><label for="lk-city">Default city</label></th>
						<td><input id="lk-city" name="<?php echo esc_attr( self::OPT ); ?>[city]" value="<?php echo esc_attr( $o['city'] ); ?>" class="regular-text">
						<p class="description">For example Brampton, Surrey, New York, London, Sydney, Amritsar. The full list is at lifekundali.com/festivals/.</p></td></tr>
					<tr><th>Default theme</th>
						<td><select name="<?php echo esc_attr( self::OPT ); ?>[theme]">
							<option value="light" <?php selected( $o['theme'], 'light' ); ?>>Light</option>
							<option value="dark" <?php selected( $o['theme'], 'dark' ); ?>>Dark</option>
						</select></td></tr>
					<tr><th>Credit link</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPT ); ?>[credit]" value="1" <?php checked( $o['credit'], 1 ); ?>>
						Show a small "by Life Kundali" link under each widget (optional, off by default)</label></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<h2>Shortcodes</h2>
			<p><code>[lk_panchang]</code> <code>[lk_festivals n="6"]</code> <code>[lk_gurdwara]</code> <code>[lk_gurughar]</code> <code>[lk_muhurta]</code> <code>[lk_birth_chart]</code> <code>[lk_matching]</code></p>
			<p>Each takes <code>city="Surrey"</code> and <code>theme="dark"</code> to override the defaults above.</p>
		</div>
		<?php
	}
}

class LK_Panchang_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct( 'lk_panchang_widget', 'Panchang & Festival Dates', array( 'description' => 'Daily panchang, festivals, gurdwara dates or the Gurughar calendar for your city.' ) );
	}

	public function widget( $args, $instance ) {
		$kind = ! empty( $instance['kind'] ) ? $instance['kind'] : 'panchang';
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo LK_Panchang::render( $kind, empty( $instance['city'] ) ? array() : array( 'city' => $instance['city'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$kind  = isset( $instance['kind'] ) ? $instance['kind'] : 'panchang';
		$city  = isset( $instance['city'] ) ? $instance['city'] : '';
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		?>
		<p><label>Title <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"></label></p>
		<p><label>Show <select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'kind' ) ); ?>">
			<?php foreach ( array( 'panchang' => 'Daily panchang', 'festivals' => 'Coming festivals', 'gurdwara' => 'Gurdwara dates', 'gurughar' => 'Gurughar calendar', 'muhurta' => 'Muhurta clock' ) as $v => $l ) : ?>
				<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $kind, $v ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select></label></p>
		<p><label>City (blank = the default in Settings) <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'city' ) ); ?>" value="<?php echo esc_attr( $city ); ?>"></label></p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( $new['title'] ),
			'kind'  => sanitize_key( $new['kind'] ),
			'city'  => sanitize_text_field( $new['city'] ),
		);
	}
}

LK_Panchang::init();
