<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_ids', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');

            $table->uuid('value');
            $table->string('name');

            $table->softDeletes();
            $table->timestamps();

            $table->index('value');
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
