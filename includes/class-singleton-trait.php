<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

trait SingletonTrait {
    /**
     * The single instance of the class.
     *
     * @var SingletonTrait|null
     */
    private static $instances = array();

    public static function get_instance() {
        $cls = static::class;
        if ( !isset( self::$instances[ $cls ] ) ) {
            self::$instances[ $cls ] = new static();
        }
        return self::$instances[ $cls ];
    }

    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}
}
