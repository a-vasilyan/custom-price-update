<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .wrap{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 450px;
        }
        .button, #product{
            width:200px;
        }
        .h3{
            text-align: center;
        }
        .select{
            font-size:20px;
            line-height:2;
        }
        table{
            width: 100%;
            border-radius: 25px;
            text-align: center;
        }
        table td{
            border-radius: 12px;
        }
    </style>
</head>
<body>
    
</body>
</html>
<?php
/*
Plugin Name: Custom Price Update for WooCommerce
Description: Plugin to update product prices in WooCommerce.
Version: 1.0
Author: BIG GEEK
*/

function custom_price_update_menu() {
    add_submenu_page(
        'woocommerce',
        'Обновление цен',
        'Обновление цен',
        'manage_options',
        'custom_price_update',
        'custom_price_update_page'
    );
}
add_action('admin_menu', 'custom_price_update_menu');

function custom_price_update_page() {
    ?>
    
    <div class="wrap">
        <h2 style="font-size:50px;"><?php echo esc_html__('Обновление цен', 'custom-price-update'); ?></h2>
        <form method="post" action="">
            <?php
            echo '<label for="price" style="font-size:20px; line-height:2;">' . esc_html__('Цена:', 'custom-price-update') . '</label><br>';
            echo '<input type="number" name="price" id="price" style="width:200px;" step="0.01" min="0" required><br>';

            echo '<label for="product" style="font-size:20px; line-height:2;">' . esc_html__('Выберите продукт:', 'custom-price-update') . '</label><br>';
            echo '<select name="product" id="product">';
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
            );
            $products = get_posts($args);
            foreach ($products as $product) {
                echo '<option value="' . esc_attr($product->ID) . '">' . esc_html($product->post_title) . '</option>';
            }
            echo '</select><br>';

            if (isset($_POST['product'])) {
                $product_id = absint($_POST['product']);
                $product = wc_get_product($product_id);
                if ($product && $product->is_type('variable')) {
                    echo '<h3>' . esc_html__('Вариации продукта', 'custom-price-update') . '</h3>';
                    echo '<table border="1">';
                    foreach ($product->get_available_variations() as $variation) {
                        echo '<tr>';
                        echo '<td><input type="checkbox" name="variations[]" value="' . esc_attr($variation['variation_id']) . '"></td>';
                        echo '<td>' . esc_html(implode(', ', $variation['attributes'])) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }

            wp_nonce_field('custom_price_update_nonce', 'custom_price_update_nonce');
            echo '<input type="submit" name="update_prices" class="button button-primary" style="font-size:15px; margin-top:20px;" value="' . esc_attr__('Сохранить', 'custom-price-update') . '">';

            if (isset($_POST['update_prices']) && isset($_POST['custom_price_update_nonce']) && wp_verify_nonce($_POST['custom_price_update_nonce'], 'custom_price_update_nonce')) {
                update_prices();
            }
            ?>
        </form>
    </div>
    <?php
}

function update_prices() {
    $product_id = absint($_POST['product']);
    $new_price = round(floatval($_POST['price']) * 3, 0);
    $variations = isset($_POST['variations']) ? $_POST['variations'] : array();

    update_post_meta($product_id, '_price', $new_price);
    update_post_meta($product_id, '_regular_price', $new_price);
    update_post_meta($product_id, '_sale_price', '');

    foreach ($variations as $variation_id) {
        update_post_meta($variation_id, '_price', $new_price);
        update_post_meta($variation_id, '_regular_price', $new_price);
        update_post_meta($variation_id, '_sale_price', '');
    }

    add_settings_error('custom_price_update', 'custom_price_update_success', esc_html__('Цены успешно обновлены!', 'custom-price-update'), 'updated');
}

