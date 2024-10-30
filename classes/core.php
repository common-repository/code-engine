<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( CDEGN_PATH . '/constants/init.php' );
class Meow_CDEGN_Core
{
	public $admin = null;
	public $is_cli = false;
	public $site_url = null;
	private $option_name = 'cdegn_options';

	/** @var Meow_CDEGN_Snippets_Code_Snippets */
	protected $snippets_code_snippets = null;

	/** @var Meow_CDEGN_Snippets_Wpcode */
	protected $snippets_wpcode = null;

	public function __construct() {
		$this->site_url = get_site_url();
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		$this->snippets_code_snippets = new Meow_CDEGN_Snippets_Code_Snippets();
		$this->snippets_wpcode = new Meow_CDEGN_Snippets_Wpcode();
	}

	public function init() {
		// Part of the core, settings and stuff
		$this->admin = new Meow_CDEGN_Admin( $this );
		new Meow_CDEGN_Rest( $this );
	}

	#region Code Snippets

	/**
	 * Add the new option to the cdegn_functions option.
	 *
	 * @param array $new_option
	 * @return void
	 */
	public function add_cdegn_functions( array $new_option ): void {
		$cdegn_functions = get_option( 'cdegn_functions', [] );

		// Replace the option if it already exists, otherwise add it
		$index = $this->cdegn_functions_search( $new_option, $cdegn_functions );
		if ( $index === false ) {
			$cdegn_functions[] = $new_option;
		} else {
			$cdegn_functions[$index] = $new_option;
		}

		update_option( 'cdegn_functions', $cdegn_functions );
	}

	/**
	 * Remove the option in the cdegn_functions option.
	 *
	 * @param int $snippet_id
	 * @param string $snippet_src
	 * @return void
	 */
	public function remove_cdegn_functions( int $snippet_id, string $snippet_src ): void {
		$cdegn_functions = get_option( 'cdegn_functions', [] );

		// Remove the option if it exists, otherwise do nothing
		$index = $this->cdegn_functions_search( [ 'snippet_id' => $snippet_id, 'snippet_src' => $snippet_src ], $cdegn_functions );
		if ( $index === false ) {
			return;
		}

		unset( $cdegn_functions[$index] );
		$cdegn_functions = array_values( $cdegn_functions );
		update_option( 'cdegn_functions', $cdegn_functions );
	}

	/**
	 * Searches the cdegn_functions array for a given cdegn_function.
	 * and returns the first corresponding key if successful.
	 *
	 * @param array $needle_cdegn_function
	 * @param array $haystack_cdegn_functions
	 * @return int|false
	 */
	protected function cdegn_functions_search( array $needle_cdegn_function, array $haystack_cdegn_functions ) {
		$index = false;
		foreach ( $haystack_cdegn_functions as $key => $cdegn_function ) {
			if (
				$cdegn_function['snippet_id'] === $needle_cdegn_function['snippet_id']
				&& $cdegn_function['snippet_src'] === $needle_cdegn_function['snippet_src']
			) {
				$index = $key;
				break;
			}
		}
		return $index;
	}

	/**
	 * Analyze a code snippet.
	 *
	 * @param int $snippet_id
	 * @param string $snippet_src
	 * @return array
	 */
	public function analyze_code( int $snippet_id, string $snippet_src ): array {
		$this->check_snippet_src_availability( $snippet_src );

		$snippet = $this->get_snippet( $snippet_id, $snippet_src );
		return $this->parse_snippet_code( $snippet->code() );
	}

	/**
	 * Get a specific snippet.
	 *
	 * @param int $snippet_id
	 * @param string $snippet_src
	 * @return Meow_CDEGN_Models_Snippet
	 */
	public function get_snippet( int $snippet_id, string $snippet_src ): Meow_CDEGN_Models_Snippet {
		switch ( $snippet_src ) {
			case Meow_CDEGN_Snippets_Code_Snippets::$src:
				return $this->snippets_code_snippets->getById( $snippet_id );
			case Meow_CDEGN_Snippets_Wpcode::$src:
				return $this->snippets_wpcode->getById( $snippet_id );
		}
		throw new Exception( __( 'Unsupported source selected.', 'code-engine' ) );
	}

