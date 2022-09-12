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
        global $dbname, $app_dir;
        self::$dbh = new DbHandlers();
        if ($tables != null) self::$exemptedTables = $tables;
        if ($columns != null) self::$exemptedColumns = $columns;
        foreach(self::$dbh->show_dbTables() as $table){
            $table_name = $table["Tables_in_".$dbname];
            if (!in_array($table_name, self::$exemptedTables)) self::$tables[] = $table_name;
        }
        self::create_javascript(self::$tables);
        exec("npm install -g browserify");
        exec("cd $app_dir/resources && browserify script/* -o js/bundle.js");
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
        if (!in_array($tbl, self::$exemptedTables)) $struct = self::$dbh->tableDesc($tbl);
        foreach($struct as $field){
            if (!in_array($field['Field'], self::$exemptedColumns)) $form_fields[] = $field;
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
        $jsave = '    $scope.'.$tb.'_save = function() {'."\r\n";
        $jsave .='        $http({'."\r\n";
        $jsave .='            url: base_api_url+"/'.$tbs.'",'."\r\n";
        $jsave .='            method: "POST",'."\r\n";
        $jsave .='            data:this.'.$tb.','."\r\n";
        $jsave .='            headers:headers'."\n";
        $jsave .='        }).then((result) =>{'."\r\n";
        $jsave .='            $scope.info = result.data.message;'."\r\n";
        $jsave .='            setTimeout(() => {'."\r\n";
        $jsave .='                setTimeout(() => { ;'."\r\n";
        $jsave .='                  window.location.assign("#!/'.$tbs.'");'."\r\n";
        $jsave .='                },100)'."\r\n";
        $jsave .='                alert($scope.info)'."\r\n";
        $jsave .='            },500);'."\r\n";
        $jsave .='        }, function(error){'."\r\n";
        $jsave .='            $scope.error = error.statusText;'."\r\n";
        $jsave .='        });'."\r\n";
        $jsave .='    };'."\r\n\r\n";
        return $jsave;
    }

    private static function get_js_view_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $jsview = '    $scope.'.$tb.'_view_single = function(id){'."\r\n";
        $jsview .='        $http({'."\r\n";
        $jsview .='            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $jsview .='            method: "GET",'."\r\n";
        $jsview .='            headers:headers'."\n";
        $jsview .='        }).then((result) =>{'."\r\n";
        $jsview .='            $scope.'.$tb.' = result.data.message;'."\r\n";
        $jsview .='        }, function(error){'."\r\n";
        $jsview .='            $scope.error = error.statusText;'."\r\n";
        $jsview .='        });'."\r\n";
        $jsview .='    };'."\r\n\r\n"; 
        return $jsview;
    }

    private static function get_js_update_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $upstr = '    $scope.do_'.$tb.'_update = function(id){'."\r\n";
        $upstr .='        $http({'."\r\n";
        $upstr .='            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $upstr .='            method: "PUT",'."\r\n";
        $upstr .='            data:this.'.$tb.','."\r\n";
        $upstr .='            headers:headers'."\n";
        $upstr .='        }).then((result) =>{'."\r\n";
        $upstr .='            $scope.'.$tb.' = result.data.message;'."\r\n";
        $upstr .='        }, function(error){'."\r\n";
        $upstr .='            $scope.berror = error.statusText;'."\r\n";
        $upstr .='        });'."\r\n".'    }'."\r\n\r\n";

        return $upstr;
        
    }

    private static function get_js_delete_method($tbs) {
        $tb = Inflect::singularize($tbs);
        $delstr = '    $scope.'.$tb.'_delete = function(id){'."\r\n";
        $delstr .='            $http({'."\r\n";
        $delstr .='                url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $delstr .='                method: "DELETE",'."\r\n";
        $delstr .='                 headers:headers'."\n";
        $delstr .='            }).then((result) =>{'."\r\n";
        $delstr .='               $scope.'.$tb.' = result.data.message;'."\r\n";
        $delstr .='            }, function(error){'."\r\n";
        $delstr .='               $scope.error = error.statusText;'."\r\n";
        $delstr .='            });'."\r\n";
        $delstr .='    };'."\r\n\r\n"; 
        return $delstr;

    }

    private static function get_js_main_view($tbs) {
        $tb = Inflect::singularize($tbs);
        $mainstr  ='    $http({'."\r\n";
        $mainstr .='        url: base_api_url+"/'.$tbs.'",'."\r\n";
        $mainstr .='        method: "GET",'."\r\n";
        $mainstr .='        headers:headers'."\n";
        $mainstr .='    }).then((result) =>{'."\r\n";
        $mainstr .='        $scope.'.$tbs.' = result.data.data;'."\r\n";
        $mainstr .='    }, function(error){'."\r\n";
        $mainstr .='        $scope.error = error.statusText;'."\r\n";
        $mainstr .='    });'."\r\n\r\n";
        return $mainstr;
    }

    private static function do_sign_up_file($tb) {
        global $app_dir, $dbname;
        $jstr ="//javascript file for Sign Up using angularjs for data-binding.\r\n";
        $jstr .='app.controller ('."'".$tb."Ctrl'".', function($scope, $http) {'."\r\n\r\n";
        $jstr .='    this.'.$tb.' = { ';
        $jstr .= self::get_tab_string($tb);
        $jstr .='};'."\r\n\n\n";
        $jstr .= '    let headers = {'."\n";
        $jstr .= '        "Content-Type":"application/json",'."\n";
        $jstr .= '        "Accept":"application/json",'."\n";
        $jstr .= '    };'."\n\n";
        $jstr .= '    $scope.sign_up = function() {'."\r\n";
        $jstr .='        $http({'."\r\n";
        $jstr .='            url: base_api_url+"/auth/register",'."\r\n";
        $jstr .='            method: "POST",'."\r\n";
        $jstr .='            data:this.'.$tb.','."\r\n";
        $jstr .='        }).then((result) =>{'."\r\n";
        $jstr .='            $scope.info = result.message;'."\r\n";
        $jstr .='            window.location.assign("../resources/login.html");'."\r\n";
        $jstr .='        }, function(error){'."\r\n";
        $jstr .='            $scope.error = error.statusText;'."\r\n";
        $jstr .='        });'."\r\n";
        $jstr .='    };'."\r\n\r\n";
        $jstr .='  });'."\r\n";
        $file_dir = $app_dir."/resources/js";
        $js_file = $app_dir."/resources/js/signup.js";

        if(is_readable($js_file)){
            file_put_contents($js_file, $jstr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
    }


    private static function do_sign_in_file($tb) {
        global $app_dir, $dbname;
        $jstr ="//javascript file for Sign Up using angularjs for data-binding.\r\n";
        $jstr .='app.controller ('."'".$tb."Ctrl'".', function($scope, $http) {'."\r\n\r\n";
        $jstr .='    this.users = { email: "", password: "" }'."\r\n";
        $jstr .= '    let headers = {'."\n";
        $jstr .= '        "Content-Type":"application/json",'."\n";
        $jstr .= '        "Accept":"application/json",'."\n";
        $jstr .= '    };'."\n\n";

        $jstr .= '    $scope.sign_in = function() {'."\r\n";
        $jstr .='        $http({'."\r\n";
        $jstr .='            url: base_api_url+"/auth/login",'."\r\n";
        $jstr .='            method: "POST",'."\r\n";
        $jstr .='            data:this.users,'."\r\n";
        $jstr .='        }).then((result) =>{'."\r\n";
        $jstr .='            $scope.info = result.data.message;'."\r\n";
        $jstr .='            local_store("add", "'.$dbname.'User", { token: result.data.data.token});'."\r\n";
        $jstr .='            setTimeout(() => {'."\n";
        $jstr .='                window.location.replace("views/dashboard.html");'."\r\n";
        $jstr .='            }, 500);'."\n\r";
        $jstr .='        }, function(error){'."\r\n";
        $jstr .='            $scope.error = error.statusText;'."\r\n";
        $jstr .='        });'."\r\n";
        $jstr .='    };'."\r\n\r\n";
        $jstr .='  });'."\r\n";
        $file_dir = $app_dir."/resources/js";
        $js_file = $app_dir."/resources/js/signin.js";

        if(is_readable($js_file)){
            file_put_contents($js_file, $jstr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
    }

    
    private static function do_js_file($tb){
        global $app_dir, $dbname;
        $tbj = Inflect::singularize($tb);
        $jstr ="//javascript file for ".$tb." using angularjs for data-binding.\r\n";
        $jstr .='app.controller ('."'".$tb."Ctrl'".', function($scope, $http) {'."\r\n\r\n";
        $jstr .='    this.'.$tbj.' = { ';
        $jstr .= self::get_tab_string($tb);
        $jstr .='};'."\r\n\n\n";
        $jstr .='     $scope.addnew = "#!/'.$tbj.'";'."\n";
        $jstr .='     $scope.upload = "#!/'.$tbj.'_upload";'."\n";
        $jstr .='     $scope.currentTableColumn = [';
        $jstr .= implode(",", array_map( fn($field) => '"'.$field["Field"].'"' , self::get_table_columns($tb)));
        $jstr .='];'."\r\n";
        $jstr .='     $scope.exportCSV = () => {'."\r\n";
        $jstr .='         const EXCEL_EXTENSION = ".xlsx";'."\r\n";
        $jstr .='         const exportTable = document.getElementById("'.$tbj.'_table");'."\r\n";
        $jstr .='         const fileName = "'.$tbj.'";'."\r\n";
        $jstr .='         const ws  = XLSX.utils.table_to_sheet(exportTable);'."\r\n";
        $jstr .='         const workbook = XLSX.utils.book_new();'."\r\n";
        $jstr .='         XLSX.utils.book_append_sheet(workbook, ws, "Sheet1");'."\r\n";
        $jstr .='         XLSX.writeFile(workbook, `${fileName}${EXCEL_EXTENSION}`);'."\r\n";
        $jstr .='     }'."\r\n\n";
        $jstr .='     $scope.searchPage = () => {'."\r\n";
        $jstr .='         const crit = $("#searchTerm").val();'."\r\n";
        $jstr .='         const searchValue = $("#searchText").val().toUpperCase();'."\r\n";
        $jstr .='         const table = document.getElementById("'.$tbj.'_table");'."\r\n";
        $jstr .='         const tr = table.lastElementChild.children;'."\r\n";
        $jstr .='         if (crit != null && crit != -1) {'."\r\n";
        $jstr .='             for (const row of tr) {'."\r\n";
        $jstr .='                 let tdValue = row.cells[crit].textContent || row.cells[crit].innerText;'."\r\n";
        $jstr .='                 tdValue = tdValue.toUpperCase();'."\r\n";
        $jstr .='                 if (tdValue.indexOf(searchValue) > -1) {'."\r\n";
        $jstr .='                     $(row).show();'."\r\n";
        $jstr .='                 } else {'."\r\n";
        $jstr .='                     $(row).hide();';
        $jstr .='                 }'."\r\n";
        $jstr .='             }'."\r\n";
        $jstr .='         } else {'."\r\n";
        $jstr .='             window.alert("select a search criteria")'."\r\n";
        $jstr .='         }'."\r\n";
        $jstr .='     }'."\r\n\n";
        $jstr .= '   let user_token = local_store("get", "'.$dbname.'User").token;'."\n\n";
        $jstr .= '    let headers = {'."\n";
        $jstr .= '        "Content-Type":"application/json",'."\n";
        $jstr .= '        "Accept":"application/json",'."\n";
        $jstr .= '        "Authorization":"Bearer "+user_token'."\n";
        $jstr .= '    };'."\n\n";
        //$jstr .="    this.update = {col_name:'', col_value:''};\r\n\r\n";
        $jstr .= self::get_js_save_method($tb); //create and update method
        $jstr .= self::get_js_view_method($tb); //retrieve method
        $jstr .= self::get_js_update_method($tb); //update method trigger
        $jstr .= self::get_js_delete_method($tb); //delete method
        $jstr .= self::get_js_main_view($tb); //main model view
        $jstr .="});\r\n";
        $file_dir = $app_dir."/resources/script";
        $js_file = $app_dir."/resources/script/".$tbj.".js";
        if(is_readable($js_file)){
            file_put_contents($js_file, $jstr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
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
            if ($table == "users") {
                self::do_sign_up_file($table);
                self::do_sign_in_file($table);
            }
        }
    }
}
?>


