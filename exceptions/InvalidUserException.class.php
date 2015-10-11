<?php
namespace ngs\framework\exceptions {
	class InvalidUserException extends \Exception {

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function __construct($msg, $type = false) {
			if (NGS()->getEnvironment() == "production") {
				return;
			}
			if($msg != ""){
				$this->message = $msg;
			}
			$trace = $this->getTrace();
			header('Content-Type: application/json; charset=utf-8');
			header("HTTP/1.0 403 Forbidden");
			echo json_encode(array("type" => $type, "msg" => $this->getMessage(),  "_tmst_" => time()));exit;
		}

	}

}
?>