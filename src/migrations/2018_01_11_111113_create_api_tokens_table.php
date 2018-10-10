<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');

            $table->string('user_id');
            $table->string('token');
            $table->string('refresh_token')->nullable();
            $table->boolean('remember')->default(0);
            $table->dateTime('expires_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_tokens');
    }
}
