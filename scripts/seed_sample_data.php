<?php
/**
 * Sample Data Seeder for Trattoria-Gusto Digital Menu
 *
 * Populates the local database with realistic Trattoria-Gusto products, categories,
 * and branches for local development and demonstration when the remote source
 * database is inaccessible or in testing environments.
 *
 * Usage: php scripts/seed_sample_data.php [--clear]
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use App\Helpers\Env;
use App\Helpers\Database;

Env::load(BASE_PATH . '/.env');

echo "=== Trattoria-Gusto — Sample Data Seeder ===\n";

$pdo = Database::local();
$clear = in_array('--clear', $argv ?? [], true);

if ($clear) {
    echo "Clearing existing sample products, categories, and branches...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE branch_products");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("TRUNCATE TABLE child_categories");
    $pdo->exec("TRUNCATE TABLE parent_categories");
    $pdo->exec("TRUNCATE TABLE branches");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "[OK] Tables truncated.\n";
}

// 1. Seed Branches
$branches = [
    [
        'code' => 'BOLE_MED',
        'name' => 'Trattoria-Gusto Bole Medhanialem',
        'address' => 'Cameroon St, Next to Medhanialem Mall, Bole, Addis Ababa',
        'phone' => '+251 116 61 77 00',
        'is_active' => 1
    ],
    [
        'code' => 'KAZANCHIS',
        'name' => 'Trattoria-Gusto Kazanchis (Intercontinental)',
        'address' => 'Jomo Kenyatta St, Near UNECA & Intercontinental Hotel, Addis Ababa',
        'phone' => '+251 115 50 44 22',
        'is_active' => 1
    ],
    [
        'code' => 'SARBET',
        'name' => 'Trattoria-Gusto Sarbet',
        'address' => 'Roosevelt St, Near AU Headquarters, Sarbet, Addis Ababa',
        'phone' => '+251 113 72 88 11',
        'is_active' => 1
    ],
    [
        'code' => 'PIASSA',
        'name' => 'Trattoria-Gusto Piassa Churchil',
        'address' => 'Churchill Ave, Arada Sub-City, Addis Ababa',
        'phone' => '+251 111 56 33 44',
        'is_active' => 1
    ],
    [
        'code' => 'AIRPORT_T2',
        'name' => 'Trattoria-Gusto Bole Airport Terminal 2',
        'address' => 'Bole International Airport, Departures Level 2, Addis Ababa',
        'phone' => '+251 116 65 12 34',
        'is_active' => 1
    ]
];

$branchStmt = $pdo->prepare(
    "INSERT INTO branches (code, name, address, phone, is_active)
     VALUES (:code, :name, :address, :phone, :is_active)
     ON DUPLICATE KEY UPDATE name = VALUES(name), address = VALUES(address), phone = VALUES(phone)"
);
foreach ($branches as $b) {
    $branchStmt->execute($b);
}
echo "[OK] Seeded " . count($branches) . " branches.\n";

// 2. Category Hierarchy & Products
// Reflects source format: parent_preference -> child_preference -> articles
$menuData = [
    [
        'parent_id' => 101,
        'parent_name' => 'Hot Beverages',
        'children' => [
            [
                'child_id' => 1001,
                'child_name' => 'Espresso & Classics',
                'items' => [
                    [
                        'code' => 'KLD-HB-001',
                        'name' => 'Trattoria-Gusto Traditional Macchiato',
                        'desc' => 'Our signature Ethiopian roast espresso layered with velvety steamed local whole milk foam.',
                        'price' => 75.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-HB-002',
                        'name' => 'Double Espresso Single Origin',
                        'desc' => 'Intense, rich double shot extracted from premium washed Yirgacheffe Arabica beans.',
                        'price' => 70.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-HB-003',
                        'name' => 'Caffè Americano',
                        'desc' => 'Espresso diluted with hot water creating a light layer of crema and deep aromatic notes.',
                        'price' => 65.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-HB-004',
                        'name' => 'Kaldis Cappuccino',
                        'desc' => 'Equal parts dark espresso, steamed fresh milk, and rich microfoam dusted with organic cocoa powder.',
                        'price' => 85.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-HB-005',
                        'name' => 'Caffè Latte',
                        'desc' => 'Smooth and creamy espresso combined with generous steamed milk and light foam topping.',
                        'price' => 90.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ],
            [
                'child_id' => 1002,
                'child_name' => 'Specialty Hot Drinks',
                'items' => [
                    [
                        'code' => 'KLD-HB-006',
                        'name' => 'Caramel Mocha Supreme',
                        'desc' => 'Double espresso infused with Belgian dark chocolate, rich caramel sauce, and steamed milk.',
                        'price' => 110.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-HB-007',
                        'name' => 'Hot Chocolate with Marshmallows',
                        'desc' => 'Rich, velvety melted Dutch cocoa with steamed whole milk and mini marshmallows.',
                        'price' => 95.00,
                        'unit' => 'Cup',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'parent_id' => 102,
        'parent_name' => 'Cold & Iced Beverages',
        'children' => [
            [
                'child_id' => 1003,
                'child_name' => 'Iced Coffees & Frappes',
                'items' => [
                    [
                        'code' => 'KLD-CB-001',
                        'name' => 'Kaldis Iced Caramel Macchiato',
                        'desc' => 'Chilled milk marked with espresso, sweetened with vanilla syrup and topped with caramel drizzle over ice.',
                        'price' => 120.00,
                        'unit' => 'Glass',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-CB-002',
                        'name' => 'Nitro Cold Brew Coffee',
                        'desc' => '18-hour cold steeped Ethiopian Sidama coffee, smooth naturally sweet finish with a creamy head.',
                        'price' => 110.00,
                        'unit' => 'Glass',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-CB-003',
                        'name' => 'Mocha Frappe Blended Drink',
                        'desc' => 'Espresso blended with dark chocolate chips, milk, and ice, finished with whipped cream.',
                        'price' => 135.00,
                        'unit' => 'Glass',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ],
            [
                'child_id' => 1004,
                'child_name' => 'Fresh Juices & Smoothies',
                'items' => [
                    [
                        'code' => 'KLD-CB-004',
                        'name' => 'Special Spris Juice (Layered)',
                        'desc' => 'Traditional Ethiopian tri-color layered fresh avocado, mango, and papaya with a squeeze of lime.',
                        'price' => 115.00,
                        'unit' => 'Glass',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-CB-005',
                        'name' => 'Fresh Strawberry Banana Smoothie',
                        'desc' => 'Fresh organic strawberries blended with ripe banana, plain yoghurt, and a touch of highland honey.',
                        'price' => 125.00,
                        'unit' => 'Glass',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'parent_id' => 103,
        'parent_name' => 'Traditional Teas & Infusions',
        'children' => [
            [
                'child_id' => 1005,
                'child_name' => 'Ethiopian Highland Teas',
                'items' => [
                    [
                        'code' => 'KLD-TE-001',
                        'name' => 'Ethiopian Spiced Tea (Korerima & Cinnamon)',
                        'desc' => 'Black highland Wush Wush tea brewed with cardamom, cinnamon bark, cloves, and fresh ginger.',
                        'price' => 60.00,
                        'unit' => 'Pot',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-TE-002',
                        'name' => 'Fresh Lemon Grass Herbal Tea',
                        'desc' => 'Steeped garden lemongrass infused with fresh mint leaves and organic forest honey.',
                        'price' => 65.00,
                        'unit' => 'Pot',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'parent_id' => 104,
        'parent_name' => 'Bakery & Fresh Pastries',
        'children' => [
            [
                'child_id' => 1006,
                'child_name' => 'French Viennoiserie',
                'items' => [
                    [
                        'code' => 'KLD-BK-001',
                        'name' => 'Golden Butter Croissant',
                        'desc' => 'Flaky, butter-rich, golden-baked artisan croissant prepared fresh daily.',
                        'price' => 95.00,
                        'unit' => 'Piece',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-BK-002',
                        'name' => 'Pain au Chocolat',
                        'desc' => 'Traditional layered puff pastry filled with two batons of Belgian bittersweet dark chocolate.',
                        'price' => 110.00,
                        'unit' => 'Piece',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-BK-003',
                        'name' => 'Warm Cinnamon Swirl Danish',
                        'desc' => 'Spiced cinnamon and brown sugar swirl with light vanilla cream glaze.',
                        'price' => 105.00,
                        'unit' => 'Piece',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ],
            [
                'child_id' => 1007,
                'child_name' => 'Cakes & Desserts',
                'items' => [
                    [
                        'code' => 'KLD-BK-004',
                        'name' => 'New York Style Baked Cheesecake',
                        'desc' => 'Dense, creamy Philadelphia cheesecake on graham cracker crust with strawberry coulis.',
                        'price' => 160.00,
                        'unit' => 'Slice',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-BK-005',
                        'name' => 'Rich Triple Chocolate Fudge Cake',
                        'desc' => 'Layers of moist dark chocolate sponge filled and frosted with silky dark chocolate ganache.',
                        'price' => 150.00,
                        'unit' => 'Slice',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'parent_id' => 105,
        'parent_name' => 'Breakfast & Light Meals',
        'children' => [
            [
                'child_id' => 1008,
                'child_name' => 'Sandwiches & Paninis',
                'items' => [
                    [
                        'code' => 'KLD-FD-001',
                        'name' => 'Kaldis Special Club Sandwich',
                        'desc' => 'Triple-decker toasted bread with roasted chicken breast, beef bacon, boiled egg, lettuce, tomato, and seasoned fries.',
                        'price' => 280.00,
                        'unit' => 'Plate',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-FD-002',
                        'name' => 'Grilled Chicken Mozzarella Panini',
                        'desc' => 'Marinated herb grilled chicken, melted fresh mozzarella, pesto sauce, and sundried tomatoes on ciabatta.',
                        'price' => 250.00,
                        'unit' => 'Plate',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-FD-003',
                        'name' => 'Classic Beef Cheeseburger',
                        'desc' => 'Pure prime beef patty grilled to perfection with cheddar cheese, crisp lettuce, red onion, and house relish.',
                        'price' => 295.00,
                        'unit' => 'Plate',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ],
            [
                'child_id' => 1009,
                'child_name' => 'Breakfast Plates',
                'items' => [
                    [
                        'code' => 'KLD-FD-004',
                        'name' => 'Sourdough Avocado Toast with Poached Eggs',
                        'desc' => 'Toasted sourdough bread topped with crushed seasoned avocado, cherry tomatoes, feta, and two poached free-range eggs.',
                        'price' => 240.00,
                        'unit' => 'Plate',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-FD-005',
                        'name' => 'Traditional Ethiopian Chechebsa',
                        'desc' => 'Shredded unleavened flatbread fried in seasoned clarified butter (kibe) with berbere and served with fresh plain yoghurt.',
                        'price' => 180.00,
                        'unit' => 'Plate',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'parent_id' => 106,
        'parent_name' => 'Coffee Beans & Retail Goods',
        'children' => [
            [
                'child_id' => 1010,
                'child_name' => 'Single Origin Whole Beans',
                'items' => [
                    [
                        'code' => 'KLD-RT-001',
                        'name' => 'Yirgacheffe Grade 1 Washed (250g)',
                        'desc' => 'Distinctive floral jasmine notes, lively lemon acidity, and clean bergamot tea-like body. Medium roast.',
                        'price' => 380.00,
                        'unit' => 'Bag',
                        'val' => '1',
                        'img' => null
                    ],
                    [
                        'code' => 'KLD-RT-002',
                        'name' => 'Sidama Natural Roast (250g)',
                        'desc' => 'Deep blueberry and ripe stone fruit aromas, milk chocolate sweetness, and heavy velvety finish. Dark roast.',
                        'price' => 360.00,
                        'unit' => 'Bag',
                        'val' => '1',
                        'img' => null
                    ]
                ]
            ]
        ]
    ]
];

$parentStmt = $pdo->prepare(
    "INSERT INTO parent_categories (source_id, name, name_normalized, is_visible, sort_order)
     VALUES (:sid, :name, :nn, 1, :sort)
     ON DUPLICATE KEY UPDATE name = VALUES(name), name_normalized = VALUES(name_normalized), id = LAST_INSERT_ID(id)"
);

$childStmt = $pdo->prepare(
    "INSERT INTO child_categories (parent_category_id, source_id, name, name_normalized, is_visible, sort_order)
     VALUES (:pid, :sid, :name, :nn, 1, :sort)
     ON DUPLICATE KEY UPDATE name = VALUES(name), name_normalized = VALUES(name_normalized), id = LAST_INSERT_ID(id)"
);

$prodStmt = $pdo->prepare(
    "INSERT INTO products (
        article_code, name, gsl_type_id, gsl_type, consignee_unit,
        default_value, is_active_source,
        src_parent_pref_id, src_parent_pref,
        src_child_pref_id, src_child_pref,
        parent_category_id, child_category_id,
        local_description, is_visible, price, price_currency,
        synced_at
    ) VALUES (
        :code, :name, 1, 'Sale Item', :unit,
        :val, 1,
        :src_pid, :src_pname,
        :src_cid, :src_cname,
        :parent_id, :child_id,
        :desc, 1, :price, 'ETB',
        NOW()
    ) ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        src_parent_pref = VALUES(src_parent_pref),
        src_child_pref = VALUES(src_child_pref),
        parent_category_id = VALUES(parent_category_id),
        child_category_id = VALUES(child_category_id),
        local_description = VALUES(local_description),
        price = VALUES(price)"
);

$parentSort = 10;
$totalItems = 0;

foreach ($menuData as $parentGroup) {
    $parentStmt->execute([
        ':sid'  => $parentGroup['parent_id'],
        ':name' => $parentGroup['parent_name'],
        ':nn'   => mb_strtolower(trim($parentGroup['parent_name'])),
        ':sort' => $parentSort
    ]);
    // Get parent ID
    $checkParent = $pdo->prepare("SELECT id FROM parent_categories WHERE source_id = ?");
    $checkParent->execute([$parentGroup['parent_id']]);
    $localParentId = (int)$checkParent->fetchColumn();
    $parentSort += 10;

    $childSort = 10;
    foreach ($parentGroup['children'] as $childGroup) {
        $childStmt->execute([
            ':pid'  => $localParentId,
            ':sid'  => $childGroup['child_id'],
            ':name' => $childGroup['child_name'],
            ':nn'   => mb_strtolower(trim($childGroup['child_name'])),
            ':sort' => $childSort
        ]);
        $checkChild = $pdo->prepare("SELECT id FROM child_categories WHERE source_id = ?");
        $checkChild->execute([$childGroup['child_id']]);
        $localChildId = (int)$checkChild->fetchColumn();
        $childSort += 10;

        foreach ($childGroup['items'] as $item) {
            $prodStmt->execute([
                ':code'      => $item['code'],
                ':name'      => $item['name'],
                ':unit'      => $item['unit'],
                ':val'       => $item['val'],
                ':src_pid'   => $parentGroup['parent_id'],
                ':src_pname' => $parentGroup['parent_name'],
                ':src_cid'   => $childGroup['child_id'],
                ':src_cname' => $childGroup['child_name'],
                ':parent_id' => $localParentId,
                ':child_id'  => $localChildId,
                ':desc'      => $item['desc'],
                ':price'     => $item['price']
            ]);
            $totalItems++;
        }
    }
}

echo "[OK] Successfully seeded:\n";
echo "     - " . count($menuData) . " Parent Categories\n";
echo "     - " . array_sum(array_map(fn($p) => count($p['children']), $menuData)) . " Child Categories\n";
echo "     - " . $totalItems . " Products with descriptions and verified prices\n";
echo "=== Sample data seeding complete ===\n";

