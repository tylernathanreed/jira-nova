<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Blueprints
     |--------------------------------------------------------------------------
     |
     | Blueprints are used to define the Schema of your tables for migrations.
     | Some of the columns that can be defined using blueprints will pull
     | from this configuration file. Hopefully it's straight forward.
     |
     */

    'money' => [
        'total' => 10,
        'places' => 2
    ],

    'percent' => [
        'total' => 6,
        'places' => 4
    ],

    'belongsTo' => [
        'onUpdate' => 'CASCADE',
        'onDelete' => 'NO ACTION'
    ],

    'timestampsBy' => [
        'model' => App\Models\User::class
    ]
];
