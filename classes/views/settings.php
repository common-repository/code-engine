<?php
/**
 * Settings view
 */
// Load the jQuery UI dialog dependencies
if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_script( 'jquery-ui-dialog' );
wp_enqueue_style( 'wp-jquery-ui-dialog' );

wp_enqueue_script( 'code-engine' );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Code Engine Settings', 'code-engine' ); ?></h1>
    <h2 class="title"><?php esc_html_e( 'Functions', 'code-engine' ); ?></h2>
    <?php $this->list_table->views(); ?>
    <?php $this->list_table->display(); ?>
    <h2 class="title"><?php esc_html_e( 'Settings', 'code-engine' ); ?></h2>
    <input type="button" id="btn-display-functions-json" class="button button-primary" value="<?php esc_html_e( 'Display Functions JSON', 'code-engine' ); ?>" />
    <p><?php esc_html_e( 'This feature will log the content of the JSON in the Developer Tools Console.', 'code-engine' ); ?></p>
</div>

<div id="action-result-dialog" class="hidden" style="min-width: 400px; max-width:800px">
    <p></p>
</div>

<?php wp_nonce_field( 'wp_rest', '_wpnonce_rest', false ); ?>