	/**
	 * Parse a snippet code.
	 *
	 * @param string $code
	 * @return array
	 */
	protected function parse_snippet_code( string $code ): array {
		$pattern = '/function\s+(\w+)\s*\((.*?)\)/';
		if ( preg_match_all( $pattern, $code, $matches ) !== 1 ) {
			throw new Exception(  __( 'Input must contain exactly one function definition.', 'code-engine') );
		}

		$function_name = $matches[1][0];
		$arg_string = $matches[2][0];

		$args = [];
		if ( $arg_string ) {
			$arg_parts = explode( ',', $arg_string );
			foreach ( $arg_parts as $arg_part ) {
				$arg_part = trim( $arg_part );
				if ( strpos( $arg_part, '=' ) !== false ) {
					list( $argName, $argDefault ) = explode( '=', $arg_part, 2 );
					$args[ trim( $argName )] = [
						'default' => trim( $argDefault )
					];
				} else {
					$args[ trim( $arg_part )] = [];
				}
			}
		}

		return [
			'function_name' => $function_name,
			'args' => $args
		];
	}

	/**
	 * Get the code quality of a code snippet via the AI Engine.
	 * Throws an exception if the AI Engine is not installed.
	 *
	 * @param int $snippet_id
	 * @param string $snippet_src
	 * @return string
	 * @throws Exception
	 */
	public function get_code_quality_via_ai_engine( int $snippet_id, string $snippet_src ): string {
		$this->check_snippet_src_availability( $snippet_src );

		global $mwai;
		if ( !isset( $mwai ) ) {
			throw new Exception( __( 'AI Engine is not installed. Please download it <a href="https://wordpress.org/plugins/ai-engine/" target="_blank">here</a>.', 'code-engine' ) );
		}

		$snippet = $this->get_snippet( $snippet_id, $snippet_src );
		$result = $mwai->simpleTextQuery( "Please check this code, in terms of quality, and security. List the potential issues. Each issue should be on one line, use a line return between each issue. Keep the issues and explanations short. Maximum of 10 issues. Here is the code:\n\n" . $snippet->code() );
		return nl2br( $result );
	}

	/**
	 * Check the snippet source availability.
	 * Throws an exception if the snippet source is not supported.
	 *
	 * @param string $snippet_src
	 * @return void
	 * @throws Exception
	 */
	protected function check_snippet_src_availability( string $snippet_src ): void {
		$support_srcs = [
			Meow_CDEGN_Snippets_Code_Snippets::$src,
			Meow_CDEGN_Snippets_Wpcode::$src,
		];
		if ( !in_array( $snippet_src, $support_srcs, true ) ) {
			throw new Exception( __( 'Only Code Snippets and WPCode are supported for now.', 'code-engine' ) );
		}
	}

	#endregion

	#region Capabilities

	public function can_access_settings() {
		return apply_filters( 'cdegn_allow_setup', current_user_can( 'manage_options' ) );
	}

	public function can_access_features() {
		return apply_filters( 'cdegn_allow_usage', current_user_can( 'administrator' ) );
	}

	#endregion

	#region Options

	public function get_option( $option, $default = null ) {
		$options = $this->get_all_options();
		return $options[$option] ?? $default;
	}

	public function get_all_options( $force = false ) {
		// We could cache options this way, but if we do, the apply_filters seems to be called too early.
		// That causes issues with filters used to modify the options dynamically (in AI Engine, for example).
		// if ( !$force && !is_null( $this->options ) ) {
		// 	return $this->options;
		// }
		$options = get_option( $this->option_name, [] );
		foreach ( CDEGN_OPTIONS as $key => $value ) {
			if ( !isset( $options[$key] ) ) {
				$options[$key] = $value;
			}
		}
		return $options;
	}

	public function update_options( $options ) {
		if ( !update_option( $this->option_name, $options, false ) ) {
			return false;
		}
		$options = $this->get_all_options( true );
		return $options;
	}

	public function update_option( $option, $value ) {
		$options = $this->get_all_options( true );
		$options[$option] = $value;
		return $this->update_options( $options );
	}

	public function reset_options() {
		delete_option( $this->option_name );
		return $this->get_all_options();
	}

	#endregion
}

?>
