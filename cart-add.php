<?php
session_start();
?>

<link rel="stylesheet" href="./css/cart-add.css">

<body>
    <div class="container">
        <?php

        $pdo = new PDO('mysql:host=mysql313.phy.lolipop.lan;dbname=LAA1557214-loslogosshop;charset=utf8', 'LAA1557214', 'Pass0331');

        // ログインユーザーまたはゲストの判定
        $customer_id = $_SESSION['customer']['id'] ?? null;
        $guest_id = $_SESSION['guest_id'] ?? null;

        //　ゲストIDがない場合、新しく作成
        if (!isset($_SESSION['guest_id'])) {
            $stmt = $pdo->prepare('INSERT INTO guest (session_id, session_create_time, session_update_time) VALUES (?, NOW(), NOW())');
            $stmt->execute([session_id()]);
            $_SESSION['guest_id'] = $pdo->lastInsertId();
        }


        // product-detailのPOSTから商品IDと数量を取得
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        //　入力チェック
        if (!$product_id || $quantity <= 0) {
            die('不正なリクエストです。');
        }

        try {
            $pdo->beginTransaction();

            $cart_id = null;

            // ログインしている場合のカートID取得
            if ($customer_id) {
                $stmt = $pdo->prepare('SELECT cart_id FROM shoppingcart WHERE customer_id = ?');
                $stmt->execute([$customer_id]);
                $cart_id = $stmt->fetchColumn();
            }
            // ゲストの場合のカートID取得
            elseif ($guest_id) {
                $stmt = $pdo->prepare('SELECT cart_id FROM shoppingcart WHERE guest_id = ?');
                $stmt->execute([$guest_id]);
                $cart_id = $stmt->fetchColumn();
            }

            // カートが存在しない場合、新規作成
            if (!$cart_id) {
                $stmt = $pdo->prepare('INSERT INTO shoppingcart (customer_id, guest_id) VALUES (?, ?)');
                $stmt->execute([$customer_id, $guest_id]);
                $cart_id = $pdo->lastInsertId(); // 新しく作成したカートのIDを取得
            }

            // カートアイテムの確認または追加
            $stmt = $pdo->prepare('SELECT cart_item_id, quantity FROM cartitem WHERE cart_id = ? AND product_id = ?');
            $stmt->execute([$cart_id, $product_id]);
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cart_item) {
                // 既存アイテムの数量を更新
                $new_quantity = $cart_item['quantity'] + $quantity;
                $stmt = $pdo->prepare('UPDATE cartitem SET quantity = ? WHERE cart_item_id = ?');
                $stmt->execute([$new_quantity, $cart_item['cart_item_id']]);
            } else {
                // 新しいアイテムを追加
                $stmt = $pdo->prepare('INSERT INTO cartitem (cart_id, product_id, quantity) VALUES (?, ?, ?)');
                $stmt->execute([$cart_id, $product_id, $quantity]);
            }

            $pdo->commit();

            echo '<div class="input-field">';
            echo '<h2>カートに商品を追加しました。</h2>';
            echo '</div>';
            echo '<a href="cart-page.php" class="cart-look-button">カートを見る</a><br><br>';
            echo '<p><a href="home-page.php" class="home-button">ホーム画面へ</a></p>';
        } catch (Exception $e) {
            $pdo->rollBack();
            die('エラー:' . $e->getMessage());
        }
        ?>
    </div>
</body>