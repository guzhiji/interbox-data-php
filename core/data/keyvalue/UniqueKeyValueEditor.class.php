<?php

LoadIBC1Class('DataService', 'data');

/**
 * an editor for creating, updating and deleting unique key-value pairs.
 * 
 * @version 0.2.20130309
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class UniqueKeyValueEditor {

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
     * a list of keys to be removed
     * @var array 
     */
    private $_toRemove;

    /**
     * if RemoveAll() is invoked
     * @var bool 
     */
    private $_toRemoveAll;

    /**
     * a value the editor is bound to
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
     * data type of a value (of a key-value pair)
     * @var int 
     */
    private $_valueType;

    /**
     * if the service supports time fields
     * @var int 
     */
    private $_timeIncluded;

    /**
     * constructs the editor object.
     * 
     * @param string $service   name of the data service, which has to be of the type "keyvalue"
     * @param mixed $bindingvalue   ignored when the service does not support binding
     */
    function __construct($service, $bindingvalue = NULL) {
        $this->_service = DataService::GetService($service, 'keyvalue');
        $args = $this->_service->GetExtraArgs();
        $this->_valueType = $args['value_type'];
        $this->_bindingType = isset($args['binding_type']) ? $args['binding_type'] : NULL;
        $this->_bindingValue = $bindingvalue;
        $this->_timeIncluded = isset($args['time_included']) && $args['time_included'];
        $this->_toRemoveAll = FALSE;
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
     * gets the value the editor is bound to.
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
     * gets the data type of a value (of a key-value pair)
     * @return int 
     */
    public function GetValueType() {
        return $this->_valueType;
    }

    /**
     * if time is supported by the service.
     * 
     * @return bool 
     */
    public function IsTimeIncluded() {
        return $this->_timeIncluded;
    }

    /**
     * adds a key with its value, or updates the value of a key.
     * 
     * @param string $key
     * @param mixed $value 
     */
    public function SetValue($key, $value) {
        $this->_list[$key] = $value;
    }

    /**
     * removes a key.
     * 
     * @param string $key 
     */
    public function Remove($key) {
        $this->_toRemove[] = $key;
        unset($this->_list[$key]);
    }

    /**
     *  removes all keys and values.
     * 
     * keys belongs to:
     * - when binding supported, the binding value
     * - otherwise, the data service
     * This means keys not related to the binding value (if binding supported)
     * will not be affected when use this method.
     */
    public function RemoveAll() {
        $this->_toRemoveAll = TRUE;
        $this->_list = array();
    }

    /**
     * persists all changes.
     * 
     * The processing sequence is:
     * - remove all / remove listed key-value pairs
     * - update / insert
     * 
     * Therefore, it is obvious that updating / inserting an item first,
     * and removing it before one saving operation will receive a surprise.
     * However, in most cases, doing this is meaningless.
     * If you do, please save twice, each after the two types of operations.
     */
    public function Save() {

        $conn = $this->_service->GetDBConn();

        if ($this->_toRemoveAll) {
            // remove all
            $sql = $conn->CreateDeleteSTMT($this->_service->GetDataTableName('kv'));
            if ($this->_bindingType !== NULL)
                $sql->AddEqual('kvBindingValue', $this->_bindingValue, $this->_bindingType, IBC1_LOGICAL_AND);
            $sql->Execute();
            $sql->CloseSTMT();
        } else if (!empty($this->_toRemove)) {
            // remove listed items
            $sql = $conn->CreateDeleteSTMT($this->_service->GetDataTableName('kv'));
            foreach ($this->_toRemove as $key) {
                $sql->ClearConditions();
                if ($this->_bindingType !== NULL)
                    $sql->AddEqual('kvBindingValue', $this->_bindingValue, $this->_bindingType, IBC1_LOGICAL_AND);
                $sql->AddEqual('kvKey', $key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
                $sql->Execute();
            }
            $sql->CloseSTMT();
        }

        // save updated items
        foreach ($this->_list as $key => $value) {
            // assume the item exists, try to update
            $sql = $conn->CreateUpdateSTMT($this->_service->GetDataTableName('kv'));
            $sql->AddValue('kvValue', $value, $this->_valueType);
            if ($this->_timeIncluded)
                $sql->AddValue('kvTimeUpdated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            if ($this->_bindingType !== NULL)
                $sql->AddEqual('kvBindingValue', $this->_bindingValue, $this->_bindingType, IBC1_LOGICAL_AND);
            $sql->AddEqual('kvKey', $key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            $sql->Execute();
            $a = $sql->GetAffectedRowCount();
            $sql->CloseSTMT();

            // test if updated
            if (!$a) {
                // if not, insert the item instead
                $sql = $conn->CreateInsertSTMT($this->_service->GetDataTableName('kv'));
                if ($this->_bindingType !== NULL)
                    $sql->AddValue('kvBindingValue', $this->_bindingValue, $this->_bindingType);
                $sql->AddValue('kvKey', $key, IBC1_DATATYPE_PLAINTEXT);
                $sql->AddValue('kvValue', $value, $this->_valueType);
                if ($this->_timeIncluded)
                    $sql->AddValue('kvTimeCreated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
                $sql->Execute();
                $sql->CloseSTMT();
            }
        }
    }

}
