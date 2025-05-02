<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;

class Config {

  /** @var array<Page> $adminPages */
  private $adminPages = [];

  /** @var array<Notice> $adminNotices */
  private $adminNotices = [];

  /** @var array<Endpoint> $endpoints */
  private $endpoints = [];

  /** @var array<Script> $scripts */
  private $scripts = [];


  /** @return array<Page> */
  public function adminPages() {
    return $this->adminPages;
  }


  /** @return void */
  public function addAdminPage( Page $page ) {
    $this->adminPages[] = $page;
  }


  /** @return array<Notice> */
  public function adminNotices() {
    return $this->adminNotices;
  }


  /** @return void */
  public function addAdminNotice( Notice $notice ) {
    $this->adminNotices[] = $notice;
  }


  /** @return array<Endpoint> */
  public function endpoints() {
    return $this->endpoints;

  }


  /** @return static */
  public function addEndpoint( Endpoint $endpoint ) {
    $this->endpoints[] = $endpoint;
    return $this;
  }


  /**
   * @param array<Endpoint> $endpoints
   *
   * @return static
   */
  public function setEndpoints( $endpoints ) {
    $this->endpoints = $endpoints;
    return $this;
  }


  /** @return array<Script> */
  public function scripts() {
    return $this->scripts;
  }


  /** @return static */
  public function addScript( Script $script ) {
    $this->scripts[] = $script;
    return $this;
  }


}
