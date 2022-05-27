<?php

    return [
        "host" => "localhost",
        "port" => "3306",
        "user" => "admin",
        "password" => "admin",
        "database" => "school_manager",
        /*"user" => "test_user",
        "password" => "test12345",
        "database" => "test_db",
        "database_type" => "mysql",*/
        "excludeTables" => ['migrations'],
        "excludeColumns" => ['id','createdAt','created_at','updatedAt','updated_at','deletedAt','deleted_at']
    ];
