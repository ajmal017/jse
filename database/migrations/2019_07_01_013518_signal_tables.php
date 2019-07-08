<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SignalTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* 4 tables for signals */
        for($i = 1; $i < 5; $i++) {
            Schema::create('signal_' . $i, function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('date')->nullable();
                $table->bigInteger('time_stamp')->nullable();
                $table->string('type')->nullable();
                $table->string('order_id')->nullable();
                $table->string('status')->nullable();
                $table->string('order_type')->nullable();
                $table->string('direction')->nullable();
                $table->double('signal_price')->nullable();
                $table->bigInteger('signal_volume')->nullable();
                $table->bigInteger('volume')->nullable(); // execution_volume
                $table->bigInteger('volume_reminder')->nullable();
                $table->bigInteger('accum_volume')->nullable();

                /* Profit, etc */
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
