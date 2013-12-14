<?php

define('IBC1_DEFAULT_LANGUAGE', 'zh-cn');

define('IBC1_ENCODING', 'UTF-8');
define('IBC1_PREFIX', 'ibc1');

//define('IBC1_SYSTEM_ROOT', 'C:/Users/guzhiji/wamp/www/DigitalBox_3/src/'); //slash at the end
//define('IBC1_SYSTEM_ROOT', '/var/www/DigitalBox_3/src/'); //slash at the end
//$GLOBALS['IBC1_FRAMEWORK_CACHING'] = array('BoxCacheProvider', 'modules');

define('IBC1_TIME_ZONE', 'shanghai');
define('IBC1_TIME_P_TIME', 'H:i:s');
define('IBC1_TIME_P_DATE', 'Y-m-d');
define('IBC1_TIME_P_DATETIME', 'Y-m-d H:i:s');

$GLOBALS['IBC1_HTMLFILTER_CONFIG'] = array(
    array(
        'a' => array('href', 'target', 'title'),
        'img' => array('src', 'border', 'title', 'alt', 'width', 'height'),
        'table' => array('border', 'width', 'height'),
        'tr' => array(),
        'td' => array('width', 'height'),
        'th' => array('width', 'height'),
        'br' => array(),
        'p' => array(),
        'b' => array(),
        'strong' => array(),
        'i' => array(),
        'em' => array(),
        'font' => array('face', 'color', 'size'),
        'h1' => array(),
        'h2' => array(),
        'h3' => array(),
        'h4' => array(),
        'h5' => array(),
        'h6' => array()
    ),
    array(
        array(
            'src',
            'href'
        ),
        array(
            'http',
            'https',
            'ftp',
            'mailto'
        )
    )
);


$GLOBALS['IBC1_DATASERVICES'] = array(
    'catalogtest' => array(
        'type' => 'catalog',
        'host' => 'localhost:3306',
        'user' => 'root',
        'pwd' => '',
        'dbname' => 'digitalbox3_test',
        'driver' => 'mysqli'
    ),
    'usertest' => array(
        'type' => 'user',
        'host' => 'localhost:3306',
        'user' => 'root',
        'pwd' => '',
        'dbname' => 'digitalbox3_test',
        'driver' => 'mysqli',
        'extra' => array(
            'user_levels' => array(
                'level 1',
                'level 2',
                'level 3'
            ),
            'initial_user' => array(
                'uid' => 'webmaster',
                'pwd' => 'mypwd'
            )
        )
    ),
    'keyvaluetest' => array(
        'type' => 'keyvalue',
        'host' => 'localhost:3306',
        'user' => 'root',
        'pwd' => '',
        'dbname' => 'digitalbox3_test',
        'driver' => 'mysqli',
        'extra' => array(
            'binding_type' => 0, //optional,IBC1_DATATYPE_INTEGER
            'binding_length' => 10, //optional
            'value_type' => 2, //IBC1_DATATYPE_PLAINTEXT
            'value_length' => 255,
            'time_included' => FALSE//optional, FALSE by default
        )
    ),
    'articletest' => array(
        'type' => 'keyvalue',
        'host' => 'localhost:3306',
        'user' => 'root',
        'pwd' => '',
        'dbname' => 'digitalbox3_test',
        'driver' => 'mysqli',
        'extra' => array(
            'binding_type' => 0, //optional,IBC1_DATATYPE_INTEGER
            'binding_length' => 10, //optional
            'value_type' => 3, //IBC1_DATATYPE_RICHTEXT
            'value_length' => 0,
            'time_included' => FALSE//optional, FALSE by default
        )
    ),
    'commenttest' => array(
        'type' => 'keyvalue',
        'host' => 'localhost:3306',
        'user' => 'root',
        'pwd' => '',
        'dbname' => 'digitalbox3_test',
        'driver' => 'mysqli',
        'extra' => array(
            'binding_type' => 0, //optional,IBC1_DATATYPE_INTEGER
            'binding_length' => 10, //optional
            'value_type' => 2, //IBC1_DATATYPE_PLAINTEXT
            'value_length' => 1023,
            'time_included' => TRUE//optional, FALSE by default
        )
    )
);
