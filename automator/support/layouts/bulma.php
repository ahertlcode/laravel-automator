<?php
namespace Automator\Support\Layouts;

use Automator\Support\Inflect;
use Automator\Support\DbHandlers;

class Bulma{
    private static $filestr;
    private static $con;
    public static function make(){
        global $app_dir;
        self::getHeaders();
        self::getNavBar();
        self::defMainArea();
        self::setFooter();
        $layout_file = $app_dir."/resources/views/layouts/bulma.blade.php";
        file_put_contents($layout_file, self::$filestr);
    }

    private static function setFooter(){
        self::$filestr .= "    ".'<div class="hero-foot">'."\n            ";
        self::$filestr .= '<div class="level">'."\n                ";
        self::$filestr .= '<div class="level-left">&copy;&nbsp;AHERTL, All rights reserved</div>'."\n                ";
        self::$filestr .= '<div class="level-right">powered by: AHERTL&trade;</div>'."\n            ";
        self::$filestr .= '</div>'."\n        ";
        self::$filestr .= '</div>'."\n        ";
        self::$filestr .= '</section>'."\n    ";
        self::$filestr .= '</body>'."\n";
        self::$filestr .= '</html>';
    }

    private static function getHeaders(){
        self::$filestr = '<!DOCTYPE html>'."\n";
        self::$filestr .= '<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">'."\n    ";
        self::$filestr .= '<head>'."\n        ";
        self::$filestr .= '<meta charset="UTF-8">'."\n        ";
        self::$filestr .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n        ";
        self::$filestr .= '<meta http-equiv="X-UA-Compatible" content="ie=edge">'."\n\n        ";
        self::$filestr .= '<!--Scripts-->'."\n        ";
        self::$filestr .= '<script src="{{ asset(\'js/bulma.js\') }}" defer></script>'."\n\n        ";
        self::$filestr .= '<!-- Fonts -->'."\n        ";
        self::$filestr .= '<link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">'."\n\n        ";
        self::$filestr .= '<!-- Styles -->'."\n        ";
        self::$filestr .= '<link href="{{ asset(\'images/logo.png\') }}" rel="shortcut icon" type="image/x-icon">'."\n        ";
        self::$filestr .= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">'."\n        ";
        self::$filestr .= '<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">'."\n\n        ";
        self::$filestr .= '<!-- Bulma Version 0.7.5-->'."\n        ";
        self::$filestr .= '<link rel="stylesheet" href="https://unpkg.com/bulma@0.7.5/css/bulma.min.css" />'."\n        ";
        self::$filestr .= '<link rel="stylesheet" type="text/css" href="{{asset(\'css/bulma.min.css\')}}">'."\n\n        ";
        self::$filestr .= '<!-- CSRF Token -->'."\n        ";
        self::$filestr .= '<meta name="csrf-token" content="{{ csrf_token() }}">'."\n        ";
        self::$filestr .= '<title>{{ config(\'app.name\', \'Laravel\') }}</title>'."\n    ";
        self::$filestr .= '</head>'."\n    ".'<body class="has-navbar-fixed-top">'."\n        ";
        self::$filestr .= '<section class="hero is-light is-fullheight-with-navbar">'."\n            ";
        self::$filestr .= '@if (Route::has(\'login\'))'."\n            ";
        return self::$filestr;
    }

    private static function getNavBar(){
        global $dbname;
        self::$filestr .= '<div class="hero-head">'."\n                ";
        self::$filestr .= '@auth'."\n                ";
        self::$filestr .= '<nav class="navbar is-transparent is-fixed-top" role="navigation" aria-label="main navigation">'."\n                    ";
        self::$filestr .= '<div class="navbar-brand">'."\n                        ";
        self::$filestr .= self::getBrand($dbname);
        self::$filestr .= '</div>'."\n                    ";
        self::$filestr .= self::getNavBarMenu();
        self::$filestr .= '</nav>'."\n            ";
        self::$filestr .= '</div>'."\n        ";
        return self::$filestr;
    }

