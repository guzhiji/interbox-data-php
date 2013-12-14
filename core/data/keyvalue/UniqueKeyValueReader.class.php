<?php

LoadIBC1Class('DataService', 'data');

/**
 * a reader for unique key-value pairs.
 * 
 * @version 0.1.20130309
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class UniqueKeyValueReader {

    /**
     * the data service
     * @var DataService 
     */
    private $_service;

    /**
     * contains all loaded key-value pairs
     * @var array 
     */
    private $_list;

    /**
     * a value the reader is bound to
     * @var mixed 
     */
    private $_bindingValue;

    /**
     * data type of the binding value, or NULL if the service does not 
     * support binding
     * @var int|null 
     */
    private $_bindingType;

    /**
     * if query data just after required (lazy loading)
     * @var bool 
     */
    private $_isLazy;

    /**
     * constructs the reader object.
     * 
     * @param string $service       name of the data service, which has to be of the type "keyvalue"
     * @param mixed $bindingvalue        ignored when the service does not support binding
     * @param bool $islazy      if enable lazy loading
     */
    function __construct($service, $bindingvalue = NULL, $islazy = FALSE) {
        $this->_service = DataService::GetService($service, 'keyvalue');
        $args = $this->_service->GetExtraArgs();
        $this->_bindingType = isset($args['binding_type']) ? $args['binding_type'] : NULL;
        $this->_bindingValue = $bindingvalue;
        $this->_isLazy = $islazy;

        // load data in advance if not lazy
        if (!$islazy) {
            $conn = $this->_service->GetDBConn();
            $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('kv'));
            $this->_bindValue($sql);
            $sql->Execute();
            while ($r = $sql->Fetch(1))
                $this->_list[$r->kvKey] = $r;
            $sql->CloseSTMT();
        }
    }

    /**
     * sets the binding value as a condition to the SQL statement object.
     * 
     * @param DBSQLSTMT $sql 
     */
    private function _bindValue($sql) {
        if ($this->_bindingType !== NULL)
            $sql->AddEqual('kvBindingValue', $this->_bindingValue, $this->_bindingType, IBC1_LOGICAL_AND);
    }

    /**
     * gets the data service object
     * 
     * @return DataService 
     */
    public function GetDataService() {
        return $this->_service;
    }

    /**
     * gets the value the reader is bound to.
     * 
     * @return mixed 
     */
    public function GetBindingValue() {
        return $this->_bindingValue;
    }

    /**
     * gets the data type of the binding value.
     * 
     * @return int|null     returns nulll when the service does not support binding 
     */
    public function GetBindingType() {
        return $this->_bindingType;
    }

    /**
     * gets all keys bound to the binding value or all keys in the data service if binding 
     * is not supported.
     * 
     * @return array 
     */
    public function GetKeys() {
        if (!$this->_isLazy)
            return array_keys($this->_list);

        $keys = array();
        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('kv'));
        $sql->AddField('kvKey');
        $this->_bindValue($sql);
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $keys[] = $r->kvKey;
        }
        $sql->CloseSTMT();
        return $keys;
    }

    /**
     * gets value to the give key.
     * 
     * @param string $key
     * @return mixed    null if the key is not found
     */
    public function GetValue($key) {
        $obj = $this->GetObject($key);
        if (empty($obj))
            return NULL;
        return $obj->kvValue;
    }

    /**
     * gets an object that contains all data for the given key.
     * 
     * @param string $key
     * @return object|null     null if the key is not found
     */
    public function GetObject($key) {
        if (!$this->_isLazy)
            return isset($this->_list[$key]) ? $this->_list[$key] : NULL;

        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('kv'));
        $this->_bindValue($sql);
        $sql->AddEqual('kvKey', $key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();

        return $r;
    }

    /**
     * tests if a key exists with key-value pairs bound to the the 
     * binding value or in the data service if binding is not supported.
     * 
     * @param string $key
     * @return bool 
     */
    public function KeyExists($key) {
        if (!$this->_isLazy)
            return isset($this->_list[$key]);

        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('kv'));
        $sql->AddField('1');
        $this->_bindValue($sql);
        $sql->AddEqual('kvKey', $key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        return !!$r;
    }

}
