<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.vote
 */
class VoteItemEditor extends DataItem {

    private $id = 0;
    private $isnew = TRUE;

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "vot");
        $this->GetError()->SetSource(__CLASS__);
    }

    public function Create() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $this->id = 0;
        $this->isnew = TRUE;
    }

    public function Open($id) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddField("votID");
        $sql->AddEqual("votID", $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $this->id = $id;
            $this->isnew = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function GetID() {
        return $this->id;
    }

    public function SetCaption($caption) {
        $this->SetValue("Caption", $caption, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetTime($startTime, $endTime="") {
        if ($startTime != "")
            $this->SetValue("StartTime", $startTime, IBC1_DATATYPE_DATETIME);
        if ($endTime != "")
            $this->SetValue("EndTime", $endTime, IBC1_DATATYPE_DATETIME);
    }

    /**
     * to limit the number of choices at one vote
     * @param int $min
     * must be larger than or equals 1;<br />
     * @param int $max
     * when =-1 it means no limit;<br />
     *      =0  it means no change when modifying a vote;<br />
     *      >0  it must be larger than or equals min
     */
    public function SetNumber($min, $max=0) {
        if ($min >= 1)
            $this->SetValue("MinNumber", $min, IBC1_DATATYPE_INTEGER);
        if ($max == -1 || $max >= $min)
            $this->SetValue("MaxNumber", $max, IBC1_DATATYPE_INTEGER);
    }

    public function SetContentID($id) {
        $this->SetValue("ContentID", $id, IBC1_DATATYPE_INTEGER);
    }

    public function SetCatalogService($servicename) {
        LoadIBC1Class("DataServiceManager", "datamodels");
        $sm = new DataServiceManager($this->GetDBConnProvider(), $this->GetError());
        if ($sm->Exists($servicename, "clg"))
            $this->SetValue("CatalogService", $servicename, IBC1_DATATYPE_PLAINTEXT);
    }

    public function GetEntryList() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        LoadIBC1Class("EntryListReader", "vote");
        $elr = new EntryListReader($this->GetDBConnProvider(), $this->GetServiceName(), $this->GetError());
        $elr->OpenVote($this->id);
        return $elr;
    }

    public function Save() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $essential = 4;
        if ($this->isnew) {
            if ($this->Count() < $essential) {
                $this->GetError()->AddItem(1, "some fields have not been set");
                return FALSE;
            }
            $conn = $this->GetDBConn();
            $sql = $conn->CreateInsertSTMT("ibc1_vote" . $this->GetServiceName() . "_vote");
            $this->MoveFirst();
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }

            $r = $sql->Execute();
            if ($r == FALSE) {
                $sql->CloseSTMT();
                $this->GetError()->AddItem(2, "数据库操作出错");
                return FALSE;
            }
            $this->id = $sql->GetLastInsertID();
            $sql->CloseSTMT();
            return TRUE;
        } else {
            if ($this->Count() == 0) {
                $this->GetError()->AddItem(1, "no fields have been changed");
                return FALSE;
            }
            $conn = $this->GetDBConn();
            $sql = $conn->CreateUpdateSTMT();
            $sql->SetTable("ibc1_vot" . $this->GetServiceName() . "_vote");
            $this->MoveFirst();
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->AddEqual("votID", $this->id);
            $r = $sql->Execute();
            $sql->CloseSTMT();
            if ($r == FALSE) {
                $this->GetError()->AddItem(2, "数据库操作出错");
                return FALSE;
            }
            return TRUE;
        }
    }

    public function DeleteVote($id=-1) {
        if ($id == -1) {
            $id = $this->id;
            $this->id = 0;
            $this->Clear();
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT("ibc1_vot" . $this->GetServiceName() . "_entry");
        $sql->AddEqual("entVoteID", $id, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $sql->CloseSTMT();
        $sql = $conn->CreateDeleteSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddEqual("votID", $id, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $sql->CloseSTMT();
    }

}

?>
