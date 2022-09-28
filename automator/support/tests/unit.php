<?php

namespace Automator\Support\Tests;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class Unit {
    private static $dbh;
    private static $tables = array();


    public static function make(){
        global $dbname;
        self::$dbh = new DbHandlers();
        $dtables = self::$dbh->show_dbTables();
        foreach($dtables as $tb){
            self::$tables[] = $tb["Tables_in_".$dbname];
        }
        self::MakeTest();   
    }

    public static function MakeTest() {
        global $app_dir;
        $tables = self::$tables;
        foreach ($tables as $tbl) {
            $obj = self::getModel($tbl);

            self::touchUnitTestCase($obj['model'], $tbl);
        }



    }

    private static function getModel($tbl) {
        $tbl = strtolower($tbl);
        $table = Inflect::singularize($tbl);
        $model_name = "";

        if(strpos($table, "_")>-1){
            $parts = explode("_", $table);
            foreach($parts as $part){
                $model_name .= ucwords($part);
            }

        }else{
            $model_name = ucwords($table);
        }

        return ['model' => $model_name];
    }

    private static function touchUnitTestcase($model, $table) {
        global $app_dir;
        $test_name = strtolower($table);
        $ctrlstr = "<?php\n\n";
        $ctrlstr .= "namespace Tests\Unit;\n\n";
        $ctrlstr .= "use App\Models\\".$model.";\n" ;
        $ctrlstr .= "use Illuminate\Foundation\Testing\RefreshDatabase;\n";
        $ctrlstr .= "use Tests\TestCase;\n\n";
        $ctrlstr .= "class ".$model."Test extends TestCase\n{\n";
        $ctrlstr .= "    use RefreshDatabase;\n\n";
        $ctrlstr .= "    //create functions for your unit test here\n\n\n";
        $ctrlstr .= "\n}\n";
        file_put_contents($app_dir."/tests/Unit/".$model."Test.php", $ctrlstr);
    }
}