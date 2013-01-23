<?php

LoadIBC1Class('PropertyList', 'util');

/**
 *
 * @version 0.8.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class ContentItemEditor extends PropertyList {

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
     * to create a new content.
     * 
     * initializes the editor to receive data,
     *  but does not save data before Save() is invoked.
     */
    public function Create() {
        $this->ID = 0;
        $this->IsNew = TRUE;
    }

    /**
     * to modify an existing content.
     * 
     * initializes the editor to receive changes,
     *  but does not save changes before Save() is invoked.
     * 
     * @param int $ID content id
     */
    public function Open($ID) {
        $this->IsNew = FALSE;
        $this->ID = intval($ID);
    }

    /**
     * save the content into database.
     * 
     * add if Create() invoked:
     * <code>
     * LoadIBC1Class('ContentItemEditor', 'data.catalog');
     * $editor=new ContentItemEditor('catalogtest');
     * $editor->Create();
     * $editor->SetName('content 2');
     * try{
     *     $editor->Save(1);
     *     echo 'succeeded:'.$editor->GetID()."\n";
     * }catch(Exception $ex){
     *     echo 'failed:'.$ex->getMessage()."\n";
     * }
     * </code>
     * 
     * update if Open() invoked:
     * <code>
     * LoadIBC1Class('ContentItemEditor', 'data.catalog');
     * $editor=new ContentItemEditor('catalogtest');
     * $editor->Open(1);
     * $editor->SetName('content 1');
     * try{
     *     $editor->Save();
     *     echo "succeeded\n";
     * }catch(Exception $ex){
     *     echo 'failed:'.$ex->getMessage()."\n";
     * }
     * </code>
     * @param int $CatalogID unnecessary if Open() invoked
     */
    public function Save($CatalogID = 0) {

        if ($this->IsNew) {//create content
            //check input parameter
            if ($CatalogID <= 0) {
                throw new ServiceException('parent catalog not found');
            }
            if (!$this->dataservice->RecordExists('catalog', 'clgID', $CatalogID)) {
                throw new ServiceException('parent catalog not found');
            }
            $this->SetValue('cntCatalogID', $CatalogID, IBC1_DATATYPE_INTEGER);

            //check essential fields
            if ($this->GetValue('cntName') == NULL)
                throw new ServiceException('content name has not been set');
//            if ($this->GetValue("cntUID") == NULL)
//                throw new ServiceException("user has not been set");
            $this->SetValue('cntTimeCreated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            //insert
            $id = $this->dataservice->InsertRecord('content', $this);
            if ($id) {
                $this->ID = $id;
                $this->IsNew = FALSE;
            }
        } else if ($this->ID > 0) {//modify content
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have not been changed');
            }

            //update
            $this->SetValue('cntTimeUpdated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            $this->dataservice->UpdateRecord('content', 'cntID', $this->ID, $this);
        } else {
            throw new ServiceException('no content is open');
        }
    }

    /**
     * delete content.
     * 
     * @param int $id   optional, delete the opened one if not given
     */
    public function Delete($id = 0) {
        if ($id == 0)
            $id = $this->ID;
        $this->dataservice->DeleteRecord('content', 'cntID', $id);
    }

    /**
     * move the catalog to a new parent catalog.
     * 
     * @param int $CatalogID    parent catalog id
     */
    public function MoveTo($CatalogID) {
        if ($this->IsNew) {
            throw new ServiceException('cannot move an unsaved content');
        }
        if (!$this->dataservice->RecordExists('catalog', 'clgID', $CatalogID)) {
            throw new ServiceException('the catalog does not exist');
        } else {
            $this->SetValue('cntCatalogID', $CatalogID);
        }
    }

    /**
     * get id if not new.
     * 
     * @return int
     */
    public function GetID() {
        if ($this->IsNew) {
            throw new ServiceException('cannot get the id of an unsaved content');
        }
        return $this->ID;
    }

    /**
     * set content name (required).
     * 
     * @param string $name pure text with no html
     */
    public function SetName($name) {
        $this->SetValue('cntName', $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set content author.
     * 
     * @param string $author pure text with no html
     */
    public function SetAuthor($author) {
        $this->SetValue('cntAuthor', $author, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetKeywords($keywords) {
        $this->SetValue('cntKeywords', $keywords, IBC1_DATATYPE_WORDLIST);
    }

    public function SetWorth($n) {
        $this->SetValue('cntWorth', $n);
    }

    /**
     * set content ordinal for ordering.
     * 
     * does not check for repetition nor continuity.
     * @param int $n any integers
     */
    public function SetOrdinal($n) {
        $this->SetValue('cntOrdinal', $n);
    }

    public function SetUID($uid) {
        $this->SetValue('cntUID', $uid, IBC1_DATATYPE_PLAINTEXT);
    }

    public function AddVisitCount() {
        $this->SetValue('cntVisitCount', 'cntVisitCount+1', IBC1_DATATYPE_EXPRESSION);
    }

    public function ClearVisitCount() {
        $this->SetValue('cntVisitCount', 0);
    }

    public function SetModule($module) {
        $this->SetValue('cntModule', $module, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set minimal user levels for visitors and for admins
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
     * @return bool
     */
    public function SetLevels($visitlevel, $adminlevel) {
        $visitlevel = intval($visitlevel);
        $adminlevel = intval($adminlevel);
        if ($visitlevel < 0)
            $visitlevel = -1;
        if ($adminlevel < 0)
            $adminlevel = -1;
        if ($visitlevel <= $adminlevel && $adminlevel != 0) {
            $this->SetValue('cntVisitLevel', $visitlevel);
            $this->SetValue('cntAdminLevel', $adminlevel);
            return TRUE;
        }
        return FALSE;
    }

}
