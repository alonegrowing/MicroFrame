<?php
/**
 *
 * @author  wangg <alonegrowing@gmail.com>
 * @since   2018-11-29
 * @version v0.1.0
 */

namespace MicroFrame\Utils\Account;



class SourceTypeMd5 {

    public static function getSourceTypeMd5($source_type) {
        if(empty($source_type)){
            return '';
        }
        return isset(\Ap::$config["sourceType"][$source_type]) ?\Ap::$config["sourceType"][$source_type] : '';
    }
}