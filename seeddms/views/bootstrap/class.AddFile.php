<?php
/**
 * Implementation of AddFile view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for AddFile view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AddFile extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$partitionsize = $this->params['partitionsize'];
		$maxuploadsize = $this->params['maxuploadsize'];
		$dropfolderdir = $this->params['dropfolderdir'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
		if($enablelargefileupload)
			$this->printFineUploaderJs($this->params['settings']->_httpRoot.'op/op.UploadChunks.php', $partitionsize, $maxuploadsize);

		if($dropfolderdir) {
			$this->printDropFolderChooserJs("addfileform");
		}
		$this->printFileChooserJs();
?>

$(document).ready( function() {
	/* The fineuploader validation is actually checking all fields that can contain
	 * a file to be uploaded. First checks if an alternative input field is set,
	 * second loops through the list of scheduled uploads, checking if at least one
	 * file will be submitted.
	 */
	jQuery.validator.addMethod("fineuploader", function(value, element, params) {
		uploader = params[0];
		arr = uploader.getUploads();
		for(var i in arr) {
			if(arr[i].status == 'submitted')
				return true;
		}
		return false;
	}, "<?php printMLText("js_no_file");?>");
	$("#addfileform").validate({
		debug: false,
		ignore: ":hidden:not(.do_validate)",
<?php
		if($enablelargefileupload) {
?>
		submitHandler: function(form) {
			/* fileuploader may not have any files if drop folder is used */
			if(userfileuploader.getUploads().length)
				userfileuploader.uploadStoredFiles();
			else
				form.submit();
		},
<?php
		}
?>
		rules: {
<?php
		if($enablelargefileupload) {
?>
			'userfile-fine-uploader-uuids': {
				fineuploader: [ userfileuploader, $('#dropfolderfileaddfileform') ]
			}
<?php
		} else {
?>
			'userfile[]': {
				require_from_group: [1, ".fileupload-group"],
				maxsize: <?= $maxuploadsize ?>
			},
			dropfolderfileaddfileform: {
				require_from_group: [1, ".fileupload-group"]
			}
<?php
		}
?>
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
			comment: "<?php printMLText("js_no_comment");?>",
			'userfile[]': "<?php printMLText("js_no_file");?>"
		},
		errorPlacement: function( error, element ) {
			if ( element.is( ":file" ) ) {
				error.appendTo( element.parent().parent().parent());
			} else {
				error.appendTo( element.parent());
			}
		}
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$strictformcheck = $this->params['strictformcheck'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$uploadedattachmentispublic = $this->params['uploadedattachmentispublic'];
		$maxuploadsize = $this->params['maxuploadsize'];
		$dropfolderdir = $this->params['dropfolderdir'];

		$this->htmlAddHeader('<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');
		if($enablelargefileupload) {
			$this->htmlAddHeader('<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/fine-uploader/jquery.fine-uploader.min.js"></script>'."\n", 'js');
			$this->htmlAddHeader($this->getFineUploaderTemplate(), 'js');
		}

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		$this->contentHeading(getMLText("linked_files"));
		$msg = getMLText("max_upload_size").": ".SeedDMS_Core_File::format_filesize($maxuploadsize);
		$this->warningMsg($msg);

?>

<form class="form-horizontal" action="../op/op.AddFile.php" enctype="multipart/form-data" method="post" name="addfileform" id="addfileform">
<input type="hidden" name="documentid" value="<?php print $document->getId(); ?>">
<?php
		$this->contentContainerStart();
		$this->formField(
			getMLText("local_file"),
			($enablelargefileupload ? $this->getFineUploaderHtml() : $this->getFileChooserHtml('userfile[]', false))
		);
		if($dropfolderdir) {
			$this->formField(
				getMLText("dropfolder_file"),
				$this->getDropFolderChooserHtml("addfileform")
			);
		}
		$options = array();
		$options[] = array("", getMLText('document'));
		$versions = $document->getContent();
		foreach($versions as $version) {
			$options[] = array($version->getVersion(), getMLText('version')." ".$version->getVersion());
		}
		$this->formField(
			getMLText("version"),
			array(
				'element'=>'select',
				'id'=>'version',
				'name'=>'version',
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
			)
		);
		$this->formField(
			getMLText("comment"),
			array(
				'element'=>'textarea',
				'id'=>'comment',
				'name'=>'comment',
				'rows'=>4,
				'cols'=>80,
				'required'=>$strictformcheck
			)
		);
		if ($document->getAccessMode($user) >= M_READWRITE) {
			$this->formField(
				getMLText("document_link_public"),
				array(
					'element'=>'input',
					'type'=>'checkbox',
					'id'=>'public',
					'name'=>'public',
					'value'=>'true',
					'checked'=>$uploadedattachmentispublic,
				)
			);
		}
		$this->contentContainerEnd();
		$this->formSubmit(getMLText('add'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();

	} /* }}} */
}
