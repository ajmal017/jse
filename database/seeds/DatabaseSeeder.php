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

        /* Seed users */
        DB::table('users')->insert([
            'name' => 'slinger',
            'email' => 'nextbb@yandex.ru',
            'password' => bcrypt('659111')
        ]);

        DB::table('users')->insert([
            'name' => 'Jesse',
            'email' => 'Jesse@ravencapital.co',
            'password' => bcrypt('$1Raven1$')
        ]);

        DB::table('users')->insert([
            'name' => 'nastya',
            'email' => 'art@nastya.com',
            'password' => bcrypt('nastya')
        ]);

        /* Seed bots */
        DB::table('bots')->insert([
            'created_at' => now(),
            'name' => 'Bot_01',
            'db_table_name' => 'bot_1',
            'account_id' => 1,
            'symbol_id' => 1,
            'time_frame' => 1,
            'bars_to_load' => 50,
            'volume' => 100,
            'front_end_id' => '12345',
            'rate_limit' => 4,
            'status' => 'idle',
            'memo' => 'First big bot'
        ]);

        DB::table('bots')->insert([
            'created_at' => now(),
            'name' => 'Bot_02',
            'db_table_name' => 'bot_2',
            'account_id' => 2,
            'symbol_id' => 2,
            'time_frame' => 5,
            'bars_to_load' => 125,
            'volume' => 45,
            'front_end_id' => '12346',
            'rate_limit' => 4,
            'status' => 'idle',
            'memo' => 'Another bot'
        ]);

        DB::table('bots')->insert([
            'created_at' => now(),
            'name' => 'Bot_slow',
            'db_table_name' => 'bot_3',
            'account_id' => 3,
            'symbol_id' => 1,
            'time_frame' => 5,
            'bars_to_load' => 11,
            'volume' => 177,
            'front_end_id' => '12347',
            'rate_limit' => 4,
            'status' => 'idle',
            'memo' => "Obama's bot"
        ]);

        DB::table('bots')->insert([
            'created_at' => now(),
            'name' => 'Bot_fast',
            'db_table_name' => 'bot_4',
            'account_id' => 4,
            'symbol_id' => 2,
            'time_frame' => 5,
            'bars_to_load' => 400,
            'volume' => 1200,
            'front_end_id' => '12348',
            'rate_limit' => 7,
            'status' => 'idle',
            'memo' => "Putin's bot"
        ]);

        /* Seed exchanges */
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

        /* Seed accounts */
        DB::table('accounts')->insert([
            'created_at' => now(),
            'name' => 'Boris',
            'exchange_id' => 1,
            'bot_id' => 1,
            'api' => 'AdpGKvlnElQmowv-SgKu9kiF',
            'api_secret' => 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1',
            'status' => 'ok',
            'is_testnet' => true,
            'memo' => 'live account'
        ]);

        DB::table('accounts')->insert([
            'created_at' => now(),
            'name' => 'No money',
            'exchange_id' => 1,
            'bot_id' => 2,
            'api' => 'AdpGKvlnElQmowv-SgKu9kiF',
            'api_secret' => 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1',
            'status' => 'ok',
            'is_testnet' => false,
            'memo' => 'live account'
        ]);

        DB::table('accounts')->insert([
            'created_at' => now(),
            'name' => 'Putin',
            'exchange_id' => 1,
            'bot_id' => 3,
            'api' => '123',
            'api_secret' => '456',
            'status' => 'ok',
            'is_testnet' => true,
            'memo' => 'Good is good'
        ]);

        DB::table('accounts')->insert([
            'created_at' => now(),
            'name' => 'Obama',
            'exchange_id' => 2,
            'bot_id' => 4,
            'api' => '123',
            'api_secret' => '456',
            'status' => 'ok',
            'is_testnet' => true,
            'memo' => 'Good is good'
        ]);

        /* Seed symbols */
        DB::table('symbols')->insert([
            'created_at' => now(),
            'exchange_id' => 1,
            'execution_symbol_name' => 'BTC/USD',
            'history_symbol_name' => 'XBTUSD',
            'commission' => 0.0075,
            'is_active' => true,
            'memo' => 'Execution and history symbol names are different'
        ]);

        DB::table('symbols')->insert([
            'created_at' => now(),
            'exchange_id' => 1,
            'execution_symbol_name' => 'ETH/USD',
            'history_symbol_name' => 'ETHUSD',
            'commission' => 0.0075,
            'is_active' => true,
            'memo' => 'Name is the same'
        ]);

        DB::table('symbols')->insert([
            'created_at' => now(),
            'exchange_id' => 2,
            'execution_symbol_name' => 'ADAM19',
            'history_symbol_name' => 'ADAM19',
            'commission' => 0.025,
            'is_active' => false,
            'memo' => 'a futures'
        ]);
    }
}
