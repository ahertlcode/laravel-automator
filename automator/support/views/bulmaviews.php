<?php
namespace Automator\Support\Views;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class BulmaViews{
    private static $tables = array();
    private static $dbh;
    public static function make(){
        global $dbname;
        self::$dbh = new DbHandlers();
        foreach(self::$dbh->show_dbTables() as $table){
            self::$tables[] = $table["Tables_in_".$dbname];
        }
        self::forms(self::$tables);
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
        $form_str = '@extends(\'layouts.bulma\')'."\n";
        $form_str .= '@section(\'title\', \'creating new '.Inflect::singularize($table).'\')'."\n";
        $form_str .= '@section(\'sidebar\')'."\n";
        $form_str .= '@parent'."\n";
        $form_str .= '@endsection'."\n";
        $form_str .= '@section(\'content\')'."\n";
        $form_str .= '<form action="{{ route(\''.$table.'.create\') }}" class="form container" method="POST" enctype="multipart/form-data">';
        $form_str .= "\n".'    <h1 class="title is-3">ADD '.strtoupper(str_replace("_"," ",Inflect::singularize($table))).'</h1>'."\n";
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
        $form_str .= self::getButtonGrp();
        $form_str .= "</form>\n@endsection";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/create.blade.php";
        if(is_readable($views_file)){
            file_put_contents($views_file, $form_str);
        }else{
            exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir./resources/views/");
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
        $form_str = '@extends(\'layouts.bulma\')'."\n";
        $form_str .= '@section(\'title\', \'Editing '.Inflect::singularize(ucwords($table)).'\')'."\n";
        $form_str .= '@section(\'sidebar\')'."\n";
        $form_str .= '@parent'."\n";
        $form_str .= '@endsection'."\n";
        $form_str .= '@section(\'content\')'."\n";
        $form_str .= '<form action="{{ route(\''.$table.'.edit\') }}" class="form container" method="POST" enctype="multipart/form-data">';
        $form_str .= "\n".'    <h1 class="title is-3">EDIT '.strtoupper(str_replace("_"," ",Inflect::singularize($table))).'</h1>'."\n";
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
        $form_str .= self::getButtonGrp();
        $form_str .= "</form>\n@endsection";
        $file_dir = $app_dir."/resources/views/$table";
        $views_file = $app_dir."/resources/views/$table/edit.blade.php";
        if(is_readable($views_file)){
            file_put_contents($views_file, $form_str);
        }else{
            if(!is_dir($file_dir)) exec("mkdir $file_dir");
            exec("chmod -R 755 $app_dir./resources/views/");
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
        $input_str = "\n".'    <div class="field">'."\n";
        $input_str .= '        <label class="label">'.$label.'</label>'."\n";
        $input_str .= '        <div class="control">'."\n";
        $input_str .= '            <input id="'.$field_id.'" name="'.$field_id.'" class="input @error(\''.$field_id.'\') is-invalid @enderror" value="{{ old(\''.$field_id.'\') }}" type="'.$type.'" ';
        if($is_required){
            $input_str .= ' required';
        }
        $input_str .= '>'."\n            ";
        $input_str .= '@error(\''.$field_id.'\')'."\n            ";
        $input_str .= '<span class="notification is-danger">'."\n                ";
        $input_str .= '<strong>{{ $message }}</strong>'."\n            ";
        $input_str .= '</span>'."\n            ";
        $input_str .= '@enderror'."\n";
        $input_str .='        </div>'."\n";
        /*if($is_required){
            $input_str .= '        <p class="help">This field is required</p>'."\n";
        }*/
        $input_str .= '    </div>';
        return $input_str;
    }

    private static function getSelectField($name, $table){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $new_label = ucwords(str_replace(" id", "", strtolower($label)));
        $items = Inflect::pluralize(strtolower(str_replace(" ","_",trim($new_label))));
        $select_str = "\n".'    <div class="field">'."\n";
        $select_str .= '        <label class="label">'.trim($new_label).'</label>'."\n";
        $select_str .= '        <div class="select" style="width:100%;">'."\n";
        $select_str .= '            <select class="input @error(\''.$field_id.'\') is-invalid @enderror" >'."\n";
        $select_str .= '                <option value="-1">Select '.$new_label.'</option>'."\n";
        $select_str .= '                @foreach($'.$items.' as $'.Inflect::singularize($items).')'."\n";
        $select_str .= '                <option value="{{$'.Inflect::singularize($items).'->id}}">{{$'.Inflect::singularize($items).'->'.Inflect::singularize($items).'}}</option>'."\n";
        $select_str .= '                @endforeach'."\n";
        //$select_str .= '                <option>{{item.name}}</option>'."\n";
        $select_str .= '            </select>'."\n";
        $select_str .= '        </div>'."\n";
        $select_str .= '    </div>'."\n";
        return $select_str;
    }

    private static function getTextarea($name, $table){
        [$field_id, $label, $model] = self::getLabels($name, $table);
        $txt_str = "\n".'    <div class="field">'."\n";
        $txt_str .= '        <label class="label">'.$label.'</label>'."\n";
        $txt_str .= '            <div class="control">'."\n";
        $txt_str .= '                <textarea id="'.$field_id.'" name="'.$field_id.'" class="textarea @error(\''.$field_id.'\') is-invalid @enderror" ></textarea>'."\n";
        $txt_str .= '            </div>'."\n";
        $txt_str .= '        </div>'."\n";
        return $txt_str;
    }

    private static function getButtonGrp(){
        $btn_str = "\n".'    <div class="field is-grouped">'."\n";
        $btn_str .= '        <p class="control">'."\n";
        $btn_str .= '            <button type="submit" class="button is-primary">'."\n";
        $btn_str .= '                Submit'."\n";
        $btn_str .= '            </button>'."\n";
        $btn_str .= '        </p>'."\n";
        $btn_str .= '        <p class="control">'."\n";
        $btn_str .= '            <button type="reset" class="button is-light">'."\n";
        $btn_str .= '                Clear'."\n";
        $btn_str .= '            </button>'."\n";
        $btn_str .= '        </p>'."\n";
        $btn_str .= '    </div>'."\n";
        return $btn_str;
    }

}