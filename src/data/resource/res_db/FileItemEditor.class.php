<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.database
 */
class FileItemEditor extends DataItem {

    private $id = 0;

    function __construct(DBConnProvider $Conns, $ServiceName) {
        parent::OpenService($Conns, $ServiceName, "res");
    }

    public function Open($id) {
        $this->id = $id;
    }

    public function GetID() {
        return $this->id;
    }

    public function SetName($name) {
        $this->SetValue("filName", $name, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SaveInfo() {

        if ($this->Count() == 0) {
            throw new Exception("no fields have not been set");
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddEqual("filID", $this->id);
        $this->MoveFirst();
        while (list($key, $item) = $this->GetEach()) {
            $sql->AddValue($key, $item[0], $item[1]);
        }

        $r = $sql->Execute();
        $sql->CloseSTMT();
        if (!$r) {
            return FALSE;
        }
        return TRUE;
    }

    public function Delete() {

        $conn = $this->GetDBConn();

        $sql = $conn->CreateDeleteSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddEqual("filID", $this->id);
        $r = $sql->Execute();
        $sql->CloseSTMT();
        if (!$r) {

            return FALSE;
        }
        return TRUE;
    }

}

?>