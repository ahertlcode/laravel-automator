<?php

namespace Automator\Support\Tests;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class Feature {
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

            self::touchTestCase($obj['model'], $tbl);
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

    private static function touchTestCase($model, $table) {
        global $app_dir;
        $test_name = strtolower($table);
        $ctrlstr = "<?php\n\n";
        $ctrlstr .= "namespace Tests\Feature;\n\n";
        $ctrlstr .= "use App\Models\\".$model.";\n" ;
        $ctrlstr .= "use Illuminate\Foundation\Testing\RefreshDatabase;\n";
        $ctrlstr .= "use Tests\TestCase;\n\n";
        $ctrlstr .= "class ".$model."Test extends TestCase\n{\n";
        $ctrlstr .= "    use RefreshDatabase;\n\n";
        $ctrlstr .= "        /** @test */\n";
        $ctrlstr .= "    ".self::storeTestData($test_name, $model);
        $ctrlstr .= "        /** @test */\n";
        $ctrlstr .= "    ".self::updateTestData($test_name, $model);
        $ctrlstr .= "        /** @test */\n";
        $ctrlstr .= "    ".self::deleteTestData($test_name, $model);
        $ctrlstr .= "\n}\n";
        file_put_contents($app_dir."/tests/Feature/".$model."Test.php", $ctrlstr);
    } 


    private static function storeTestData($test_name, $model) {
        $functstr = "public function a_".$test_name."_can_be_created()\n    {\n";
        $functstr .= "        \$this->withoutExceptionHandling();\n";
        $functstr .= "        \$response = \$this->post('/".Inflect::pluralize($test_name)."',[\n";
        $functstr .= "              //enter your test data here\n";
        $functstr .= "          ]);\n";
        $functstr .= "         $".$test_name." = ".$model."::first();\n";
        $functstr .= "         \$this->assertCount(1, {$model}::all());";
        $functstr .= "    \n    }\n\n";
        return $functstr;
    }

    private static function updateTestData($test_name, $model) {
        $functstr = "public function a_".$test_name."_can_be_updated()\n    {\n"; 
        $functstr .= "        \$this->withoutExceptionHandling();\n";
        $functstr .= "        \$response = \$this->post('/".Inflect::pluralize($test_name)."',[\n";
        $functstr .= "              //enter your test data here\n";
        $functstr .= "         ]);\n";
        $functstr .= "        $".$test_name." = ".$model."::first();\n";
        $functstr .= "        \$response = \$this->patch('/".Inflect::pluralize($test_name)."/'.$"."{$test_name}->id,[\n";
        $functstr .= "              //enter values to update or patch the previous data here\n";
        $functstr .= "         ]);\n";
        $functstr .= "       \$this->assertEquals(1, {$model}::first());";
        $functstr .= "\n    }\n\n";
        return $functstr;
    }

    private static function deleteTestdata($test_name, $model) {
        $functstr = "public function a_".$test_name."_can_be_deleted()\n    {\n"; 
        $functstr .= "       \$this->withoutExceptionHandling();\n";
        $functstr .= "       \$this->post('/{$test_name}s',[\n";
        $functstr .= "          //enter your test data here\n";
        $functstr .= "        ]);\n";
        $functstr .= "       $".$test_name." = ".$model."::first();\n";
        $functstr .= "       \$this->assertEquals(1, {$model}::first());\n";
        $functstr .= "       \$response = \$this->delete('/".Inflect::pluralize($test_name)."'.$"."{$test_name}->id);\n";
        $functstr .= "       \$this->assertEquals(0, {$model}::all());";
        $functstr .= "   \n    }\n\n";
        return $functstr;
    }
}