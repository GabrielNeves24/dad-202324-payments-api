<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateViewAuthUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS VIEW_AUTH_USERS');
        $query = "
            CREATE VIEW VIEW_AUTH_USERS AS
            SELECT id, 'A' AS user_type, email AS username, password, NULL AS confirmation_code, name, email, 0 AS blocked, NULL AS photo_url, NULL AS deleted_at
            FROM users
            UNION ALL
            SELECT phone_number AS id, 'V' AS user_type, phone_number AS username, password, confirmation_code, name, email, blocked, photo_url, deleted_at
            FROM vcards
        ";

        DB::statement($query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS VIEW_AUTH_USERS');
    }
}

