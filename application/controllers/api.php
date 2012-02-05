<?php

date_default_timezone_set('America/Los_Angeles');
class api extends CI_Controller {
  function __construct() {
    parent::__construct();
    $this->load->spark('php-activerecord/0.0.1');
    $this->load->spark('curl/1.2.0');
    $this->load->database();
  }

  function _respond($data) {
    echo json_encode($data);
    exit;
  }

  function ping() {
    $this->_respond(array('response' => 'Pong!'));
  }

  function venues_near() {
    $lat = $this->input->post('lat', TRUE);
    $lon = $this->input->post('lon', TRUE);
    $token = $this->input->post('token', TRUE);
    $center = $lat . ',' . $lon;

    $url = 'https://graph.facebook.com/search?';
    $url .= 'type=place';
    $url .= '&center=' . $center;
    $url .= '&distance=500';
    $url .= '&limit=40';
    $url .= '&access_token=' . $token;

    $result = $this->curl->simple_get($url);
    $json = json_decode($result);
    $nearby = array();
    foreach ($json->data as $place) {
      $nearby[] = $place->id;
    }

    if (count($nearby)) {
      $result = array();
      $venues = Venue::find('all',
        array('conditions' => array('place_fbid in (?)', $nearby)));
      foreach ($venues as $venue) {
        $result[] = json_decode($venue->to_json());
      }

      $this->_respond($result);
    }
  }

  function check_in() {
    $lat = $this->input->post('lat', TRUE);
    $lon = $this->input->post('lon', TRUE);
    $venue_id = $this->input->post('venue_id', TRUE);
    $token = $this->input->post('token', TRUE);
    $fbuid = $this->input->post('fbuid', TRUE);

    $venue = Venue::find($venue_id);
    $format = 'Y-m-d H:i:s';
    $midnight = date($format, strtotime('midnight'));
    $midnight_tomorrow = date($format, strtotime('midnight tomorrow'));
    $today_bill = Bill::find('first',
      array('conditions' => array('venue_id = ? AND date >= ? AND date < ?',
      $venue_id, $midnight, $midnight_tomorrow)));
    if ($today_bill) {
      $already_here = Attendee::find('first',
        array('conditions' => array('bill_id = ? AND fbid = ?',
        $today_bill->id, $fbuid)));
      if ($already_here) {
        $already_here->token = $token;
        $already_here->left_show = 0;
        $already_here->save();

        $this->_respond(json_decode($already_here->to_json()));
      } else {
        $url = 'https://graph.facebook.com/me/checkins';
        $data = array(
          'coordinates' => '{"latitude": "'.$lat.'", "longitude": "'.$lon.'"}',
          'place' => $venue->place_fbid,
          'access_token' => $token
        );
        $result = $this->curl->simple_post($url, $data);
        $json = json_decode($result);
        if (isset($json->id)) {
          $attendee = new Attendee();
          $attendee->bill_id = $today_bill->id;
          $attendee->fbid = $fbuid;
          $attendee->token = $token;
          $attendee->checked_in = date($format);
          $attendee->save();
          $this->_respond(json_decode($attendee->to_json()));
        } else {
          $this->_respond(json_decode('fail'));
        }
      }
    } else {
      $this->_respond(array());
    }
  }

  function bill() {
    $bill_id = $this->input->get('bill_id', TRUE);

    $bill = Bill::find($bill_id);

    if ($bill) {
      $this->_respond(json_decode($bill->to_json(array(
        'include' => array('performances' => array('include' => 'band'),
                           'venue'),
        'methods' => array('audience_size')
      ))));
    } else {
      $this->_respond('fail');
    }
  }

  function leave_bill() {
    $bill_id = $this->input->post('bill_id', TRUE);
    $token = $this->input->post('token', TRUE);

    $attendee = Attendee::find('first', array('conditions' =>
      array('bill_id = ? AND token = ?', $bill_id, $token)
    ));

    if ($attendee) {
      $attendee->left_show = 1;
      $attendee->save();
      $this->_respond('ok');
    } else {
      $this->_respond('fail');
    }
  }

  function play() {
    if ($this->uri->segment(3)) {
      $performance = Performance::find($this->uri->segment(3));

      if ($performance) {
        $sql = "UPDATE performances SET now_playing = 0 WHERE bill_id = '" .
          $performance->bill_id ."'";
        $this->db->query($sql);

        $performance->now_playing = 1;
        $performance->save();
        echo $performance->band->name . " now playing.\n";
        $attendees = $performance->bill->attendees;
        foreach ($attendees as $attendee) {
          echo 'Posting to open graph for user '.$attendee->fbid."\n";
          $data = array();
          $data['access_token'] = $attendee->token;
          $data['band'] = $performance->band->sample_object;
          $url = 'https://graph.facebook.com/me/showgoer:see';
          $result = $this->curl->simple_post($url, $data);
          echo $result . "\n";
        }
      }
    }
  }

  function og() {
    $band_id = $this->uri->segment(3);
    $band = Band::find($band_id);
    $this->load->view('/og/band', array('band' => $band));
  }
}

