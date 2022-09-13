<?php
namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class Apimake
{
    private $con;
    private $tables = array();
    private $relations;
    private $excludeTables;
    private $excludeColumns;
    public function Automate($opt=null, $tables=null, $columns=null){
        global $dbname;
        $this->con = new DbHandlers();
        if ($tables != null) $this->excludeTables = $tables;
        if ($columns != null) $this->excludeColumns = $columns;
        $dtables = $this->con->show_dbTables();
        foreach($dtables as $tb){
            $this->tables[] = $tb["Tables_in_".$dbname];
        }
        $this->MakeResources();
    }

    private function MakeResources(){
        global $app_dir;
        $tables = $this->tables;
        foreach($tables as $tbl) {
            $obj = $this->getModel($tbl);

            if (strtoupper($tbl) == "USERS" || strtoupper($tbl) == "USER") {
                $artisan_cmd = "php artisan make:controller ".$obj['controller']." --model=".$obj['model'];
                exec("cd $app_dir && $artisan_cmd");
            } else {
                if (!in_array($tbl, $this->excludeTables)) {
                    $artisan_cmd = "php artisan make:model ".$obj["model"]." -cr";
                    exec("cd $app_dir && $artisan_cmd");
                }
            }

            if (!in_array($tbl, $this->excludeTables)) {
                $this->touchModel($obj['model'], $tbl);
                $this->touchController($obj['controller'], $tbl);
            }
        }
        $this->touchRoute();
        $this->touchEnv();
        exec("cd $app_dir && php artisan migrate");
    }

    private function touchEnv() {
        echo "touching env file...";
        global $app_dir;
        global $config;
        $envs = explode("\n", \file_get_contents($app_dir."/.env"));
        $envStr = "";
        foreach($envs as $env) {
            $pair = explode("=", $env);
            if ($pair[0]=="DB_USERNAME") $envStr .= str_replace($pair[1], $config['user'], $env)."\n";
            elseif ($pair[0]=="DB_PASSWORD") $envStr .= str_replace($pair[1], $config['password'], $env)."\n";
            else $envStr .= $env."\n";
        }
        if (!empty($envStr)) \file_put_contents($app_dir."/.env", $envStr);
    }

    private function touchRoute(){
        global $app_dir;
        $routes = '<?php'."\r\n\r\n".'use Illuminate\Http\Request;'."\r\n";
        $routes .= 'use Illuminate\Support\Facades\Route;'."\r\n";
        $routes .= 'use App\Http\Controllers\AuthController;'."\r\n";
        $routes .= <<<END
        /*
        |--------------------------------------------------------------------------
        | API Routes
        |--------------------------------------------------------------------------
        |
        | Here is where you can register API routes for your application. These
        | routes are loaded by the RouteServiceProvider within a group which
        | is assigned the "api" middleware group. Enjoy building your API!
        |
        */
        END;
        $routes .= "\r\n".'Route::post(\'/auth/register\', [AuthController::class, \'register\']);'."\r\n";
        $routes .= 'Route::post(\'/auth/login\', [AuthController::class, \'login\']);'."\r\n";

        $routes .= 'Route::group([\'middleware\' => [\'auth:sanctum\']], function() {'."\r\n";
        $routes .= '    Route::get(\'/user\', function(Request $request) {'."\r\n";
        $routes .= '        return auth()->user();'."\r\n".'    });'."\r\n\r\n";

        $routes .= '    Route::post(\'/auth/logout\', [AuthController::class, \'logout\']);'."\r\n";

        foreach($this->tables as $table) {
            $ctrl = $this->getModel($table)['controller'];
            $routes .= "\n\n    /**\n     * API endpoints for ".$table."\n     * responding to all API calls\n     * GET, POST, PUT/PATCH, DELETE\n     */\n";
            $routes .= "    Route::apiResource('".$table."', '\App\Http\Controllers\\".$ctrl."');\n";
        }
        $routes .= "\r\n".'});'."\r\n";
        $api_route_file = $app_dir."/routes/api.php";
        \file_put_contents($api_route_file, $routes);
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
        $content = file_get_contents($app_dir."/app/Models/".$model.".php");
        $modelstr = trim(str_replace('use Auth;','', explode("class", $content)[0]));
        if (!strpos($modelstr, 'use Auth;') && $model != 'User') $modelstr .= "\r\n".'use Auth;'."\r\n"; 
        $props = $this->getFields($table);
        $modelstr .= "\r\n\r\n\r\n";
        if ($model != 'User') $modelstr .= "class ".$model." extends Model\n{\n    use HasFactory;\n";
        if ($model == 'User')  $modelstr .= "class ".$model." extends Authenticatable implements MustVerifyEmail\n{\n    use HasFactory;\n    use HasApiTokens;\n";
        $modelstr .= '    protected $table="'.$table.'";'."\r\n";
        foreach($this->con->tableDesc($table) as $field) {
            if ($field['Key'] == 'PRI') $modelstr .= '    protected $primaryKey="'.$field['Field'].'";'."\r\n";
        }
        $modelstr .= "    /**\n     *\n     * Mass assignable columns\n     *\n     */\n";
        $modelstr .= '    protected $fillable = [';
        $fite = 0;
        foreach($props['fillable'] as $fillable) {
            if($fite < sizeof($props['fillable'])-1){
                $modelstr .= "'".$fillable."',";
            }else{
                $modelstr .= "'".$fillable."'";
            }
            $fite += 1;
        }
        $modelstr .= ",'user_id','owner_id'";
        $modelstr .= "];\r\n\r\n";

        $modelstr .= "    /**\n     *\n     * Hidden columns not to be returned in query result.\n     *\n     */\n";
        $modelstr .= '    protected $hidden = [';
        $fite = 0;
        foreach($props['hidden'] as $item){
            if($fite < sizeof($props['hidden'])-1){
                $modelstr .= "'".$item."',";
            }else{
                $modelstr .= "'".$item."'";
            }
            $fite += 1;
        }
        $modelstr .= "];\r\n\r\n";
        $fkeys = $this->getTableFkeys($table);
        if ($fkeys != null) $this->dorelation($fkeys);
        if ($this->relations != "") $modelstr .= $this->relations;
        $modelstr .= "\n}";
        file_put_contents($app_dir."/app/Models/".$model.".php", $modelstr);
    }

    function touchController($controller, $table){
        global $app_dir;
        $props = $this->getFields($table);
        $cols = $props['fillable'];
        $ctrlstr = explode("class", file_get_contents($app_dir."/app/Http/Controllers/".$controller.".php"))[0];
        $fkeys = $this->getTableFkeys($table);
        if ($fkeys != null) {
            foreach($fkeys as $fkey) {
                $insertmodel = 'use App\Models\\'.$this->getModel($fkey['REFERENCED_TABLE_NAME'])['model'].';';
                if (!strpos($ctrlstr, $insertmodel)) $ctrlstr .= $insertmodel."\r\n";
            }
        }
        if (!strpos($ctrlstr, 'use App\Traits\ApiResponser;')) $ctrlstr .= 'use App\Traits\ApiResponser;'."\r\n";
        if (!strpos($ctrlstr, 'use Auth;')) $ctrlstr .= "use Auth;\n\n";
        $ctrlstr .= "class ".$controller." extends Controller\n{\n";
        if (!strpos($ctrlstr, 'use ApiResponser;')) $ctrlstr .= '    use ApiResponser;'."\r\n";
        //$ctrlstr .= $this->getCurrentUser();
        $ctrlstr .= $this->getIndexFunct($table);
        $ctrlstr .= $this->getStoreFunct($table, $cols);
        $ctrlstr .= $this->getShowFunct($table);
        $ctrlstr .= $this->getUpdateFunct($table, $cols);
        $ctrlstr .= $this->getDestroyFunct($table);
        $ctrlstr .= $this->getStub();
        $ctrlstr .= "\n}\n";
        file_put_contents($app_dir."/app/Http/Controllers/".$controller.".php", $ctrlstr);
        exec('cp Controller/AuthController.php '.$app_dir."/app/Http/Controllers");
        exec('cp -r Traits '.$app_dir."/app");
    }

    private function getFields($tbl){
        $struct = array();
        $fields = array();
        $hfield = array();
        $sql = "desc ".$tbl;
        $struct = $this->con->tableDesc($tbl);
       
        foreach($struct as $field){
            if (!in_array($field['Field'], $this->excludeColumns)) $fields[] = $field["Field"];
            if (in_array($field['Field'], $this->excludeColumns)) $hfield[] = $field['Field'];
        }

        if ($tbl == "users" || $tbl == "user") {
            array_push($hfield, "password");
        }

        return ['fillable'=>$fields, 'hidden'=>$hfield];
    }

    private function getTableFkeys($tbl) {
        global $dbname;
        $foreignKeys = array();
        $tsql = "desc ".$tbl;
        $cols = $this->con->tableDesc($tbl);
        foreach($cols as $col) {
            if ($col['Key'] == "MUL") $foreignKeys = array_merge($foreignKeys, $this->con->getFkeys($dbname, $tbl, $col['Field']));
        }
        if (count($foreignKeys) > 0) return $foreignKeys;
        return null;
    }

    private function dorelation($fkeys){
        foreach($fkeys as $fkey) {
            $model = $this->getModel($fkey['REFERENCED_TABLE_NAME'])['model'];
            $col = $fkey['COLUMN_NAME'];
            $this->relations .= "    /**\n     * Get the ".$fkey['REFERENCED_TABLE_NAME']." for this model.\n     *\n     * @return App\\".$model."\n     */\n";
            $this->relations .= '    public function '.$fkey['REFERENCED_TABLE_NAME']."()\n    {\n";
            $this->relations .= '        return $this->belongsTo(';
            $this->relations .= "'App\Models\\".$model."', '".$col."')->get();\n";
            $this->relations .= "    }\n\n";
        }
    }

    private function getIndexFunct($table){
        $functstr = "    /**\n     * Display a listing of the resource.\n     *\n     * @return \Illuminate\Http\Response\n     */\n";
        $model = $this->getModel($table)['model'];
        $fkeys = $this->getTableFkeys($table); //$this->getFields($table)['foreignKey'];
        $functstr .= "    public function index()\n    {\n";
        $functstr .= '        $'.$table.'  = '.$model.'::where(["owner_id" => $this->getDataOwner()->id])->orwhere(["user_id" => $this->currentUser()->id])->get();'."\r\n";
        if ($fkeys != null) {
            if (count($fkeys)>0) {
                $functstr .= '        $pointer = 0;'."\r\n";
                $functstr .= '        foreach($'.$table.' as $'.Inflect::singularize($table).') {'."\n";
                foreach($fkeys as $fkey) {
                    $functstr .= '            $'.$table.'[$pointer]["'.Inflect::singularize($fkey["REFERENCED_TABLE_NAME"]).'"] = $'.Inflect::singularize($table).'->'.$fkey['REFERENCED_TABLE_NAME'].'();'."\n";
                }
                $functstr.= '            $pointer++;'."\r\n";
                $functstr .= '        }'."\n";
            }
        }
        $functstr .= '        return $this->success($'.$table.', "'.ucfirst($table).' retrieved!", 200);'."\r\n";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getStoreFunct($table, $cols){
        $functstr = "    /**\n     * Store a newly created resource in storage.\n     *\n     ".'* @param  \Illuminate\Http\Request  $request'."\n";
        $functstr .="     * @return \Illuminate\Http\Response\n     */\n";
        $model = $this->getModel($table)['model'];
        $functstr .= '    public function store(Request $request)'."\n    {\n";
        $functstr .= '        $request->request->add(["owner_id"=>$this->getDataOwner()->id]);'."\r\n\r\n";
        $functstr .= '        $request->request->add(["user_id"=>$this->currentUser()->id]);'."\r\n\r\n";
        $fields = $this->getFields($table)['fillable'];
        $functstr .= '        $request->validate(['."\r\n";
        foreach($fields as $field) {
            $functstr .= '            \''.$field.'\' => \'required\','."\r\n";
        }
        $functstr .= '        ]);'."\r\n\r\n";
        $functstr .= '        $isExist = '.$this->getModel($table)['model'].'::where(['."\r\n";
        foreach($fields as $field) {
            $functstr .= '            \''.$field.'\' => $request->'.$field.','."\r\n";
        }
        $functstr .= '        ])->exists();'."\r\n\r\n";

        $functstr .= '        if(!$isExist) {'."\r\n";
        $functstr .= '            if('.$model.'::create($request->all())) {'."\r\n";
        $functstr .= '                return $this->success(null, "'.Inflect::singularize($table).' created successfully!", 201);'."\r\n";
        $functstr .= '            } else {'."\r\n";
        $functstr .= '                return $this->error("There is an error!", 500);'."\r\n";
        $functstr .= '            }'."\r\n";
        $functstr .= '        } else {'."\r\n";
        $functstr .= '            return $this->error("This '.Inflect::singularize($table).' entry exists.", 400);'."\r\n";
        $functstr .= '        }'."\r\n";
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
        $functstr .= '        return $this->success($'.strtolower($param).', "'.ucfirst($param).' returned!", 200);'."\r\n";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private function getUpdateFunct($table, $cols){
        $model = $this->getModel($table)['model'];
        $param = Inflect::singularize($table);
        $functstr = "    /**\n     * Update the specified resource in storage.\n     *\n";
        $functstr .= "     * @param  \Illuminate\Http\Request  ".'$request'."\n";
        $functstr .= "     * @param  \App\\$model  ".'$'.$param."\n";
        $functstr .= "     * @return \Illuminate\Http\Response\n     */\n";
        $functstr .= "    public function update(Request ".'$request'.", $model ".'$'.$param.")\n    {\n";
        $functstr .= '        if ($'.$param.'->update($request->all())) {'."\r\n";
        $functstr .= '            return $this->success($'.strtolower($param).', "'.ucfirst($param).' updated successfully!", 200);'."\r\n";
        $functstr .= '        } else {'."\r\n";
        $functstr .= '            return $this->error("There is an error", 500);'."\r\n";
        $functstr .= '        }'."\r\n";
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
        $functstr .= '         if ($'.$param.'->delete()) {'."\r\n";
        $functstr .= '             return $this->success($'.$param.', "'.ucfirst($param).' deleted successfully!", 200);'."\r\n";
        $functstr .= '         } else {'."\r\n";
        $functstr .= '              return $this->error("There is an error", 500);'."\r\n";
        $functstr .= '         }'."\r\n";
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