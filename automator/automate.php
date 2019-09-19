<?php
namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class Automate
{
    private $con;
    private $tables = array();
    private $relations;
    public function Automate(){
        global $dbname;
        $this->con = new DbHandlers();
        $dtables = $this->con->show_dbTables();
        foreach($dtables as $tb){ 
            $this->tables[] = $tb["Tables_in_".$dbname];
        }
        $this->MakeResources();
    }

    private function MakeResources(){
        global $app_dir;
        $tables = $this->tables;
        foreach($tables as $tbl){
            if($tbl === "users") continue;
            $obj = $this->getModel($tbl);
            $artisan_cmd = "php artisan make:model ".$obj["model"]." -cr";
            
            exec("cd $app_dir && $artisan_cmd");

            $this->touchModel($obj['model'], $tbl);
            $this->touchController($obj['controller'], $tbl);
            $this->touchRoute($tbl, $obj['controller']);
        }
    }

    private function touchRoute($table, $ctrl){
        global $app_dir;
        $routes = "\n\n/**\n * API endpoints for ".$table."\n * responding to all API calls\n * GET, POST, PUT/PATCH, DELETE\n */\n";
        $routes .= "Route::apiResource('".$table."', '".$ctrl."');\n";
        $api_route_file = $app_dir."/routes/api.php";
        //echo "Initial filesize before writing ".file_size($api_route_file)."\n";
        $fp = fopen($api_route_file, "a+");
        fwrite($fp, $routes);
        //echo "Filesize after writing routes ".file_size($api_route_file)."\n";
    }

    private function getModel($tbl){
        $tbl = strtolower($tbl);
        $table = Inflect::singularize($tbl);
        $obj = "";
        if(strpos($table, "_")>-1){
            $parts = explode("_", $table);
            foreach($parts as $part){
                $obj .= ucwords($part);
            }
            
        }else{
            $obj = ucwords($table);
        }

        $ctrlObj = $obj."Controller";
        return ['model' => $obj, 'controller' => $ctrlObj];
    }

    private function touchModel($model, $table){
        global $app_dir;
        $this->relations = "";
        $content = file_get_contents($app_dir."/app/".$model.".php");
        $modelstr = explode("class", $content)[0];
        $modelstr .= "class ".$model." extends Model\n{\n";
        $props = $this->getFields($table);
        $modelstr .= "    /**\n     *\n     * Mass assignable columns\n     *\n     */\n";
        $modelstr .= '    protected $fillable = [';
        $fite = 0;
        foreach($props['fillable'] as $item){
            if(strpos($item, "_id")>-1 || strpos($item, "_by")>-1) $this->dorelation($item);
            if($item == "") continue;
            if($fite < sizeof($props['fillable'])-1){
                $modelstr .= "'".$item."',";
            }else{
                $modelstr .= "'".$item."'";
            }
    
            $fite += 1;
        }
        $modelstr .= "];\n\n";
        $modelstr .= "    /**\n     *\n     * Hidden columns not to be returned in query result.\n     *\n     */\n";
        $modelstr .= '    protected $hidden = [';
        $fite = 0;
        foreach($props['hidden'] as $item){
            if(strpos($item, "_id")>-1 || strpos($item, "_by")>-1) $this->dorelation($item);
            if($item == "") continue;
            if($fite < sizeof($props['hidden'])-1){
                $modelstr .= "'".$item."',";
            }else{
                $modelstr .= "'".$item."'";
            }
    
            $fite += 1;
        }
        $modelstr .= "];\n\n";
        $modelstr .= $this->relations;
        $modelstr .= "\n}";
        file_put_contents($app_dir."/app/".$model.".php", $modelstr);
    }

    function touchController($controller, $table){
        global $app_dir;
        $ctrlstr = explode("class", file_get_contents($app_dir."/app/Http/Controllers/".$controller.".php"))[0];
        $ctrlstr .= "use Auth;\n\n";
        $ctrlstr .= "class ".$controller." extends Controller\n{\n";
        $ctrlstr .= $this->getCurrentUser();
        $ctrlstr .= $this->getIndexFunct($table);
        $ctrlstr .= $this->getStoreFunct($table);
        $ctrlstr .= $this->getShowFunct($table);
        $ctrlstr .= $this->getUpdateFunct($table);
        $ctrlstr .= $this->getDestroyFunct($table);
        $ctrlstr .= $this->getStub();
        $ctrlstr .= "\n}\n";
        file_put_contents($app_dir."/app/Http/Controllers/".$controller.".php", $ctrlstr);
    }

    private function getFields($tbl){
        $struct = array();
        $fields = array();
        $sql = "desc ".$tbl;
        $struct = $this->con->tableDesc($tbl);
            
        foreach($struct as $field){
            if($field["Field"]==="id" || $field["Field"]==="created_at" || $field["Field"]==="updated_at" || $field["Field"]==="status") continue;
            $fields[] = $field["Field"];
        }
        $hfield = ['id','created_at','updated_at','status'];
        return ['fillable'=>$fields, 'hidden'=>$hfield];
    }
    
    private function dorelation($col){
        if(strpos($col, "_id")>-1)
        {
            $mod = str_replace("_id","",$col);
        }else if(strpos($col, "_by")>-1){
            $mod = str_replace("_by","",$col);
        }
        
        $model = $this->getModel(Inflect::pluralize($mod));
        $this->relations .= "    /**\n     * Get the ".$mod." for this model.\n     *\n     * @return App\\".$model['model']."\n     */\n";
        $this->relations .= '    public function '.$mod."()\n    {\n";
        $this->relations .= '        return $this->belongsTo(';
        $this->relations .= "'App\\".$model['model']."', '".$col."')->get();\n";
        $this->relations .= "    }\n\n";
    }

    private function getCurrentUser(){
        $functstr = "    /**\n     * Return the currently login user\n     * An instance of App\User model\n";
        $functstr .= "     * @return App\User\n     */\n";
        $functstr .= "    protected function currentUser()\n    {\n";
        $functstr .= "        return Auth::guard('api')->user();";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getIndexFunct($table){
        $functstr = "    /**\n     * Display a listing of the resource.\n     *\n     * @return \Illuminate\Http\Response\n     */\n";
        $model = $this->getModel($table)['model'];
        $functstr .= "    public function index()\n    {\n";
        $functstr .= '        if($this->currentUser()){'."\n";
        $functstr .= '            return '.$model."::where('status', '1');";
        $functstr .= "\n        }else{\n";
        $functstr .= '            return response()->json(["info"=>"You must be logged in."], 403);';
        $functstr .= "\n        }";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getStoreFunct($table){
        $functstr = "    /**\n     * Store a newly created resource in storage.\n     *\n     ".'* @param  \Illuminate\Http\Request  $request'."\n";
        $functstr .="     * @return \Illuminate\Http\Response\n     */\n";
        $model = $this->getModel($table)['model'];
        $functstr .= '    public function store(Request $request)'."\n    {\n";
        $functstr .= '        if($this->currentUser()){'."\n";
        $functstr .= '            '.$model.'::create($request->all());'."\n";
        $functstr .= '            return response()->json(['."\n";
        $functstr .= '                "info"=>"'.ucwords(Inflect::singularize($table)).' successfully created."'."\n";
        $functstr .= '            ], 201);';
        $functstr .= "\n        }else{\n";
        $functstr .= '            return response()->json(["info"=>"You must be logged in."], 403);';
        $functstr .= "\n        }";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getShowFunct($table){
        $model = $this->getModel($table)['model'];
        $param = Inflect::singularize($table);
        $functstr = "    /**\n     * Display the specified resource.\n     *\n";
        $functstr .= "     * @param  \App\\$model ".'$'.strtolower($param)."\n";
        $functstr .= "     * @return \Illuminate\Http\Response\n     */\n";
        $functstr .= '    public function show('.$model.' $'.strtolower($param).")\n    {\n";
        $functstr .= '        if($this->currentUser()){'."\n";
        $functstr .= '            return $'.strtolower($param).";";
        $functstr .= "\n        }else{\n";
        $functstr .= '            return response()->json(["info"=>"You must be logged in."], 403);';
        $functstr .= "\n        }";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getUpdateFunct($table){
        $model = $this->getModel($table)['model'];
        $param = Inflect::singularize($table);
        $functstr = "    /**\n     * Update the specified resource in storage.\n     *\n";
        $functstr .= "     * @param  \Illuminate\Http\Request  ".'$request'."\n";
        $functstr .= "     * @param  \App\\$model  ".'$'.$param."\n";
        $functstr .= "     * @return \Illuminate\Http\Response\n     */\n";
        $functstr .= "    public function update(Request ".'$request'.", $model ".'$'.$param.")\n    {\n";
        $functstr .= '        if($this->currentUser()){'."\n";
        $functstr .= '            $'.$param."->update(".'$request'."->all());\n";
        $functstr .= "            return response()->json(['info' => '".ucwords($param)." successfully updated.'], 200);";
        $functstr .= "\n        }else{\n";
        $functstr .= '            return response()->json(["info"=>"You must be logged in."], 403);';
        $functstr .= "\n        }";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getDestroyFunct($table){
        $model = $this->getModel($table)['model'];
        $param = Inflect::singularize($table);
        $functstr = "    /**\n     * Remove the specified resource from storage.\n     *\n";
        $functstr .= "     * @param  \App\\$model  ".'$'.$param."\n";
        $functstr .= "     * @return \Illuminate\Http\Response\n     */\n";
        $functstr .= "    public function destroy($model ".'$'."$param)\n    {\n";
        $functstr .= '        if($this->currentUser()){'."\n";
        $functstr .= '            $'.$param."->delete();"."\n";
        $functstr .= "            return response()->json(['info' => '".ucwords($param)."  deleted successfully.'], 200);";
        $functstr .= "\n        }else{\n";
        $functstr .= '            return response()->json(["info"=>"You must be logged in."], 403);';
        $functstr .= "\n        }";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getStub(){
        $stubstr = "\n\n    /**\n     *\n     * Add Business Logic function below here.\n     *\n";
        $stubstr .= "     * Do not delete anything above.\n     * Neither should you add anything above.\n";
        $stubstr .= "     * In other to keep every neat and functional.\n     *\n";
        $stubstr .= "     * Happy coding...\n     *\n     */\n";
        return $stubstr;
    }

}