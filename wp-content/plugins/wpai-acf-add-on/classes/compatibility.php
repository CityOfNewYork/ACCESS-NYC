<?php

class PMAI_Compatibility{

    /**
     * @param $file
     * @return mixed|string
     */
    public static function basename( $file ){
        return function_exists('wp_all_import_basename') ? wp_all_import_basename($file) : basename($file);
    }

}