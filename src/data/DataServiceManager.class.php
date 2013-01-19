<?php

abstract class DataServiceManager {

    private $_connm;
    private $_servicename;
    private $_servicetype;

    function __construct($ServiceName, $ServiceType) {
        LoadIBC1Class('DBConnManager', 'datamodels');
        $this->_connm = new DBConnManager($ServiceName, $ServiceType);
        $this->_servicename = $ServiceName;
        $this->_servicetype = $ServiceType;
    }

    /**
     * It creates multiple tables in batch and ensure no failures happen,
     * otherwise drop all of them even if some of tables can be created successfully;
     * some errors may appear in the error list even though this function returns TRUE,
     * because there are some tables that exists before creation.
     * @param array $sqlset
     * Each element in the array contains a DBSQLSTMT object
     * to create a table and the corresponding table name for dropping it
     * if it fails to be created.
     * @param DBConn $conn
     * optional, a database connection;
     * if not provided, a new connection will be established
     * @return bool
     */
    protected function CreateTables(&$sqlset, DBConn &$conn = NULL) {
        if ($conn == NULL)
            $conn = $this->GetDBConn();
        if ($conn == NULL)
            return FALSE;
        $c = count($sqlset);
        for ($i = 0; $i < $c; $i++) {
            $sql = $sqlset[$i];
            if (!$conn->TableExists($sql[1])) {
                try {
                    $sql[0]->Execute();
                } catch (Exception $ex) {
                    for (; $i >= 0; $i--) {
                        try {
                            $stmt = $conn->CreateTableSTMT("drop", $sql[1]);
                            $stmt->Execute();
                            $stmt->CloseSTMT();
                        } catch (Exception $ex2) {
                            //do nothing
                        }
                    }
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * get the database connection provider
     * @return DBConnProvider 
     */
    public function GetDBConnProvider() {
        return $this->_connm->GetDBConnProvider();
    }

    /**
     * get a database connection (DBConn) object
     * @return DBConn
     */
    public function GetDBConn() {
        return $this->_connm->GetDBConn();
    }

    public function GetServiceName() {
        return $this->_servicename;
    }

    public function GetServiceType() {
        return $this->_servicetype;
    }

    public abstract function IsInstalled();

    public abstract function Install();

    public abstract function Uninstall();

    public abstract function Optimize();
}

class ManagerException extends Exception {
    
}