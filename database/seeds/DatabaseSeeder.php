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

        /* Seed bots */
        DB::table('bots')->insert([
            'created_at' => now(),
            'name' => 'Bot_01',
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
            'account_id' => 1,
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
            'account_id' => 2,
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
            'name' => 'Bitmex new',
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
            'bot_id' => 2,
            'api' => 'AdpGKvlnElQmowv-SgKu9kiF',
            'api_secret' => 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1',
            'status' => 'ok',
            'is_testnet' => false,
            'memo' => 'live account'
        ]);

        DB::table('accounts')->insert([
            'created_at' => now(),
            'name' => 'Derebit',
            'bot_id' => 1,
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
            'execution_symbol_name' => 'XBT/USD',
            'history_symbol_name' => 'BTC/USD',
            'commission' => 0.0075,
            'is_active' => true,
            'memo' => 'Execution and history symbol names are different'
        ]);

        DB::table('symbols')->insert([
            'created_at' => now(),
            'exchange_id' => 1,
            'execution_symbol_name' => 'ETH/USD',
            'history_symbol_name' => 'ETH/USD',
            'commission' => 0.0075,
            'is_active' => true,
            'memo' => 'Name is the same'
        ]);

        DB::table('symbols')->insert([
            'created_at' => now(),
            'exchange_id' => 2,
            'execution_symbol_name' => 'ADA19',
            'history_symbol_name' => 'ADA19',
            'commission' => 0.025,
            'is_active' => false,
            'memo' => 'a futures'
        ]);
    }
}
