<?php
class FinishType extends AppModel {
	var $name = 'FinishType';
	var $useTable = 'ReproAfwerkingSoort';
	var $primaryKey = 'code_afwerkingsoort';
	var $autoIncrement = false;
//	function afterFind($result){
//		pr($result);die();
//	}
}
?>