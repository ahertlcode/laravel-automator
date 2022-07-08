<?php
namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Utilities;

class AngularApp {
    private static $tables = array();
    private static $exemptedTables;
    private static $exemptedColumns;
    private static $dbh;
    public static function Automate($opt=null, $tables=null, $columns=null){
        global $dbname;
        self::$dbh = new DbHandlers();
        if ($tables != null) self::$exemptedTables = $tables;
        if ($columns != null) self::$exemptedColumns = $columns;
        foreach(self::$dbh->show_dbTables() as $table){
            self::$tables[] = $table["Tables_in_".$dbname];
        }
        self::create_javascript(self::$tables);
    }

    private static function exempted($tb){
        $found = 0;
        foreach(self::$exemptedTables as $t){
            if ($t == $tb){
                $found += 1;
            } else {
                $found += 0;
            }
        }
        if ($found > 0){
                return true;
            } else {
                return false;
        }
    }

    private static function get_table_columns($tbl){
        $form_fields = array();
        $struct = self::$dbh->tableDesc($tbl);
        foreach($struct as $field){
            if($field["Field"]==="id" || $field["Field"]==="status" || $field["Field"]==="created_by" || $field["Field"]==="user_id") continue;
            if($field["Field"]==="password_digest" || $field["Field"]==="password_reset_token" || $field["Field"]==="api_token" || $field["Field"]==="remember_token") continue;
            $form_fields[] = $field;
        }
        return $form_fields;
    }

    private static function get_tab_string($tb){
        $cols = self::get_table_columns($tb);
        $dstr = "";
        $iter = 0;
        foreach ($cols as $dcel){
            if($iter < sizeof($cols)-1){
            $dstr .= $dcel['Field'].":'', ";
            $iter++;
            } else { $dstr .= $dcel['Field'].":''"; }
        }
        return $dstr;
    }

    private static function get_js_save_method($tbs){
        $tb = Inflect::singularize($tbs);
        $jsave = '    $scope.'.$tb.'_save = function($scope, $http) {'."\r\n";
        $jsave .='        $http({'."\r\n";
        $jsave .='            url: base_api_url+"/'.$tbs.'",'."\r\n";
        $jsave .='            method: "POST",'."\r\n";
        $jsave .='            data:this.'.$tb."\r\n";
        $jsave .='        }).then((result) =>{'."\r\n";
        $jsave .='            $scope.info = result.message;'."\r\n";
        $jsave .='        }, function(error){'."\r\n";
        $jsave .='            $scope.error = error.statusText;'."\r\n";
        $jsave .='        });'."\r\n";
        $jsave .='    };'."\r\n\r\n";
        return $jsave;
    }

    private static function get_js_view_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $jsview = '    $scope.'.$tb.'_view_single = function($scope, $http, id){'."\r\n";
        $jsview .='        $http({'."\r\n";
        $jsview .='            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $jsview .='            method: "GET",'."\r\n";
        $jsview .='        }).then((result) =>{'."\r\n";
        $jsview .='            $scope.'.$tb.' = result.message;'."\r\n";
        $jsview .='        }, function(error){'."\r\n";
        $jsview .='            $scope.error = error.statusText;'."\r\n";
        $jsview .='        });'."\r\n";
        $jsview .='    };'."\r\n\r\n"; 
        return $jsview;
    }

    private static function get_js_update_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $upstr = '    $scope.do_'.$tb.'_update = function($scope, $http, id){'."\r\n";
        $upstr .='        $http({'."\r\n";
        $upstr .='            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $upstr .='            method: "PUT",'."\r\n";
        $upstr .='            data:this.'.$tb."\r\n";
        $upstr .='        }).then((result) =>{'."\r\n";
        $upstr .='            $scope.'.$tb.' = result.message;'."\r\n";
        $upstr .='        }, function(error){'."\r\n";
        $upstr .='            $scope.berror = error.statusText;'."\r\n";
        $upstr .='        });'."\r\n".'    }'."\r\n\r\n";

        return $upstr;
        
    }

    private static function get_js_delete_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $delstr = '    $scope.'.$tb.'_delete = function($scope, $http, id){'."\r\n";
        $delstr .='            $http({'."\r\n";
        $delstr .='                url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $delstr .='                method: "DELETE",'."\r\n";
        $delstr .='                data:{"method":"delete", "table":"'.$tbs.'", "col_name":coln, "col_value":colv }'."\r\n";
        $delstr .='            }).then((result) =>{'."\r\n";
        $delstr .='               $scope.'.$tb.' = result.message;'."\r\n";
        $delstr .='            }, function(error){'."\r\n";
        $delstr .='               $scope.error = error.statusText;'."\r\n";
        $delstr .='            });'."\r\n";
        $delstr .='    };'."\r\n\r\n"; 
        return $delstr;

    }

    private static function get_js_main_view($tbs) {
        $tb = Inflect::singularize($tbs);
        $mainstr  ='        $http({'."\r\n";
        $mainstr .='            url: base_api_url+"/'.$tbs.'",'."\r\n";
        $mainstr .='            method: "GET",'."\r\n";
        $mainstr .='        }).then((result) =>{'."\r\n";
        $mainstr .='            $scope.'.$tbs.' = result.message;'."\r\n";
        $mainstr .='        }, function(error){'."\r\n";
        $mainstr .='            $scope.error = error.statusText;'."\r\n";
        $mainstr .='        });'."\r\n\r\n";
        return $mainstr;
    }

    private static function do_js_file($tb){
        global $app_dir;
        $tbj = Inflect::singularize($tb);
        $jstr ="//javascript file for ".$tb." using angularjs for data-binding.\r\n";
        //$jstr .='var base_api_url = "http://localhost:8085/'.$jdb->dbname.'/api/";'."\r\n";
        $jstr .='var user = local_store({}, "'.$tb.'User", "get");'."\r\n";
        $jstr .="var app = angular.module('".$tb."View', []);\r\n\r\n";
        $jstr .='app.controller ('."'".$tb."Ctrl'".', function($scope, $http) {'."\r\n\r\n";
        $jstr .='    this.'.$tbj.' = { ';
        $jstr .= self::get_tab_string($tb);
        $jstr .='};'."\r\n\n\n";
        //$jstr .="    this.update = {col_name:'', col_value:''};\r\n\r\n";
        $jstr .= self::get_js_save_method($tb); //create and update method
        $jstr .= self::get_js_view_method($tb); //retrieve method
        $jstr .= self::get_js_update_method($tb); //update method trigger
        $jstr .= self::get_js_delete_method($tb); //delete method
        $jstr .= self::get_js_main_view($tb); //main model view
        $jstr .="});\r\n";
        $file_dir = $app_dir."/resources/js/$tb";
        $js_file = $app_dir."/resources/js/$tb/".$tbj.".js";
        if(is_readable($js_file)){
            file_put_contents($js_file, $jstr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/js/");
            $fp = fopen($js_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
        //utilities::writetofile($jstr, self::$base_dir."/assets/src/", $tbj, "js");
    }

    private static function create_javascript($tbls){
        foreach ($tbls as $table) {
            if (self::exempted($table) === false){
                self::do_js_file($table);
            }
        }
    }
}
?>


