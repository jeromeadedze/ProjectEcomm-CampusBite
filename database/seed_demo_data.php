<?php
/**
 * Demo data for local testing: 60 days of realistic orders so the kitchen and admin dashboards have something to show.
 *
 *   php database/seed_demo_data.php            replace any demo data with a fresh 60 days
 *   php database/seed_demo_data.php --remove   delete the demo data only
 *
 * Everything it creates hangs off demo customers whose email ends in @demo.campusbite.app, so removing them
 * (and their orders, payments, messages and tracking) puts the database back exactly as it was.
 * Never run this on a live server.
 */
if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
require_once __DIR__ . '/../core/db_class.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

const DEMO_DOMAIN = '@demo.campusbite.app';
const DAYS = 60;
const CUSTOMERS = 160;

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
mt_srand(20261121); // the same demo history every run

function remove_demo(PDO $db) {
    $ids = "SELECT customer_id FROM cb_customer WHERE customer_email LIKE '%" . DEMO_DOMAIN . "'";
    $orders = "SELECT order_id FROM cb_orders WHERE customer_id IN ($ids)";
    $db->exec("DELETE FROM cb_support_replies WHERE message_id IN (SELECT message_id FROM cb_support_messages WHERE customer_id IN ($ids))");
    $db->exec("DELETE FROM cb_support_messages WHERE customer_id IN ($ids)");
    $db->exec("DELETE FROM cb_refunds WHERE order_id IN ($orders)");
    $db->exec("DELETE FROM cb_order_tracking WHERE order_id IN ($orders)");
    $db->exec("DELETE FROM cb_payment WHERE order_id IN ($orders)");
    $db->exec("DELETE FROM cb_orderdetails WHERE order_id IN ($orders)");
    $db->exec("DELETE FROM cb_favorites WHERE customer_id IN ($ids)");
    $db->exec("DELETE FROM cb_cart WHERE c_id IN ($ids)");
    $db->exec("DELETE FROM cb_orders WHERE customer_id IN ($ids)");
    $n = $db->exec("DELETE FROM cb_customer WHERE customer_email LIKE '%" . DEMO_DOMAIN . "'");
    return $n;
}

// Weighted random pick: $weights is [value => weight]
function pick(array $weights) {
    $r = mt_rand() / mt_getrandmax() * array_sum($weights);
    foreach ($weights as $value => $w) {
        if (($r -= $w) <= 0) {
            return $value;
        }
    }
    return array_key_last($weights);
}

$db->beginTransaction();
$removed = remove_demo($db);
if (in_array('--remove', $argv, true)) {
    $db->commit();
    echo "Removed $removed demo customers and everything linked to them.\n";
    exit;
}

