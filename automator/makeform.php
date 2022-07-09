<?php
    namespace Automator;

    use Automator\Support\Inflect;
    use Automator\Support\DbHandlers;
    use Automator\Support\Forms;

    class MakeForm {
        private $form;

        public function Automate($opt){
            $this->form = $opt[2];
            $app = (isset($opt[3])) ? $opt[3] : null;
            $landing = (in_array('--landing', $opt)) ? true : false;
            if ($this->form == 'bulma') {
                Forms\Bulma::make($app, $landing);
            }
        }

    }