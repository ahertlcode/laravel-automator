# Laravel Automator
This application is a tool for generating laravel application. If the laravel application as not been created at all it will create it and scafold the entire CRUD engine from database tables creating a RESTful API for the database specified in the `config/config.php` file. This configuration file has to be editted to reflect the database settings for the database you are using for your application.

If the laravel application has been created this application is smart to find out and then scafold the CRUD engine for a laravel RESTful API from the database table.
The generated/scafolded RESTful API should work out of the box without the need to add any function except for your application logic that you will need to add to it. Also the User model and Auth module is not touched so you can decide on your user authentication using the laravel inbuilt users authentication mechanism.

Some assumption were made in designing this tools which are:

1, The database name is used as your application name in the case of an already created laravel application.

2, The table names are pluralized a convention that every laravel developers are already used to.

3, Tables and columns name are created all in small caps and those made up of multiple words are joined with an underscore. Foreign fields columns are named in such a way that it reflects the name of the table it related to in singular form with and underscore `id` e.g `user_id` will be the foreign key column of a particular table say `orders` that references the `users` table on the `id` column of the `users` table.

4, In the instance where a table needs to carry columns the persons that created the entry as well as the person that updated it `created_by` and `modified_by` columns are expected to be used and both should reference the `users` table on the `id` column.

If all these are true of your database and tables with all the columns then you should have well qualified models that comes with all the necessary relationship that will make your life simple improving the generated application.

## Basic Usage

Clone the repos as follows:

    git clone https://github.com/ahertlcode/laravel-automator.git

change to the root directory of the application when the cloning is completed.

    cd laravel-automator
Then run this command

    php make

Please ensure to edit/create a `config.php` file in the config directory of laravel-automator before running the `php make` command as the make file will read and use the database configurations from the `config.php`. That is an important part of the whole thing that can not be whisked away f you don't want to find bugs you may not be able to get.

Your `config.php` content should be similar to this.

    <?php

    return [
        "host" => "localhost",
        "port" => "3306",
        "user" => "database user name", 
        "password" => "database user password", 
        "database" => "database name",
        "database_type" => "database server type e.g mysql, mariadb, postgrel e.t.c",
    ];

>#### The application has of now only support mysql.
# Happy Coding.

