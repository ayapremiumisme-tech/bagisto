<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class QRIS extends Payment
{
    protected $code = 'qris';

    public function getRedirectUrl() {}

    public function getImage()
    {
        $url = $this->getConfigData('image');

        if ($url) {
            return Storage::url($url);
        }

        try {
            return bagisto_asset('images/qris.jpg', 'shop');
        } catch (\Exception $e) {
            return '';
        }
    }
}
