<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Models\CoreConfig;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\CartRule\Repositories\CartRuleRepository;
use Webkul\CMS\Repositories\PageRepository;

echo "=== Setting up Ayapremiumisme Store ===\n\n";

// 1. Update Channel Name
$channelRepo = app(ChannelRepository::class);
$channel = $channelRepo->find(1);

if ($channel) {
    $translation = $channel->translations()->where('locale', 'id')->first();
    if ($translation) {
        $translation->update(['name' => 'Ayapremiumisme']);
        echo "✓ Channel name updated to 'Ayapremiumisme'\n";
    }
}

// 2. Configure Payment Methods
echo "\n--- Configuring Payment Methods ---\n";

$paymentMethods = [
    'cashondelivery' => [
        'active' => '1',
        'title' => 'Cash On Delivery (COD)',
        'description' => 'Bayar di tempat saat barang diterima',
        'sort' => '1',
        'instructions' => 'Pembayaran dilakukan secara tunai saat barang sampai di alamat Anda.',
    ],
    'moneytransfer' => [
        'active' => '1',
        'title' => 'Transfer Bank',
        'description' => 'Pembayaran melalui transfer bank',
        'sort' => '2',
    ],
    'banktransfer' => [
        'active' => '1',
        'title' => 'Transfer Bank (BCA/Mandiri/BNI/BRI)',
        'description' => 'Pembayaran melalui transfer bank ke rekening kami',
        'sort' => '3',
        'bank_details' => "BCA: 1234567890 a.n. Ayapremiumisme\nMandiri: 9876543210 a.n. Ayapremiumisme\nBNI: 5556667777 a.n. Ayapremiumisme\nBRI: 1112223334 a.n. Ayapremiumisme",
    ],
    'ewallet' => [
        'active' => '1',
        'title' => 'E-Wallet (GoPay/OVO/Dana/ShopeePay)',
        'description' => 'Pembayaran menggunakan E-Wallet favorit Anda',
        'sort' => '4',
        'ewallet_details' => "GoPay: 081234567890\nOVO: 081234567890\nDana: 081234567890\nShopeePay: 081234567890",
    ],
    'qris' => [
        'active' => '1',
        'title' => 'QRIS',
        'description' => 'Scan QR Code menggunakan aplikasi pembayaran apa pun yang mendukung QRIS',
        'sort' => '5',
    ],
];

foreach ($paymentMethods as $method => $settings) {
    CoreConfig::where('code', 'LIKE', "sales.payment_methods.{$method}.%")->delete();
    foreach ($settings as $key => $value) {
        CoreConfig::create([
            'code' => "sales.payment_methods.{$method}.{$key}",
            'value' => $value,
            'channel_code' => 'default',
            'locale_code' => null,
        ]);
    }
    echo "✓ Payment method '{$method}' configured\n";
}

// 3. Configure Shipping
echo "\n--- Configuring Shipping ---\n";

$shippingSettings = [
    'sales.carriers.freeshipping.active' => '1',
    'sales.carriers.freeshipping.title' => 'Gratis Ongkir',
    'sales.carriers.freeshipping.description' => 'Gratis ongkos kirim',
    'sales.carriers.freeshipping.sort' => '1',
    'sales.carriers.freeshipping.amount' => '0',
    'sales.carriers.flatrate.active' => '1',
    'sales.carriers.flatrate.title' => 'Ongkir Tetap',
    'sales.carriers.flatrate.description' => 'Ongkos kirim dengan tarif tetap',
    'sales.carriers.flatrate.sort' => '2',
    'sales.carriers.flatrate.type' => 'per_unit',
    'sales.carriers.flatrate.base_amount' => '15000',
    'sales.carriers.flatrate.base_country' => 'ID',
    'sales.carriers.flatrate.base_state' => '',
    'sales.shipping.origin.country' => 'ID',
    'sales.shipping.origin.state' => 'JI',
    'sales.shipping.origin.city' => 'Jakarta',
    'sales.shipping.origin.address' => 'Jl. Contoh No. 123',
    'sales.shipping.origin.zip' => '12345',
    'sales.shipping.origin.phone' => '02112345678',
    'sales.shipping.origin.store_name' => 'Ayapremiumisme',
];

