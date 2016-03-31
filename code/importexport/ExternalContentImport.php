<?php
class ExternalContentImport extends CsvBulkLoader {
	public function processRecord($record, $columnMap, &$results, $preview = false) {

		//if we have reached this point, assume that the row has been parsed already

		//application name
		$applicationName = isset($record['Application']) ? $record['Application'] : null;

		//area name
		$areaName = isset($record['Area']) ? $record['Area'] : null;

		//page name and URL
		$pageName = isset($record['PageName']) ? $record['PageName'] : null;
		$pageUrl = isset($record['PageUrl']) ? $record['PageUrl'] : null;

		//content ID and content
		$contentExternalID = isset($record['ContentID']) ? $record['ContentID'] : null;
		$contentContent = isset($record['Content']) ? $record['Content'] : null;

		//what type of content?
		$contentType = isset($record['Type']) ? $record['Type'] : null;


		//find or make an existing application. If it's new, set the name and write it
		$application = $this->findOrMake('ExternalContentApplication', $applicationName);
		if($application && !$application->ID) {
			$application->write();
		}

		//find or make an existing area. If it's new, set the name and application ID, then write it
		$area = $this->findOrMake('ExternalContentArea', $areaName);
		if($area && !$area->ID) {
			$area->ApplicationID = $application->ID;
			$area->write();
		}

		//find or make existing page. If it's new, set the name, URL, and area ID, then write it
		$contentPage = $this->findOrMake('ExternalContentPage', $pageName);
		if($contentPage && !$contentPage->ID) {
			$contentPage->URL = Convert::raw2sql($pageUrl);
			$contentPage->AreaID = $area->ID;
			$contentPage->write();
		}

		//find or make existing content type. If it's new, determine if it's plaintext or not, then write
		$type = $this->findOrMake('ExternalContentType', $contentType);
		if($type && !$type->ID) {
			$type->ContentIsPlaintext = true;
			if(preg_match('/rich text$/', strtolower($contentType))) {
				$type->ContentIsPlaintext = false;
			}
			$type->write();
		}

		//find or make existing content by ContentID. If it's new, set the content and type, then write
		$c = $this->findOrMake('ExternalContent', $contentExternalID, 'ExternalID');
		if($c) {
			if($c->ID) {
				$results->addUpdated($c, 'content record skipped');
			} else {
				$c->Content = $this->deWordify($contentContent);
				$c->TypeID = $type->ID;
				$c->write();
				$results->addCreated($c, 'content record created');
			}

			//add the page created above as a relation to this content
			$c->Pages()->add($contentPage);
		}

		return $c;		
	}
	
	public function getImportSpec(){
		// CSV format shown to the user, does not affect functionality
		return array(
			'fields' => array(
				'Application' => 'Application.Name',
				'Area'        => 'Area.Name',
				'PageName'    => 'Page.Name',
				'PageUrl'     => 'Page.URL',
				'ContentID'   => 'Content.ID',
				'Content'     => 'Content.Content',
				'Type'        => 'Type.Name',
			),
			'relations' => array(),
		);
	}

	/**
	 * Override BulkLoader::load with custom deleteExistingRecords functionality
	 * @param  $filepath same as @link BulkLoader::load
	 * @return same as @link BulkLoader::load
	 */
	public function load($filepath) {
		increase_time_limit_to(3600);
		increase_memory_limit_to('512M');

		// if replacing data, we need to individually delete objects from the bottom-up
		// this means deleting in a particular order:
		// 1. Content, 2. Page, 3. Area, 4.Application
		if($this->deleteExistingRecords) {
			ExternalContentType::get()->removeAll();
			ExternalContent::get()->removeAll();
			ExternalContentPage::get()->removeAll();
			ExternalContentArea::get()->removeAll();
			ExternalContentApplication::get()->removeAll();
		}

		return $this->processAll($filepath);
	}

	/**
	 * Create a new dataobject, or find one matching the specified key and name
	 * If the dataobject is new, it will set the $key to the given $name
	 * This function will not write to the database, it will just return existing objects,
	 * or newly created ones that haven't been written yet.
	 * @param  [string] $dataObject [description]
	 * @param  [string] $data       [description]
	 * @param  string $nameKey    [description]
	 * @return [DataObject]             [description]
	 */
	private function findOrMake($dataObject, $name, $key = 'Name') {
		if(!class_exists($dataObject)) return null;

		$do = $dataObject::create();

		if($dataObject && $key && $name) {
			$key = Convert::raw2sql($key);
			$name = Convert::raw2sql($name);
			$do = $dataObject::get()->find($key, $name);
			if(!($do && $do->ID)) {
				$do = $dataObject::create();
				$do->$key = $name;
			}
		}
		return $do;
	}

	/**
	 * Convert "smart" Microsoft Word characters to standard ASCII
	 * see http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
	 * @param   $content string that contains Word generated characters
	 * @return string
	 */
	private function deWordify($content) {
		$search = 
			array(chr(145), //‘ msword single quote
			chr(146), //’  msword single quote
			chr(147), //“  msword double quote
			chr(148), //”  msword double quote
			chr(151) // msword emdash
		); 

		$replace = array("'", 
			"'", 
			'"', 
			'"', 
			'-'
		); 

		return str_replace($search, $replace, $content);
	}


}