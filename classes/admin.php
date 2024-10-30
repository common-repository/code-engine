<?php
class Meow_CDEGN_Admin {

	public $core;

	/** @var Meow_CDEGN_List_Table */
	public $list_table;

	protected $menu_slug = 'cdegn_settings';

	public function __construct( $core ) {
		$this->core = $core;

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( CDEGN_ENTRY ), array( $this, 'plugin_action_links' ), 10, 2 );
		}
	}

	public function app_menu() {
		// Needs to initialize the list before admin-header.php called.
		$this->list_table = new Meow_CDEGN_List_Table( $this->core );

		add_submenu_page( 'options-general.php', 'Code Engine', 'Code Engine', 'manage_options',
			$this->menu_slug, array( $this, 'admin_settings' ) );
	}

	public function admin_settings() {
		$this->list_table->prepare_items();
		include_once 'views/settings.php';
	}

	public function plugin_action_links( array $actions, string $plugin_file ): array {
		if ( plugin_basename( CDEGN_ENTRY ) !== $plugin_file ) {
			return $actions;
		}

		return array_merge(
			[
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( add_query_arg( 'page', $this->menu_slug, admin_url( 'options-general.php' ) ) ),
					esc_html__( 'Settings', 'code-engine' )
				),
			],
			$actions,
		);
	}
}

?>
