<?php

class Meow_CDEGN_Rest
{
	private $core = null;
	private $namespace = 'code-engine/v1';

	public function __construct( $core ) {
		if ( !current_user_can( 'administrator' ) ) {
			return;
		}
		$this->core = $core;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	public function rest_api_init() {
		try {
			// POST endpoints
			register_rest_route( $this->namespace, '/register', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_register' ),
			) );
			register_rest_route( $this->namespace, '/unregister', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_unregister' ),
			) );
			register_rest_route( $this->namespace, '/refresh', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_refresh' ),
			) );
			register_rest_route( $this->namespace, '/check-quality', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_check_quality' ),
			) );

			// GET endpoints
			register_rest_route( $this->namespace, '/functions', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_functions' ),
			) );
		}
		catch ( Exception $e ) {
			var_dump( $e );
		}
	}

	public function rest_register( $request ) {
		try	{
			$json = $request->get_json_params();
			$snippet_id = isset( $json['snippet_id'] ) ? (int)$json['snippet_id'] : null;
			$snippet_src = isset( $json['snippet_src'] ) ? (string)$json['snippet_src'] : null;
			if ( !$snippet_id || !$snippet_src ) {
				throw new Exception( __( 'Snippet ID and Snippet src are required.', 'code-engine') );
			}

			$snippet_information = $this->core->analyze_code( $snippet_id, $snippet_src );
			$new_option = [
				'snippet_id' => $snippet_id,
				'snippet_src' => $snippet_src,
				'name' => $snippet_information['function_name'],
				'desc' => '',
				'args' => $snippet_information['args'],
			];
			$this->core->add_cdegn_functions( $new_option );

			$cdegn_function = new Meow_CDEGN_Models_Cdegn_Function( $new_option );

			return new WP_REST_Response( [
				'message' => 'Registered successfully.',
				'overview' => $cdegn_function->overview(),
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	public function rest_unregister( $request ) {
		try	{
			$json = $request->get_json_params();
			$snippet_id = isset( $json['snippet_id'] ) ? (int)$json['snippet_id'] : null;
			$snippet_src = isset( $json['snippet_src'] ) ? (string)$json['snippet_src'] : null;
			if ( !$snippet_id || !$snippet_src ) {
				throw new Exception( __( 'Snippet ID and Snippet src are required.', 'code-engine') );
			}

			$this->core->remove_cdegn_functions( $snippet_id, $snippet_src );

			return new WP_REST_Response( [
				'message' => 'Unregistered successfully.',
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	public function rest_refresh( $request ) {
		try	{
			$json = $request->get_json_params();
			$snippet_id = isset( $json['snippet_id'] ) ? (int)$json['snippet_id'] : null;
			$snippet_src = isset( $json['snippet_src'] ) ? (string)$json['snippet_src'] : null;
			if ( !$snippet_id || !$snippet_src ) {
				throw new Exception( __( 'Snippet ID and Snippet src are required.', 'code-engine') );
			}

			$snippet_information = $this->core->analyze_code( $snippet_id, $snippet_src );
			$new_option = [
				'snippet_id' => $snippet_id,
				'snippet_src' => 'Code Snippets',
				'name' => $snippet_information['function_name'],
				'desc' => '',
				'args' => $snippet_information['args'],
			];
			$this->core->add_cdegn_functions( $new_option );

			$cdegn_function = new Meow_CDEGN_Models_Cdegn_Function( $new_option );

			return new WP_REST_Response( [
				'message' => 'Refreshed successfully.',
				'overview' => $cdegn_function->overview(),
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	public function rest_check_quality( $request ) {
		try	{
			$json = $request->get_json_params();
			$snippet_id = isset( $json['snippet_id'] ) ? (int)$json['snippet_id'] : null;
			$snippet_src = isset( $json['snippet_src'] ) ? (string)$json['snippet_src'] : null;
			if ( !$snippet_id || !$snippet_src ) {
				throw new Exception( __( 'Snippet ID and Snippet src are required.', 'code-engine') );
			}

			$detail = $this->core->get_code_quality_via_ai_engine( $snippet_id, $snippet_src );

			return new WP_REST_Response( [
				'success' => true,
				'detail' => $detail,
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	public function rest_functions() {
		try	{
			return new WP_REST_Response( [
				'functions' => array_map( function( $cdegn_function ) {
					return new Meow_CDEGN_Models_Cdegn_Function( $cdegn_function );
				}, get_option( 'cdegn_functions', [] ) ),
			], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'message' => $e->getMessage() ], 500 );
		}
	}
}
