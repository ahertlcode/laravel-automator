<?php
namespace Automator\Support\Forms;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Utilities;


class Bulma {
    private static $tables = array();
    private static $dbh;
    private static $jsapp;
    public static function make($jsapp=null, $landing=false){
        global $dbname;
        if ($jsapp != null) self::$jsapp = $jsapp;
        self::$dbh = new DbHandlers();
        foreach(self::$dbh->show_dbTables() as $table){
            self::$tables[] = $table["Tables_in_".$dbname];
        }
        self::forms(self::$tables);
        if($landing) self::makeLandingPage(self::$tables);
        self::copy_assets();
    }


    private static function headerFile() {
        global $dbname;
        $lheaders = "<!DOCTYPE html>\r\n".'  <html lang="en">'."\r\n";
        $lheaders .= "    <head>\r\n      <title>$dbname &reg;::Portal</title>\r\n";
        $lheaders .= '        <meta content="text/html" charset="utf-8" >'."\r\n";
        $lheaders .= '        <meta name="viewport" content="width=device-width, initial-scale=1">'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/bulma.min.css">'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/jquery-ui.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/jquery.datepick.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/uploadfile.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/fontawesome-all.min.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/custom/slide-menu.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="../../assets/css/custom/table-header.css" >'."\r\n";
        $lheaders .= "      </head>";
        return $lheaders;
    }

    private static function makeLandingPage($tables) {
        global $dbname;
        global $app_dir;
        $lheaders = self::headerFile();
        $lbodyo = "\r\n    ".' <body>'."\r\n";
        $lbodyo .= '            <nav class="navbar" role="navigation" aria-label="main navigation">'."\n";
        $lbodyo .= '                <div class="navbar-brand">'."\n";
        $lbodyo .= '                    <a class="navbar-item" href="/">'."\n";
        $lbodyo .= '                        <h3>'.$dbname.'</h3>'."\n";
        $lbodyo .= '                     </a>'."\n";
        $lbodyo .= '                     <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">'."\n";
        $lbodyo .= '                        <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                        <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                        <span aria-hidden="true"></span>'."\n";
        $lbodyo .= '                     </a>'."\n";
        $lbodyo .= '                </div>'."\n";
        $lbodyo .= '                <div class="navbar-menu">'."\n";
        $lbodyo .= '                    <div class="navbar-end">'."\n";   
        $lbodyo .= '                        <div class="navbar-item">'."\n";
        $lbodyo .= '                            <div class="buttons">'."\n";
        $lbodyo .= '                                <a class="button is-primary">'."\n";
        $lbodyo .= '                                    <strong>Sign up</strong>'."\n";
        $lbodyo .= '                                </a>'."\n";
        $lbodyo .= '                                <a class="button is-light">'."\n";
        $lbodyo .= '                                    Log in'."\n";
        $lbodyo .= '                                </a>'."\n";
        $lbodyo .= '                            </div>'."\n";
        $lbodyo .= '                        </div>'."\n";
        $lbodyo .= '                    </div>'."\n";
        $lbodyo .= '                </div>'."\n";
        $lbodyo .= '            </nav>'."\n";
        $lbodyo .= '            <section class="main-content columns is-fullheight">'."\n";
        $lbodyo .= '                <aside class="column is-2 is-narrow-mobile is-fullheight section is-hidden-mobile">'."\n";
        $lbodyo .= '                    <p class="menu-label is-hidden-touch">Navigation</p>'."\n";
        $lbodyo .= '                    <ul class="menu-list">'."\n";
        foreach ($tables as $tbl) {
            $lbodyo .= '                    <li>'."\n";
            $lbodyo .= '                        <a href="#">'."\n";
            $lbodyo .= '                            '.$tbl.'    '."\n";
            $lbodyo .= '                        </a>'."\n";
            $lbodyo .= '                    </li>'."\n";
        }
        $lbodyo .= '                </ul>';
        $lbodyo .= '            </aside>'."\n";
        $lbodyo .= '            <div class="container column is-10">'."\n";
        $lbodyo .= '                <div class="section">'."\n";
        $lbodyo .= '                </div>'."\n";
        $lbodyo .= '            </div>'."\n";
        $lbodyo .= '        </section>'."\n";
        $lbody = $lheaders.$lbodyo;
        $file_dir = $app_dir."/resources/views/";
        $views_file = $app_dir."/resources/views/index.html";
        if(is_readable($views_file)){
            file_put_contents($views_file, $lbody);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir/resources/views/");
            $fp = fopen($views_file,"w+");
            fwrite($fp, "file created", 128);
            fclose($fp);
            file_put_contents($views_file, $lbody);
        }

    }

    private static function copy_assets(){
        global $app_dir;
        if(!file_exists($app_dir.'/assets/css'))
        {
            mkdir($app_dir.'/assets/css', 0777, true);
            mkdir($app_dir.'/assets/js', 0777, true);
            mkdir($app_dir.'/assets/webfonts', 0777, true);
            mkdir($app_dir.'/assets/ckeditor', 0777, true);
            mkdir($app_dir.'/server', 0777, true);
        }

        utilities::xcopy('automator/css/', $app_dir.'/assets/css');
        utilities::xcopy('automator/js/', $app_dir.'/assets/js');
        utilities::xcopy('automator/webfonts/', $app_dir.'/assets/webfonts');
        utilities::xcopy('automator/ckeditor/', $app_dir.'/assets/ckeditor');
    }



