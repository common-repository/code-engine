<?php
/**
 * Model class for handling the arg of cdegn_functions
 */
final class Meow_CDEGN_Models_Cdegn_Function_Arg implements JsonSerializable {

    /** @var string */
    private $name;

    /** @var string */
    private $desc;

    /** @var mixed */
    private $default;

    public function __construct( string $name, array $info ) {
        $this->name = $name;
        $this->desc = '';
        if ( isset( $info['default'] ) ) {
            $this->default = $info['default'] === null ? 'null' : $info['default'];
        } else {
            $this->default = null;
        }
    }

    public function format(): string {
        return $this->default === null ? $this->name : $this->name . ' = ' . $this->default;
    }

    public function jsonSerialize(): array {
        $json = [
            'name' => $this->name,
            'desc' => $this->desc,
        ];
        if ( $this->default === null ) {
            return $json;
        }

        $default = $this->default;
        // Updated to null if it's a string 'null'
        if ( $default === 'null' ) {
            $default = null;
        } elseif ( is_numeric( $default) ) {
            // Convert to number if it's a number
            $default = (int)$default;
        } elseif ( strtolower( $default ) === 'true' ) {
            $default = true;
        } elseif ( strtolower( $default ) === 'false' ) {
            $default = false;
        } elseif ( in_array( substr( $default, 0, 1 ), [ '"', "'" ], true ) && in_array( substr( $default, -1 ), [ '"', "'" ], true )) {
            // Remove quotes if it's a string
            $default = substr( $default, 1, -1 );
        }

        return array_merge( $json, [
            'default' => $default,
        ] );
    }
}
