<?php

LoadIBC1Class('ItemList', 'util');
LoadIBC1Class('DataService', 'data');

/**
 *
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.user
 */
class LevelListReader extends ItemList {

    private $_service;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'user');
    }

    public function GetDataService() {
        return $this->_service;
    }

    public function LoadList() {

        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('level'));
        $sql->AddField('*');
        $sql->Execute();
        $this->Clear();
        while ($r = $sql->Fetch(1))
            $this->AddItem($r);
        $sql->CloseSTMT();
    }

}