// ---------- Kitchens and their meals ----------
$kitchens = $db->query("SELECT brand_id, brand_name, zone, commission_rate FROM cb_brands")->fetchAll(PDO::FETCH_ASSOC);
$mealsByKitchen = [];
foreach ($db->query("SELECT p.product_id, p.product_brand, p.product_price, p.prep_time_minutes, c.cat_name
                     FROM cb_products p JOIN cb_categories c ON c.cat_id = p.product_cat")->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $mealsByKitchen[(int)$m['product_brand']][] = $m;
}
// How busy each kitchen is (bigger campus cafeterias get more orders); kitchens without meals get none
$popularity = [1 => 22, 2 => 20, 3 => 19, 4 => 14, 5 => 13, 6 => 12];
$kitchenWeights = [];
foreach ($kitchens as $k) {
    if (!empty($mealsByKitchen[(int)$k['brand_id']])) {
        $kitchenWeights[(int)$k['brand_id']] = $popularity[(int)$k['brand_id']] ?? 8;
    }
}
$kitchenById = array_column($kitchens, null, 'brand_id');
if (!$kitchenWeights) {
    $db->rollBack();
    exit("No kitchens have meals yet, so there is nothing to order.\n");
}

// ---------- Demo students ----------
$first = ['Ama', 'Kofi', 'Akosua', 'Kwame', 'Abena', 'Yaw', 'Efua', 'Kojo', 'Adwoa', 'Kwabena', 'Esi', 'Kwaku', 'Afua', 'Fiifi', 'Akua',
          'Nana', 'Selorm', 'Elikem', 'Dzifa', 'Mawuli', 'Naa', 'Nii', 'Aba', 'Ekow', 'Araba', 'Kweku', 'Adjoa', 'Edem', 'Delali', 'Sena',
          'Jessica', 'Daniel', 'Priscilla', 'Emmanuel', 'Gifty', 'Samuel', 'Grace', 'Michael', 'Lydia', 'Joseph'];
$last = ['Mensah', 'Owusu', 'Boateng', 'Asante', 'Osei', 'Agyeman', 'Appiah', 'Darko', 'Ofori', 'Amoah', 'Addo', 'Quaye', 'Tetteh',
         'Lartey', 'Adjei', 'Antwi', 'Nyarko', 'Sarpong', 'Frimpong', 'Kumi', 'Ansah', 'Acheampong', 'Bonsu', 'Gyamfi', 'Danso',
         'Agbeko', 'Dogbe', 'Kpodo', 'Amedzro', 'Tsikata'];
$hostels = array_keys(CustomerController::HOSTELS);
$demoPass = password_hash('Demo123!', PASSWORD_DEFAULT);
$insCustomer = $db->prepare("INSERT INTO cb_customer (customer_name, customer_email, customer_pass, customer_contact, campus_hostel, user_role)
                             VALUES (?, ?, ?, ?, ?, 2)");
$customers = [];
for ($i = 1; $i <= CUSTOMERS; $i++) {
    $f = $first[array_rand($first)];
    $l = $last[array_rand($last)];
    $insCustomer->execute(["$f $l", strtolower("$f.$l.$i") . DEMO_DOMAIN, $demoPass, '024' . str_pad((string)mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT), $hostels[array_rand($hostels)]]);
    // Some students order a lot, most now and then
    $customers[(int)$db->lastInsertId()] = ['weight' => pick([1 => 55, 3 => 30, 8 => 15]), 'hostel' => $hostels[array_rand($hostels)], 'favourite' => array_rand($kitchenWeights)];
}
$customerWeights = array_map(function ($c) { return $c['weight']; }, $customers);

// ---------- Orders ----------
$insOrder = $db->prepare("INSERT INTO cb_orders (customer_id, brand_id, invoice_no, order_date, order_status, delivery_zone, delivery_fee,
                                             drop_location, special_instructions, commission_rate, cancelled_at, cancelled_by, cancel_reason)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insLine = $db->prepare("INSERT INTO cb_orderdetails (order_id, product_id, qty, price, notes) VALUES (?, ?, ?, ?, ?)");
$insPay = $db->prepare("INSERT INTO cb_payment (amt, customer_id, order_id, currency, payment_mode, transaction_reference, payment_date, verified_at,
                                            kitchen_share, campusbite_share)
                        VALUES (?, ?, ?, 'GHS', ?, ?, ?, ?, ?, ?)");
$insTrack = $db->prepare("INSERT INTO cb_order_tracking (order_id, status, status_message, updated_at) VALUES (?, ?, ?, ?)");
$insRefund = $db->prepare("INSERT INTO cb_refunds (order_id, pay_id, amount, status, created_at) VALUES (?, ?, ?, 'not_needed', ?)");

$hourWeights = [7 => 3, 8 => 5, 9 => 4, 10 => 4, 11 => 9, 12 => 16, 13 => 15, 14 => 8, 15 => 4, 16 => 5, 17 => 8, 18 => 12, 19 => 14, 20 => 11, 21 => 6, 22 => 3];
$payModes = ['Paystack - MTN MoMo' => 60, 'Paystack - Telecel Cash' => 15, 'Paystack - Card' => 25];
$notes = [null => 85, 'Less pepper please' => 4, 'Extra shito' => 4, 'No onions' => 3, 'Call when you arrive' => 4];
$reasons = ['customer' => ['Ordered by mistake', 'Changed my mind', 'Ordered from the wrong kitchen'],
            'kitchen' => ['Ran out of this meal', 'Kitchen closing early today', 'Too many orders right now']];
$stepMessages = ['Placed' => 'Order placed successfully and received by kitchen.', 'Preparing' => 'The kitchen is preparing your meal.',
                 'Ready' => 'Your meal is packed and ready to go.', 'In Transit' => 'Your meal is on its way.', 'Delivered' => 'Delivered. Enjoy your meal!'];
$usedInvoices = array_flip($db->query("SELECT invoice_no FROM cb_orders")->fetchAll(PDO::FETCH_COLUMN));
$now = time();
$fees = ProductController::DELIVERY_FEES;
$orderCount = 0;
$todayOrders = [];

for ($back = DAYS - 1; $back >= 0; $back--) {
    $dayStart = strtotime(date('Y-m-d 00:00:00', strtotime("-$back days")));
    $weekday = (int)date('N', $dayStart);
    // Quieter weekends, steady growth over the 60 days, a little day-to-day noise
    $volume = ($weekday >= 6 ? 34 : 58) * (0.75 + 0.5 * (DAYS - $back) / DAYS) * (0.85 + mt_rand(0, 30) / 100);
    // Pick the day's order times first and sort them, so order ids follow the clock like real orders
    $times = [];
    for ($n = 0; $n < (int)round($volume); $n++) {
        $when = $dayStart + pick($hourWeights) * 3600 + mt_rand(0, 3599);
        if ($when <= $now - 120) {
            $times[] = [$when, null]; // nothing in the future, or in the last two minutes
        }
    }
    if ($back === 0) {
        // A few orders from the last hour for every kitchen, at each stage, so the staff screens have a live queue
        foreach (array_keys($kitchenWeights) as $kid) {
            foreach ([4, 18, 36, 58] as $mins) {
                if (mt_rand(1, 100) <= 80) {
                    $times[] = [$now - ($mins + mt_rand(0, 5)) * 60, $kid];
                }
            }
        }
    }
    usort($times, function ($a, $b) { return $a[0] <=> $b[0]; });
    foreach ($times as [$when, $liveKitchen]) {
        $customerId = pick($customerWeights);
        $kitchenId = $liveKitchen ?: (mt_rand(1, 100) <= 45 ? $customers[$customerId]['favourite'] : pick($kitchenWeights));
        $kitchen = $kitchenById[$kitchenId];
        $menu = $mealsByKitchen[$kitchenId];

        // A main, sometimes a second main, often a drink or dessert
        $mains = array_values(array_filter($menu, function ($m) { return !preg_match('/drink|smoothie|dessert|cake|pastr/i', $m['cat_name']); })) ?: $menu;
        $sides = array_values(array_filter($menu, function ($m) { return preg_match('/drink|smoothie|dessert|cake|pastr/i', $m['cat_name']); }));
        $lines = [$mains[array_rand($mains)]];
        if (mt_rand(1, 100) <= 18) { $lines[] = $mains[array_rand($mains)]; }
        if ($sides && mt_rand(1, 100) <= 45) { $lines[] = $sides[array_rand($sides)]; }

        $items = 0.0;
        $qty = [];
        foreach ($lines as $m) {
            $q = mt_rand(1, 100) <= 12 ? 2 : 1;
            $qty[] = $q;
            $items += $q * (float)$m['product_price'];
        }
        $fee = (float)($fees[$kitchen['zone']] ?? $fees['Zone A']);
        $rate = (float)$kitchen['commission_rate'];
        $commission = round($items * $rate, 2);

        // Status: anything older than about 90 minutes has been delivered; newer orders are still moving
        $ageMin = ($now - $when) / 60;
        $status = 'Delivered';
        if ($ageMin < 12) { $status = 'Placed'; }
        elseif ($ageMin < 30) { $status = 'Preparing'; }
        elseif ($ageMin < 45) { $status = 'Ready'; }
        elseif ($ageMin < 75) { $status = 'In Transit'; }
        $cancelledBy = null;
        if (!$liveKitchen && mt_rand(1, 100) <= 4) {
            $status = 'Cancelled';
            $cancelledBy = mt_rand(1, 100) <= 55 ? 'customer' : 'kitchen';
        }

        do { $invoice = mt_rand(100000, 999999); } while (isset($usedInvoices[$invoice]));
        $usedInvoices[$invoice] = true;
        $ts = date('Y-m-d H:i:s', $when);
        $cancelAt = $cancelledBy ? date('Y-m-d H:i:s', $when + mt_rand(3, 15) * 60) : null;
        $insOrder->execute([$customerId, $kitchenId, $invoice, $ts, $status, $kitchen['zone'], $fee, $customers[$customerId]['hostel'],
                            mt_rand(1, 100) <= 15 ? 'Call when you get to the gate' : null, $rate, $cancelAt, $cancelledBy,
                            $cancelledBy ? $reasons[$cancelledBy][array_rand($reasons[$cancelledBy])] : null]);
        $orderId = (int)$db->lastInsertId();
        foreach ($lines as $i => $m) {
            $insLine->execute([$orderId, $m['product_id'], $qty[$i], $m['product_price'], pick($notes) ?: null]);
        }

        $total = $items + $fee;
        $insPay->execute([$total, $customerId, $orderId, pick($payModes), 'DEMO-' . strtoupper(bin2hex(random_bytes(6))), $ts, $ts,
                          round($total - $commission, 2), $commission]);
        $payId = (int)$db->lastInsertId();

        // Timeline, as the tracking page shows it
        $insTrack->execute([$orderId, 'Placed', $stepMessages['Placed'], $ts]);
        if ($status === 'Cancelled') {
            $insTrack->execute([$orderId, 'Cancelled', 'Order cancelled and refunded in full.', $cancelAt]);
            $insRefund->execute([$orderId, $payId, $total, $cancelAt]);
        } else {
            $offset = 0;
            foreach (['Preparing' => 4, 'Ready' => 18, 'In Transit' => 6, 'Delivered' => 14] as $step => $mins) {
                if (array_search($step, array_keys($stepMessages)) > array_search($status, array_keys($stepMessages))) {
                    break;
                }
                $offset += $mins + mt_rand(0, 6);
                $insTrack->execute([$orderId, $step, $stepMessages[$step], date('Y-m-d H:i:s', $when + $offset * 60)]);
            }
        }
        if ($back === 0) {
            $todayOrders[] = [$orderId, $customerId, $kitchenId];
        }
        $orderCount++;
    }
}

// ---------- A few customer messages, some still waiting for the kitchen ----------
$insMsg = $db->prepare("INSERT INTO cb_support_messages (customer_id, order_id, brand_id, topic, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$recent = $db->query("SELECT o.order_id, o.customer_id, o.brand_id, o.order_date FROM cb_orders o JOIN cb_customer c ON c.customer_id = o.customer_id
                      WHERE c.customer_email LIKE '%" . DEMO_DOMAIN . "' AND o.order_status = 'Delivered' ORDER BY o.order_date DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
$messages = [
    ['Order issue', 'My order arrived without the drink I paid for. Can you check?', 'Open'],
    ['Order issue', 'The food was cold when it got to my hostel.', 'Open'],
    ['Other', 'Do you have a vegetarian option for the jollof?', 'Open'],
    ['Feedback', 'Delivery was really fast today, thank you!', 'Resolved'],
    ['Order issue', 'I got the wrong wrap, I ordered the spicy one.', 'Resolved'],
];
foreach ($messages as $i => [$topic, $text, $status]) {
    $o = $recent[$i * 3] ?? null;
    if ($o) {
        $insMsg->execute([$o['customer_id'], $o['order_id'], $o['brand_id'], $topic, $text, $status, date('Y-m-d H:i:s', strtotime($o['order_date']) + 3600)]);
    }
}

$db->commit();
printf("Removed %d old demo customers. Added %d demo customers and %d orders over %d days (%d today).\n",
    $removed, CUSTOMERS, $orderCount, DAYS, count($todayOrders));
