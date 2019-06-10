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
         * @see https://drive.google.com/file/d/1e4dh1W2Fu7Zr2HnHpxsMdEMR971kLbyd/view?usp=sharing
         */
        Schema::create('bots', function (Blueprint $table) {
            $table->increments('id'); // Key
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('db_table_name')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('symbol_id')->nullable();
            $table->integer('time_frame')->nullable();
            $table->integer('bars_to_load')->nullable();
            $table->integer('volume')->nullable();
            $table->integer('front_end_id')->nullable();
            $table->integer('rate_limit')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('strategy_id')->nullable(); // Key
            $table->string('memo')->nullable();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            /* Bot foreign key column */
            $table->unsignedInteger('bot_id')->nullable();
            //$table->foreign('bot_id')->references('id')->on('bots')->onDelete('restrict');

            $table->unsignedInteger('exchange_id')->nullable();
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
            //$table->unsignedInteger('account_id')->nullable();
            //$table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');

            $table->string('name')->nullable();
            $table->string('url')->nullable();
            $table->string('live_api_path')->nullable();
            $table->string('testnet_api_path')->nullable();
            $table->string('status')->nullable();
            $table->text('memo')->nullable();
        });

        /**
         * Set a foreign key for Accounts.exchnage_id -> Exchnages.id
         * Schema TABLE is used! Instead of Schema::create!
         */
        Schema::table('accounts', function (Blueprint $table) {
            /* Exchanges. One to many. One exchange - many accounts */
            $table->foreign('exchange_id')->references('id')->on('exchanges')->onDelete('restrict');
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

        Schema::create('strategy_types', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('memo')->nullable();
        });

        Schema::create('pricechannel_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('time_frame')->nullable();
            $table->integer('sma_filter_period')->nullable();
            $table->string('memo')->nullable();
        });

        Schema::create('macd_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('ema_period')->nullable();
            $table->integer('macd_line_period')->nullable();
            $table->integer('macd_signalline_period')->nullable();
            $table->string('memo')->nullable();
        });

        Schema::create('strategies', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->nullable();

            /* Strategy type foreign key column */
            $table->unsignedInteger('strategy_type_id')->nullable();
            $table->foreign('strategy_type_id')->references('id')->on('strategy_types')->onDelete('restrict');

            /* Price channel settings foreign key column */
            $table->unsignedInteger('pricechannel_settings_id')->nullable();
            $table->foreign('pricechannel_settings_id')->references('id')->on('pricechannel_settings')->onDelete('restrict');

            /* MACD settings foreign key column */
            $table->unsignedInteger('macd_settings_id')->nullable();
            $table->foreign('macd_settings_id')->references('id')->on('macd_settings')->onDelete('restrict');

            $table->boolean('is_active')->nullable();
            $table->string('memo')->nullable();
        });

        /**
         * Set a foreign key for Bots.strategy_id  Strategies.id
         */
        /*Schema::table('bots', function (Blueprint $table) {
            $table->foreign('strategy_id')->references('id')->on('strategies')->onDelete('restrict');
        });*/
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
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('symbols');
        Schema::dropIfExists('strategies');
        Schema::dropIfExists('strategy_types');
        Schema::dropIfExists('pricechannel_settings');
        Schema::dropIfExists('macd_settings');
    }
}
