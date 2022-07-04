<?php

    return [
        "host" => "localhost",
        "port" => "3306",
        "user" => "root",
        "password" => "Tope1234$",
        "database" => "primalfit_solutions",
        "database_type" => "mysql",
        "excludeTables" => ['migrations'],
        "excludeColumns" => ['id','createdAt','created_at','updatedAt','updated_at','deletedAt','deleted_at']
    ];
