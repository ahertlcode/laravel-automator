<?php

namespace Automator;

class Utilities { 

    public static function writetofile($content, $path, $filename, $filetype){
        (!file_exists($path)) ? mkdir($path, 0777, true) : "";
        $fp = fopen($path.$filename.".".$filetype, "w+");
        fwrite($fp, $content, strlen($content));
        fclose($fp);
    }

    public static function xcopy($src, $dest) {
        foreach (scandir($src) as $file) {
            if (!is_readable($src . '/' . $file)) continue;
            if (is_dir($src .'/' . $file) && ($file != '.') && ($file != '..') && !file_exists($dest.'/'.$file) )
            {
                mkdir($dest . '/' . $file, 0777, true);
                self::xcopy($src . '/' . $file, $dest . '/' . $file);
            } else if(!is_dir($src.'/'.$file)) {
                copy($src . '/' . $file, $dest . '/' . $file);
            } else {}
        }
    }
}
?>