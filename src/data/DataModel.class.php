<?php

/**
 * An abstract class for data models, providing basic methods for 
 * managing data services.
 * 
 * A data model is a type of data service, such as the catalog data model;
 * a data service is an instance of data model, such as a catalog service 
 * named 'catalogtest'.
 * To use a data service, a service name for the instance should be given 
 * so as to open the service and a database connection is estabilished for 
 * the service and the corresponding data tables dedicated to the service 
 * become accessible.
 * 
 * @version 0.9.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.dataservices
 */
abstract class DataModel {

    private $_connm = NULL;
    private $_servicename = '';
    private $_issrvopen = FALSE;

    protected function OpenService($ServiceName, $ServiceType) {

        //close before open
        if ($this->_connm != NULL)
            $this->CloseService();

        //find data service, throw exception if not found
        LoadIBC1Class('DBConnManager', 'datamodels');
        $this->_connm = new DBConnManager($ServiceName, $ServiceType);

        //if no exception thrown, proceeds
        $this->_servicename = $ServiceName;
        $this->_issrvopen = TRUE;
    }

    public function CloseService() {
        $this->_servicename = '';
        $this->_issrvopen = FALSE;
        $this->_connm = NULL;
    }

    private function _checkService() {
        if (
                $this->_servicename == '' ||
                !$this->_issrvopen ||
                $this->_connm == NULL
        )
            throw new Exception('service is not open');
    }

    public function IsServiceOpen() {
        return $this->_issrvopen;
    }

    public function GetServiceName() {
        $this->_checkService();
        return $this->_servicename;
    }

    protected function GetDataTableName($table) {
        $this->_checkService();
        return IBC1_PREFIX . '_' . $this->_servicename . '_' . $table;
    }

    public function GetDBConnProvider() {
        $this->_checkService();
        return $this->_connm->GetDBConnProvider();
    }

    public function GetDBConn() {
        $this->_checkService();
        return $this->_connm->GetDBConn();
    }

}

class ServiceException extends Exception {
    
}