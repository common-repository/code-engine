<?php
/**
 * A class for handling snippets of Code Snippets.
 */
class Meow_CDEGN_Snippets_Code_Snippets {

    /** @var string */
    public static $src = 'Code Snippets';

    /** @var object */
    private $wpdb;

    /** @var string */
    private $table_name = 'snippets';

    /** @var string */
    private $table_name_with_prefix = 'snippets';

    /** @var array<string> */
    private $scope = ['single-use'];

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name_with_prefix = $this->wpdb->prefix . $this->table_name;
    }

    /**
     * Get the specific snippet
     *
     * @param int $id
     * @return Meow_CDEGN_Models_Snippet|null
     */
    public function getById( int $id ) {
        $placeholder = implode( ',', array_fill( 0, count( $this->scope ), '%s' ) );

        $sql = $this->wpdb->prepare( "
            SELECT *
            FROM $this->table_name_with_prefix
            WHERE id = %d AND scope IN ( $placeholder )
        ", array_merge( [ $id ], $this->scope ) );

        $result = $this->wpdb->get_row( $sql );

        return $result
            ? new Meow_CDEGN_Models_Snippet(
                $result->id,
                Meow_CDEGN_Snippets_Code_Snippets::$src,
                $result->name,
                $result->description,
                $result->code,
                $this->get_edit_url( $result->id )
            )
            : null;
    }

    /**
     * Get a snippets
     *
     * @param string $cdegn_order
     * @param string $order_by
     * @param int $limit
     * @param int $offset
     * @return array<Meow_CDEGN_Models_Snippet>
     */
    public function get( string $cdegn_order, string $order_by, int $limit, int $offset ) {
        $placeholder = implode( ',', array_fill( 0, count( $this->scope ), '%s' ) );

        $sql = $this->wpdb->prepare( "
            SELECT id, name, description, code
            FROM $this->table_name_with_prefix
            WHERE scope IN ( $placeholder )
            ORDER BY %s %s
            LIMIT %d OFFSET %d
        ", array_merge( $this->scope, [ $order_by, $cdegn_order, $limit, $offset ] ) );

        $results = $this->wpdb->get_results( $sql );

        return array_map( function ($item) {
            return new Meow_CDEGN_Models_Snippet(
                $item->id,
                Meow_CDEGN_Snippets_Code_Snippets::$src,
                $item->name,
                $item->description,
                $item->code,
                $this->get_edit_url( $item->id )
            );
        }, $results );
    }

    /**
     * Get the total number of snippets
     *
     * @return int
     */
    public function get_total() {
        $placeholder = implode( ',', array_fill( 0, count( $this->scope ), '%s' ) );

        $sql = $this->wpdb->prepare( "
            SELECT COUNT(*)
            FROM $this->table_name_with_prefix
            WHERE scope IN ( $placeholder )
        ", $this->scope );

        $results = $this->wpdb->get_var( $sql );

        return (int)$results;
    }

    /**
     * Get the edit URL for a snippet
     *
     * @param int $id
     * @return string
     */
    protected function get_edit_url( int $id ): string {
        $base_url = 'admin.php?page=edit-snippet';

		// Check if the snippet is shared on the network
		$is_shared_network_snippet = false;
		$network = function_exists( 'is_network_admin' ) ? is_network_admin() : false;
		if ( $network ) {
			$shared_network_snippets = get_site_option( 'shared_network_snippets', [] );
			$is_shared_network_snippet = in_array( $id, $shared_network_snippets, true );
		}

		// Build the URL depends on the context
		$url = $is_shared_network_snippet ? network_admin_url( $base_url ) : self_admin_url( $base_url );

		// Add the ID
		return add_query_arg( 'id', absint( $id ), $url );
    }
}
