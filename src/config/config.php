<?php

/**
 * Epay Settings
 * Created by Angel Bachev <angelbachev@gmail.com>
 */

return [
    'mode' => env('EPAY.mode', 'stage'),

    'stage' => [
        'submit_url'  => 'https://devep2.datamax.bg/ep2/epay2_demo/',
        'client_id'   => env('EPAY.stage.client_id'),
        'secret'      => env('EPAY.stage.secret'),
        'success_url' => env('EPAY.stage.success_url'),
        'cancel_url'  => env('EPAY.stage.cancel_url'),
    ],

    'prod' => [
        'submit_url'  => 'https://epay.bg',
        'client_id'   => env('EPAY.prod.client_id'),
        'secret'      => env('EPAY.prod.secret'),
        'success_url' => env('EPAY.prod.success_url'),
        'cancel_url'  => env('EPAY.prod.cancel_url'),
    ],
];
