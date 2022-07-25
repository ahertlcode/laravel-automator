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
        /*foreach (scandir($src) as $file) {
            if (!is_readable($src . '/' . $file)) continue;
            (file_exists($src.'/'.$file) && !file_exists($dest)) ? mkdir('-p '.$dest, 0777, true) : null;
            if (is_dir($file) && ($file != '.') && ($file != '..') && !file_exists($dest.'/'.$file) )
            {
                echo 'does not exists... creating it..'."\n";
                mkdir('-p '.$dest . '/' . $file, 0777, true);
                self::xcopy($src . '/' . $file, $dest . '/' . $file);
            } else if(!is_dir($src.'/'.$file)) {
                copy($src . '/' . $file, $dest . '/' . $file);
            } else {}
        }*/

        if (is_dir($src)) {
            (!is_dir($dest)) ? mkdir($dest, 0777, true) : null;
            $all = glob("$src$ext");
            if (count($all)>0) {
                foreach($all as $a) {
                    echo $a.'-'.count($all)."\r\n";
                    $ff = basename($a);
                    (is_dir($a)) ? self::xcopy("$src$ff/", "$dest$ff/") : copy($a, "$dest$ff");
                }
            }
        }
    }
}
?>