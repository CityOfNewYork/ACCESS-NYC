<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_logger
 *
 * Handles custom logging for Ninja Forms and Ninja Forms Extensions.
 *
 * PSR-3 and WordPress Compliant where applicable.
 */
final class NF_Database_Logger extends NF_Abstracts_Logger
{
    protected $_current = array();

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log( $level, $message, array $context = array() )
    {
        $message = $this->interpolate( $message, $context );

        // Create Log Object
        $log = Ninja_Forms()->form()->object()->get();
        $log->update_setting( 'type', 'log' )
            ->update_setting( 'level', $level )
            ->update_setting( 'message', $message );

        foreach ($context as $key => $value) {
            $log->update_setting($key, maybe_serialize($value));
        }

        // Add to Database
        $log->save();

        // Add to Current Property Array
        $this->_current[ $level ][] = $log;
    }

    /**
     * Get current logs for the request lifecycle
     *
     * @param string $level
     * @return array
     */
    public function get_current( $level = '' )
    {
        return ( $level ) ? $this->_current[ $level ] : $this->_current;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param $message
     * @param array $context
     * @return string
     */
    protected function interpolate( $message, array $context = array() )
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {

            if( is_array( $val ) ) continue;

            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