    private static function forms($tables){
        if(is_array($tables)){
            foreach($tables as $table){
                self::make_form($table);
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
            if($field["Field"]==="id" || $field["Field"]==="status" || $field["Field"]==="created_by" || $field["Field"]==="user_id") continue;
            if($field["Field"]==="password_digest" || $field["Field"]==="password_reset_token" || $field["Field"]==="api_token" || $field["Field"]==="remember_token") continue;
            $form_fields[] = $field;
        }
        self::do_html_form_create($form_fields, $table);
        self::do_html_form_edit($form_fields, $table);
    }

    /**
     * .input .textarea .select
     * .checkbox .radio
     */
    private static function do_html_form_create($fields, $table){
        global $dbname;
        global $app_dir;
        $filename = Inflect::singularize($table);
        $form_str = '<!DOCTYPE html>'."\n";
        $form_str .= '<html lang="en">'."\n";
        $form_str .= '  <head>'."\n";
        $form_str .= '      <title> create'.$table.'</title>'."\n";
        $form_str .= '      <meta charset="UTF-8">'."\n";
        $form_str .= '      <meta name="viewport" content="width=device-width, initial-scale=1">'."\n";
        $form_str .= '  </head>'."\n";
        $form_str .= '  <body>'."\n";
        $form_str .= '      <form class="form container" method="POST" enctype="multipart/form-data">'."\n";
        $form_str .= '          <h1 class="title is-3">ADD '.strtoupper(str_replace("_"," ",Inflect::singularize($table))).'</h1>'."\n";
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
        $form_str .= '  </body>'."\n";
        $form_str .= '</html>'."\n";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/create.".ucfirst($table).".html";
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
        $form_str = '<!DOCTYPE html>'."\n";
        $form_str .= '<html lang="en">'."\n";
        $form_str .= '  <head>'."\n";
        $form_str .= '      <title> create'.$table.'</title>'."\n";
        $form_str .= '      <meta charset="UTF-8">'."\n";
        $form_str .= '      <meta name="viewport" content="width=device-width, initial-scale=1">'."\n";
        $form_str .= '  </head>'."\n";
        $form_str .= '  <body>'."\n";
        $form_str .= '    <form class="form container" method="POST" enctype="multipart/form-data">'."\n";
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
        $form_str .= '  </body>'."\n";
        $form_str .= '</html>'."\n";
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

    private static function getInputField($name, $type, $table, $is_required){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $input_str = "\n".'         <div class="field">'."\n";
        $input_str .= '            <label class="label">'.$label.'</label>'."\n";
        $input_str .= '            <div class="control">'."\n";
        $input_str .= '             <input id="'.$field_id.'" name="'.$field_id.'"';
        if (self::$jsapp == "ng") { $input_str .= ' ng-model="'.$table.'.'.$field_id.'"'; };
        $input_str.=' class="input" type="'.$type.'" ';
        if($is_required){
            $input_str .= ' required';
        }
        $input_str .= '>'."\n";
        $input_str .='            </div>'."\n";
        /*if($is_required){
            $input_str .= '        <p class="help">This field is required</p>'."\n";
        }*/
        $input_str .= '        </div>';
        return $input_str;
    }

    private static function getSelectField($name, $table){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $new_label = ucwords(str_replace(" id", "", strtolower($label)));
        $items = Inflect::pluralize(strtolower(str_replace(" ","_",trim($new_label))));
        $select_str = "\n".'         <div class="field">'."\n";
        $select_str .= '                <label class="label">'.trim($new_label).'</label>'."\n";
        $select_str .= '                <div class="select" style="width:100%;">'."\n";
        $select_str .= '                    <select class="input" ';
        if (self::$jsapp == "ng") { $select_str .= ' ng-model="'.$table.'.'.$field_id.'"'; };
        $select_str .=' >'."\n";
        $select_str .= '                        <option value="-1">Select '.$new_label.'</option>'."\n";
        //$select_str .= '                @foreach($'.$items.' as $'.Inflect::singularize($items).')'."\n";
        //$select_str .= '                        <option value="{{$'.Inflect::singularize($items).'->id}}">{{$'.Inflect::singularize($items).'->'.Inflect::singularize($items).'}}</option>'."\n";
        //$select_str .= '                @endforeach'."\n";
        //$select_str .= '                <option>{{item.name}}</option>'."\n";
        $select_str .= '                    </select>'."\n";
        $select_str .= '                </div>'."\n";
        $select_str .= '             </div>'."\n";
        return $select_str;
    }

    private static function getTextarea($name, $table){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $txt_str = "\n".'             <div class="field">'."\n";
        $txt_str .= '                   <label class="label">'.$label.'</label>'."\n";
        $txt_str .= '                   <div class="control">'."\n";
        $txt_str .= '                       <textarea id="'.$field_id.'" name="'.$field_id.'"';
        if (self::$jsapp == "ng") { $txt_str .= ' ng-model="'.$table.'.'.$field_id.'"'; };
        $txt_str .= ' class="textarea"></textarea>'."\n";
        $txt_str .= '                   </div>'."\n";
        $txt_str .= '                 </div>'."\n";
        return $txt_str;
    }

    private static function getButtonGrp($tbl=null){
        $btn_str = "\n".'        <div class="field is-grouped">'."\n";
        $btn_str .= '            <p class="control">'."\n";
        $btn_str .= '              <button ';
        if (self::$jsapp == "ng") { $btn_str .= 'type="button" ng-click="save'.ucwords($tbl).'()"'; };
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