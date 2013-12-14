<?php

LoadIBC1Class('PropertyList', 'util');
LoadIBC1Class('DataService', 'data');

/**
 *
 * @version 0.8.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class CatalogItemEditor extends PropertyList {

    protected $IsNew = TRUE;
    protected $ID = 0;
    protected $dataservice;

    function __construct($service) {
        $this->dataservice = DataService::GetService($service, 'catalog');
    }

    public function GetDataService() {
        return $this->dataservice;
    }

    /**
     * to create a new catalog.
     * 
     * initializes the editor to receive data,
     *  but does not save data before Save() is invoked.
     */
    public function Create() {
        $this->IsNew = TRUE;
        $this->ID = 0;
    }

    /**
     * to modify an existing catalog.
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
     * save the catalog into database.
     * 
     * add if Create() invoked:
     * <code>
     * LoadIBC1Class('CatalogItemEditor', 'data.catalog');
     * $editor=new CatalogItemEditor('catalogtest');
     * $editor->Create();
     * $editor->SetName('catalog 2');
     * $editor->Save();
     * echo $editor->GetID();
     * </code>
     * 
     * update if Open() invoked:
     * <code>
     * LoadIBC1Class('CatalogItemEditor', 'data.catalog');
     * $editor=new CatalogItemEditor('catalogtest');
     * $editor->Open(1);
     * $editor->SetName('catalog 2');
     * try{
     *      $editor->Save();
     * } catch (Exception $ex){
     *      echo $ex->getMessage();
     * }
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

            if ($ParentID > 0) {//if the catalog is not at the top level
                if (!$this->dataservice->RecordExists('catalog', 'clgID', $ParentID)) {
                    throw new ServiceException('parent catalog not found');
                }
            }
            $this->SetValue('clgParentID', $ParentID);

            //check essential fields
            if ($this->GetValue('clgName') == NULL)
                throw new ServiceException('catalog name has not been set');

            //insert
            $this->ID = $this->dataservice->InsertRecord('catalog', $this);
            $this->IsNew = FALSE;
        } else if ($this->ID > 0) {//modify a catalog
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have not been changed');
            }

            //update
            $this->dataservice->UpdateRecord('catalog', 'clgID', $this->ID, $this);
        } else {
            throw new ServiceException('no catalog is open');
        }
    }

    /**
     * delete an empty catalog.
     * 
     * A non-empty catalog cannot be deleted.
     * 
     * @param int $id optional, delete the opened one if not given
     */
    public function Delete($id = 0) {
        if ($id == 0) {
            $id = $this->ID;
        }
        if (!$this->dataservice->RecordExists('catalog', 'clgID', $id)) {
            throw new ServiceException('no access');
        }
        $nonempty = FALSE;
        //check child contents
        if ($this->dataservice->RecordExists('content', 'cntCatalogID', $id)) {
            $nonempty = TRUE;
        } else {
            //check child catalogs
            if ($this->dataservice->RecordExists('catalog', 'clgParentID', $id)) {
                $nonempty = TRUE;
            }
        }
        if ($nonempty) {
            throw new ServiceException('only empty catalog can be deleted');
        } else {
            $this->dataservice->DeleteRecord('catalog', 'clgID', $id);
        }
    }

    /**
     * get id if not new.
     * 
     * @return int
     */
    public function GetID() {
        if ($this->IsNew) {
            throw new ServiceException('cannot get the id of an unsaved catalog');
        }
        return $this->ID;
    }

    /**
     * set catalog name (required).
     * 
     * @param string $name
     */
    public function SetName($name) {
        $this->SetValue('clgName', $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set catalog ordinal for ordering.
     * 
     * does not check for repetition nor continuity
     * @param int $n any integers
     */
    public function SetOrdinal($n) {
        $this->SetValue('clgOrdinal', $n);
    }

    /**
     * move the catalog to a new parent catalog.
     * 
     * @param int $ParentID    parent catalog id
     */
    public function MoveTo($ParentID) {
        if ($this->IsNew) {
            throw new ServiceException('cannot move an unsaved catalog');
        }
        $ParentID = intval($ParentID);
        if (!$this->dataservice->RecordExists('catalog', 'clgID', $ParentID)) {
            throw new ServiceException('the catalog does not exist');
        } else {
            //TODO go through sub catalogs to exclude loops
            $this->SetValue('clgParentID', $ParentID);
        }
    }

    /**
     * set owner of the catalog.
     * 
     * admin should be within the list of admin for the opened catalog
     * @param string $uid
     */
    public function SetUID($uid) {
        $this->SetValue('clgUID', $uid, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set minimal user levels for visitors and for admins.
     * 
     * @param int $visitlevel
     * <ul>
     * <li>level=0 - visible to everyone</li>
     * <li>level>0 - visible to users with or with higher levels</li>
     * <li>level=-1 - invisible to anyone except the author</li>
     * </ul>
     * @param int $adminlevel
     * <ul>
     * <li>level>0 - admin by users with or with higher levels</li>
     * <li>level=-1 - only the author can admin</li>
     * </ul>
     * @return boolean 
     */
    public function SetLevels($visitlevel, $adminlevel) {
        $visitlevel = intval($visitlevel);
        $adminlevel = intval($adminlevel);
        if ($visitlevel < 0)
            $visitlevel = -1;
        if ($adminlevel < 0)
            $adminlevel = -1;
        if ($visitlevel <= $adminlevel && $adminlevel != 0) {
            $this->SetValue('clgVisitLevel', $visitlevel);
            $this->SetValue('clgAdminLevel', $adminlevel);
            return TRUE;
        }
        return FALSE;
    }

}
