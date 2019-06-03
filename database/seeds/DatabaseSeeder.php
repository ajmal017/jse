<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        DB::table('exchanges')->insert([
            //'name' => Str::random(10),
            'created_at' => now(),
            'name' => 'Bitmex',
            'url' => 'http://www.bitmex.com',
            'live_api_path' => 'http://api.bitmex.com',
            'testnet_api_path' => 'http://testnet.bitmex.com',
            'status' => 'online',
            'memo' => 'Main'
        ]);

        DB::table('exchanges')->insert([
            'created_at' => now(),
            'name' => 'Kraken',
            'url' => 'http://www.kraken.com',
            'live_api_path' => 'http://api.kraken.com',
            'testnet_api_path' => 'http://test.kraken.com',
            'status' => 'offline',
            'memo' => 'Main 2'
        ]);

        DB::table('exchanges')->insert([
            'created_at' => now(),
            'name' => 'Derebit',
            'url' => 'http://www.derebit.com',
            'live_api_path' => 'http://derebit.com',
            'testnet_api_path' => 'http://derebit.com',
            'status' => 'offline',
            'memo' => 'For testing'
        ]);

    }
}
