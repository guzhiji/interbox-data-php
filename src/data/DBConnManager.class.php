<?php

class DBConnManager {

    private $_dbconnp = NULL;
    private $_dbconn = NULL;
    private $_servicename;

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
    }

    public function GetDBConnProvider() {
        return $this->_dbconnp;
    }

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

}