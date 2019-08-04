<?php

namespace Auth\Auth\Database\Seeders;

use Auth\Auth\Entities\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
        $user = new User;
        $user->name = 'Billy';
        $user->email = 'willian.empari@gmail.com';
        $user->password = bcrypt('1064681');
        $user->save();
    }
}
