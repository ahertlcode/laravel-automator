<?php

    return [
        "host" => "localhost",
        "port" => "3306",
        "user" => "root",
        "password" => "Tope1234$",
        "database" => "primalfits",
        "database_type" => "mysql",
        "excludeTables" => ['migrations'],
        "excludeColumns" => ['id','status','password_digest','password_reset_token','api_token','remember_token','createdAt','created_at','updatedAt','updated_at','deletedAt','deleted_at']
    ];

?>