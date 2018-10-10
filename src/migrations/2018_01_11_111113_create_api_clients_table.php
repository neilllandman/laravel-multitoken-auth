<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(\Illuminate\Support\Facades\Config::get('multipletokens.tables.api_clients'), function (Blueprint $table) {
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
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('multipletokens.tables.api_clients'));
    }
}
