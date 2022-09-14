<?php
namespace Automator\Support\Forms;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Utilities;


class Bulma {

    private static $tables = array();
    private static $dbh;
    private static $jsapp;
    private static $excludeTables;
    private static $excludeColumns;

    public static function make($jsapp=null, $landing=false, $reporting=false, $opt=null, $tables=null, $columns=null){
        global $dbname;
        if ($jsapp != null) self::$jsapp = $jsapp;
        self::$dbh = new DbHandlers();
        if ($tables != null) self::$excludeTables = $tables;
        if ($columns != null) self::$excludeColumns = $columns;
        foreach(self::$dbh->show_dbTables() as $table){
            $table_name = $table["Tables_in_".$dbname];

            if (!in_array($table_name, self::$excludeTables)) self::$tables[] = $table_name;
        }
        self::forms(self::$tables);
        if ($reporting) self::makeReports(self::$tables);
        if($jsapp == "ng") self::makeAngularRoute(self::$tables);
        if($landing) self::makeLandingPage(self::$tables);
        self::makeIndexPage(self::$tables);
        self::createSignInPage(self::$tables);
        self::copy_assets();
    }

    private static function do_html_table($table, $fields) {
        global $app_dir;
        $cols = sizeof($fields)+1;
        $tb = Inflect::singularize($table);
        $tblStr = '<tool-bar add-new="addnew" upload-page="upload" export-page="exportCSV();" search-bar="searchPage();" search-term="currentTableColumn"></tool-bar>'."\r\n";
        $tblStr .= '<table class="table is-hoverable is-striped is-fullwidth" ng-controller="'.$table.'Ctrl"  id="'.$tb.'_table">'."\r\n";
        $tblStr .= '    <thead>'."\r\n";
        $tblStr .= '        <tr>'."\r\n";
        foreach($fields as $field) {
            $tblStr .= '            <th>'.trim(strtoupper(str_replace("id","",str_replace("_"," ",$field)))).'</th>'."\r\n";
        }
        $tblStr .= '            <th>&nbsp;</th>'."\r\n";
        $tblStr .= '        </tr>'."\r\n";
        $tblStr .= '    </thead>'."\r\n";
        $tblStr .= '    <tbody>'."\r\n";
        $tblStr .= '        <tr ng-repeat=" '.Inflect::singularize($table).' in '.$table.'">'."\r\n";
        foreach($fields as $field) {
            $tblStr .= '            <td>{{'.Inflect::singularize($table).'.'.$field.'}}</td>'."\r\n";
        }
        $tblStr .='            <td>'."\r\n";
        $tblStr .='                <a class="has-text-success" href="#!/'.Inflect::singularize($table).'/edit/{{'.Inflect::singularize($table).'.id}}">'."\r\n";
        $tblStr .='                    <span class="icon">'."\r\n";
        $tblStr .='                        <i class="fa fa-edit"></i>'."\r\n";
        $tblStr .='                    </span>'."\r\n";
        $tblStr .='                </a>&nbsp;'."\r\n";
        $tblStr .='                <a class="has-text-success" href="#!/'.Inflect::singularize($table).'/view/{{'.Inflect::singularize($table).'.id}}">'."\r\n";
        $tblStr .='                    <span class="icon">'."\r\n";
        $tblStr .='                        <i class="fa fa-eye"></i>'."\r\n";
        $tblStr .='                    </span>'."\r\n";
        $tblStr .='                </a>&nbsp;'."\r\n";
        $tblStr .='                <a class="has-text-danger" ng-click="remove('.Inflect::singularize($table).'.id);">'."\r\n";
        $tblStr .='                    <span class="icon">'."\r\n";
        $tblStr .='                        <i class="fa fa-trash"></i>'."\r\n";
        $tblStr .='                   </span>'."\r\n";
        $tblStr .='               </a>'."\r\n";
        $tblStr .='           </td>'."\r\n";
        $tblStr .= '        </tr>'."\r\n";
        $tblStr .= '    </tbody>'."\r\n";
        $tblStr .= '    <tfoot>'."\r\n";
        $tblStr .= '       <tr>'."\r\n".'            <td colspan="'.$cols.'">'."\r\n";
        $tblStr .= '                <button type="button" ng-repeat="(index, '.Inflect::singularize($table).') in '.$table.'">{{index}}</button>'."\r\n";
        $tblStr .= '            </td>'."\r\n".'        </tr>'."\r\n";
        $tblStr .= '    </tfoot>'."\r\n";
        $tblStr .= '</table>'."\r\n";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/".$table.".html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $tblStr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $tblStr);
        }
    }

    private static function do_html_table_two_column($table, $fields){
        global $app_dir;
        $tblStr = '<table class="table is-hoverable is-striped is-fullwidth" ng-controller="'.$table.'Ctrl">'."\r\n";
        $tblStr .= '    <tbody>'."\r\n";
        foreach($fields as $field) {
            $tblStr .= '        <tr>'."\r\n";
            $tblStr .= '            <th>'.strtoupper($field).'</th>';
            $tblStr .= '<td>{{'.Inflect::singularize($table).'.'.$field.'}}</td>'."\r\n";
            $tblStr .= '        </tr>'."\r\n";
        }
        $tblStr .= '    </tbody>'."\r\n";
        $tblStr .= '</table>'."\r\n";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/".Inflect::singularize($table)."_view.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $tblStr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $tblStr);
        }
    }

    private static function do_html_upload_page($table) {
        global $app_dir;
        $tb = Inflect::singularize($table);
        $upStr = '<div class="container">'."\r\n";
        $upStr .='    <h1 class="title is-3">Upload '.ucfirst($table).'</h1>';
        $upStr .='    <div class="columns is-justify-content-center">'."\r\n";
        $upStr .='        <div class="column is-6-tablet is-5-desktop is-4-widescreen is-3-fullhd">'."\r\n";
        $upStr .='            <form method="POST" class="box p-5" enctype="multipart/form-data">'."\r\n";
        $upStr .='                <label class="label is-block mb-4">'."\r\n";
        $upStr .='                <span class="is-block mb-2"> Upload '.ucfirst($table).'</span>'."\r\n";
        $upStr .='                <div class="file is-fullwidth">'."\r\n";
        $upStr .='                    <label class="file-label">'."\r\n";
        $upStr .='                    <input type="file" class="file-input" name="'.$tb.'_file">'."\r\n";
        $upStr .='                    <span class="file-icon"><i class="fas fa-upload"></i></span>'."\r\n";
        $upStr .='                    <span class="file-label">Choose a file...</span>'."\r\n";
        $upStr .='                </div>'."\r\n";
        $upStr .='            </form>'."\r\n";
        $upStr .='        </div>'."\r\n";
        $upStr .='    </div>'."\r\n";
        $upStr .='</div>'."\r\n";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/".Inflect::singularize($table)."_upload.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $upStr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $upStr);
        }
    }

    private static function makeReports($tables) {
        foreach($tables as $table) {
            $report_fields = array();
            $struct = self::$dbh->tableDesc($table);
            foreach($struct as $field){
                if (!in_array($field['Field'], self::$excludeColumns)) $report_fields[] = $field['Field'];
            }
            self::do_html_table($table, $report_fields);
            self::do_html_table_two_column($table, $report_fields);
            self::do_html_upload_page($table);
        }
    }

    private static function indexJsFile() {
        global $dbname,$app_dir;
        $routeStr = 'var base_api_url = "http://localhost:8000/api";'."\r\n";
        $routeStr .= 'var app = angular.module("'.$dbname.'App", ["ngRoute"]);'."\r\n";
        $file_dir = $app_dir."/resources/js";
        $routefile = $app_dir."/resources/js/index.js";
        if(is_readable($routefile)){
            file_put_contents($routefile, $routeStr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/js/");
            $fp = fopen($routefile,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($routefile, $routeStr);
        }
    }

    private static function makeAngularRoute($tables) {
        global $dbname, $app_dir;
        self::indexJsFile();
        $routeStr = "";
        $routeStr .= 'app.config(function($routeProvider) {'."\r\n";
        $routeStr .= '    $routeProvider'."\r\n";
        foreach($tables as $table) {
            $routeStr .= '    .when("/'.$table.'", {'."\r\n";
            $routeStr .= '        templateUrl: "'.$table.'/'.$table.'.html",'."\r\n";
            $routeStr .= '        controller: "'.$table.'Ctrl",'."\r\n";
            $routeStr .= '    })'."\r\n";
            $routeStr .= '    .when("/'.Inflect::singularize($table).'", {'."\r\n";
            $routeStr .= '        templateUrl: "'.$table.'/'.Inflect::singularize($table).'.html",'."\r\n";
            $routeStr .= '        controller: "'.$table.'Ctrl",'."\r\n";
            $routeStr .= '    })'."\r\n";
            $routeStr .= '    .when("/'.Inflect::singularize($table).'_upload", {'."\r\n";
                $routeStr .= '        templateUrl: "'.$table.'/'.Inflect::singularize($table).'_upload.html",'."\r\n";
                $routeStr .= '        controller: "'.$table.'Ctrl",'."\r\n";
                $routeStr .= '    })'."\r\n";
            $routeStr .= '    .when("/'.Inflect::singularize($table).'/edit/:id", {'."\r\n";
            $routeStr .= '        templateUrl: "'.$table.'/'.Inflect::singularize($table).'.html",'."\r\n";
            $routeStr .= '        controller: "'.$table.'Ctrl",'."\r\n";
            $routeStr .= '    })'."\r\n";
            $routeStr .= '    .when("/'.Inflect::singularize($table).'/view/:id", {'."\r\n";
            $routeStr .= '        templateUrl: "'.$table.'/'.Inflect::singularize($table).'_view.html",'."\r\n";
            $routeStr .= '        controller: "'.$table.'Ctrl",'."\r\n";
            $routeStr .= '    })'."\r\n";
        }
        $routeStr .= '    .when("/auth/registers", {'."\r\n";
        $routeStr .= '      templateUrl: "signup.html",'."\r\n";
        $routeStr .= '      controller: "usersCtrl"'."\r\n";
        $routeStr .='    })'."\r\n";
        $routeStr .= '    .when("/auth/login", {'."\r\n";
        $routeStr .= '      templateUrl: "login.html",'."\r\n";
        $routeStr .= '      controller: "usersCtrl"'."\r\n";
        $routeStr .='    })'."\r\n";
        $routeStr .= '    .otherwise({'."\r\n";
        $routeStr.='        redirectTo : "/"'."\r\n";
        $routeStr .='    })'."\r\n";
        $routeStr .='});'."\r\n";
        $file_dir = $app_dir."/resources/js";
        $routefile = $app_dir."/resources/js/route.js";
        if(is_readable($routefile)){
            file_put_contents($routefile, $routeStr);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/js/");
            $fp = fopen($routefile,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($routefile, $routeStr);
        }
    }

    private static function headerFile($isDash = false) {
        global $dbname;
        $lheaders = "<!DOCTYPE html>\r\n".'  <html lang="en">'."\r\n";
        $lheaders .= "    <head>\r\n      <title>".ucfirst($dbname)."&reg;::Portal</title>\r\n";
        $lheaders .= '        <meta content="text/html" charset="utf-8" >'."\r\n";
        $lheaders .= '        <meta name="viewport" content="width=device-width, initial-scale=1">'."\r\n";
        if ($isDash) {
            $lheaders .= '        <link rel="stylesheet" href="../css/bulma.min.css">'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="../css/jquery-ui.css">'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="../css/fontawesome-all.min.css" >'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="../css/custom/uploadfile.css" >'."\r\n";
            $lheaders .= '        <link rel="icon" href="data:;base64,=">'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="../css/custom/slide-menu.css" >'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="../css/custom/table-header.css" >'."\r\n";
        } else {
            $lheaders .= '        <link rel="stylesheet" href="css/bulma.min.css">'."\r\n";
            $lheaders .= '        <link rel="stylesheet" href="css/fontawesome-all.min.css" >'."\r\n";
        }
        $lheaders .= "    </head>";
        return $lheaders;
    }

    private static function  getSnippet() {
        $snippetStr ='doPop = (title, content) => {'."\n";
        $snippetStr .='            $(".modal-card-title").html(title);'."\n";
        $snippetStr .='            $(".modal-card-body").load(content);'."\n";
        $snippetStr .='            $(".modal").toggleClass("is-active");'."\n";
        $snippetStr .='        }'."\n";
        $snippetStr .= '        $($(".delete")[0]).on("click", (e) => { $(".modal").toggleClass("is-active"); })'."\n";
        return $snippetStr;
    }

    private static function getscripts($tbi, $rty = null, $location=null){
        $html_body = "";
        if ($location == "landingPage") {
            $html_body .= "\r\n    ".'<script  src="../js/jquery.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/angular.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/angular-route.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/jquery-ui.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/jquery.table2excel.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/jquery.uploadfile.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/utility.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/autocomplete.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/index.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/route.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/xlsx.full.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/bundle.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../components/toolbar/toolbar.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../components/addnew/addnew.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../components/upload/upload.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../components/export/export.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../components/search/search.js"></script>';
            $html_body .= "\r\n    ".'<script  src="../js/useFontAwesome.js"></script>';
            $html_body .= "\r\n    ".'<script>'."\n";
            $html_body .='        '.self::getSnippet();
            $html_body .= "    ".'</script>';
        } else {
            $html_body .= "\r\n    ".'<script  src="js/jquery.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="js/angular.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="js/angular-route.min.js"></script>';
            $html_body .= "\r\n    ".'<script  src="js/utility.js"></script>';
            $html_body .= "\r\n    ".'<script  src="js/autocomplete.js"></script>';
            $html_body .= "\r\n    ".'<script  src="js/index.js"></script>';
        }

        if ($rty == 'signup') {
            $html_body .= "\r\n    ".'<script  src="js/signup.js"></script>';
        }
        if ($rty == 'signin') {
            $html_body .= "\r\n    ".'<script  src="js/signin.js"></script>';
        }
        
        return $html_body;
    }

    private static function getModal() {
        $modalStr = <<< END
            <div class="modal">
                <div class="modal-background"></div>
                <div class="modal-card">
                    <header class="modal-card-head">
                        <p class="modal-card-title">Modal title</p>
                        <button class="delete" aria-label="close"></button>
                    </header>
                    <section class="modal-card-body">
                        <!-- Content ... -->
                    </section>
                    <footer class="modal-card-foot"></footer>
                </div>
            </div>
        END;
        return $modalStr;
    }

    private static function createSignInPage($tables) {
        global $dbname;
        global $app_dir;
        $sheaders = self::headerFile();
        $sbody ="\r\n   ".'<body ng-app="'.$dbname.'App">'."\r\n";
        $sbody .= '         <form class="form container" method="POST" enctype="multipart/form-data" ng-controller="usersCtrl">'."\n";
        $sbody .= '          <div class="hero is-fullheight">'."\n";
        $sbody .= '              <div class="hero-body is-justify-content-center is-align-items-center">'."\n";
        $sbody .= '                  <div class="columns is-flex is-flex-direction-column box">'."\n";
        $sbody .='                       <h3 class="is-size-3 has-text-centered has-text-primary has-text-weight-bold">Sign In</h3>'."\n";
        $sbody .= '                      <div class="column">'."\n";
        $sbody .= '                          <label for="username">Email:</label>'."\n";
        $sbody .= '                          <input id="email" name="email" ng-model="users.email" class="input is-primary" type="text" placeholder="Enter your username">'."\n";
        $sbody .= '                      </div>'."\n";
        $sbody .= '                      <div class="column">'."\n";
        $sbody .= '                          <label for="password">Password</label>'."\n";
        $sbody .= '                          <input id="password" name="password" ng-model="users.password" class="input is-primary" type="password" placeholder="Password">'."\n";
        $sbody .= '                          <a href="#" class="is-size-7 has-text-primary">forget password?</a>'."\n";
        $sbody .= '                      </div>'."\n";
        $sbody .= '                      <div class="column">'."\n";
        $sbody .= '                          <button class="button is-primary is-fullwidth"';
        if (self::$jsapp == "ng") { $sbody .= 'type="button" ng-click="sign_in()"'; };
        $sbody .= ' >Login</button>'."\n";
        $sbody .= '                      </div>'."\n";
        $sbody .= '                      <div class="has-text-centered">'."\n";
        $sbody .= '                          <p class="is-size-7">Dont Have an Account?<a href="signup.html" class="has-text-primary">Sign up</a></p>'."\n";
        $sbody .= '                      </div>'."\n";
        $sbody .= '                  </div>'."\n";
        $sbody .= '              </div>'."\n";
        $sbody .= '          </div>'."\n";
        $sbody .= '         </form>'."\n";
        $signin = 'signin';
        $Sscripts = self::getscripts($tables, $signin);
        $sfooter = "\n\n".'   </body>'."\n";
        $sfooter .= '</html>'."\n";
        $sbodyo = $sheaders.$sbody.$Sscripts.$sfooter;
        $file_dir = $app_dir."/resources";
        $views_file = $app_dir."/resources/login.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $sbodyo);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $sbodyo);
        }
    }

    private static function do_register_form_create($fields,$table) {
        global $dbname;
        global $app_dir;
        $form_str = self::headerFile();
        $form_str .="\r\n".'    <body ng-app="'.$dbname.'App" style="margin-top:50px;">'."\r\n";
        $form_str .= '       <form class="form container" method="POST" enctype="multipart/form-data" ng-controller="'.$table.'Ctrl">'."\n";
        $form_str .='         <div class="hero is-fullheight">'."\n";
        $form_str .='           <div class="columns is-flex is-flex-direction-column box">'."\n";
        $form_str .='               <h2 class="is-size-2 has-text-centered has-text-primary has-text-weight-bold">Sign Up</h2>'."\n";
        foreach($fields as $field){
            $req = false;
            if (strpos($field["Type"], "int")>-1) {
                if($field["Key"]!=="MUL"){
                    if($field["Null"]==="NO") $req = true;
                    $display = 'none';
                    $form_str .= "    ".self::getSUInputField($field["Field"], "number", $table, $req, $display);
                }else if($field["Key"] === "MUL"){
                    $display = 'none';
                    $form_str .= "    ".self::getSUSelectField($field["Field"], $table, $display );
                }
            }
            else if(strpos($field["Type"], "varchar")>-1){
                if($field["Null"]==="NO") $req = true;
                $display = 'block';
                $form_str .= "    ".self::getSUInputField($field["Field"], "text", $table, $req, $display);
            }else if(strpos($field["Type"], "text")>-1){
                $display = 'block';
                $form_str .= "    ".self::getSUTextarea($field["Field"], $table, $display);
            }
        }
        $form_str .= "\r\n".'        <div class="field">'."\n";
        $form_str .='                 <label class="label" for="password_confirmation">Confirm Password</label>'."\n";
        $form_str .='                 <div class="control">'."\n";
        $form_str .='                   <input id="password_confirmation" name="password_confirmation" ng-model="users.password_confirmation" class="input" type="password" required>'."\r\n";
        $form_str .='                 </div>'."\r\n";
        $form_str .='                </div>'."\r\n";
        $form_str .="\r\n".'<a class="has-text-right is-size-6 is-underlined" href="login.html">Already A member?</a>';
        $form_str .= "\n".'        <div class="field is-grouped">'."\n";
        $form_str .= '            <p class="control">'."\n";
        $form_str .= '              <button ';
        if (self::$jsapp == "ng") { $form_str .= 'type="button" ng-click="sign_up()"'; };
        
        $form_str .= ' class="button is-primary">'."\n";
        $form_str .= '                Submit'."\n";
        $form_str .= '              </button>'."\n";
        $form_str .= '            </p>'."\n";
        $form_str .= '            <p class="control">'."\n";
        $form_str .= '              <button type="reset" class="button is-light">'."\n";
        $form_str .= '                Clear'."\n";
        $form_str .= '              </button>'."\n";
        $form_str .= '            </p>'."\n";
        $form_str .= '        </div>'."\n";

        $form_str .="\n".'           </div>'."\n";
        $form_str .='       </div>'."\n";
        $form_str .= "      </form>"."\n";
        $signup = 'signup';
        $Sscripts = self::getscripts($table, $signup);
        $sfooter = "\n\n".'   </body>'."\n";
        $sfooter .= '</html>'."\n";
        $formbody = $form_str.$Sscripts.$sfooter;
        $file_dir = $app_dir."/resources";
        $views_file = $app_dir."/resources/signup.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $formbody);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $formbody);
        }
    }
    
    private static function createSignUpPage($table) {
        $form_fields = array();
        $struct = self::$dbh->tableDesc($table);
        foreach($struct as $field){
            if (!in_array($field['Field'], self::$excludeColumns)) $form_fields[] = $field;
        }
        self::do_register_form_create($form_fields, $table);
    }

    private static function makeIndexPage($tables) {
        global $dbname;
        global $app_dir;
        
        $lheaders = self::headerFile();
        $lbodyo = "\r\n    ".' <body>'."\r\n";
        $lbodyo .= '               <nav class="navbar" role="navigation" aria-label="main navigation">'."\n";
        $lbodyo .= '                   <div class="navbar-brand">'."\n";
        $lbodyo .= '                       <a class="navbar-item" href="/">'."\n";
        $lbodyo .= '                           <h1 class="is-title is-3"><strong>'.strtoupper($dbname).'</strong></h1>'."\n";
        $lbodyo .= '                       </a>'."\n";
        $lbodyo .= '                       <a role="button" href="javascript:void(0);" class="navbar-burger" aria-label="menu" aria-expanded="false" onclick="myFunction()">'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                       </a>'."\n";
        $lbodyo .= '                   </div>'."\n";
        $lbodyo .= '                   <div class="navbar-menu">'."\n";
        $lbodyo .= '                       <div class="navbar-end">'."\n";   
        $lbodyo .= '                           <div class="navbar-item">'."\n";
        $lbodyo .= '                                <div class="buttons">'."\n";
        $lbodyo .= '                                    <a class="button is-primary" href="login.html">'."\n";
        $lbodyo .= '                                        <strong>Log In</strong>'."\n";
        $lbodyo .= '                                    </a>'."\n";
        $lbodyo .= '                                    <a class="button is-light" href="signup.html">'."\n";
        $lbodyo .= '                                        Sign Up'."\n";
        $lbodyo .= '                                    </a>'."\n";
        $lbodyo .= '                                </div>'."\n";
        $lbodyo .= '                           </div>'."\n";
        $lbodyo .= '                       </div>'."\n";
        $lbodyo .= '                   </div>'."\n";
        $lbodyo .= '               </nav>'."\n";
        $lscripts = self::getscripts($tables);
        $lfooter = "\n\n".'   </body>'."\n";
        $lfooter .= '</html>'."\n";
        $lbody = $lheaders.$lbodyo.$lscripts.$lfooter;
        $file_dir = $app_dir."/resources";
        $views_file = $app_dir."/resources/index.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $lbody);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $lbody);
        }
    }
    private static function makeLandingPage($tables) {
        global $dbname;
        global $app_dir;
        $lheaders = self::headerFile(true);
        $lbodyo = "\r\n    ".' <body ng-app="'.$dbname.'App">'."\r\n";
        $lbodyo .= '           <div class="container-fluid">'."\n";
        $lbodyo .= '               <nav class="navbar" role="navigation" aria-label="main navigation">'."\n";
        $lbodyo .= '                   <div class="navbar-brand">'."\n";
        $lbodyo .= '                       <a class="navbar-item" href="#">'."\n";
        $lbodyo .= '                           <h3>'.ucfirst($dbname).'</h3>'."\n";
        $lbodyo .= '                       </a>'."\n";
        $lbodyo .= '                       <a role="button" href="javascript:void(0);" class="navbar-burger" aria-label="menu" aria-expanded="false" onclick="myFunction()">'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                           <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                       </a>'."\n";
        $lbodyo .= '                   </div>'."\n";
        $lbodyo .= '                   <div class="navbar-menu">'."\n";
        $lbodyo .= '                       <div class="navbar-end">'."\n";   
        $lbodyo .= '                           <div class="navbar-item">'."\n";
        $lbodyo .= '                           </div>'."\n";
        $lbodyo .= '                       </div>'."\n";
        $lbodyo .= '                   </div>'."\n";
        $lbodyo .= '               </nav>'."\n";
        $lbodyo .= '               <section class="main-content columns is-fullheight">'."\n";
        $lbodyo .= '                   <aside class="column is-2 is-narrow-mobile is-fullheight p-2 is-dark">'."\n";
        $lbodyo .= '                       <!--<p class="menu-label is-hidden-touch">Navigation</p>-->'."\n";
        $lbodyo .= '                       <ul class="menu-list">'."\n";
        foreach ($tables as $tbl) {
            $lbodyo .= '                       <li>'."\n";
            $lbodyo .= '                           <a href="#!/'.$tbl.'">'."\n";
            $lbodyo .= '                               '.ucwords(str_replace('_', " ",$tbl)).'    '."\n";
            $lbodyo .= '                           </a>'."\n";
            $lbodyo .= '                       </li>'."\n";
        }
        $lbodyo .= '                       </ul>'."\n";
        $lbodyo .= '                </aside>'."\n";
        $lbodyo .= '                <div class="container column is-10">'."\n";
        $lbodyo .= '                    <div ng-view></div>'."\r\n";
        $lbodyo .= '                </div>'."\n";
        $lbodyo .= '            </section>'."\n";
        $lbodyo .= '        </div>'."\n";
        $lbodyo .= self::getModal();
        $lscripts = self::getscripts($tables,null,'landingPage');
        $lfooter = "\n\n".'   </body>'."\n";
        $lfooter .= '</html>'."\n";
        $lbody = $lheaders.$lbodyo.$lscripts.$lfooter;
        $file_dir = $app_dir."/resources/views";
        $views_file = $app_dir."/resources/views/dashboard.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $lbody);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $lbody);
        }

    }

    private static function copy_assets(){
        global $app_dir;
        
        utilities::xcopy('automator/css', $app_dir.'/resources/css');
        utilities::xcopy('automator/js', $app_dir.'/resources/js');
        utilities::xcopy('automator/components', $app_dir.'/resources/components');
        utilities::xcopy('automator/webfonts', $app_dir.'/resources/webfonts');
        utilities::xcopy('automator/ckeditor', $app_dir.'/resources/ckeditor');
    }



    private static function forms($tables){
        if(is_array($tables)){
            foreach($tables as $table){
                if (strtoupper($table) == 'USERS') {
                    self::createSignUpPage($table);
                }
                self::make_form($table);
                //self::make_view_table($table);
            }
        }else{
            echo "Parameter must be an array.";
        }
        //do_dash_board($tables);
    }

    private static function make_form($table){
        $form_fields = array();
        $struct = self::$dbh->tableDesc($table);
        foreach($struct as $field){
            if (!in_array($field['Field'], self::$excludeColumns)) $form_fields[] = $field;
        }
        self::do_html_form_create($form_fields, $table);
        #self::do_html_form_edit($form_fields, $table);
    }



    /**
     * .input .textarea .select
     * .checkbox .radio
     */
    private static function do_html_form_create($fields, $table){
        global $dbname;
        global $app_dir;
        $filename = Inflect::singularize($table);
        $addDatePicker = false;
        $form_str = '      <form class="form container" method="POST" enctype="multipart/form-data" ng-controller="'.$table.'Ctrl">'."\n";
        $form_str .= '          <h1 class="title is-3">ADD '.strtoupper(str_replace("_"," ",Inflect::singularize($table))).'</h1>'."\n";
        foreach($fields as $field){
            $req = false;
            if (strpos($field['Field'], 'date') > -1) $addDatePicker = true;
            if(strpos($field["Type"], "int")>-1 && $field["Key"]!=="MUL"){
                if($field["Null"]==="NO") $req = true;
                $form_str .= "    ".self::getInputField($field["Field"], "number", $table, $req);
            }else if($field["Key"] === "MUL"){
                $form_str .= "    ".self::getSelectField($field["Field"], $table);
            }else if(strpos($field["Type"], "varchar")>-1){
                if($field["Null"]==="NO") $req = true;
                $form_str .= "    ".self::getInputField($field["Field"], "text", $table, $req);
            }else if(strpos($field["Type"], "text")>-1){
                $form_str .= "    ".self::getTextarea($field["Field"], $table);
            }
        }
        $form_str .= self::getButtonGrp($table);
        $form_str .= "      </form>"."\n";

        if ($addDatePicker == true) {
            $form_str .= "      $( function() {\n\r";
            $form_str .= "            $( \".datepicker\" ).map((i, item) => {\n\r";
            $form_str .= "                  $(item).datepicker();\n\r";
            $form_str .= "            })\n\r";
            $form_str .= "      } );\n\r";
        }
        
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/".Inflect::singularize($table).".html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $form_str);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $form_str);
        }
    }

    private static function do_html_form_edit($fields, $table){
        global $dbname;
        global $app_dir;
        $filename = Inflect::singularize($table);
        $form_str = '    <form class="form container" method="POST" enctype="multipart/form-data">'."\n";
        $form_str .= '      <h1 class="title is-3">EDIT '.strtoupper(str_replace("_"," ",Inflect::singularize($table))).'</h1>'."\n";
        foreach($fields as $field){
            $req = false;
            if(strpos($field["Type"], "int")>-1 && $field["Key"]!=="MUL"){
                if($field["Null"]==="NO") $req = true;
                $form_str .= "    ".self::getInputField($field["Field"], "number", $table, $req);
            }else if($field["Key"] === "MUL"){
                $form_str .= "    ".self::getSelectField($field["Field"], $table);
            }else if(strpos($field["Type"], "varchar")>-1){
                if($field["Null"]==="NO") $req = true;
                $form_str .= "    ".self::getInputField($field["Field"], "text", $table, $req);
            }else if(strpos($field["Type"], "text")>-1){
                $form_str .= "    ".self::getTextarea($field["Field"], $table);
            }
        }
        $form_str .= self::getButtonGrp($table);
        $form_str .= "      </form>"."\n";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/edit.".ucfirst($table).".html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $form_str);
        }else{
            if(!is_dir($file_dir)) exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $form_str);
        }
    }

    private static function getLabels($name,$table){
        $field_id = strtolower($name);
        if(strpos($name, "_")>-1){
            $label = "";
            $lbl = explode("_", $name);
            foreach($lbl as $lab){
                $label .= ucwords($lab)." ";
            }
        }else{
            $label = ucwords($name);
        }
        $model = Inflect::singularize($table).'.'.$name;
        return [$field_id, $label, $model];
    }

    private static function getSUInputField($name, $type, $table, $is_required, $disp=null) {
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $tb = Inflect::singularize($table);
        if (strpos($name, 'password') > -1) $type = 'password';
        $input_str = "\n".'         <div class="field" ';
        $input_str .= '>'."\n";
        $input_str .= '            <label class="label">'.$label.'</label>'."\n";
        $input_str .= '            <div class="control">'."\n";
        $input_str .= '             <input id="'.$field_id.'" name="'.$field_id.'"';
        if (self::$jsapp == "ng") { $input_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };
        $input_str.=' class="input" ';
        if ($disp == 'block') {
            $input_str .= 'type="'.$type.'"';
        }
        if($is_required){
            $input_str .= ' required';
        }
        $input_str .='>'."\n";
        $input_str .='            </div>'."\n";
        $input_str .= '        </div>';
        if ($disp == 'none') {
            $input_str ="\r\n".'        <input type="hidden" id="'.$field_id.'" name="'.$field_id.'" value="">'."\r\n";
        }

        return $input_str;
    }

    private static function getInputField($name, $type, $table, $is_required, $disp=null){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $tb = Inflect::singularize($table);
        $input_str = "\n".'         <div class="field">'."\n";
        $input_str .= '            <label class="label">'.$label.'</label>'."\n";
        $input_str .= '            <div class="control">'."\n";
        $input_str .= '             <input id="'.$field_id.'" name="'.$field_id.'"';

        if (self::$jsapp == "ng") { $input_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };

        //if (self::$jsapp == "ng") { $input_str .= ' ng-model="'.Inflect::singularize($table).'.'.$field_id.'"'; };

        $input_str.=' class="input ';
        if (strpos($name, 'date') > -1) $input_str .= 'datepicker';
        $input_str.='"';
        if ($disp == null || $disp == 'block') {
            $input_str .= 'type="'.$type.'"';
        }elseif ($disp == 'none') {
            $input_str .= 'type = "hidden"';
        }
        if($is_required){
            $input_str .= ' required';
        }
        $input_str .='>'."\n";
        $input_str .='            </div>'."\n";
        /*if($is_required){
            $input_str .= '        <p class="help">This field is required</p>'."\n";
        }*/
        $input_str .= '        </div>';
        return $input_str;
    }

    private static function getSUSelectField($name, $table, $disp=null) {
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $tb = Inflect::singularize($table);
        $new_label = ucwords(str_replace(" id", "", strtolower($label)));
        $items = Inflect::pluralize(strtolower(str_replace(" ","_",trim($new_label))));
        $select_str = "\n".'         <div class="field"';
        $select_str .= '>'."\n";
        $select_str .= '              <label class="label">'.trim($new_label).'</label>'."\n";
        $select_str .= '              <div class="select" style="width:100%;">'."\n";
        $select_str .= '                  <select class="input" ';

        if (self::$jsapp == "ng") { $select_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };
        //if (self::$jsapp == "ng") { $select_str .= ' ng-model="'.Inflect::singularize($table).'.'.$field_id.'"'; };

        if ($disp == 'none') {
            $select_str .= ' hidden="hidden" ';
        }
        $select_str .='>'."\n";
        $select_str .= '                      <option value="-1">Select '.$new_label.'</option>'."\n";
        $select_str .= '                  </select>'."\n";
        $select_str .= '              </div>'."\n";
        $select_str .= '         </div>'."\n";

        if ($disp == 'none') {
            $select_str ="\r\n".'       <input type="hidden" id="'.$field_id.'" name="'.$field_id.'" value="">'."\r\n";
        }

        return $select_str;
    }

    private static function getSelectField($name, $table){
        global $app_dir;
        global $dbname;
        $fkey = self::$dbh->getFkeys($dbname, $table, $name);
        $tb = Inflect::singularize($table);
        $ref_table =  ($fkey!=null) ? $fkey[0]["REFERENCED_TABLE_NAME"] : null;
        $ref_file = "$ref_table/".Inflect::singularize($ref_table).".html";
        $ref_value = Inflect::singularize($ref_table);
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $new_label = ucwords(str_replace(" id", "", strtolower($label)));
        $items = Inflect::pluralize(strtolower(str_replace(" ","_",trim($new_label))));
        $select_str = "\n".'         <div class="field">'."\n";
        $select_str .= '                <label class="label">'.trim($new_label).'</label>'."\n";
        $select_str .= '                <div class="select" style="width:100%;">'."\n";
        $select_str .= '                    <select class="input" ';

        if (self::$jsapp == "ng") { $select_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };
        //if (self::$jsapp == "ng") { $select_str .= ' ng-model="'.Inflect::singularize($table).'.'.$field_id.'"'; };

        $select_str .='>'."\n";
        $select_str .= '                        <option value="-1">--- Select '.$new_label.'---</option>'."\n";
        $select_str .= '                        <option ng-repeat="'.$ref_value.' in '.$ref_table.'" value={{'.$ref_value.'.id}}>{{'.$ref_value.'.id}}</option>';
        $select_str .= '                    </select>'."\n";
        $select_str .= '                    <a onclick="doPop(\'Add '.ucwords(Inflect::singularize(str_replace("_"," ",$ref_table))).'\',\''.$ref_file.'\');" class="btn"><i class="fa fa-plus"></i></a>'."\n";
        $select_str .= '                </div>'."\n";
        $select_str .= '             </div>'."\n";
        return $select_str;
    }

    private static function getSUTextarea($name, $table,$disp=null) {
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $tb = Inflect::singularize($table);
        $txt_str = "\n".'             <div class="field" ';
        $txt_str .= '>'."\n";
        $txt_str .= '                   <label class="label">'.$label.'</label>'."\n";
        $txt_str .= '                   <div class="control">'."\n";
        $txt_str .= '                       <textarea id="'.$field_id.'" name="'.$field_id.'"';

        if (self::$jsapp == "ng") { $txt_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };

        //if (self::$jsapp == "ng") { $txt_str .= ' ng-model="'.Inflect::singularize($table).'.'.$field_id.'"'; };

        $txt_str .= ' class="textarea"';
        $txt_str .= '></textarea>'."\n";
        $txt_str .= '                   </div>'."\n";
        $txt_str .= '                 </div>'."\n";
        if ($disp == 'none') {
            $txt_str ="\r\n".'          <input type="hidden" id="'.$field_id.'" name="'.$field_id.'" value="">'."\r\n";
        }
        return $txt_str; 

    }


    private static function getTextarea($name, $table){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $tb = Inflect::singularize($table);
        $txt_str = "\n".'             <div class="field">'."\n";
        $txt_str .= '                   <label class="label">'.$label.'</label>'."\n";
        $txt_str .= '                   <div class="control">'."\n";
        $txt_str .= '                       <textarea id="'.$field_id.'" name="'.$field_id.'"';
        if (self::$jsapp == "ng") { $txt_str .= ' ng-model="'.$tb.'.'.$field_id.'"'; };
        $txt_str .= ' class="textarea"';
        $txt_str .= '></textarea>'."\n";
        $txt_str .= '                   </div>'."\n";
        $txt_str .= '                 </div>'."\n";
        return $txt_str;
    }

    private static function getButtonGrp($tbl=null){
        $btn_str = "\n".'        <div class="field is-grouped">'."\n";
        $btn_str .= '            <p class="control">'."\n";
        $btn_str .= '              <button ';
        if (self::$jsapp == "ng") { $btn_str .= 'type="button" ng-click="'.Inflect::singularize($tbl).'_save()"'; };

        $btn_str .= ' class="button is-primary">'."\n";
        $btn_str .= '                Submit'."\n";
        $btn_str .= '              </button>'."\n";
        $btn_str .= '            </p>'."\n";
        $btn_str .= '            <p class="control">'."\n";
        $btn_str .= '              <button type="reset" class="button is-light">'."\n";
        $btn_str .= '                Clear'."\n";
        $btn_str .= '              </button>'."\n";
        $btn_str .= '            </p>'."\n";
        $btn_str .= '        </div>'."\n";
        return $btn_str;
    }

}