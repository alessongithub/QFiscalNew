<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Plan;

class AddEmissorPlan extends Migration
{
    public function up()
    {
        Plan::create([
            'name' => 'Emissor Fiscal',
            'slug' => 'emissor',
            'description' => 'Plano exclusivo para emissão de documentos fiscais',
            'price' => 39.90,
            'max_users' => 1,
            'max_clients' => 100,
            'has_api_access' => false,
            'has_support' => true,
            'is_active' => true
        ]);
    }

    public function down()
    {
        Plan::where('slug', 'emissor')->delete();
    }
}