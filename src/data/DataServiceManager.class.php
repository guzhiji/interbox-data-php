<?php

/**
 * 
 * @version 0.8.20130119
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data
 */
abstract class DataServiceManager extends DataService {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;

    function __construct($ServiceName, $ServiceType) {
        parent::__construct($ServiceName, $ServiceType);
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

    public abstract function IsInstalled();

    public abstract function Install();

    public abstract function Uninstall();

    public abstract function Optimize();
}

class ManagerException extends Exception {
    
}