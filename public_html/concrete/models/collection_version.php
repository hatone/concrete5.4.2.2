<?php 
	defined('C5_EXECUTE') or die("Access Denied.");
/**
 * Contains the collection version object.
 * @package Pages
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * An object that maps to versions of collections. Each page in concrete is a _collection_ of blocks, each of which has different versions (for version control.)
 * @package Pages
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
	class CollectionVersion extends Object {
	
		var $cvIsApproved;
		var $cID;
		protected $attributes = array();
		public $customAreaStyles = array();
		public $layoutStyles = array();
		
		/** 
		 * Returns the actual cvID numerical value for a particular cID/cvID combo
		 */
		public static function getNumericalVersionID($cID, $cvID) {
			if ($cvID == 'RECENT' || $cvID == 'ACTIVE') {
				$ca = new Cache();
				$cv = $ca->get('collection_version_id', $cID . ':' . $cvID);
				if ($cv != false) {
					return $cv;
				}
				
				// first, we make sure that the cID is for an actual page. If we're pointing to another page (an alias)
				// we need to use THAT cID
				$db = Loader::db();
				$_cID = $cID;
				$cPointerID = $db->GetOne("select cPointerID from Pages where cID = ?", array($cID));
				if ($cPointerID > 0) {
					$_cID = $cPointerID;
				}
				
				$v = array($_cID);
				switch($cvID) {
					case 'RECENT':
						$cvIDa = $db->GetOne("select cvID from CollectionVersions where cID = ? order by cvID desc", $v);
						break;
					case 'ACTIVE':
						$cvIDa = $db->GetOne("select cvID from CollectionVersions where cID = ? and cvIsApproved = 1", $v);
						break;
				}
				if ($cvIDa != false) {
					$ca->set('collection_version_id', $cID . ':' . $cvID, $cvIDa);
				}
				return $cvIDa;
			} else {
				// cvID IS numerical
				return $cvID;
			}
		}
		
		public function refreshCache() {
			$db = Loader::db();
			$cID = $this->cID;
			$cvIDs = $db->GetCol("select cvID from CollectionVersions where cID = ?", $this->cID);
			foreach($cvIDs as $cvID) {
				Cache::delete('page', $cID . ':' . $cvID);
				Cache::delete('collection_version', $cID . ':' . $cvID);
				Cache::delete('collection_blocks', $cID . ':' . $cvID);
				Cache::delete('collection_version_id', $cID . ':' . $cvID);
				Cache::delete('collection_version_id', $cID . ':RECENT');
				Cache::delete('collection_version_id', $cID . ':ACTIVE');
			}
		}
		
		public function get(&$c, $cvID) {
			if (is_string($cvID)) {
				$cvID = CollectionVersion::getNumericalVersionID($c->getCollectionID(), $cvID);
			}
			
			$ca = new Cache();
			$cv = $ca->get('collection_version', $c->getCollectionID() . ':' . $cvID);
			if ($cv instanceof CollectionVersion) {
				return $cv;
			}
			
			$cv = new CollectionVersion();
			$db = Loader::db();
			
			$q = "select cvID, cvIsApproved, cvIsNew, cvHandle, cvName, cvDescription, cvDateCreated, cvDatePublic, cvAuthorUID, cvApproverUID, cvComments from CollectionVersions where cID = ? and cvID = ?";

			$r = $db->query($q, array($c->getCollectionID(), $cvID));
			if ($r) {
				$row = $r->fetchRow();					
				if ($row) {
					$cv->setPropertiesFromArray($row);
				}
			}
			
			// load the attributes for a particular version object
			Loader::model('attribute/categories/collection');			
			$cv->attributes = CollectionAttributeKey::getAttributes($c->getCollectionID(), $cvID);
			
			$cv->cID = $c->getCollectionID();			
			$cv->cvIsMostRecent = $cv->_checkRecent();
			
			$r = $db->GetAll('select csrID, arHandle from CollectionVersionAreaStyles where cID = ? and cvID = ?', array($c->getCollectionID(), $cvID));
			foreach($r as $styles) {
				$cv->customAreaStyles[$styles['arHandle']] = $styles['csrID'];
			}
			
			$ca = new Cache();
			$ca->set('collection_version', $c->getCollectionID() . ':' . $cvID, $cv);
			
			return $cv;
		}

		public function getAttribute($ak) {
			if (is_object($this->attributes)) {
				return $this->attributes->getAttribute($ak);
			}
		}

		function isApproved() {return $this->cvIsApproved;}
		function isMostRecent() {return $this->cvIsMostRecent;}
		function isNew() {return $this->cvIsNew;}
		function getVersionID() {return $this->cvID;}
		function getVersionName() {return $this->cvName;}	
		function getVersionComments() {return $this->cvComments;}
		function getVersionAuthorUserID() {return $this->cvAuthorUID;}
		function getVersionApproverUserID() {return $this->cvApproverUID;}
		function getVersionAuthorUserName() {
			if ($this->cvAuthorUID > 0) {
				$db = Loader::db();
				return $db->GetOne('select uName from Users where uID = ?', array($this->cvAuthorUID));
			}
		}
		function getVersionApproverUserName() {
			if ($this->cvApproverUID > 0) {
				$db = Loader::db();
				return $db->GetOne('select uName from Users where uID = ?', array($this->cvApproverUID));
			}
		}
		
		/**
		 * Gets the date the collection version was created 
		 * if user is specified, returns in the current user's timezone
		 * @param string $type (system || user)
		 * @return string date formated like: 2009-01-01 00:00:00 
		*/
		function getVersionDateCreated($type = 'system') {
			if(ENABLE_USER_TIMEZONES && $type == 'user') {
				$dh = Loader::helper('date');
				return $dh->getLocalDateTime($this->cvDateCreated);
			} else {
				return $this->cvDateCreated;
			}
		}
		
		function canWrite() {return $this->cvCanWrite;}
		
		function setComment($comment) {
			$thisCVID = $this->getVersionID();
			$comment = ($comment != null) ? $comment : "Version {$thisCVID}";
			$v = array($comment, $thisCVID, $this->cID);
			$db = Loader::db();
			$q = "update CollectionVersions set cvComments = ? where cvID = ? and cID = ?";
			$r = $db->query($q, $v);
			
			$this->versionComments = $comment;
		}
		
		function createNew($versionComments) {
			$db = Loader::db();
			$newVID = $this->getVersionID() + 1;
			$c = Page::getByID($this->cID, $this->cvID);

			$u = new User();
			$versionComments = (!$versionComments) ? t("New Version %s", $newVID) : $versionComments;
			
			$dh = Loader::helper('date');
			$v = array($this->cID, $newVID, $c->getCollectionName(), $c->getCollectionHandle(), $c->getCollectionDescription(), $c->getCollectionDatePublic(), $dh->getSystemDateTime(), $versionComments, $u->getUserID(), 1);
			$q = "insert into CollectionVersions (cID, cvID, cvName, cvHandle, cvDescription, cvDatePublic, cvDateCreated, cvComments, cvAuthorUID, cvIsNew)
				values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				
			$q2 = "select akID, avID from CollectionAttributeValues where cID = ? and cvID = ?";
			$v2 = array($c->getCollectionID(), $this->getVersionID());
			$r2 = $db->query($q2, $v2);
			while ($row2 = $r2->fetchRow()) {
				$v3 = array(intval($c->getCollectionID()), $newVID, $row2['akID'], $row2['avID']);
				$recordExists = intval($db->getOne('SELECT count(*) FROM CollectionAttributeValues WHERE cID=? AND cvID=? AND akID=? AND avID=?',$v3))?1:0;
				if(!$recordExists) $db->query("insert into CollectionAttributeValues (cID, cvID, akID, avID) values (?, ?, ?, ?)", $v3); 
			}
			
			$r = $db->prepare($q);
			$res = $db->execute($r, $v);
			
			$nv = CollectionVersion::get($c, $newVID);
			Events::fire('on_page_version_add', $c, $nv);
			$nv->refreshCache();
			// now we return it
			return $nv;
		}
		
		function _checkRecent() {
			// basically checks to see if this version is the most recent version. You're not allowed to edit
			// versions that are not the most recent.
			
			$cID = $this->cID;
			
			$db = Loader::db();
			$q = "select cvID from CollectionVersions where cID = '{$cID}' order by cvID desc";
			$cvID = $db->getOne($q);
			return ($cvID == $this->cvID);
		}
		
		function approve() {
			$db = Loader::db();
			$u = new User();
			$uID = $u->getUserID();
			$cvID = $this->cvID;
			$cID = $this->cID;
			$c = Page::getByID($cID, $this->cvID);
			
			$ov = Page::getByID($cID, 'ACTIVE');
			
			$oldHandle = $ov->getCollectionHandle();
			$newHandle = $this->cvHandle;

			// update a collection updated record
			$dh = Loader::helper('date');
			$db->query('update Collections set cDateModified = ? where cID = ?', array($dh->getLocalDateTime(), $cID));

			// first we remove approval for the other version of this collection
			$v = array($cID);
			$q = "update CollectionVersions set cvIsApproved = 0 where cID = ?";
			$r = $db->query($q, $v);
			$ov->refreshCache();
			
			// now we approve our version
			$v2 = array($uID, $cID, $cvID);
			$q2 = "update CollectionVersions set cvIsNew = 0, cvIsApproved = 1, cvApproverUID = ? where cID = ? and cvID = ?";
			$r = $db->query($q2, $v2);
			
			// next, we rescan our collection paths for the particular collection, but only if this isn't a generated collection
			if ((($oldHandle != $newHandle) || $oldHandle == '') && (!$c->isGeneratedCollection())) {
				$c->rescanCollectionPath();
			}
			Events::fire('on_page_version_approve', $c);
			$c->reindex();
			$this->refreshCache();
		}
		
		public function discard() {
			// discard's my most recent edit that is pending
			$u = new User();
			if ($this->isNew()) {
				$this->delete();
			}
			$this->refreshCache();

		}
		
		public function removeNewStatus() {
			$db = Loader::db();
			$db->query("update CollectionVersions set cvIsNew = 0 where cID = ? and cvID = ?", array($this->cID, $this->cvID));
			$this->refreshCache();
		}
		
		function deny() {
			$db = Loader::db();
			$cvID = $this->cvID;
			$cID = $this->cID;
			
			// first we update a collection updated record
			$dh = Loader::helper('date');
			$db->query('update Collections set cDateModified = ? where cID = ?', array($dh->getLocalDateTime(), $cID));
			
			// first we remove approval for all versions of this collection
			$v = array($cID);
			$q = "update CollectionVersions set cvIsApproved = 0 where cID = ?";
			$r = $db->query($q, $v);
			
			// now we deny our version
			$v2 = array($cID, $cvID);
			$q2 = "update CollectionVersions set cvIsApproved = 0, cvApproverUID = 0 where cID = ? and cvID = ?";
			$r2 = $db->query($q2, $v2);
			$this->refreshCache();
		}
		
		function delete() {
			$db = Loader::db();
			
			$cvID = $this->cvID;
			$c = Page::getByID($this->cID, $cvID);
			$cID = $c->getCollectionID();
			
			$q = "select bID, arHandle from CollectionVersionBlocks where cID = '{$cID}' and cvID='{$cvID}'";
			$r = $db->query($q);
			if ($r) {
				while ($row = $r->fetchRow()) {
					if ($row['bID']) {
						$b = Block::getByID($row['bID'], $c, $row['arHandle']);
						if (is_object($b)) {
							$b->deleteBlock();
						}
					}
					unset($b);
				}
			}
			
			$r = $db->Execute('select avID, akID from CollectionAttributeValues where cID = ? and cvID = ?', array($cID, $cvID));
			Loader::model('attribute/categories/collection');			
			while ($row = $r->FetchRow()) {
				$cak = CollectionAttributeKey::getByID($row['akID']);
				$cav = $c->getAttributeValueObject($cak);
				if (is_object($cav)) {
					$cav->delete();
				}
			}
			
			$db->Execute('delete from CollectionVersionBlockStyles where cID = ? and cvID = ?', array($cID, $cvID));
			$db->Execute('delete from CollectionVersionAreaStyles where cID = ? and cvID = ?', array($cID, $cvID));
			$db->Execute('delete from CollectionVersionAreaLayouts where cID = ? and cvID = ?', array($cID, $cvID));
			
			$q = "delete from CollectionVersions where cID = '{$cID}' and cvID='{$cvID}'";
			$r = $db->query($q);
			$this->refreshCache();

		}
	}

/**
 * An object that holds a list of versions for a particular collection.
 * @package Pages
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
	class VersionList extends Object {
	
		var $vArray = array();
		
		function VersionList(&$c, $limit = -1, $page = false) {
			$db = Loader::db();
			
			$cID = $c->getCollectionID();
			$this->total = $db->GetOne('select count(cvID) from CollectionVersions where cID = ?', $cID);
			$q = "select cvID from CollectionVersions where cID = '$cID' order by cvID desc ";
			if ($page > 1) {
				$pl = ($page-1) * $limit;
			}			
			if ($page > 1) {
				$q .= "limit " . $pl . ',' . $limit;
			} else if ($limit > -1) {
				$q .= "limit " . $limit;
			}
			$r = $db->query($q);
	
			if ($r) {
				while ($row = $r->fetchRow()) {
					$this->vArray[] = CollectionVersion::get($c, $row['cvID'], true);
				}
				$r->free();
			}
					
			return $this;
		}
		
		function getVersionListArray() {
			return $this->vArray;
		}
		
		function getVersionListCount() {
			return $this->total;
		}
	
	}
	
?>