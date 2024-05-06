<?php

namespace App\Classes;

class CommonData
{
    public static function getStates(): array
    {
        return [
            [
                'code' => 'AUH',
                'name' => 'Abu Dhabi',
            ],
            [
                'code' => 'AJM',
                'name' => 'Ajman',
            ],
            [
                'code' => 'DXB',
                'name' => 'Dubai',
            ],
            [
                'code' => 'FUJ',
                'name' => 'Fujairah',
            ],
            [
                'code' => 'RAK',
                'name' => 'Ras Al Khaimah',
            ],
            [
                'code' => 'SHJ',
                'name' => 'Sharjah',
            ],
            [
                'code' => 'UAQ',
                'name' => 'Umm Al Quwain',
            ],
        ];
    }
}
