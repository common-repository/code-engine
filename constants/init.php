<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDEGN_OPTIONS', [] );

function cdegn_enqueue_scripts(  ) {
    $screen = get_current_screen();
    if ( $screen->id === 'settings_page_cdegn_settings' ) {
        wp_register_script( 'code-engine', CDEGN_URL . 'js/code-engine.js', [], CDEGN_VERSION, true );
        wp_enqueue_script( 'code-engine' );
        wp_localize_script( 'code-engine', 'code_engine', [
            'rest_url' => rest_url( 'code-engine/v1' ),
        ]);
    }
}

add_action( 'admin_enqueue_scripts', 'cdegn_enqueue_scripts' );