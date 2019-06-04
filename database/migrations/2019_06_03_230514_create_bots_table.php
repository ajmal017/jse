<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Create a base table first.
         * Then create a second table and only then set foreign keys (reference to the first table).
         * id column must be changed from default bigIncrements to increments!
         */
        Schema::create('bots', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('name')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('time_frame')->nullable();
            $table->integer('bars_to_load')->nullable();
            $table->integer('volume')->nullable();
            $table->integer('front_end_id')->nullable();
            $table->integer('rate_limit')->nullable();
            $table->string('status')->nullable();
            $table->integer('strategy_id')->nullable();
            $table->string('memo')->nullable();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            /* Bot foreign key column */
            $table->unsignedInteger('bot_id')->nullable();
            $table->foreign('bot_id')->references('id')->on('bots')->onDelete('restrict');

            $table->integer('exchange_id')->nullable();
            $table->string('name')->nullable();
            $table->string('api')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_testnet')->nullable();
            $table->string('memo')->nullable();
        });

        Schema::create('exchanges', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            /* Accounts */
            $table->unsignedInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');

            //$table->string('account_id')->nullable();
            $table->string('name')->nullable();
            $table->string('url')->nullable();
            $table->string('live_api_path')->nullable();
            $table->string('testnet_api_path')->nullable();
            $table->string('status')->nullable();
            $table->text('memo')->nullable();
        });

        Schema::create('symbols', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            /* Accounts */
            $table->unsignedInteger('exchange_id')->nullable();
            $table->foreign('exchange_id')->references('id')->on('exchanges')->onDelete('restrict');

            $table->string('execution_symbol_name')->nullable();
            $table->string('history_symbol_name')->nullable();
            $table->string('commission')->nullable();
            $table->string('memo')->nullable();
            $table->boolean('is_active')->nullable();

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
        Schema::dropIfExists('accounts');
    }
}
