<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.filesystem
 */
class FileItemEditor extends DataItem {

    private $id = 0;

    //private $isnew=TRUE;

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "res");
        $this->GetError()->SetSource(__CLASS__);
    }

    public function Open($id) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $this->id = $id;
        //$this->isnew=FALSE;
    }

    /* public function CreateFileInfo()
      {
      $this->id=0;
      $this->isnew=TRUE;
      }
     */

    public function GetID() {
        return $this->id;
    }

    public function SetName($name) {
        $this->SetValue("filName", $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /*
      public function SetExtName($extname)
      {
      $this->SetValue("filExtName",$extname,IBC1_DATATYPE_PLAINTEXT);
      }
      public function SetTime($time)
      {
      $this->SetValue("filTime",$time,IBC1_DATATYPE_PLAINTEXT);
      }
      public function SetUser($uid)
      {
      $this->SetValue("filUID",$uid,IBC1_DATATYPE_PLAINTEXT);
      }
      public function SetDirectory($dir)
      {
      $this->SetValue("filDir",$dir,IBC1_DATATYPE_INTEGER);
      }
      public function SetSize($size)
      {
      $this->SetValue("filSize",$size,IBC1_DATATYPE_INTEGER);
      }
     */

    public function SaveInfo() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        /*
          $essential=5;
          if($this->isnew)
          {
          if($this->Count()<$essential)
          {
          $this->GetError()->AddItem(1,"some fields have not been set");
          return FALSE;
          }
          $sql=$conn->CreateInsertSTMT("ibc1_res".$this->GetServiceName()."_file");

          }
          else
         */
        //{

        if ($this->Count() == 0) {
            $this->GetError()->AddItem(1, "no fields have not been set");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddEqual("filID", $this->id);
        //}
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
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        //if($this->isnew) return FALSE;

        $conn = $this->GetDBConn();

        $sql = $conn->CreateSelectSTMT("ibc1_dataservices_resource");
        $sql->AddField("Root");
        $sql->AddEqual("ServiceName", $this->GetServiceName(), IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            return FALSE;
        }
        $root = $r->Root;

        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("filUID");
        $sql->AddField("filDir");
        $sql->AddField("filID");
        $sql->AddField("filExtName");
        $sql->AddEqual("filID", $this->id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $filename = str_replace("\\", "/", $root);
            if (substr($filename, -1) != "/")
                $filename.="/";
            $filename.=$r->filUID . "/" . $r->filDir . "/" . $r->filID;
            if ($r->filExtName != "")
                $filename.="." . $r->filExtName;
            @unlink($filename);
            $sql = $conn->CreateDeleteSTMT("ibc1_res" . $this->GetServiceName() . "_file");
            $sql->AddEqual("filID", $this->id);
            $r = $sql->Execute();
            $sql->CloseSTMT();
            if (!$r) {

                return FALSE;
            }
            return TRUE;
        }

        return FALSE;
    }

}

?>