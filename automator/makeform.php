<?php
    namespace Automator;

    use Automator\Support\Forms;

    class MakeForm {
        private $form;

        public function Automate($opt, $tables=null, $columns=null){
            $this->form = $opt[2];
            $app = (isset($opt[3])) ? $opt[3] : null;
            $landing = (in_array('--landing', $opt)) ? true : false;
            $reporting = (in_array('--report', $opt)) ? true :  false;
            if ($this->form == 'bulma') {
                Forms\Bulma::make($app, $landing, $reporting, $opt, $tables, $columns);
            }
        }

    }