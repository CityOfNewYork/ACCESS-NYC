<?php

class TimberImage_UnitTestCase extends Timber_UnitTestCase
{
    public $_files;

    protected function addFile($file)
    {
        $this->_files[] = $file;
    }

    public function set_up()
    {
        parent::set_up();
        $this->_files = [];
    }

    public function tear_down()
    {
        parent::tear_down();
        if (isset($this->_files) && is_array($this->_files)) {
            foreach ($this->_files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->_files = [];
        }
    }

    /* ----------------
        * Helper functions
        ---------------- */

    public static function replace_attachment($old_id, $new_id)
    {
        $uploadDir = wp_get_upload_dir();
        $newFile = $uploadDir['basedir'] . '/' . get_post_meta($new_id, '_wp_attached_file', true);
        $oldFile = $uploadDir['basedir'] . '/' . get_post_meta($old_id, '_wp_attached_file', true);
        if (!file_exists(dirname($oldFile))) {
            mkdir(dirname($oldFile), 0777, true);
        }
        copy($newFile, $oldFile);
        $meta = wp_generate_attachment_metadata($old_id, $oldFile);
        wp_update_attachment_metadata($old_id, $meta);
        wp_delete_post($new_id, true);
    }

    public static function copyTestAttachment($img = 'arch.jpg', $dest_name = null)
    {
        $upload_dir = wp_get_upload_dir();
        if (is_null($dest_name)) {
            $dest_name = $img;
        }
        $destination = $upload_dir['path'] . '/' . $dest_name;
        copy(__DIR__ . '/assets/' . $img, $destination);
        return $destination;
    }

    public static function getTestAttachmentURL($img = 'arch.jpg', $relative = false)
    {
        $upload_dir = wp_get_upload_dir();
        $result = $upload_dir['url'] . '/' . $img;
        if ($relative) {
            $result = str_replace(home_url(), '', $result);
        }
        return $result;
    }

    public static function is_connected()
    {
        $connected = @fsockopen("www.google.com", 80, $errno, $errstr, 3);
        if ($connected) {
            $is_conn = true; //action when connected
            fclose($connected);
        } else {
            $is_conn = false; //action in connection failure
        }
        return $is_conn;
    }
}
