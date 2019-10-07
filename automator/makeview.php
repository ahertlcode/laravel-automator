<?php
namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Support\Views;

class MakeView{
    private $theme;
    public function Automate($views_theme){
        $this->theme = $views_theme;
        if($this->theme == "bulma"){
            Views\BulmaViews::make();
        }else if($this->theme == "bootstrap"){
            Views\BootstrapViews::make();
        }else if($this->theme == "w3css"){
            Views\W3cssViews::make();
        }else{
            return ['status'=>'failed', 'message'=>'specified theme not supported'];
        }
    }
}