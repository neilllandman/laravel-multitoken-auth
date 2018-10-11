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
        Schema::create(\Illuminate\Support\Facades\Config::get('multipletokens.tables.clients'), function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');

            $table->string('name')->unique();
            $table->uuid('value');

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
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('multipletokens.tables.clients'));
    }
}
