<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call(RolesAndPermissionSeeder::class);

        User::create([
            'name'    => 'Desarrollador',
            'last_name'   => 'RedInstantic',
            'document_number'   => '0123456789',
            'phone_number'   => '0123456789',
            'address'   => 'Desarrollador',
            'email'   => 'desarrollador1@redinstantic.com',
            'password' => bcrypt('12345678'),
        ])->assignRole('Administrador');

        User::create([
            'name'    => 'David Garcia',
            'last_name'    => 'David Garcia',
            'document_number'    => '455666',
            'phone_number'    => '545454',
            'address'    => 'Dprueba',
            'email'   => 'prueba@redsuelva.com',
            'password' => bcrypt('12345678'),

        ])->assignRole('Coordinador');

        /* $user = new User();
        $user->name = "Desarrollador";
        $user->last_name = "RedInstantic";
        $user->document_number = "0123456789";
        $user->phone_number = "0123456789";
        $user->address = "Desarrollador";
        $user->email = "desarrollador1@redinstantic.com";
        $user->password = Hash::make("12345678");
        $user->save(); */


    }
}