foreach ($shippingSettings as $code => $value) {
    CoreConfig::updateOrCreate(
        ['code' => $code, 'channel_code' => 'default'],
        ['value' => $value, 'locale_code' => null]
    );
}
echo "✓ Shipping methods configured\n";

// 4. Create categories
echo "\n--- Creating Categories ---\n";

$categoryRepo = app(CategoryRepository::class);

$rootCategories = $categoryRepo->getRootCategories();
$rootCategory = $rootCategories->first();
if (!$rootCategory) {
    $rootCategory = $categoryRepo->create([
        'name' => 'Root',
        'description' => 'Root',
        'status' => 1,
        'parent_id' => null,
    ]);
    echo "  Root category created\n";
}

$categories = [
    ['slug' => 'elektronik', 'name' => 'Elektronik', 'description' => 'Produk elektronik'],
    ['slug' => 'fashion', 'name' => 'Fashion', 'description' => 'Pakaian dan aksesoris fashion'],
    ['slug' => 'makanan-minuman', 'name' => 'Makanan & Minuman', 'description' => 'Makanan dan minuman'],
    ['slug' => 'kesehatan-kecantikan', 'name' => 'Kesehatan & Kecantikan', 'description' => 'Produk kesehatan dan kecantikan'],
    ['slug' => 'rumah-tangga', 'name' => 'Rumah Tangga', 'description' => 'Perlengkapan rumah tangga'],
    ['slug' => 'olahraga-hobi', 'name' => 'Olahraga & Hobi', 'description' => 'Alat olahraga dan hobi'],
];

$categoriesMap = [];
foreach ($categories as $catData) {
    try {
        $existing = DB::table('category_translations')
            ->where('slug', $catData['slug'])
            ->first();
        if ($existing) {
            $fullCat = $categoryRepo->find($existing->category_id);
            echo "… Category '{$catData['name']}' already exists\n";
        } else {
            $fullCat = $categoryRepo->create([
                'name' => $catData['name'],
                'slug' => $catData['slug'],
                'description' => $catData['description'],
                'status' => 1,
                'parent_id' => $rootCategory->id,
            ]);
            echo "✓ Category '{$catData['name']}' created\n";
        }
        $categoriesMap[$catData['name']] = $fullCat->id;
    } catch (\Exception $e) {
        echo "✗ Error creating category '{$catData['name']}': " . $e->getMessage() . "\n";
    }
}

// 5. Create Products
echo "\n--- Creating Products ---\n";

$productRepo = app(ProductRepository::class);
$attrFamilyRepo = app(AttributeFamilyRepository::class);
$inventorySourceRepo = app(InventorySourceRepository::class);

$defaultFamily = $attrFamilyRepo->findWhere(['code' => 'default'])->first();
if (!$defaultFamily) $defaultFamily = $attrFamilyRepo->first();

$invSource = $inventorySourceRepo->findWhere(['code' => 'default'])->first();
if (!$invSource) $invSource = $inventorySourceRepo->first();

