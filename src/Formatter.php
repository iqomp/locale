<?php

/**
 * Locale plugin for iqomp/formatter
 * @package iqomp/locale
 * @version 1.1.0
 */

namespace Iqomp\Locale;

class Formatter
{
    protected static function getPropValue(object $object, string $field)
    {
        $obj  = clone $object;
        $keys = explode('.', $field);

        foreach ($keys as $ky) {
            if (is_array($obj)) {
                $obj = $obj[$ky];
            } elseif (is_object($obj)) {
                $obj = $obj->$ky;
            }

            if (!is_array($obj) && !is_object($obj)) {
                return $obj;
            }
        }

        return $obj;
    }

    public static function locale($value, $fld, $object)
    {
        $data = json_decode($value, true);
        if (!$data) {
            return null;
        }

        $params = [];
        foreach ($data['map'] as $key => $value) {
            $val = $value;

            if (substr($val, 0, 1) === '$') {
                $val = self::getPropValue($object, substr($val, 1));
            }

            $params[$key] = $val;
        }

        return Locale::translate($data['text'], $params, $data['domain']);
    }
}
