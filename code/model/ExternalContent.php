<?php 


class ExternalContent extends DataObject {
	
	private static $singular_name = 'Content';
	private static $plural_name = 'Content items';
	
	private static $db = array(
		'ExternalID' => 'Varchar',
		'Content' => 'HTMLText',	
	);
	
	
	private static $has_one = array(
		'Type' => 'ExternalContentType',	
	);
	
	private static $many_many = array(
		'Pages'	=> 'ExternalContentPage',
	);
	
	
	/**
	 * Combine summary fields with field labels
	 * @var array
	 */
	private static $summary_fields = array(
		'ExternalID' => 'External ID',
		'ContentSummary' => 'Content',
		'Type.Name' => 'Content type',
	);
		
	/**
	 * Strip HTML from content summary
	 */
	public function ContentSummary(){
		return $this->obj('Content')->Summary(10);
	}
			
	public function canView($member = null) {
		// FIXME: proper permission check
		return true;
	}
	
}