$products = [
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'SMARTPHONE-X',
        'productName' => 'Smartphone X Pro Max',
        'shortDescription' => 'Smartphone flagship dengan kamera 108MP',
        'description' => 'Smartphone terbaru dengan layar AMOLED 6.7 inci, RAM 12GB, ROM 256GB, kamera utama 108MP, dan baterai 5000mAh. Dilengkapi dengan fitur NFC dan fast charging 65W.',
        'price' => 7999000,
        'weight' => 0.5,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'smartphone-x-pro-max',
        'categories' => ['Elektronik'],
        'qty' => 50,
    ],
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'KAOS-COTTON',
        'productName' => 'Kaos Premium Cotton Hitam',
        'shortDescription' => 'Kaos katun premium nyaman dipakai',
        'description' => 'Kaos premium berbahan 100% katun combed 30s. Nyaman dipakai sehari-hari, tersedia ukuran S, M, L, XL.',
        'price' => 99000,
        'weight' => 0.2,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'kaos-premium-cotton-hitam',
        'categories' => ['Fashion'],
        'qty' => 100,
    ],
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'KOPI-ARABIKA',
        'productName' => 'Kopi Arabika Gayo 250gr',
        'shortDescription' => 'Kopi arabika asli Gayo, Aceh',
        'description' => 'Kopi Arabika Gayo dengan cita rasa khas dataran tinggi Aceh. Diproses secara natural, menghasilkan body yang smooth dengan after taste sweet.',
        'price' => 45000,
        'weight' => 0.25,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'kopi-arabika-gayo-250gr',
        'categories' => ['Makanan & Minuman'],
        'qty' => 200,
    ],
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'SERUM-VITC',
        'productName' => 'Serum Vitamin C Brightening',
        'shortDescription' => 'Serum vitamin C untuk kulit cerah',
        'description' => 'Serum dengan kandungan Vitamin C 10% yang mampu mencerahkan kulit, mengurangi noda hitam, dan melindungi dari radikal bebas.',
        'price' => 75000,
        'weight' => 0.05,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'serum-vitamin-c-brightening',
        'categories' => ['Kesehatan & Kecantikan'],
        'qty' => 150,
    ],
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'LAMPU-TAMAN',
        'productName' => 'Lampu Taman Solar Panel LED',
        'shortDescription' => 'Lampu taman tenaga surya otomatis',
        'description' => 'Lampu taman dengan panel surya, otomatis menyala saat gelap. Hemat listrik, tahan air IP65.',
        'price' => 35000,
        'weight' => 0.3,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'lampu-taman-solar-panel-led',
        'categories' => ['Rumah Tangga'],
        'qty' => 80,
    ],
    [
        'type' => 'simple',
        'attribute_family_id' => $defaultFamily->id,
        'sku' => 'YOGA-MAT',
        'productName' => 'Yoga Mat Premium Anti Slip',
        'shortDescription' => 'Matras yoga anti slip tebal 6mm',
        'description' => 'Matras yoga premium dengan bahan TPE ramah lingkungan. Tebal 6mm, permukaan anti slip. Dilengkapi tas carrier.',
        'price' => 125000,
        'weight' => 0.8,
        'status' => 1,
        'visible_individually' => 1,
        'url_key' => 'yoga-mat-premium-anti-slip',
        'categories' => ['Olahraga & Hobi'],
        'qty' => 60,
    ],
];

foreach ($products as $prodData) {
    try {
        $existing = DB::table('products')->where('sku', $prodData['sku'])->first();
        if ($existing) {
            echo "… Product '{$prodData['productName']}' already exists\n";
            continue;
        }

        $categoryIds = [];
        foreach ($prodData['categories'] as $catName) {
            if (isset($categoriesMap[$catName])) {
                $categoryIds[] = $categoriesMap[$catName];
            }
        }

        $product = $productRepo->create([
            'type' => $prodData['type'],
            'attribute_family_id' => $prodData['attribute_family_id'],
            'sku' => $prodData['sku'],
            'productName' => $prodData['productName'],
            'shortDescription' => $prodData['shortDescription'],
            'description' => $prodData['description'],
            'price' => $prodData['price'],
            'weight' => $prodData['weight'],
            'status' => $prodData['status'],
            'visible_individually' => $prodData['visible_individually'],
            'url_key' => $prodData['url_key'],
            'guest_checkout' => 1,
            'new' => 1,
            'featured' => 1,
            'categories' => $categoryIds,
            'channel_id' => 1,
            'inventories' => [
                $invSource->id => $prodData['qty'],
            ],
        ]);

        echo "✓ Product '{$prodData['productName']}' created\n";
    } catch (\Exception $e) {
        echo "✗ Error creating product '{$prodData['productName']}': " . $e->getMessage() . "\n";
    }
}

// 6. Create Cart Rules / Coupons
echo "\n--- Creating Discount/Coupon ---\n";

$cartRuleRepo = app(CartRuleRepository::class);

$coupons = [
    [
        'name' => 'Diskon 10% untuk Pembeli Baru',
        'description' => 'Dapatkan diskon 10% untuk pembelian pertama',
        'coupon_code' => 'WELCOME10',
        'uses_per_coupon' => 100,
        'usage_per_customer' => 1,
        'action_type' => 'by_percent',
        'discount_amount' => 10,
        'free_shipping' => 0,
        'apply_to_shipping' => 0,
        'status' => 1,
    ],
    [
        'name' => 'Gratis Ongkir Min. Belanja Rp200rb',
        'description' => 'Gratis ongkos kirim',
        'coupon_code' => 'GRATISONGKIR',
        'uses_per_coupon' => 100,
        'usage_per_customer' => 1,
        'action_type' => 'by_percent',
        'discount_amount' => 100,
        'free_shipping' => 1,
        'apply_to_shipping' => 1,
        'status' => 1,
    ],
];

