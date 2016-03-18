<?php 


class ExternalContentAdmin extends ModelAdmin implements PermissionProvider {
	
	private static $managed_models = array(
		'ExternalContent',
		'ExternalContentApplication',
		'ExternalContentArea',
		'ExternalContentPage',
		'ExternalContentType',
	);
	
	private static $url_segment = 'external-content';
	
	private static $menu_title = 'External Content';

	private static $model_importers = array(
		'ExternalContent' => 'ExternalContentImport',
	);
	
	public function getEditForm($id = null, $fields = null){
		// add ability to search
		
		$form = parent::getEditForm($id, $fields);
		$gridFieldName = $this->sanitiseClassName($this->modelClass);
		$gridField = $form->Fields()->fieldByName($gridFieldName);
		$gridField->getConfig()
			->addComponent(new GridFieldFilterHeader())
			->removeComponentsByType('GridFieldPrintButton');
		if($this->modelClass !== 'ExternalContent') {
			$gridField->getConfig()->removeComponentsByType('GridFieldExportButton');
		}

		return $form;
	}
	
	public function providePermissions() {
		return array(
			'VIEW_EXTERNAL_CONTENT_API' => 'Ability to view and use the external content API'
		);
	}	
}