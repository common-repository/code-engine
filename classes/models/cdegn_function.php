<?php
/**
 * Model class for handling the option of cdegn_functions
 */
final class Meow_CDEGN_Models_Cdegn_Function implements JsonSerializable {

    /** @var int */
    private $snippet_id;

    /** @var string */
    private $snippet_src;

    /** @var string */
    private $name;

    /** @var string */
    private $desc;

    /** @var array */
    private $args;

    public function __construct( array $item ) {
        $this->snippet_id = $item['snippet_id'];
        $this->snippet_src = $item['snippet_src'];
        $this->name = $item['name'];
        $this->desc = $item['desc'];
        $this->args = array_map( function( $arg_variable, $arg_info ) {
            return new Meow_CDEGN_Models_Cdegn_Function_Arg( $arg_variable, $arg_info );
        }, array_keys( $item['args'] ), $item['args'] );
    }

    public function snippet_id(): int {
        return $this->snippet_id;
    }

    public function snippet_src(): string {
        return $this->snippet_src;
    }

    public function overview(): string {
        $args = array_map( function( $arg ) { return $arg->format(); }, $this->args );
        $overview = sprintf( 'function %s( %s )', $this->name, implode( ', ', $args ) );
        return $overview;
    }

    public function jsonSerialize(): mixed {
        return [
            'snippetId' => $this->snippet_id,
            'snippetSrc' => $this->snippet_src,
            'name' => $this->name,
            'desc' => $this->desc,
            'args' => $this->args,
        ];
    }
}
