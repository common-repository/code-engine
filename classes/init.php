<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'MeowPro_CDEGN_Core' ) && class_exists( 'Meow_CDEGN_Core' ) ) {

  function cdegn_thanks_admin_notices() {
    $message = esc_html__( 'Thanks for installing the Pro version of Code Engine :) However, the free version is still enabled. Please disable or uninstall it.', 'code-engine' );
    echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
  }

	add_action( 'admin_notices', 'cdegn_thanks_admin_notices' );
	return;
}

spl_autoload_register(function ( $class ) {
  $necessary = true;
  $file = null;
  if ( strpos( $class, 'Meow_CDEGN_Snippets' ) !== false ) {
    $file = CDEGN_PATH . '/classes/snippets/' . str_replace( 'meow_cdegn_snippets_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'Meow_CDEGN_Models' ) !== false ) {
    $file = CDEGN_PATH . '/classes/models/' . str_replace( 'meow_cdegn_models_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'Meow_CDEGN' ) !== false ) {
    $file = CDEGN_PATH . '/classes/' . str_replace( 'meow_cdegn_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowPro_CDEGN' ) !== false ) {
    $necessary = false;
    $file = CDEGN_PATH . '/premium/' . str_replace( 'meowpro_cdegn_', '', strtolower( $class ) ) . '.php';
  }
  if ( $file ) {
    if ( !$necessary && !file_exists( $file ) ) {
      return;
    }
    require( $file );
  }
});

global $cdegn_core;
$cdegn_core = new Meow_CDEGN_Core();

?>
