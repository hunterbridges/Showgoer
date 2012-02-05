<?php

class Attendee extends ActiveRecord\Model {
  static $belongs_to = array(
    array('bill')
  );
}

