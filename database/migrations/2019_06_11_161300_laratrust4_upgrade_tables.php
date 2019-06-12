<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class Laratrust4UpgradeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {

        Schema::table('role_user', function (Blueprint $table) {
           // Drop user foreign key and primary with role_id
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
            $table->dropPrimary(['user_id', 'role_id']);

            $table->string('user_type');
        });

        DB::table('role_user')->update(['user_type' => 'App\Models\User']);

        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->primary(['user_id', 'role_id', 'user_type']);
        });



        Schema::table('permission_user', function (Blueprint $table) {
           // Drop user foreign key and primary with permission_id
            $table->dropForeign(['user_id']);
            $table->dropForeign(['permission_id']);
            $table->dropPrimary(['permission_id', 'user_id']);

            $table->string('user_type');
        });

        DB::table('permission_user')->update(['user_type' => 'App\Models\User']);

        Schema::table('permission_user', function (Blueprint $table) {
            $table->foreign('permission_id')->references('id')->on('permissions')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->primary(['permission_id', 'user_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
    }
}
