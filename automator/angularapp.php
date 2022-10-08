<?php

namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Utilities;

class AngularApp
{
    private static $tables = array();
    private static $exemptedTables;
    private static $exemptedColumns;
    private static $dbh;
    public static function Automate($opt = null, $tables = null, $columns = null)
    {
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

    private static function exempted($tb)
    {
        $found = 0;
        foreach(self::$exemptedTables as $t){
            if ($t == $tb){
                $found += 1;
            } else {
                $found += 0;
            }
        }
        if ($found > 0) {
            return true;
        } else {
            return false;
        }
    }

    private static function get_table_columns($tbl)
    {
        $form_fields = array();
        if (!in_array($tbl, self::$exemptedTables)) $struct = self::$dbh->tableDesc($tbl);
        foreach ($struct as $field) {
            if (!in_array($field['Field'], self::$exemptedColumns)) $form_fields[] = $field;
        }
        return $form_fields;
    }

    private static function get_tab_string($tb)
    {
        $cols = self::get_table_columns($tb);
        $dstr = "";
        $iter = 0;
        foreach ($cols as $dcel){
            if($iter < sizeof($cols)-1){
                $dstr .= $dcel['Field'].":'', ";
                $iter++;
            } else {
                $dstr .= $dcel['Field'].":''";
            }
        }
        return $dstr;
    }

    private static function get_js_save_method($tbs){
        $tb = Inflect::singularize($tbs);
        $jsave = '    $scope.'.$tb.'_save = function() {'."\r\n";
        $jsave .= '        $http({'."\r\n";
        $jsave .= '            url: base_api_url+"/'.$tbs.'",'."\r\n";
        $jsave .= '            method: "POST",'."\r\n";
        $jsave .= '            data:this.'.$tb.','."\r\n";
        $jsave .= '            headers:headers'."\n";
        $jsave .= '        }).then((result) =>{'."\r\n";
        $jsave .= '            $scope.info = result.data.message;'."\r\n";
        $jsave .= '            setTimeout(() => {'."\r\n";
        $jsave .= '                setTimeout(() => { ;'."\r\n";
        $jsave .= '                  window.location.assign("#!/'.$tbs.'");'."\r\n";
        $jsave .= '                },100)'."\r\n";
        $jsave .= '                alert($scope.info)'."\r\n";
        $jsave .= '            },500);'."\r\n";
        $jsave .= '        }, function(error){'."\r\n";
        $jsave .= '            $scope.error = error.statusText;'."\r\n";
        $jsave .= '        });'."\r\n";
        $jsave .= '    };'."\r\n\r\n";
        return $jsave;
    }

    private static function get_js_view_method($tbs)
    {
        $tb = Inflect::singularize($tbs);
        $jsview = '    $scope.'.$tb.'_view_single = function(){'."\r\n";
        $jsview .= '        const id = $routeParams.id'."\r\n";
        $jsview .= '        $http({'."\r\n";
        $jsview .= '            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $jsview .= '            method: "GET",'."\r\n";
        $jsview .= '            headers:headers'."\n";
        $jsview .= '        }).then((result) =>{'."\r\n";
        $jsview .= '            $scope.'.$tb.' = result.data.data;'."\r\n";
        $jsview .= '        }, function(error){'."\r\n";
        $jsview .= '            $scope.error = error.statusText;'."\r\n";
        $jsview .= '        });'."\r\n";
        $jsview .= '    };'."\r\n\r\n";
        return $jsview;
    }

    private static function get_js_update_method($tbs)
    {
        $tb = Inflect::singularize($tbs);
        $upstr = '    $scope.do_'.$tb.'_update = function(id){'."\r\n";
        $upstr .= '        $http({'."\r\n";
        $upstr .= '            url: base_api_url+"/'.$tbs.'/"+id,'."\r\n";
        $upstr .= '            method: "PUT",'."\r\n";
        $upstr .= '            data:this.'.$tb.','."\r\n";
        $upstr .= '            headers:headers'."\n";
        $upstr .= '        }).then((result) =>{'."\r\n";
        $upstr .= '            $scope.'.$tb.' = result.data.message;'."\r\n";
        $upstr .= '        }, function(error){'."\r\n";
        $upstr .= '            $scope.berror = error.statusText;'."\r\n";
        $upstr .= '        });'."\r\n".'    }'."\r\n\r\n";

        return $upstr;
    }

    private static function get_js_upload_method($tbs){
        $tb = Inflect::singularize($tbs);
        $uplstr = '     $scope.'.$tb.'_upload = function(id){'."\r\n";
        $uplstr .= '         var xlsxflag = false'."\r\n";
        $uplstr .= '         if ($("#'.$tb.'file").val().toLowerCase().indexOf(".xlsx") > 0) {'."\r\n";
        $uplstr .= '             xlsxflag = true;'."\r\n";
        $uplstr .= '         }'."\r\n";
        $uplstr .= '         var reader = new FileReader();'."\r\n";
        $uplstr .= '         reader.onload = function (e) {'."\r\n";
        $uplstr .= '             var data = e.target.result;'."\r\n";
        $uplstr .= '             if (xlsxflag) {'."\r\n";
        $uplstr .= '                 var workbook = XLSX.read(data, { type: "binary" });'."\r\n";
        $uplstr .= '             } else {'."\r\n";
        $uplstr .= '                 var workbook = XLS.read(data, { type: "binary" });'."\r\n";
        $uplstr .= '             }'."\r\n";
        $uplstr .= '              var sheet_name_list = workbook.SheetNames;'."\r\n";
        $uplstr .= '              sheet_name_list.forEach(function (y) { //Iterate through all sheets'."\r\n";
        $uplstr .= '                  if (xlsxflag) {'."\r\n";
        $uplstr .= '                      this.' . $tb . ' = XLSX.utils.sheet_to_json(workbook.Sheets[y]);' . "\r\n";
        $uplstr .= '                  } else {' . "\r\n";
        $uplstr .= '                      this.' . $tb . ' = XLS.utils.sheet_to_row_object_array(workbook.Sheets[y]);' . "\r\n";
        $uplstr .= '                  }' . "\r\n";
        $uplstr .= '                  if (this.' . $tb . '.length > 0) {' . "\r\n";
        $uplstr .= '                      for (let i = 0; i<this.' . $tb . '.length; i++) {' . "\r\n";
        $uplstr .= '                          $http({' . "\r\n";
        $uplstr .= '                              url: base_api_url+"/' . $tbs . '",' . "\r\n";
        $uplstr .= '                              method: "POST",' . "\r\n";
        $uplstr .= '                              data:this.' . $tb . '[i],' . "\r\n";
        $uplstr .= '                              headers:headers' . "\r\n";
        $uplstr .= '                          }).then((result) =>{' . "\r\n";
        $uplstr .= '                              $scope.info = result.data.message;' . "\r\n";
        $uplstr .= '                           }, function(error){' . "\r\n";
        $uplstr .= '                               $scope.error = error.data.message;' . "\r\n";
        $uplstr .= '                           })' . "\r\n";
        $uplstr .= '                        }' . "\r\n";
        $uplstr .= '                              setTimeout(() => {' . "\r\n";
        $uplstr .= '                                  setTimeout(() => { ' . "\r\n";
        $uplstr .= '                                      window.location.assign("#!/' . $tbs . '");' . "\r\n";
        $uplstr .= '                                  },100)' . "\r\n";
        $uplstr .= '                                  alert($scope.info)' . "\r\n";
        $uplstr .= '                              },500);' . "\r\n";
        $uplstr .= '                    }' . "\r\n";
        $uplstr .= '              })' . "\r\n";
        $uplstr .= '          }' . "\r\n";
        $uplstr .= '          if (xlsxflag) {' . "\r\n";
        $uplstr .= '              reader.readAsArrayBuffer($("#' . $tb . 'file")[0].files[0]);' . "\r\n";
        $uplstr .= '          } else {' . "\r\n";
        $uplstr .= '              reader.readAsBinaryString($("#ngexcelfile")[0].files[0]);' . "\r\n";
        $uplstr .= '          }' . "\r\n";
        $uplstr .= '    }' . "\r\n\n";

        return $uplstr;
    }

    private static function get_js_delete_method($tbs)
    {
        $tb = Inflect::singularize($tbs);
        $delstr = '    $scope.' . $tb . '_delete = function(id){' . "\r\n";
        $delstr .= '            $http({' . "\r\n";
        $delstr .= '                url: base_api_url+"/' . $tbs . '/"+id,' . "\r\n";
        $delstr .= '                method: "DELETE",' . "\r\n";
        $delstr .= '                 headers:headers' . "\n";
        $delstr .= '            }).then((result) =>{' . "\r\n";
        $delstr .= '               $scope.' . $tb . ' = result.data.message;' . "\r\n";
        $delstr .= '               window.location.reload();'."\r\n";
        $delstr .= '            }, function(error){' . "\r\n";
        $delstr .= '               $scope.error = error.statusText;' . "\r\n";
        $delstr .= '            });' . "\r\n";
        $delstr .= '    };' . "\r\n\r\n";
        return $delstr;
    }

    private static function get_js_main_view($tbs)
    {
        //$tb = Inflect::singularize($tbs);
        $mainstr  = '    $http({' . "\r\n";
        $mainstr .= '        url: base_api_url+"/' . $tbs . '",' . "\r\n";
        $mainstr .= '        method: "GET",' . "\r\n";
        $mainstr .= '        headers:headers' . "\n";
        $mainstr .= '    }).then((result) =>{' . "\r\n";
        $mainstr .= '        $scope.' . $tbs . ' = result.data.data;' . "\r\n";
        $mainstr .= '    }, function(error){' . "\r\n";
        $mainstr .= '        $scope.error = error.statusText;' . "\r\n";
        $mainstr .= '    });' . "\r\n\r\n";
        return $mainstr;
    }

    private static function do_sign_up_file($tb)
    {
        global $app_dir, $dbname;
        $tbs = Inflect::singularize($tb);
        $jstr = "//javascript file for Sign Up using angularjs for data-binding.\r\n";
        $jstr .= 'app.controller (' . "'user_signupCtrl'" . ', function($scope, $http) {' . "\r\n\r\n";
        $jstr .= '    this.' . $tbs . ' = { ';
        $jstr .= self::get_tab_string($tb);
        $jstr .= '};' . "\r\n\n\n";
        $jstr .= '    let headers = {' . "\n";
        $jstr .= '        "Content-Type":"application/json",' . "\n";
        $jstr .= '        "Accept":"application/json",' . "\n";
        $jstr .= '    };' . "\n\n";
        $jstr .= '    $scope.sign_up = function() {' . "\r\n";
        $jstr .= '        $http({' . "\r\n";
        $jstr .= '            url: base_api_url+"/auth/register",' . "\r\n";
        $jstr .= '            method: "POST",' . "\r\n";
        $jstr .= '            data:this.' . $tbs . ',' . "\r\n";
        $jstr .= '        }).then((result) =>{' . "\r\n";
        $jstr .= '            $scope.info = result.data.message;' . "\r\n";
        $jstr .= '            $scope.alertDisplayed = true' . "\r\n";
        $jstr .= '            setTimeout(() => {' . "\r\n";
        $jstr .= '               setTimeout(() => {' . "\r\n";
        $jstr .= '                   window.location.assign("../resources/index.html");' . "\r\n";
        $jstr .= '               },100)' . "\r\n";
        $jstr .= '               $scope.alertDisplayed = false;' . "\r\n";
        $jstr .= '            },600);' . "\r\n";
        $jstr .= '        }, function(error){' . "\r\n";
        $jstr .= '            $scope.error = error.statusText;' . "\r\n";
        $jstr .= '        });' . "\r\n";
        $jstr .= '    };' . "\r\n\r\n";
        $jstr .= '  });' . "\r\n";
        $file_dir = $app_dir . "/resources/js";
        $js_file = $app_dir . "/resources/js/signup.js";

        if (is_readable($js_file)) {
            file_put_contents($js_file, $jstr);
        } else {
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file, "w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
    }


    private static function do_sign_in_file($tb)
    {
        global $app_dir, $dbname;
        $jstr = "//javascript file for Logging In using angularjs for data-binding.\r\n";
        $jstr .= 'app.controller (' . "'user_loginCtrl'" . ', function($scope, $http) {' . "\r\n\r\n";
        $jstr .= '    this.user = { email: "", password: "" }' . "\r\n";
        $jstr .= '    let headers = {' . "\n";
        $jstr .= '        "Content-Type":"application/json",' . "\n";
        $jstr .= '        "Accept":"application/json",' . "\n";
        $jstr .= '    };' . "\n\n";
        $jstr .= '    $scope.sign_in = function() {' . "\r\n";
        $jstr .= '        $http({' . "\r\n";
        $jstr .= '            url: base_api_url+"/auth/login",' . "\r\n";
        $jstr .= '            method: "POST",' . "\r\n";
        $jstr .= '            headers:headers,' . "\r\n";
        $jstr .= '            data:this.user,' . "\r\n";
        $jstr .= '        }).then((result) =>{' . "\r\n";
        $jstr .= '            $scope.info = result.data.message;' . "\r\n";
        $jstr .= '            local_store("add", "'.$dbname.'User", { token: result.data.data.token});' . "\r\n";
        $jstr .= '            setTimeout(() => {' . "\n";
        $jstr .= '                window.location.replace("views/dashboard.html");' . "\r\n";
        $jstr .= '            }, 500);' . "\n\r";
        $jstr .= '        }, function(error){' . "\r\n";
        $jstr .= '            $scope.error = error.statusText;' . "\r\n";
        $jstr .= '            $scope.loginErrorDisplayed = true' . "\r\n";
        $jstr .= '            setTimeout(() => {' . "\r\n";
        $jstr .= '               $scope.loginErrorDisplayed = false;' . "\r\n";
        $jstr .= '            },500)' . "\r\n";
        $jstr .= '        });' . "\r\n";
        $jstr .= '    };' . "\r\n\r\n";
        $jstr .= '  });' . "\r\n";
        $file_dir = $app_dir . "/resources/js";
        $js_file = $app_dir . "/resources/js/signin.js";

        if (is_readable($js_file)) {
            file_put_contents($js_file, $jstr);
        } else {
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file, "w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
    }

    private static function do_sign_out_file() {
        global $app_dir, $dbname;
        $jstr = 'app.controller (' . "'user_logoutCtrl'" . ', function($scope, $http) {' . "\r\n\r\n";
        $jstr .= '    let user_token = local_store("get", "'.$dbname.'User").token;'."\r\n";
        $jstr .= '    let headers = {' . "\n";
        $jstr .= '        "Content-Type":"application/json",' . "\n";
        $jstr .= '        "Accept":"application/json",' . "\n";
        $jstr .= '        "Authorization":"Bearer"+user_token'."\r\n";
        $jstr .= '    };' . "\n\n";
        $jstr .= '    $scope.logout = function() {' . "\r\n";
        $jstr .= '        $http({' . "\r\n";
        $jstr .= '            url: base_api_url+"/auth/logout",' . "\r\n";
        $jstr .= '            method: "POST",' . "\r\n";
        $jstr .= '        })' . "\r\n";
        $jstr .= '            local_store("del", "' . $dbname . 'User");' . "\r\n";
        $jstr .= '            window.location.replace("../index.html");' . "\r\n";
        $jstr .= '    }' . "\r\n";
        $jstr .= '});' . "\r\n";
        $file_dir = $app_dir . "/resources/js";
        $js_file = $app_dir . "/resources/js/signout.js";

        if (is_readable($js_file)) {
            file_put_contents($js_file, $jstr);
        } else {
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file, "w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }

    }


    private static function do_js_file($tb)
    {
        global $app_dir, $dbname;
        $fkey = self::$dbh->getFkeys($dbname, $tb);
        $tbj = Inflect::singularize($tb);
        $jstr = "//javascript file for " . $tb . " using angularjs for data-binding.\r\n";
        $jstr .= 'app.controller (' . "'" . $tb . "Ctrl'" . ', function($scope, $http, $routeParams) {' . "\r\n\r\n";
        $jstr .= '    this.' . $tbj . ' = { ';
        $jstr .= self::get_tab_string($tb);
        $jstr .= '};' . "\r\n\n\n";
        $jstr .= '     $scope.addnew = "#!/' . $tbj . '";' . "\n";
        $jstr .= '     $scope.search = "";' . "\n";
        $jstr .= '     $scope.upload = "#!/' . $tbj . '_upload";' . "\n";
        $jstr .= '     $scope.currentTableColumn = [';
        $jstr .= implode(",", array_map(fn ($field) => '"' . $field["Field"] . '"', self::get_table_columns($tb)));
        $jstr .= '];' . "\r\n\n";
        
        //$jstr .="    this.update = {col_name:'', col_value:''};\r\n\r\n";
        $jstr .= '     let user_token = local_store("get", "'.$dbname.'User").token;' . "\r\n\n";
        $jstr .= '     let headers = {' . "\r\n";
        $jstr .= '       "Content-Type":"application/json",' . "\r\n";
        $jstr .= '       "Accept":"application/json",' . "\r\n";
        $jstr .= '       "Authorization":"Bearer"+user_token' . "\r\n";
        $jstr .= '     }'."\r\n\n";
        $jstr .='     $scope.exportCSV = () => {'."\r\n";
        $jstr .='         const EXCEL_EXTENSION = ".xlsx";'."\r\n";
        $jstr .='         const exportTable = document.getElementById("'.$tbj.'_table");'."\r\n";
        $jstr .='         const fileName = "'.$tbj.'";'."\r\n";
        $jstr .='         const ws  = XLSX.utils.table_to_sheet(exportTable);'."\r\n";
        $jstr .='         const workbook = XLSX.utils.book_new();'."\r\n";
        $jstr .='         XLSX.utils.book_append_sheet(workbook, ws, "Sheet1");'."\r\n";
        $jstr .='         XLSX.writeFile(workbook, `${fileName}${EXCEL_EXTENSION}`);'."\r\n";
        $jstr .='     }'."\r\n\n";
        $jstr .= self::get_js_save_method($tb); //create and update method
        $jstr .= self::get_js_view_method($tb); //retrieve method
        $jstr .= self::get_js_update_method($tb); //update method trigger
        $jstr .= self::get_js_upload_method($tb); //uupload files method trigger
        $jstr .= self::get_js_delete_method($tb); //delete method
        $jstr .= self::get_js_main_view($tb); //main model view
        foreach ($fkey as $ftab) {
            $jstr .= self::get_js_main_view($ftab["REFERENCED_TABLE_NAME"]);
        }
        $jstr .= "});\r\n";
        $file_dir = $app_dir . "/resources/script";
        $js_file = $app_dir . "/resources/script/" . $tbj . ".js";
        if (is_readable($js_file)) {
            file_put_contents($js_file, $jstr);
        } else {
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/script/");
            $fp = fopen($js_file, "w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($js_file, $jstr);
        }
        //utilities::writetofile($jstr, self::$base_dir."/assets/src/", $tbj, "js");
    }

    private static function create_javascript($tbls)
    {
        foreach ($tbls as $table) {
            if (self::exempted($table) === false) {
                self::do_js_file($table);
            }
            if ($table == "users") {
                self::do_sign_up_file($table);
                self::do_sign_in_file($table);
                self::do_sign_out_file();
            }
        }
    }
}
