<?php

namespace App\Helpers;

class QuantityHelper
{
    /**
     * Formata quantidade baseada na unidade do produto
     * 
     * @param float $quantity Quantidade a ser formatada
     * @param string $unit Unidade do produto
     * @return string Quantidade formatada
     */
    public static function formatByUnit($quantity, $unit)
    {
        $unit = strtoupper(trim($unit));
        
        // Unidades que precisam de casas decimais (3 casas)
        $decimalUnits = ['KG', 'G', 'L', 'ML', 'M', 'M²', 'M³'];
        
        if (in_array($unit, $decimalUnits)) {
            return number_format($quantity, 3, ',', '.');
        }
        
        // Unidades inteiras (0 casas decimais)
        return number_format($quantity, 0, ',', '.');
    }
}
