<?php

LoadIBC1Class('DataServiceManager', 'datamodels');

/**
 *
 * @version 0.7.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.keyvalue
 */
class KeyValueServiceManager extends DataServiceManager {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'keyvalue');
    }

    /**
     * create a key-value data service.
     * 
     * @param array $Config
     * structure:
     * <code>
     * array(
     *      'binding_type'=>[data type code],//optional
     *      'binding_length'=>[length of the a binding value],//optional
     *      'value_type'=>[data type code],
     *      'value_length'=>[max length of value],
     *      'time_included'=>[TRUE/FALSE]//optional, FALSE by default
     * )
     * </code>
     * @throws ManagerException 
     */
    public function Install($Config) {

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", KeyValueServiceManager::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //config
        $BindingType = isset($Config['binding_type']) ? intval($Config['binding_type']) : NULL;
        $BindingLength = isset($Config['binding_length']) ? intval($Config['binding_length']) : NULL;
        $ValueType = intval($Config['value_type']);
        $ValueLength = intval($Config['value_length']);
        $TimeIncluded = isset($Config['time_included']) && $Config['time_included'];

        //create tables
        $sqlset = array();
        $i = 0;
        $sqlset[] = array(
            $conn->CreateTableSTMT('create', IBC1_PREFIX . '_' . $ServiceName . '_list'),
            IBC1_PREFIX . '_' . $ServiceName . '_list'
        );
        $sql = &$sqlset[$i][0];
        $sql->AddField('kvID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        if ($BindingType !== NULL && $BindingLength !== NULL)
            $sql->AddField('kvBindingValue', $BindingType, $BindingLength, TRUE, NULL, FALSE, 'k_binding');
        $sql->AddField('kvKey', IBC1_DATATYPE_PLAINTEXT, 256, FALSE, NULL, FALSE, 'k_key');
        $sql->AddField('kvValue', $ValueType, $ValueLength, TRUE);
        if ($TimeIncluded) {
            $sql->AddField('kvTimeCreated', IBC1_DATATYPE_DATETIME, 0, TRUE);
            $sql->AddField('kvTimeUpdated', IBC1_DATATYPE_DATETIME, 0, TRUE);
            $TimeIncluded = 1;
        } else {
            $TimeIncluded = 0;
        }

        $r = $this->CreateTables($sqlset, $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create a Key-Value service', KeyValueServiceManager::E_INSTALLED);
        }
    }

    public function IsInstalled() {
        $s = $this->GetServiceName();
        $conn = $this->GetDBConn();
        foreach ($this->_tables as $table) {
            if (!$conn->TableExists(IBC1_PREFIX . '_' . $s . '_' . $table))
                return FALSE;
        }
        return TRUE;
    }

    public function Optimize() {
        $ServiceName = $this->GetServiceName();
        if (!$this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' is not installed", UserServiceManager::E_NOT_INSTALLED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('optimize');
        foreach ($this->_tables as $table) {
            $sql->SetTable(IBC1_PREFIX . '_' . $ServiceName . '_' . $table);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

    public function Uninstall() {
        $s = $this->GetServiceName();
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('drop');
        foreach ($this->_tables as $table) {
            $sql->SetTable(IBC1_PREFIX . '_' . $s . '_' . $table);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

}
