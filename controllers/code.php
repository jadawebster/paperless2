<?php

class CodeHandler extends ToroHandler {

	private function display_error($error){
		$this->smarty->assign('errorMsg', $error);
		$this->smarty->display('error.html');
	}
	


	/*
	 * Displays the syntax highlighted code for a student, assignment pair
	 *
	 *
	 * Test URL: http://localhost:8888/paperless2/1122/cs107/code/assign1/econner
	 */
	public function get($qid, $class, $assignment, $student, $print=False) {
		$sunetid = Utilities::get_sunetid($student);
		
		if($sunetid != USERNAME){
			Permissions::TA_GATE($qid, $class, USERNAME);
		}
		$this->basic_setup();
		$dirname = Utilities::get_student_dir($qid, $class, $assignment, $student);
		
		// /cs107.1122/repos/assign2/jdoe/comments.json
		$comments = Utilities::get_comments($dirname);
		
		$config = Utilities::get_configuration($qid, $class);
		
		$preset_comments = array();
		foreach($config->assignments as $entry){
			if($entry->dir == $assignment){
				$this->smarty->assign("assignment", $entry);
				
				if(property_exists($entry, 'comments')){
					$preset_comments = $entry->comments;
					break;
				}
			}
		}
		$this->smarty->assign("preset_comments", $preset_comments);
		
		$interactive = Permissions::is_ta_for_class($qid, $class, USERNAME);
		if($interactive){
			$this->smarty->assign("interactive", 'true');
		}else{
			$this->smarty->assign("interactive", 'false');
		}

		$this->smarty->assign("student", $student);
		
		
		// probably change this decomp
		$all_files = Utilities::get_all_files($dirname);
		$code_files = Utilities::get_code_files($dirname, $all_files, $config);
		$this->smarty->assign("comments", $comments);
		$this->smarty->assign("code_files", $code_files);
		$this->smarty->display("code.html");
	}


	/*
	 * Handles adding and deleting comments.
	 */
	public function post_xhr($qid, $class, $assignment, $student) {
		Permissions::TA_GATE($qid, $class, USERNAME);
		
		// /cs107.1122/repos/assign2/jdoe/comments.json
		$dirname = Utilities::get_student_dir($qid, $class, $assignment, $student);
		$comments = Utilities::get_comments($dirname);
		
		$file = $_POST['filename'];
		$save_range = $_POST['rangeLower'] . "-" . $_POST['rangeHigher'];
		
		if($_POST['action'] == "save") {
			$comments->$file->$save_range->commenter = USERNAME;
			$comments->$file->$save_range->comment = $_POST['text'];
		} else if($_POST['action'] == "delete") {
			unset($comments->$file->$save_range);
		}
		
		Utilities::write_comments($dirname, $comments);
		
		echo json_encode(array("status" => "ok", "action" => $_POST['action']));
	}
}


?>