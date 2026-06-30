<?php

use Webkul\Payment\Payment\CashOnDelivery;
use Webkul\Payment\Payment\QRIS;

return [
    'cashondelivery' => [
        'class' => CashOnDelivery::class,
        'code' => 'cashondelivery',
        'title' => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 1,
    ],

    'qris' => [
        'class' => QRIS::class,
        'code' => 'qris',
        'title' => 'QRIS',
        'description' => 'QRIS Pembayaran',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 2,
    ],
];
