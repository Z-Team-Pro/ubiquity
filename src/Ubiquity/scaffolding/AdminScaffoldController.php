<?php

namespace Ubiquity\scaffolding;

use Ajax\semantic\html\elements\HtmlButton;
use Ubiquity\controllers\Startup;
use Ubiquity\utils\http\USession;
use Ubiquity\controllers\admin\UbiquityMyAdminBaseController;

/**
 * Ubiquity\scaffolding$AdminScaffoldController
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *
 */
class AdminScaffoldController extends ScaffoldController {
	/**
	 *
	 * @var UbiquityMyAdminBaseController
	 */
	private $controller;
	private $jquery;

	public function __construct(UbiquityMyAdminBaseController $controller, $jquery) {
		$this->controller = $controller;
		$this->jquery = $jquery;
	}

	protected function getTemplateDir() {
		return Startup::getFrameworkDir () . "/admin/templates/";
	}

	protected function showSimpleMessage($content, $type, $title = null, $icon = "info", $timeout = NULL, $staticName = null) {
		return $this->controller->showSimpleMessage ( $content, $type, $title, $icon, $timeout, $staticName );
	}

	protected function _addMessageForRouteCreation($path, $jsCallback = "") {
		$msgContent = "<br>Created route : <b>" . $path . "</b>";
		$msgContent .= "<br>You need to re-init Router cache to apply this update:";
		$btReinitCache = new HtmlButton ( "bt-init-cache", "(Re-)Init router cache", "orange" );
		$btReinitCache->addIcon ( "refresh" );
		$msgContent .= "&nbsp;" . $btReinitCache;
		$this->jquery->getOnClick ( "#bt-init-cache", $this->controller->_getFiles ()->getAdminBaseRoute () . "/_refreshCacheControllers", "#messages", [ "attr" => "","hasLoader" => false,"dataType" => "html","jsCallback" => $jsCallback ] );
		return $msgContent;
	}

	protected function storeControllerNameInSession($controller) {
		USession::addOrRemoveValueFromArray ( "filtered-controllers", $controller, true );
	}

	public static function createClass($controller, $template, $classname, $namespace, $uses, $extendsOrImplements, $classContent) {
		$self = new AdminScaffoldController ( $controller );
		return $self->_createClass ( $template, $classname, $namespace, $uses, $extendsOrImplements, $classContent );
	}

	public static function createMethod($controller, $access, $name, $parameters, $return, $content, $comment) {
		$self = new AdminScaffoldController ( $controller );
		return $self->_createMethod ( $access, $name, $parameters, $return, $content, $comment );
	}
}