    private static function getBrand($brand){
        $branded = '<a class="navbar-item">'."\n                            ";
        $branded .= '<h1 class="title is-2">'."\n                                ";
        $branded .= $brand."\n                            ";
        $branded .= '</h1>'."\n                        ".'</a>'."\n                        ";
        $branded .= '<a role="button" class="navbar-burger burger"';
        $branded .= ' aria-label="menu" aria-expanded="false" ';
        $branded .= 'data-target="navmenu">'."\n                            ";
        $branded .= '<span aria-hidden="true"></span>'."\n                            ";
        $branded .= '<span aria-hidden="true"></span>'."\n                            ";
        $branded .= '<span aria-hidden="true"></span>'."\n                        ";
        $branded .= '</a>'."\n                    ";
        return $branded;
    }

    private static function getNavBarMenu(){
        $menustr = '<div id="navmenu" class="navbar-menu">'."\n                        ";
        $menustr .= '<div class="navbar-end">'."\n                            ";
        $menustr .= '<div class="navbar-item has-dropdown is-hoverable">'."\n                                ";
        $menustr .= '<a class="navbar-link">'."\n                                    ";
        $menustr .= '<figure class="image is-32x32">'."\n                                        ";
        $menustr .= '<img class="is-rounded" src="{{asset(\'images/profile/Auth::user()->pix\')}}">'."\n                                    ";
        $menustr .= '</figure>'."\n                                    ";
        $menustr .= '<figcation>{{Auth::user()->name}}</figcation>'."\n                                ";
        $menustr .= '</a>'."\n                                ";
        $menustr .= '<div class="navbar-dropdown">'."\n                                    ";
        $menustr .= '<a href="#" class="navbar-item">'."\n                                        ";
        $menustr .= '<i class="fa fa-cogs"></i>&nbsp;&nbsp;'."\n                                        ";
        $menustr .= 'Account Settings'."\n                                    ";
        $menustr .= '</a>'."\n                                    ";
        $menustr .= '<a href="#" class="navbar-item">'."\n                                        ";
        $menustr .= '<i class="fa fa-power-off"></i>&nbsp;&nbsp;'."\n                                        ";
        $menustr .= 'log out'."\n                                    ";
        $menustr .= '</a>'."\n                                ";
        $menustr .= '</div>'."\n                            ";
        $menustr .= '</div>'."\n                        ";
        $menustr .= '</div>'."\n                    ";
        $menustr .= '</div>'."\n                ";
        return $menustr;
    }

    private static function defMainArea(){
        self::$filestr .= '<div class="hero-body">'."\n            ";
        self::$filestr .= '<aside class="menu">'."\n                ";
        self::$filestr .= self::getSideMenu();
        self::$filestr .= '@endauth'."\n            ";
        self::$filestr .= '</aside>'."\n                ";
        self::$filestr .= '<div class="columns"><div class="column is-8 is-right">@yield(\'content\')</div></div>'."\n                ";
        self::$filestr .= '@endif'."\n        ";
        self::$filestr .= '</div>'."\n    ";
        return self::$filestr;
    }

    private static function getSideMenu(){
        global $dbname;
        self::$con = new DbHandlers();
        $menu_items = array();
        $side_menu = "";
        $tables = self::$con->show_dbTables();
        foreach($tables as $tb){
            $menu_items[] = $tb["Tables_in_".$dbname];
        }

        foreach($menu_items as $item){
            $side_menu .= '<p class="menu-label">'."\n                    ";
            $side_menu .= Inflect::singularize(str_replace("_"," ",$item))."\n                ";
            $side_menu .= '</p>'."\n                ";
            $side_menu .= '<ul class="menu-list is-marginless is-paddingless">'."\n                    ";
            $side_menu .= '<a class="menu-item is-marginless is-paddingless" href="{{ route(\''.Inflect::singularize($item).'\') }}">'.Inflect::singularize(ucwords(str_replace("_"," ",$item))).'</a>'."\n                ";
            $side_menu .= '</ul>'."\n                ";

        }
        return $side_menu;
    }
}