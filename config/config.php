<?php

    return [
        "host" => "localhost",
        "port" => "3306",
        "user" => "abayomi",
        "password" => "aherceo2",
        "database" => "school_manager_3",
        "database_type" => "mysql",
        "excludeTables" => ['migrations','failed_jobs','personal_access_tokens','password_resets'],
        "excludeColumns" => [
            'id','status','password_digest','password_reset_token',
            'api_token','remember_token','createdAt','created_at',
            'updatedAt','updated_at','deletedAt','deleted_at',
            'owner_id','user_id'
        ]
    ];

?>