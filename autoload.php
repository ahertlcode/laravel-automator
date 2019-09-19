<?php

    class ClassAutoloader {
        public function __construct() {
            spl_autoload_register(array($this, 'loader'));
        }
        private function loader($className) {
            $filename = str_replace("\\","/",strtolower($className)) . '.php';
            if(is_readable($filename)){
                /*echo 'Trying to load ', $className, ' via ', __METHOD__, "()\n";
                echo $filename."\n";*/
                include $filename;
            }
        }
    }

    $autoloader = new ClassAutoloader();