foreach ($coupons as $cData) {
    try {
        $existing = DB::table('cart_rule_coupons')->where('code', $cData['coupon_code'])->first();
        if ($existing) {
            echo "… Coupon '{$cData['coupon_code']}' already exists\n";
            continue;
        }

        $cartRule = $cartRuleRepo->create([
            'name' => $cData['name'],
            'description' => $cData['description'],
            'status' => $cData['status'],
            'coupon_type' => 1,
            'use_auto_generation' => 0,
            'usage_per_customer' => $cData['usage_per_customer'],
            'uses_per_coupon' => $cData['uses_per_coupon'],
            'priority' => 1,
            'starts_from' => null,
            'ends_till' => null,
            'action_type' => $cData['action_type'],
            'discount_amount' => $cData['discount_amount'],
            'discount_quantity' => 1,
            'discount_step' => 1,
            'apply_to_shipping' => $cData['apply_to_shipping'],
            'free_shipping' => $cData['free_shipping'],
            'end_other_rules' => 0,
        ]);

        DB::table('cart_rule_coupons')->insert([
            'code' => $cData['coupon_code'],
            'cart_rule_id' => $cartRule->id,
            'usage_limit' => $cData['uses_per_coupon'],
            'usage_per_customer' => $cData['usage_per_customer'],
            'times_used' => 0,
            'type' => 0,
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✓ Coupon '{$cData['coupon_code']}' created\n";
    } catch (\Exception $e) {
        echo "✗ Error creating coupon '{$cData['coupon_code']}': " . $e->getMessage() . "\n";
    }
}

// 7. Create CMS Page (Banner)
echo "\n--- Creating Banner CMS Page ---\n";

try {
    $existing = DB::table('cms_page_translations')->where('url_key', 'promo-banner')->first();
    if (!$existing) {
        $cmsRepo = app(PageRepository::class);
        $cmsRepo->create([
            'url_key' => 'promo-banner',
            'page_title' => 'Promo Banner',
            'html_content' => '<div style="text-align:center;padding:40px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border-radius:12px;margin:20px 0;">
<h1 style="font-size:2.5em;margin-bottom:10px;">Selamat Datang di Ayapremiumisme! 🎉</h1>
<p style="font-size:1.2em;margin-bottom:20px;">Dapatkan diskon 10% untuk pembelian pertama Anda! Gunakan kupon <strong>WELCOME10</strong></p>
<p style="font-size:1em;">Gratis ongkir untuk minimal belanja Rp200rb dengan kupon <strong>GRATISONGKIR</strong></p>
</div>',
            'channels' => [1],
        ]);
        echo "✓ Banner CMS page created\n";
    } else {
        echo "… Banner already exists\n";
    }
} catch (\Exception $e) {
    echo "✗ Error creating banner: " . $e->getMessage() . "\n";
}

// 8. General settings
echo "\n--- General Settings ---\n";

$generalSettings = [
    'general.general.locale_options.weight_unit' => 'kgs',
    'general.content.footer.footer_content' => '<p style="text-align:center;">© 2024 Ayapremiumisme. All Rights Reserved.</p>',
    'general.content.footer.footer_toggle' => '1',
];

foreach ($generalSettings as $code => $value) {
    CoreConfig::updateOrCreate(
        ['code' => $code, 'channel_code' => 'default'],
        ['value' => $value, 'locale_code' => null]
    );
}
echo "✓ General settings updated\n";

// Run indexer
echo "\n--- Running Indexer ---\n";
Artisan::call('indexer:index', ['--mode' => ['full']]);
echo "✓ Indexer completed\n";

echo "\n=== Setup Complete! ===\n";
echo "Admin URL: http://localhost:8000/admin\n";
echo "Email: admin@example.com\n";
echo "Password: admin123\n\n";
