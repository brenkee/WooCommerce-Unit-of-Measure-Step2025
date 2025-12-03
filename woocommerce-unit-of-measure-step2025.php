<?php
/**
 * Plugin Name: WooCommerce Unit of Measure Step 2025
 * Description: Termékenként állítható minimum, maximum és lépcsőköz alapú mennyiségi korlátozások tizedes pontossággal, Blocksy kompatibilitással.
 * Author: OpenAI Assistant
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: wc-uom-step2025
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

final class WC_UOM_Step2025 {
const OPTION_SETTINGS = 'wc_uom_step2025_settings';
const META_MIN        = '_wc_uom_min_qty';
const META_MAX        = '_wc_uom_max_qty';
const META_STEP       = '_wc_uom_step_qty';
const META_DECIMALS   = '_wc_uom_allow_decimals';
const META_PRECISION  = '_wc_uom_decimal_precision';

public static function init() {
add_action( 'plugins_loaded', array( __CLASS__, 'setup' ) );
}

public static function setup() {
// Admin product fields.
add_action( 'woocommerce_product_options_inventory_product_data', array( __CLASS__, 'render_admin_fields' ) );
add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_admin_fields' ) );

// Front-end quantity args and markup.
add_filter( 'woocommerce_quantity_input_args', array( __CLASS__, 'filter_quantity_args' ), 20, 2 );
add_filter( 'woocommerce_quantity_input', array( __CLASS__, 'inject_data_attributes' ), 20, 3 );

        // Adjust quantities on add to cart and cart updates.
        add_filter( 'woocommerce_add_to_cart_quantity', array( __CLASS__, 'adjust_add_to_cart_quantity' ), 20, 2 );
        add_filter( 'woocommerce_update_cart_validation', array( __CLASS__, 'validate_cart_update' ), 20, 4 );

        // Assets and notices.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // Output formatting.
        add_filter( 'woocommerce_email_order_item_quantity', array( __CLASS__, 'format_email_quantity' ), 10, 2 );

// Settings page.
add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

// Import / Export support.
add_filter( 'woocommerce_product_export_column_names', array( __CLASS__, 'register_export_columns' ) );
add_filter( 'woocommerce_product_export_product_default_columns', array( __CLASS__, 'register_export_columns' ) );
add_filter( 'woocommerce_product_export_product_column_wc_uom_min_qty', array( __CLASS__, 'export_product_min' ), 10, 2 );
add_filter( 'woocommerce_product_export_product_column_wc_uom_max_qty', array( __CLASS__, 'export_product_max' ), 10, 2 );
add_filter( 'woocommerce_product_export_product_column_wc_uom_step_qty', array( __CLASS__, 'export_product_step' ), 10, 2 );
add_filter( 'woocommerce_product_export_product_column_wc_uom_allow_decimals', array( __CLASS__, 'export_product_allow_decimals' ), 10, 2 );
add_filter( 'woocommerce_product_export_product_column_wc_uom_decimal_precision', array( __CLASS__, 'export_product_precision' ), 10, 2 );

add_filter( 'woocommerce_csv_product_import_mapping_options', array( __CLASS__, 'import_mapping_options' ) );
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( __CLASS__, 'import_mapping_defaults' ) );
add_filter( 'woocommerce_product_import_pre_insert_product_object', array( __CLASS__, 'import_set_product_data' ), 10, 2 );
}

public static function get_settings() {
$defaults = array(
'notice_text' => __( 'A megadott mennyiséget a legközelebbi érvényes értékre módosítottuk.', 'wc-uom-step2025' ),
);

$saved = get_option( self::OPTION_SETTINGS, array() );

return wp_parse_args( $saved, $defaults );
}

public static function render_admin_fields() {
echo '<div class="options_group">';

woocommerce_wp_text_input(
array(
'id'                => self::META_MIN,
'label'             => __( 'Minimum rendelési mennyiség', 'wc-uom-step2025' ),
'desc_tip'          => true,
'description'       => __( 'Üresen hagyva az alapérték 1, vagy a legkisebb érvényes lépcső.', 'wc-uom-step2025' ),
'type'              => 'number',
'custom_attributes' => array(
'min'  => '0',
'step' => 'any',
),
)
);

woocommerce_wp_text_input(
array(
'id'                => self::META_MAX,
'label'             => __( 'Maximum rendelési mennyiség', 'wc-uom-step2025' ),
'desc_tip'          => true,
'description'       => __( 'Üresen hagyva nincs korlátozás.', 'wc-uom-step2025' ),
'type'              => 'number',
'custom_attributes' => array(
'min'  => '0',
'step' => 'any',
),
)
);

woocommerce_wp_text_input(
array(
'id'                => self::META_STEP,
'label'             => __( 'Mennyiségi lépcsőfok', 'wc-uom-step2025' ),
'desc_tip'          => true,
'description'       => __( 'Lépcsőköz, amelyre a mennyiségek kerekítődnek. Alapértelmezetten 1.', 'wc-uom-step2025' ),
'type'              => 'number',
'custom_attributes' => array(
'min'  => '0',
'step' => 'any',
),
)
);

woocommerce_wp_checkbox(
array(
'id'          => self::META_DECIMALS,
'label'       => __( 'Tizedes mennyiségek engedélyezése', 'wc-uom-step2025' ),
'description' => __( 'Lehetővé teszi a tizedesjegyek megadását.', 'wc-uom-step2025' ),
)
);

woocommerce_wp_text_input(
array(
'id'                => self::META_PRECISION,
'label'             => __( 'Tizedesjegyek száma', 'wc-uom-step2025' ),
'desc_tip'          => true,
'description'       => __( 'Csak akkor aktív, ha a tizedes mennyiségek engedélyezve vannak.', 'wc-uom-step2025' ),
'type'              => 'number',
'custom_attributes' => array(
'placeholder' => '2',
'pattern'     => '\\d*',
'min'         => '0',
),
)
);

echo '</div>';
}

public static function save_admin_fields( $product ) {
$min            = isset( $_POST[ self::META_MIN ] ) ? wc_clean( wp_unslash( $_POST[ self::META_MIN ] ) ) : '';
$max            = isset( $_POST[ self::META_MAX ] ) ? wc_clean( wp_unslash( $_POST[ self::META_MAX ] ) ) : '';
$step           = isset( $_POST[ self::META_STEP ] ) ? wc_clean( wp_unslash( $_POST[ self::META_STEP ] ) ) : '';
$allow_decimals = isset( $_POST[ self::META_DECIMALS ] ) ? 'yes' : 'no';
$precision      = isset( $_POST[ self::META_PRECISION ] ) ? absint( $_POST[ self::META_PRECISION ] ) : '';

$product->update_meta_data( self::META_MIN, $min );
$product->update_meta_data( self::META_MAX, $max );
$product->update_meta_data( self::META_STEP, $step );
$product->update_meta_data( self::META_DECIMALS, $allow_decimals );
$product->update_meta_data( self::META_PRECISION, $precision );
}

public static function filter_quantity_args( $args, $product ) {
$rules = self::get_rules( $product );

$args['min_value'] = $rules['min'];
$args['max_value'] = $rules['max'];
$args['step']      = $rules['step'];

return $args;
}

public static function inject_data_attributes( $html, $product, $args ) {
$rules = self::get_rules( $product );

$data_attrs = sprintf(
' data-wcuom-step="%s" data-wcuom-min="%s" data-wcuom-max="%s" data-wcuom-precision="%d" data-wcuom-allow-decimal="%s" data-wcuom-product="%s"',
esc_attr( $rules['step'] ),
esc_attr( '' === $rules['min'] ? '' : $rules['min'] ),
esc_attr( '' === $rules['max'] ? '' : $rules['max'] ),
absint( $rules['precision'] ),
esc_attr( $rules['allow_decimals'] ? 'yes' : 'no' ),
esc_attr( $product->get_name() )
);

return preg_replace( '/<input([^>]+class="[^"]*qty[^"]*")/i', '<input$1' . $data_attrs, $html, 1 );
}

public static function adjust_add_to_cart_quantity( $quantity, $product_id ) {
$product = wc_get_product( $product_id );

if ( ! $product ) {
return $quantity;
}

$rules    = self::get_rules( $product );
$adjusted = self::normalize_quantity( $quantity, $rules, 'nearest' );

if ( $adjusted !== $quantity ) {
self::add_adjustment_notice( $product, $quantity, $adjusted );
}

return $adjusted;
}

public static function validate_cart_update( $passed, $cart_item_key, $values, $quantity ) {
$product = wc_get_product( $values['product_id'] );

if ( ! $product ) {
return $passed;
}

$rules    = self::get_rules( $product );
$adjusted = self::normalize_quantity( $quantity, $rules, 'nearest' );

if ( $adjusted !== $quantity ) {
WC()->cart->set_quantity( $cart_item_key, $adjusted, false );
self::add_adjustment_notice( $product, $quantity, $adjusted );
}

return $passed;
}

    public static function enqueue_assets() {
        if ( ! function_exists( 'is_woocommerce' ) ) {
            return;
        }

        if ( ! is_cart() && ! is_product() && ! is_shop() && ! is_product_taxonomy() && ! is_product_category() && ! is_product_tag() ) {
            return;
        }

        $settings = self::get_settings();

        wp_enqueue_style(
            'wc-uom-step2025',
            plugins_url( 'assets/css/wc-uom-step2025.css', __FILE__ ),
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'wc-uom-step2025',
            plugins_url( 'assets/js/wc-uom-step2025.js', __FILE__ ),
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'wc-uom-step2025',
            'WCUOMStep2025',
            array(
                'noticeText' => $settings['notice_text'],
            )
        );
    }

public static function register_settings_page() {
add_submenu_page(
'woocommerce',
__( 'Mennyiségi szabályok', 'wc-uom-step2025' ),
__( 'Mennyiségi szabályok', 'wc-uom-step2025' ),
'manage_woocommerce',
'wc-uom-step2025',
array( __CLASS__, 'render_settings_page' )
);
}

public static function register_settings() {
register_setting( 'wc_uom_step2025', self::OPTION_SETTINGS, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) ) );
}

public static function sanitize_settings( $value ) {
$value = is_array( $value ) ? $value : array();
$value['notice_text'] = isset( $value['notice_text'] ) ? sanitize_text_field( $value['notice_text'] ) : '';

return $value;
}

public static function render_settings_page() {
$settings = self::get_settings();
?>
<div class="wrap">
<h1><?php esc_html_e( 'Mennyiségi szabályok beállításai', 'wc-uom-step2025' ); ?></h1>
<form method="post" action="options.php">
<?php settings_fields( 'wc_uom_step2025' ); ?>
<table class="form-table" role="presentation">
<tr>
<th scope="row"><?php esc_html_e( 'Értesítés szövege', 'wc-uom-step2025' ); ?></th>
<td>
<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[notice_text]" value="<?php echo esc_attr( $settings['notice_text'] ); ?>" />
<p class="description"><?php esc_html_e( 'Üzenet, amikor a plugin módosítja a megadott mennyiséget. Elérhető helyettesítők: {product}, {requested}, {quantity}.', 'wc-uom-step2025' ); ?></p>
</td>
</tr>
</table>
<?php submit_button(); ?>
</form>
</div>
<?php
}

public static function register_export_columns( $columns ) {
$columns['wc_uom_min_qty']           = __( 'Minimum rendelés', 'wc-uom-step2025' );
$columns['wc_uom_max_qty']           = __( 'Maximum rendelés', 'wc-uom-step2025' );
$columns['wc_uom_step_qty']          = __( 'Lépcsőfok', 'wc-uom-step2025' );
$columns['wc_uom_allow_decimals']    = __( 'Tizedes engedélyezve', 'wc-uom-step2025' );
$columns['wc_uom_decimal_precision'] = __( 'Tizedesjegyek', 'wc-uom-step2025' );

return $columns;
}

public static function export_product_min( $value, $product ) {
return $product->get_meta( self::META_MIN );
}

public static function export_product_max( $value, $product ) {
return $product->get_meta( self::META_MAX );
}

public static function export_product_step( $value, $product ) {
return $product->get_meta( self::META_STEP );
}

public static function export_product_allow_decimals( $value, $product ) {
return $product->get_meta( self::META_DECIMALS );
}

public static function export_product_precision( $value, $product ) {
return $product->get_meta( self::META_PRECISION );
}

public static function import_mapping_options( $options ) {
$options['wc_uom_min_qty']           = __( 'Minimum rendelés (UOM)', 'wc-uom-step2025' );
$options['wc_uom_max_qty']           = __( 'Maximum rendelés (UOM)', 'wc-uom-step2025' );
$options['wc_uom_step_qty']          = __( 'Lépcsőfok (UOM)', 'wc-uom-step2025' );
$options['wc_uom_allow_decimals']    = __( 'Tizedes engedélyezve (UOM)', 'wc-uom-step2025' );
$options['wc_uom_decimal_precision'] = __( 'Tizedesjegyek (UOM)', 'wc-uom-step2025' );

return $options;
}

public static function import_mapping_defaults( $columns ) {
$columns['Minimum rendelés (UOM)']        = 'wc_uom_min_qty';
$columns['Maximum rendelés (UOM)']        = 'wc_uom_max_qty';
$columns['Lépcsőfok (UOM)']               = 'wc_uom_step_qty';
$columns['Tizedes engedélyezve (UOM)']    = 'wc_uom_allow_decimals';
$columns['Tizedesjegyek (UOM)']           = 'wc_uom_decimal_precision';

return $columns;
}

public static function import_set_product_data( $product, $data ) {
$map = array(
'wc_uom_min_qty'           => self::META_MIN,
'wc_uom_max_qty'           => self::META_MAX,
'wc_uom_step_qty'          => self::META_STEP,
'wc_uom_allow_decimals'    => self::META_DECIMALS,
'wc_uom_decimal_precision' => self::META_PRECISION,
);

foreach ( $map as $column => $meta_key ) {
if ( isset( $data[ $column ] ) ) {
$product->update_meta_data( $meta_key, $data[ $column ] );
}
}

return $product;
}

protected static function get_rules( $product ) {
$allow_decimals = 'yes' === $product->get_meta( self::META_DECIMALS );
$precision      = $allow_decimals ? absint( $product->get_meta( self::META_PRECISION ) ) : 0;
$precision      = $allow_decimals && 0 === $precision ? wc_get_price_decimals() : $precision;
$precision      = $allow_decimals ? $precision : 0;

$step = wc_format_decimal( $product->get_meta( self::META_STEP ) );
$step = $step > 0 ? $step : 1;

$min = wc_format_decimal( $product->get_meta( self::META_MIN ) );
$max = wc_format_decimal( $product->get_meta( self::META_MAX ) );

$min = $min > 0 ? $min : $step;
$min = $min > 0 ? $min : 1;

$max = $max > 0 ? $max : '';

if ( ! $allow_decimals ) {
$step      = max( 1, (int) round( $step ) );
$min       = max( 1, (int) round( $min ) );
$precision = 0;
}

return array(
'allow_decimals' => $allow_decimals,
'precision'      => $precision,
'min'            => $min,
'max'            => $max,
'step'           => $step,
);
}

    protected static function normalize_quantity( $quantity, $rules, $direction = 'nearest' ) {
        $step      = $rules['step'] > 0 ? $rules['step'] : 1;
        $min       = $rules['min'] > 0 ? $rules['min'] : $step;
        $precision = isset( $rules['precision'] ) ? absint( $rules['precision'] ) : 0;

$quantity = is_numeric( $quantity ) ? (float) $quantity : $min;

if ( $quantity < $min ) {
$quantity = $min;
}

$max = $rules['max'];

if ( '' !== $max && $quantity > $max ) {
$quantity = (float) $max;
}

$offset = $quantity - $min;
$steps  = $offset / $step;

if ( 'up' === $direction ) {
$steps = ceil( $steps - 1e-6 );
} elseif ( 'down' === $direction ) {
$steps = floor( $steps + 1e-6 );
} else {
$steps = round( $steps );
}

$quantity = $min + ( $steps * $step );

if ( '' !== $max && $quantity > $max ) {
$quantity = (float) $max;
}

if ( $quantity < $min ) {
$quantity = $min;
        }

        return round( $quantity, $precision );
    }

    public static function format_email_quantity( $qty_display, $item ) {
        if ( ! is_object( $item ) || ! method_exists( $item, 'get_product' ) ) {
            return $qty_display;
        }

        $product = $item->get_product();

        if ( ! $product ) {
            return $qty_display;
        }

        $rules     = self::get_rules( $product );
        $precision = $rules['allow_decimals'] ? $rules['precision'] : 0;

        return wc_format_decimal( $item->get_quantity(), $precision, false );
    }

    protected static function add_adjustment_notice( $product, $requested, $quantity ) {
        $settings = self::get_settings();
        $message  = strtr(
            $settings['notice_text'],
array(
'{product}'   => $product->get_name(),
'{requested}' => $requested,
'{quantity}'  => $quantity,
)
);

wc_add_notice( $message, 'notice' );
}
}

WC_UOM_Step2025::init();
