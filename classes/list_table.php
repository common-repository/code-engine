<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Meow_CDEGN_List_Table extends WP_List_Table {

    /** @var array<string> a source list of snippets. */
	protected $sources = [
		[ 'source' => 'code_snippets', 'label' => 'Code Snippets' ],
		[ 'source' => 'wpcode', 'label' => 'WPCode' ],
		[ 'source' => 'others', 'label' => 'Others' ],
	];

	/** @var string default source */
	protected $default_source = 'code_snippets';

	/** @var Meow_CDEGN_Snippets_Code_Snippets */
	protected $source_code_snippets = null;

	/** @var Meow_CDEGN_Snippets_Wpcode */
	protected $source_wpcode = null;

	/** @var array<array> */
	protected $cdegn_functions = [];

    public function __construct()
    {
		// Class Settings
		$this->source_code_snippets = new Meow_CDEGN_Snippets_Code_Snippets();
		$this->source_wpcode = new Meow_CDEGN_Snippets_Wpcode();

		// Set the vars from the request
		global $cdegn_source, $cdegn_page, $cdegn_order, $cdegn_orderBy;

		$cdegn_source = $this->default_source;
		$sources = array_column( $this->sources, 'source' );
		if ( isset( $_REQUEST['source'] ) && in_array( sanitize_key( $_REQUEST['source'] ), $sources, true ) ) {
			$cdegn_source = sanitize_key( $_REQUEST['source'] );
		}
		$cdegn_order = 'asc';
		$cdegn_orderBy = 'id';
		if ( ! empty( $_REQUEST['order'] ) ) {
			$cdegn_order = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		}
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$cdegn_orderBy = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
		}

		// Set the page
		$cdegn_page = $this->get_pagenum();

		// Set the filters
		$filters = [ 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'capital_P_dangit', 'wp_kses_post' ];
		foreach ( $filters as $filter ) {
			add_filter( 'code_engine/list_table/column_description', $filter );
		}

		// Set the hidden columns
		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ) );

		// Set the cdegn_functions option
		$this->cdegn_functions = array_map( function( $cdegn_function ) {
			return new Meow_CDEGN_Models_Cdegn_Function( $cdegn_function );
		}, get_option( 'cdegn_functions', [] ) );

		// Set the args
		// @see: https://developer.wordpress.org/reference/classes/wp_list_table/__construct/
		parent::__construct(
			[
				'plural' => 'snippets',
				'singular' => 'snippet',
			]
		);
    }

	/**
	 * Set the 'id' column as hidden by default.
	 *
	 * @param array<string> $hidden
	 * @return array<string>
	 */
	public function default_hidden_columns( array $hidden ): array {
		$hidden[] = 'id';
		return $hidden;
	}

	/**
	 * Set the 'name' column as the primary column.
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name(): string {
		return 'name';
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param Meow_CDEGN_Models_Snippet $item
	 * @param string $column_name
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_default( $item, $column_name ): string {
		$cdegn_function = $this->get_cdegn_function( $item->id(), $item->src() );
		$exists_cdegn_function = $cdegn_function !== null;

		switch ( $column_name ) {
			case 'id':
				return $item->id();

			case 'name':
				$url = $item->edit_url();
				$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), $item->name() );
				$function = sprintf( '<p class="function-overview">%s</p>', $cdegn_function?->overview() ?? '' );
				return $link . $function;

			case 'description':
				return apply_filters( 'code_engine/list_table/column_description', $item->description() );

			case 'actions':
				$display_none_style = 'style="display:none;"';
				$html = '<div style="display: flex; gap: 4px; align-items: center;">';
				$html .= '<input type="button" value="' . esc_html__( 'Register', 'code-engine' ) . '" class="button button-primary cdegn-register" data-id="'. $item->id() .'" data-src="' . $item->src() . '" ' . ($exists_cdegn_function ? $display_none_style : '') . '>';
				$html .= '<input type="button" value="' . esc_html__( 'Unregister', 'code-engine' ) . '" class="button button-primary cdegn-unregister" data-id="'. $item->id() .'" data-src="' . $item->src() . '" ' . (!$exists_cdegn_function ? $display_none_style : '') . '>';
				$html .= '<input type="button" value="' . esc_html__( 'Refresh', 'code-engine' ) . '" class="button button-secondary cdegn-refresh" data-id="'. $item->id() .'" data-src="' . $item->src() . '" ' . (!$exists_cdegn_function ? $display_none_style : '') . '>';
				$html .= '<input type="button" value="' . esc_html__( 'Check Quality', 'code-engine' ) . '" class="button button-secondary cdegn-check-quality" data-id="'. $item->id() .'" data-src="' . $item->src() . '" ' . (!$exists_cdegn_function ? $display_none_style : '') . '>';
				$html .= '<span class="spinner" data-id="'. $item->id() .'" data-src="' . $item->src() . '"></span>';
				$html .= '</div>';
				return $html;
		}
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Meow_CDEGN_Models_Snippet $item
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="ids[]" value="%s">', $item->id() );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		global $cdegn_totals, $cdegn_source, $cdegn_order, $cdegn_orderBy, $cdegn_page;

		// Set options related to pagination
		$per_page = 20;
		$limit = $per_page;
		$offset = ( $cdegn_page - 1 ) * $limit;

		// Get the items
		$code_snippets = $this->source_code_snippets->get( $cdegn_order, $cdegn_orderBy, $limit, $offset );
		$wpcode = $this->source_wpcode->get( $cdegn_order, $cdegn_orderBy, $limit, $offset );
		$others = [];
		switch ( $cdegn_source ) {
			case 'code_snippets':
				$this->items = $code_snippets;
				break;
			case 'wpcode':
				$this->items = $wpcode;
				break;
			case 'others':
				$this->items = $others;
				break;
		}

		// Set the totals
		$cdegn_totals = [
			'code_snippets' => $this->source_code_snippets->get_total(),
			'wpcode' => $this->source_wpcode->get_total(),
			'others' => count( $others ),
		];

		// Set the pagination
		$total_items = $cdegn_totals[ $cdegn_source ];
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Gets a list of columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		$columns = [
			'cb' => '<input type="checkbox">',
			'name' => __( 'Name', 'code-engine' ),
			'description' => __( 'Description', 'code-engine' ),
			'id' => __( 'ID', 'code-engine' ),
			'actions' => __( 'Actions', 'code-engine' ),
		];

		return $columns;
	}

	/**
	 * Gets a list of sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		$sortable_columns = [
			'id' => [ 'id', true ],
			'name' => 'name',
		];

		return $sortable_columns;
	}

	/**
	 * Gets the list of views available on this table.
	 *
	 * @return array
	 */
	public function get_views(): array {
		global $cdegn_totals, $cdegn_source;
		$source_links = parent::get_views();

		foreach ( $this->sources as $source_info ) {
			$type  = $source_info['source'];
			$label = $source_info['label'];

			if ( ! isset( $cdegn_totals[ $type ] ) ) {
				continue;
			}

			$count = $cdegn_totals[ $type ];

			$format_single = sprintf( esc_html__( '%1$s', 'code-engine' ) . ' <span class="count">(%2$s)</span>', $label, number_format_i18n( $count ) );
			$format_plural = sprintf( esc_html__( '%1$s', 'code-engine' ) . ' <span class="count">(%2$s)</span>', $label, number_format_i18n( $count ) );

			$format = _n( $format_single, $format_plural, $count, 'code-engine' );

			$url = esc_url( add_query_arg( 'source', $type ) );

			$class = $type === $cdegn_source ? ' class="current"' : '';

			$text = sprintf( $format, esc_html( $label ), number_format_i18n( $count ) );

			$source_links[ $type ] = sprintf( '<a href="%s"%s>%s</a>', $url, $class, $text );


		}

		return $source_links;
	}

	/**
	 * Gets the specific cdegn_function by the snippet ID and source.
	 * return null if not found.
	 *
	 * @param int $id
	 * @param string $src
	 * @return Meow_CDEGN_Models_Cdegn_Function|null
	 */
	protected function get_cdegn_function( int $id, string $src ): ?Meow_CDEGN_Models_Cdegn_Function {
		$cdegn_function = array_filter( $this->cdegn_functions, function( $cdegn_function ) use ( $id, $src ) {
			return $cdegn_function->snippet_id() === $id && $cdegn_function->snippet_src() === $src;
		} );
		return count( $cdegn_function ) > 0 ? array_values( $cdegn_function )[0] : null;
	}
}
