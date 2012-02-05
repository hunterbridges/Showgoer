<?php

class Band extends ActiveRecord\Model {
  static $has_many = array(
    array('performances'),
    array('bills', 'through' => 'performances')
  );
}

