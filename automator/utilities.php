<?php

namespace Automator;

class Utilities { 

    public static function writetofile($content, $path, $filename, $filetype){
        (!file_exists($path)) ? mkdir($path, 0777, true) : "";
        $fp = fopen($path.$filename.".".$filetype, "w+");
        fwrite($fp, $content, strlen($content));
        fclose($fp);
    }

    public static function xcopy($src, $dest,$ext="*") {
        foreach (scandir($src) as $file) {

            $target = $dest.'/'.$file;
            $source = $src.'/'.$file;
           
            if (is_dir($source)) {
                if ($file != '.' && $file != '..') {
                    self::xcopy($source, $target);
                }
            } else {
                if (!file_exists($dest)) mkdir($dest, 0777, true);
                copy($source, $target);
            }

        }
    }
}
?>