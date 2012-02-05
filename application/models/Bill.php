<?php

class Bill extends ActiveRecord\Model {
  static $belongs_to = array(
    array('venue')
  );
  static $has_many = array(
    array('attendees', 'conditions' => array('left_show = 0')),
    array('performances')
  );
  function audience_size() {
    return count($this->attendees);
  }
}

