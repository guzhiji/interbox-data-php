<?php

/**
 * provides basic information of a data service.
 * 
 * A data model is a type of data service, such as the catalog data model;
 * a data service is an instance of data model, such as a catalog service 
 * named 'catalogtest'.
 * To use a data service, a service name for the instance should be given 
 * so as to open the service and a database connection is estabilished for 
 * the service and the corresponding data tables dedicated to the service 
 * become accessible.
 * 
 * @version 0.10.20130119
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data
 */
class DataService {

    private $_dbconnp = NULL;
    private $_dbconn = NULL;
    private $_servicename;
    private $_servicetype;

    public static function GetService($service, $type) {
        if ($service instanceof DataService) {
            if ($service->GetServiceType() != $type)
                throw new ServiceException("The data service is not of type '$type'.");
            return $service;
        }else {
            return new DataService($service, $type);
        }
    }

    function __construct($ServiceName, $ServiceType) {

        if (
                !isset($GLOBALS['IBC1_DATASERVICES']) ||
                !isset($GLOBALS['IBC1_DATASERVICES'][$ServiceName]) ||
                !isset($GLOBALS['IBC1_DATASERVICES'][$ServiceName]['type']) ||
                $GLOBALS['IBC1_DATASERVICES'][$ServiceName]['type'] != $ServiceType
        )
            throw new Exception("cannot find the data service $ServiceName of $ServiceType model", 1);

        //prepare connection provider
        LoadIBC1Lib('common', 'sql');
        $this->_dbconnp = new DBConnProvider();
        $this->_servicename = $ServiceName;
        $this->_servicetype = $ServiceType;
    }

    /**
     * get the database connection provider
     * @return DBConnProvider 
     */
    public function GetDBConnProvider() {
        return $this->_dbconnp;
    }

    /**
     * get a database connection (DBConn) object
     * @return DBConn
     */
    public function GetDBConn() {

        //no database connector provider
        if (empty($this->_dbconnp))
            throw new Exception('no database connector provider');

        //connected
        if (!empty($this->_dbconn) && $this->_dbconn->IsConnected())
            return $this->_dbconn;

        //connect
        $s = $GLOBALS['IBC1_DATASERVICES'][$this->_servicename];
        $this->_dbconn = $this->_dbconnp->OpenConn(
                $s['host'], $s['user'], $s['pwd'], $s['dbname'], $s['driver']
        );
        return $this->_dbconn;
    }

    public function GetServiceName() {
        return $this->_servicename;
    }

    public function GetServiceType() {
        return $this->_servicetype;
    }

    public function GetDataTableName($table) {
        return IBC1_PREFIX . '_' . $this->_servicename . '_' . $table;
    }

    /**
     *
     * @param string $table
     * @param string $field
     * @param int $id
     * @return bool 
     */
    public function RecordExists($table, $field, $id) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName($table));
        $sql->AddField('1');
        $sql->AddEqual($field, $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        return !!$r;
    }

    /**
     *
     * @param string $table
     * @param string $pk
     * @param int $id
     * @param array $fields
     * @return object 
     */
    public function ReadRecord($table, $pk, $id, $fields = array()) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName($table));
        foreach ($fields as $f => $alias)
            $sql->AddField($f, $alias);
        $sql->AddEqual($pk, $id);
        $sql->Execute();
        $obj = $sql->Fetch(1);
        $sql->CloseSTMT();
        return $obj;
    }

    public function ListRecords($data, $paginator, $sql, $fields = array()) {
        $sql->ClearFields();
        foreach ($fields as $f => $alias)
            $sql->AddField($f, $alias);
        if ($paginator != NULL && $paginator->PageSize > 0)
            $sql->SetLimit($paginator->PageSize, $paginator->PageNumber);
        $sql->Execute();
        $data->Clear();
        while ($r = $sql->Fetch(1))
            $data->AddItem($r);
        $sql->CloseSTMT();
    }

    /**
     *
     * @param string $table
     * @param string $field
     * @param int $id
     * @return bool
     */
    public function DeleteRecord($table, $field, $id) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName($table));
        $sql->AddEqual($field, $id);
        $sql->Execute();
        $r = $sql->GetAffectedRowCount() > 0;
        $sql->CloseSTMT();
        return $r;
    }

    /**
     *
     * @param string $table
     * @param PropertyList $data
     * @return int      id of the inserted record
     */
    public function InsertRecord($table, $data) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateInsertSTMT($this->GetDataTableName($table));
        $sql->AddValues($data);
        $sql->Execute();
        $id = $sql->GetLastInsertID();
        $sql->CloseSTMT();
        return $id;
    }

    /**
     *
     * @param string $table
     * @param string $field
     * @param int $id
     * @param PropertyList $data
     * @return bool
     */
    public function UpdateRecord($table, $field, $id, $data) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT($this->GetDataTableName($table));
        $sql->AddValues($data);
        $sql->AddEqual($field, $id);
        $sql->Execute();
        $r = $sql->GetAffectedRowCount() > 0;
        $sql->CloseSTMT();
        return $r;
    }

}

class ServiceException extends Exception {
    
}