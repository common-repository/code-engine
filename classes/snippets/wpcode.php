<?php
/**
 * A class for handling snippets of WPCode.
 */
class Meow_CDEGN_Snippets_Wpcode {

    /** @var string */
    public static $src = 'WPCode';

    /** @var string */
    private $post_type = 'wpcode';

    /** @var string */
    private $taxonomy_code_type = 'wpcode_type';

    /** @var string */
    private $term_field = 'slug';

    /** @var array<string> */
    private $term_slugs = [ 'php' ];

    /** @var array<string> */
    private $post_status = [ 'publish' ];

    private $order_by_pairs = [
        'id' => 'ID',
        'name' => 'title',
    ];

    public function __construct(){
    }

    /**
     * Get the specific snippet
     *
     * @param int $id
     * @return Meow_CDEGN_Models_Snippet|null
     */
    public function getById( int $id )
    {
        $post = get_post($id);

        return $post
            ? new Meow_CDEGN_Models_Snippet(
                $post->ID,
                Meow_CDEGN_Snippets_Wpcode::$src,
                $post->post_title,
                $post->excerpt,
                $post->post_content,
                $this->get_edit_url( $post->ID )
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
        $order_by = $this->order_by_pairs[ $order_by ] ?? 'ID';
        $args = array(
            'post_type' => $this->post_type,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy_code_type,
                    'field'    => $this->term_field,
                    'terms'    => $this->term_slugs,
                ),
            ),
            'post_status' => $this->post_status,
            'order' => strtoupper( $cdegn_order ),
            'orderby' => $order_by,
            'posts_per_page' => $limit,
            'offset' => $offset,
        );
        $posts = get_posts( $args );

        return array_map( function( $post ) {
            return new Meow_CDEGN_Models_Snippet(
                $post->ID,
                Meow_CDEGN_Snippets_Wpcode::$src,
                $post->post_title,
                $post->excerpt,
                $post->post_content,
                $this->get_edit_url( $post->ID )
            );
        }, $posts );
    }

    /**
     * Get the total number of snippets
     *
     * @return int
     */
    public function get_total() {
        $args = array(
            'post_type' => $this->post_type,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy_code_type,
                    'field'    => $this->term_field,
                    'terms'    => $this->term_slugs,
                ),
            ),
            'post_status' => $this->post_status,
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        $posts = get_posts( $args );

        return count( $posts );
    }

    /**
     * Get the edit URL for a snippet
     *
     * @param int $id
     * @return string
     */
    protected function get_edit_url( int $id ): string {
        $base_url = 'admin.php?page=wpcode-snippet-manager';

        $url = self_admin_url( $base_url );

        return add_query_arg( 'snippet_id', absint( $id ), $url );
    }
}
