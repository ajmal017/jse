<?php

use Illuminate\Database\Seeder;

class SignalsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /* Seed users */
        DB::table('signal_1')->insert([
            'id' => 1,
            'date' => '2019-06-27 01:00:00',
            'time_stamp' => '1561597200000',
            'type' => 'signal',
            'status' => 'new',
            'signal_price' => 9870,
            'signal_volume' => 240
        ]);
    }
}
