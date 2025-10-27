<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NcmRule;

class NcmRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['ncm' => '24011000', 'requires_gtin' => true, 'note' => 'Tabaco manufaturado — Grupo I: Tabaco'],
            ['ncm' => '24012000', 'requires_gtin' => true, 'note' => 'Cigarros — Grupo I: Tabaco'],
            ['ncm' => '30049069', 'requires_gtin' => true, 'note' => 'Medicamentos diversos — Grupo I: Farmacêutico'],
            ['ncm' => '30042090', 'requires_gtin' => true, 'note' => 'Produtos farmacêuticos — Grupo I: Farmacêutico'],
            ['ncm' => '33051000', 'requires_gtin' => true, 'note' => 'Perfumes e cosméticos — Grupo II: Cosméticos'],
            ['ncm' => '33059000', 'requires_gtin' => true, 'note' => 'Produtos de higiene pessoal — Grupo II: Cosméticos'],
            ['ncm' => '22030000', 'requires_gtin' => true, 'note' => 'Cervejas — Grupo II: Bebidas'],
            ['ncm' => '22071090', 'requires_gtin' => true, 'note' => 'Álcool etílico — Grupo II: Bebidas'],
            ['ncm' => '04012010', 'requires_gtin' => true, 'note' => 'Leite integral — Grupo III: Laticínios'],
            ['ncm' => '04013000', 'requires_gtin' => true, 'note' => 'Leite desnatado — Grupo III: Laticínios'],
            ['ncm' => '08111000', 'requires_gtin' => true, 'note' => 'Frutas frescas — Grupo III: Frutas'],
            ['ncm' => '09011100', 'requires_gtin' => true, 'note' => 'Café natural — Grupo III: Café'],
            ['ncm' => '61091000', 'requires_gtin' => true, 'note' => 'Camisetas e camisolas — Varejo com GTIN'],
            ['ncm' => '49019900', 'requires_gtin' => true, 'note' => 'Livros — Varejo com GTIN'],
            ['ncm' => '17049020', 'requires_gtin' => true, 'note' => 'Gomas de mascar — Alimentos com GTIN'],
            ['ncm' => '96032900', 'requires_gtin' => true, 'note' => 'Jogos e brinquedos — Grupo I: Brinquedos'],
            ['ncm' => '25232900', 'requires_gtin' => true, 'note' => 'Cimentos — Grupo II: Material de construção'],
            ['ncm' => '28471000', 'requires_gtin' => true, 'note' => 'Água oxigenada — Grupo II: Químicos'],
            ['ncm' => '33061000', 'requires_gtin' => true, 'note' => 'Sabões líquidos — Grupo II: Higiene'],
            ['ncm' => '34011100', 'requires_gtin' => true, 'note' => 'Sabão em barra — Grupo II: Higiene'],
        ];

        // Upsert para evitar duplicação caso já exista
        NcmRule::upsert($rows, ['ncm'], ['requires_gtin','note']);
    }
}


