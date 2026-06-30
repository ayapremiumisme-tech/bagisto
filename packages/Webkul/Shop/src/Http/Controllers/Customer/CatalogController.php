<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Webkul\Shop\Http\Controllers\Controller;

class CatalogController extends Controller
{
    public function index()
    {
        return view('shop::customers.account.catalog.index');
    }
}
