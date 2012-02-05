<?php

class Venue extends ActiveRecord\Model {
  static $has_many = array(
    array('bills')
  );
}

