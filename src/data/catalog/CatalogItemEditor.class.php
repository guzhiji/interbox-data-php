<?php

LoadIBC1Class('DataItem', 'datamodels');
LoadIBC1Lib('common', 'datamodels.catalog');

/**
 *
 * @version 0.7.20111212
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class CatalogItemEditor extends DataItem {

    protected $IsNew = TRUE;
    protected $ID = 0;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'catalog');
    }

    /**
     * prepares to create a new catalog
     * 
     * initializes the editor to receive data,
     *  but does not save data before Save() is invoked.
     */
    public function Create() {
        $this->IsNew = TRUE;
        $this->ID = 0;
    }

    /**
     * prepares to modify an existing catalog
     * 
     * initializes the editor to receive changes,
     *  but does not save changes before Save() is invoked.
     * @param int $ID catalog id
     */
    public function Open($ID) {
        $this->IsNew = FALSE;
        $this->ID = intval($ID);
    }

    /**
     * save the catalog into database
     * 
     * add if Create() invoked
     * <code>
     * LoadIBC1Class('CatalogItemEditor', 'datamodels.catalog');
     * $editor=new CatalogItemEditor('catalogtest');
     * $editor->Create();
     * $editor->SetName('catalog 2');
     * $editor->Save();
     * echo $editor->GetID();
     * $editor->CloseService();
     * </code>
     * 
     * update if Open() invoked
     * <code>
     * LoadIBC1Class('CatalogItemEditor', 'datamodels.catalog');
     * $editor=new CatalogItemEditor('catalogtest');
     * $editor->Open(1);
     * $editor->SetName('catalog 2');
     * try{
     * $editor->Save();
     * } catch (Exception $ex){
     *      echo $ex->getMessage();
     * }
     * $editor->CloseService();
     * </code>
     * @param int $ParentID unnecessary if Open() invoked
     */
    public function Save($ParentID = 0) {

        if ($this->IsNew) {//create a new catalog
            //check input parameter
            $ParentID = intval($ParentID);
            if ($ParentID < 0) {
                throw new ServiceException('parent catalog not found');
            }

            $conn = $this->GetDBConn();
            if ($ParentID > 0) {//if the catalog is not at the top level
                if (!catalogExists($ParentID, $this->GetDataTableName('catalog'), $conn)) {
                    throw new ServiceException('parent catalog not found');
                }
            }
            $this->SetValue('clgParentID', $ParentID);

            //check essential fields
            if ($this->GetValue('clgName') == NULL)
                throw new ServiceException('catalog name has not been set');

            //insert
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('catalog'));
            $sql->AddValues($this);
            $sql->Execute();
            $this->ID = $sql->GetLastInsertID();
            $sql->CloseSTMT();
            $this->IsNew = FALSE;
        } else if ($this->ID > 0) {//modify a catalog
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have not been changed');
            }

            //update
            $conn = $this->GetDBConn();
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('catalog'));
            $sql->AddValues($this);
            $sql->AddEqual('clgID', $this->ID);
            $sql->Execute();
            $sql->CloseSTMT();
        } else {
            throw new ServiceException('no catalog is open');
        }
    }

    /**
     * delete an empty catalog
     * 
     * @param int $id optional, delete the opened one if not given
     */
    public function Delete($id = 0) {
        $conn = $this->GetDBConn();
        if ($id == 0) {
            $id = $this->ID;
        }
        if (!catalogExists($id, $this->GetDataTableName('catalog'), $conn)) {
            throw new ServiceException('no access');
        }
        $nonempty = FALSE;
        //check child contents
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('content'));
        $sql->AddField('cntID');
        $sql->AddEqual('cntCatalogID', $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $nonempty = TRUE;
        } else {
            //check child catalogs
            $sql = $conn->CreateSelectSTMT($this->GetDataTableName('catalog'));
            $sql->AddField('clgID');
            $sql->AddEqual('clgParentID', $id);
            $sql->Execute();
            $r = $sql->Fetch(1);
            $sql->CloseSTMT();
            if ($r) {
                $nonempty = TRUE;
            }
        }
        if ($nonempty) {
            throw new ServiceException('only empty catalog can be deleted');
        } else {
            $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('catalog'));
            $sql->AddEqual('clgID', $id);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

    /**
     * get id if not new
     * @return int
     */
    public function GetID() {
        if ($this->IsNew) {
            throw new ServiceException('cannot get the id of an unsaved catalog');
        }
        return $this->ID;
    }

    /**
     * set catalog name (essential)
     * 
     * @param string $name pure text with no html
     */
    public function SetName($name) {

        $this->SetValue('clgName', $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set catalog ordinal for ordering
     * 
     * does not check for repetition nor continuity
     * @param int $n any integers
     */
    public function SetOrdinal($n) {

        $this->SetValue('clgOrdinal', $n);
    }

    /**
     * move the catalog to a new parent catalog
     * @param int $ParentID    parent catalog id
     */
    public function MoveTo($ParentID) {
        if ($this->IsNew) {
            throw new ServiceException('cannot move an unsaved catalog');
        }
        $ParentID = intval($ParentID);
        $conn = $this->GetDBConn();
        if (!catalogExists($ParentID, $this->GetDataTableName('catalog'), $conn)) {
            throw new ServiceException('the catalog does not exist');
        } else {
            //TODO go through sub catalogs to exclude loops
            $this->SetValue('clgParentID', $ParentID);
        }
    }

    /**
     * set cheif admin of the catalog
     * 
     * admin should be within the list of admin for the opened catalog
     * @param string $uid
     */
    public function SetAdminUID($uid) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('admin'));
        $sql->AddField('admUID');
        $sql->AddEqual('admUID', $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->AddEqual('admCatalogID', $this->ID, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException("the user $uid does not exist");
        }
        $this->SetValue('clgAdminUID', $r->admUID);
    }

    /*
     * 上层目录的访问/管理权是否限制下层？
     * 1.不限制：有权管理上层目录，但有时对下层没有权（只能删除，不可管理细节）
     * 2.限制：如果上层目录不完全公开，下层必须和上层的权限设置相同；
     *         如果上层目录完全公开，下层可以增添管理组或拥有者的设置
     * */

    public function SetAdminGroup($g) {
        $this->SetValue('clgAdminGID', $g);
    }

    public function SetVisitGroup($g) {
        $this->SetValue('clgVisitGID', $g);
    }

    public function AddAdmin($UID) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('admin'));
        $sql->AddField('admID');
        $sql->AddEqual('admCatalogID', $this->ID);
        $sql->AddEqual('admUID', $UID, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $row = $sql->Fetch(1);
        $e = FALSE;
        if ($row)
            $e = TRUE;
        $sql->CloseSTMT();
        if (!$e) {
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('admin'));
            $sql->AddValue('admCatalogID', $this->ID);
            $sql->AddValue('admUID', $UID, IBC1_DATATYPE_PLAINTEXT);
            $r = $sql->Execute();
            $sql->CloseSTMT();
            if ($r == FALSE)
                return FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function RemoveAdmin($UID) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('admin'));
        $sql->AddEqual('admUID', $UID, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddEqual('admCatalogID', $this->ID, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $r = $sql->Execute();
        $sql->CloseSTMT();
        if ($r == FALSE)
            return FALSE;
        return TRUE;
    }

    public function GetAdminList() {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('admin'));
        $sql->AddField('admUID');
        $sql->AddEqual('admCatalogID', $this->ID);
        $l = new ItemList();

        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $l->AddItem($r->admUID);
        }
        $sql->CloseSTMT();
        return $l;
    }

}
