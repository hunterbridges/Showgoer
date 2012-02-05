<?php

class Performance extends ActiveRecord\Model {
  static $belongs_to = array(
    array('band'),
    array('bill')
  );
  function pretty_time() {
    $date = strtotime($this->start_date);
    return date('g:i A');
  }
}

