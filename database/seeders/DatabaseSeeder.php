<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Catálogo para todos los ambientes; usuarios de demostración solo fuera de producción. */
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        if (! app()->environment('production')) {
            $this->call(UsuariosDemoSeeder::class);
        }
    }
}
