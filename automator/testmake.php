<?php

namespace Automator;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;
use Automator\Support\Tests;

class Testmake {
    private $test;

    public function Automate($test_data) {
        $this->test = $test_data;
        if ($this->test == 'feature') {
            Tests\Feature::make();
        }elseif ($this->test == 'unit') {
            Tests\Unit::make();
        }
    }
}