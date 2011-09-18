<?php

/**
 * Manage settings
 *
 * @package Simplecart
 * @subpackage AdminController
 * @author spekkionu
 */
class Admin_SettingsController extends SimpleCart_AdminController
{

  public function init() {
    parent::init();
    if (!$this->isAllowed('admin', 'general')) {
      // Save current url for later
      $session = new Zend_Session_Namespace();
      $session->login_destination = $this->view->url();
      return $this->routeRedirect('admin_login');
    }
  }

  public function indexAction() {

  }

}