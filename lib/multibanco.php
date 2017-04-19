<?php
/**
 * IfthenPay Multibanco helper class
 *
 * @link  http://s3rgiosan.com/
 * @since 1.0.0
 */

namespace s3rgiosan\IfthenPay;

/**
 * IfthenPay Multibanco helper class.
 *
 * @since  1.0.0
 * @author Sérgio Santos <me@s3rgiosan.com>
 */
class Multibanco
{

    /**
     * Format a number according to the regional settings.
     *
     * @since  1.0.0
     * @param  string $number Number to format.
     * @return string         The formatted number.
     */
    public static function formatNumber($number)
    {
        $verify_sep_decimal = number_format(99, 2);
        $value              = $number;
        $sep_decimal        = substr($verify_sep_decimal, 2, 1);
        $has_sep_decimal    = true;

        $i = strlen($value) - 1;
        for ($i; $i !== 0; $i -= 1) {
            if (substr($value, $i, 1) === '.' || substr($value, $i, 1) === ',') {
                $has_sep_decimal = true;
                $value           = trim(substr($value, 0, $i)) . '@' . trim(substr($value, 1 + $i));
                break;
            }
        }

        if ($has_sep_decimal !== true) {
            $value = number_format($value, 2);
            $i = (strlen($value) - 1);
            for ($i; $i !== 1; $i--) {
                if (substr($value, $i, 1) === '.' || substr($value, $i, 1) === ',') {
                    $has_sep_decimal = true;
                    $value           = trim(substr($value, 0, $i)) . '@' . trim(substr($value, 1 + $i));
                    break;
                }
            }
        }

        $length = strlen($value);
        for ($i = 1; $i !== ($length - 1); $i++) {
            if (substr($value, $i, 1) === '.' || substr($value, $i, 1) === ',' || substr($value, $i, 1) === ' ') {
                $value = trim(substr($value, 0, $i)) . trim(substr($value, 1 + $i));
                break;
            }
        }

        if (strlen(strstr($value, '@')) > 0) {
            $value = trim(substr($value, 0, strpos($value, '@'))) . trim($sep_decimal) . trim(substr($value, strpos($value, '@') + 1));
        }

        return $value;
    }

    /**
     * Generate a MB reference.
     *
     * @throws \Exception
     *
     * @since  1.0.0
     * @param  string $ent_id      Entity code.
     * @param  string $subent_id   Subentity code.
     * @param  string $order_id    Order/transaction ID.
     * @param  string $order_value Order/transaction value.
     * @return string              The MB reference.
     */
    public static function generateReference($ent_id, $subent_id, $order_id, $order_value)
    {
        if (strlen($ent_id) !== 5) {
            throw new \Exception('Lamentamos mas tem de indicar uma entidade válida.');
        }

        if (strlen($subent_id) === 0) {
            throw new \Exception('Lamentamos mas tem de indicar uma subentidade válida.');
        }

        $chk_val  = 0;
        $order_id = '0000' . $order_id;

        $order_value = sprintf('%01.2f', $order_value);
        $order_value = static::formatNumber($order_value);

        if ($order_value < 1) {
            throw new \Exception('Lamentamos mas é impossível gerar uma referência MB para valores inferiores a 1 Euro.');
        }

        if ($order_value >= 1000000) {
            error_log('Pagamento fraccionado por exceder o valor limite para pagamentos no sistema Multibanco.');
        }

        while ($order_value >= 1000000) {
            static::generateReference($order_id++, 999999.99);
            $order_value -= 999999.99;
        }

        // Only the 6 characters to the right of the order_id are considered.
        if (strlen($subent_id) === 1) {
            $order_id = substr($order_id, (strlen($order_id) - 6), strlen($order_id));
            $chk_str  = sprintf('%05u%01u%06u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
        } elseif (strlen($subent_id) === 2) {
            // Only the 5 characters to the right of the order_id are considered.
            $order_id = substr($order_id, (strlen($order_id) - 5), strlen($order_id));
            $chk_str = sprintf('%05u%02u%05u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
        } else {
            // Only the 4 characters to the right of the order_id are considered.
            $order_id = substr($order_id, (strlen($order_id) - 4), strlen($order_id));
            $chk_str = sprintf('%05u%03u%04u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
        }

        // Check digits calculation.
        $chk_array = array(3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51);
        for ($i = 0; $i < 20; $i++) {
            $chk_int = substr($chk_str, 19 - $i, 1);
            $chk_val += ($chk_int % 10) * $chk_array[ $i ];
        }

        $chk_val   %= 97;
        $chk_digits = sprintf('%02u', 98 - $chk_val);

        return substr($chk_str, 5, 3) . ' ' . substr($chk_str, 8, 3) . ' ' . substr($chk_str, 11, 1) . $chk_digits;
    }
}
