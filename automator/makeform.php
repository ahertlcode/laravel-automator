<?php
    namespace Automator;

    use Automator\Support\Inflect;
    use Automator\Support\DbHandlers;
    

    class MakeForm {

        private $con;
        private $framework;
        private $tables = array();
        private $columns = array();
        private $relations;
        private $config;

        

        public function Automate($theme, $config){
            global $dbname;
            $this->framework = $theme;
            $this->config = $config;
            $this->con = new DbHandlers();
            $dtables = $this->con->show_dbTables();
            $excludeTables = $this->config["excludeTables"];
            foreach($dtables as $tb){
                if(in_array($tb, $excludeTables)) continue;
                $this->tables[] = $tb["Tables_in_".$dbname];
            }

            $this->makeForms();
        }

        private function makeForms() {
            global $app_dir;
            $tables = $this->tables;

            foreach($tables as $table) {
                $this->makeForm($table);
            }
        }

        private function makeForm($table) {
            $this->columns=array();
            $this->relations = $this->con->getFkeys($table);
            $table_columns = $this->con->tableDesc($table);
            $excludeColumns = $this->config["excludeColumns"];
            foreach($table_columns as $dcols) {
                if(in_array($dcols["Field"], $excludeColumns)) continue;
                $this->columns[] = $dcols;
            }

            $fkeys = array();
            foreach($this->relations as $fk) {
                array_push($fkeys, $fk["COLUMN_NAME"]);
            }

            $this->buildForm($fkeys);

        }

        private function buildForm($fkeys) {
            $formObj = $this->getFormStub(getcwd()."/automator/support/forms/frameworks/".$this->framework."/form.stub", 'form');
            /*
            $xpath = new \DOMXPath($formObj);
            $elements = $xpath->query('//form');
            var_dump($elements);
            foreach($elements as $element) {
                print($element->nodeValue);
            }

            print("\n\n\n");
            */
            var_dump($formObj->nodeValue);
            print("\n\n\n");
        }

        private function getFormStub($filepath, $elmType) {
            $fileStr = file_get_contents($filepath);
            return $this->parseToDom($fileStr, $elmType);
        }

        private function parseToDom($htmlStr, $elm) {
            $dom = new \DOMDocument();
            $dom->loadHTML($htmlStr);
            //return $dom;
            return $dom->documentElement->getElementsByTagName($elm)->item(0);
        }
        
    }