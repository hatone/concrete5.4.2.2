<?php   defined('C5_EXECUTE') or die("Access Denied."); 

Loader::model('page_list'); 

/* next_previous - thanks Tony */

class NextPreviousBlockController extends BlockController { 

	protected $btTable = 'btNextPrevious';
	protected $btInterfaceWidth = "430";
	protected $btInterfaceHeight = "300"; 
	
	/** 
	 * Used for localization. If we want to localize the name/description we have to include this
	 */
	public function getBlockTypeDescription() {
		return t("Navigate through sibling pages.");
	}
	
	public function getBlockTypeName() {
		return t("Next & Previous Nav");
	}
	
	public function getJavaScriptStrings() {
		return array();
	} 
	
	public function save($args) { 
		$db = Loader::db(); 
		
		$args['showArrows'] = intval($args['showArrows']) ;
		$args['loopSequence'] = intval($args['loopSequence']); 
		$args['excludeSystemPages'] = intval($args['excludeSystemPages']); 
		
		
		parent::save($args);		
	} 
	
	function view(){
		
		$nextPage=$this->getNextCollection();
		$previousPage=$this->getPreviousCollection();
		$parentPage=Page::getByID(Page::getCurrentPage()->getCollectionParentID());
		
		if( $this->linkStyle=='page_name' ){
			$nextLinkText = (!$nextPage)?'':$nextPage->getCollectionName(); 
			$previousLinkText = (!$previousPage)?'':$previousPage->getCollectionName();
			$parentLinkText = (!$parentPage)?'':$parentPage->getCollectionName();
		}else{
			$nextLinkText = $this->nextLabel;
			$previousLinkText = $this->previousLabel;
			$parentLinkText = $this->parentLabel;
		}
		
		if($this->showArrows){
			$nextLinkText = $nextLinkText.' &raquo;';
			$previousLinkText = '&laquo; '.$previousLinkText;
		}
		
		$this->set( 'nextCollection', $nextPage );
		$this->set( 'previousCollection', $previousPage );
		$this->set( 'parentCollection', $parentPage );
		
		$this->set( 'nextLinkText', $nextLinkText );
		$this->set( 'previousLinkText', $previousLinkText );		
		$this->set( 'parentLinkText', $parentLinkText );		
	}
	
	function getNextCollection(){
		global $c; 
		if(!$this->otherCollectionsLoaded) $this->loadOtherCollections();
		foreach($this->otherCollections as $photoCollection){		
			if(!$firstCollection) $firstCollection=$photoCollection;
			if( $collectionFound ) return $photoCollection;
			if( $photoCollection->cID == $c->cID ) $collectionFound=1; 
		}
		if($this->loopSequence) return $firstCollection;
	}
	
	function getPreviousCollection(){
		global $c; 
		if(!$this->otherCollectionsLoaded) $this->loadOtherCollections();
		foreach($this->otherCollections as $photoCollection){
			if( $photoCollection->cID == $c->cID && $lastCollection ) return $lastCollection;
			$lastCollection=$photoCollection;
		}
		if($this->loopSequence) return $lastCollection;
	}
	
	protected function loadOtherCollections(){
		global $c; 
		$pl = new PageList();

        if ($this->orderBy == 'chrono_desc') {
            $pl->sortByPublicDateDescending();
        } else {
    		$pl->sortByDisplayOrder();
        }

		$pl->filterByParentID( $c->cParentID );  
		if($this->excludeSystemPages) $this->excludeSystemPages($pl);
		$this->otherCollections = $pl->get(); 
		$this->otherCollectionsLoaded=1;
	}
	
	protected function excludeSystemPages($pageList){
		$systemPages=array('login.php', 'register.php', 'download_file.php', 'profile/%', 'dashboard/%','page_forbidden%','page_not_found%'); 
		//$cIDs = Cache::get('next_previous_page_list_exclude_ids', false);
		if ($cIDs == false) {
			$db = Loader::db();
			$filters = ''; 
			for ($i = 0; $i < count($systemPages); $i++) {
				$spe = $systemPages[$i];
				$filters .= 'cFilename like \'/' . $spe . '\' ';
				if ($i + 1 < count($systemPages)) {
					$filters .= 'or ';
				}
			}
			$cIDs = $db->GetCol("select cID from Pages where 1=1 and ctID = 0 and (" . $filters . ")");
			if (count($cIDs) > 0) {
				Cache::set('next_previous_page_list_exclude_ids', false, $cIDs);
			}
		}
		$cIDStr = implode(',', $cIDs);
		//echo "(p1.cID not in ({$cIDStr}) or p2.cID not in ({$cIDStr}))";
		//die;
		$pageList->filter(false, "(p1.cID not in ({$cIDStr}) or p2.cID not in ({$cIDStr}))");	
	}
}

?>
