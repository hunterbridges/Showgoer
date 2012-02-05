<?php

class webapp extends CI_Controller {
  function __construct__() {
    parent::__construct__();
  }

  function index() {
    $this->load->view('webapp');
  }
}

