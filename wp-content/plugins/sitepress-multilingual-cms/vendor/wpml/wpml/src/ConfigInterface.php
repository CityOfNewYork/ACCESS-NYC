<?php

namespace WPML;

interface ConfigInterface {


  /** @return void */
  public function loadRESTEndpoints();


  /** @return void */
  public function loadAjaxEndpoints();


  /** @return void */
  public function registerAdminPages();


  /** @return void */
  public function loadAdminNotices();


  /** @return void */
  public function loadAdminScripts();


  /** @return void */
  public function prepareUpdates();


  /** @return array<class-string,class-string> */
  public function getInterfaceMappings();


  /** @return array<class-string,array<string,string>> */
  public function getClassDefinitions();


}
