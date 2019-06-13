<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BotTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* 4 tables for trading, 1 for back testing */
        for($i = 1; $i < 6; $i++) {
            Schema::create('bot_' . $i, function (Blueprint $table) {
                $table->increments('id');
                $table->string('symbol')->nullable(); // Use nullable if this field can be blank
                $table->dateTime('date')->nullable();
                $table->bigInteger('time_stamp')->nullable();
                $table->double('open')->nullable();
                $table->double('close')->nullable();
                $table->double('high')->nullable();
                $table->double('low')->nullable();
                $table->bigInteger('volume')->nullable();
                $table->double('price_channel_high_value')->nullable();
                $table->double('price_channel_low_value')->nullable();
                // SMA
                $table->double('sma1')->nullable(); // Used for price channel filter
                $table->double('sma2')->nullable(); // Used for MACD
                // MACD
                $table->double('ema1')->nullable();
                $table->double('ema2')->nullable();
                $table->double('macd_line')->nullable();
                $table->double('macd_signal_line')->nullable();

                $table->dateTime('trade_date')->nullable();
                $table->double('trade_price')->nullable();
                $table->double('trade_commission')->nullable();
                $table->double('accumulated_commission')->nullable();
                $table->string('trade_direction')->nullable();
                $table->double('trade_volume')->nullable();
                $table->double('trade_profit')->nullable();
                $table->double('accumulated_profit')->nullable();
                $table->double('net_profit')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        for($i = 1; $i < 5; $i++) {
            Schema::dropIfExists('bot_' . $i);
        }
    }
}
