<?php
namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Support\Layouts;

class MakeLayout{
    private $layout;
    public function Automate($layout_css){
        $this->layout = $layout_css;
        if($this->layout == "bulma"){
            Layouts\Bulma::make();
        }else if($this->layout == "bootstrap"){
            Layouts\Bootstrap::make();
        }else if($this->layout == "w3css"){
            Layouts\W3css::make();
        }else{
            return ['status'=>'failed', 'message'=>'specified theme not supported'];
        }
    }
}