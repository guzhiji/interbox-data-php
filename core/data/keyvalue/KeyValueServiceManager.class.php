<?php

LoadIBC1Class('DataServiceManager', 'data');

/**
 *
 * @version 0.8.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class KeyValueServiceManager extends DataServiceManager {

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'keyvalue');
    }

    /**
     * create a key-value data service.
     * 
     * @throws ManagerException 
     */
    public function Install() {

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", self::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //config
        $Config = $this->extra_args;
        $BindingType = isset($Config['binding_type']) ? intval($Config['binding_type']) : NULL;
        $BindingLength = isset($Config['binding_length']) ? intval($Config['binding_length']) : NULL;
        $ValueType = intval($Config['value_type']);
        $ValueLength = intval($Config['value_length']);
        $TimeIncluded = isset($Config['time_included']) && $Config['time_included'];

        //create tables
        $sqlset = array();
        // table 0: kv
        $i = 0;
        $sqlset[] = array(
            $conn->CreateTableSTMT('create'),
            $this->GetDataTableName('kv')
        );
        $sql = &$sqlset[$i][0];
        $sql->SetTable($sqlset[$i][1]);
        // primary key
        $sql->AddField('kvID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        // binding to another table
        if ($BindingType !== NULL && $BindingLength !== NULL)
            $sql->AddField('kvBindingValue', $BindingType, $BindingLength, TRUE, NULL, FALSE, 'k_binding');
        // the key & value
        $sql->AddField('kvKey', IBC1_DATATYPE_PLAINTEXT, 256, FALSE, NULL, FALSE, 'k_key');
        $sql->AddField('kvValue', $ValueType, $ValueLength, TRUE);
        // time
        if ($TimeIncluded) {
            $sql->AddField('kvTimeCreated', IBC1_DATATYPE_DATETIME, 0, TRUE);
            $sql->AddField('kvTimeUpdated', IBC1_DATATYPE_DATETIME, 0, TRUE);
            $TimeIncluded = 1;
        } else {
            $TimeIncluded = 0;
        }

        $r = $this->CreateTables($sqlset, $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create a Key-Value service', self::E_INSTALLED);
        }
    }

    public function IsInstalled() {
        $conn = $this->GetDBConn();
        return $conn->TableExists($this->GetDataTableName('kv'));
    }

    public function Optimize() {
        $ServiceName = $this->GetServiceName();
        if (!$this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' is not installed", self::E_NOT_INSTALLED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('optimize');
        $sql->SetTable($this->GetDataTableName('kv'));
        $sql->Execute();
        $sql->CloseSTMT();
    }

    public function Uninstall() {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('drop');
        $sql->SetTable($this->GetDataTableName('kv'));
        $sql->Execute();
        $sql->CloseSTMT();
    }

}
