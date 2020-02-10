<?php

namespace StatCollector;

class MockDatabase {
  public function insert($table, $args) {
    return;
  }

  public function query($q) {
    return;
  }
}
