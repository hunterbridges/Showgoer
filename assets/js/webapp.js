var Showgoer = {};

(function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    document.getElementById('fb-root').appendChild(e);

    Showgoer.db = {};
  }());

window.fbAsyncInit = function() {
  FB.init({ appId: '299474116730108',
      status: true,
      cookie: true,
      xfbml: true,
      oauth: true});

  FB.Event.subscribe('auth.statusChange', handleStatusChange);
};

function login() {
  FB.login(function(response) { },
    {scope:'publish_checkins, publish_actions'});
}

function handleStatusChange(response) {
  if (response.authResponse) {
    Showgoer.db.authResponse = response.authResponse;
    console.log(response);
    updateUserInfo(response);
    getLocation(response);
    //getVenueWithLocation(null);
  }
}

function updateUserInfo(response) {
  FB.api('/me&fields=likes,id,name', function(response) {
      document.getElementById('user-info').innerHTML =
        '<img src="https://graph.facebook.com/' + response.id + '/picture">' +
        '<div id="name">' + response.name + '</div>' +
        '<div class="clear"></div>';
    });
}

// Venue flow
function getLocation(response) {
  var output = '';

  navigator.geolocation.getCurrentPosition(
    function(location) {
      console.log(location.coords.latitude + ', ' +
          location.coords.longitude);
      getVenueWithLocation(location);
      },
      function(error) {
        $('#content').html('<div class="error">'+error.message+'</div>');
      }
    );
  }

  function getVenueWithLocation(location) {
    $.ajax({
        type: 'POST',
        url: '/api/venues_near',
        data: {
          lat: location.coords.latitude,
          lon: location.coords.longitude,
          // lat: 37.41944,
          // lon: -122.14832,
          token: Showgoer.db.authResponse.accessToken
        },
        dataType: 'json',
        success: function(data) {
          console.log(data);
          if (!location) {
            location = {
              coords: {
                latitude: 37.41944,
                longitude: -122.14832
              }
            }
          }
          showVenuesMenuWithVenues(data, location);
        },
        error: function(xhr, text, error) {
          console.log(error);
        }
      }
    );
  }

  function checkInToVenue(venue_id, lat, lon) {
    $('#content').html('Checking you in...');
    $.ajax({
        type: 'POST',
        url: '/api/check_in',
        data: {
          lat: lat,
          lon: lon,
          venue_id: venue_id,
          fbuid: Showgoer.db.authResponse.userID,
          token: Showgoer.db.authResponse.accessToken
        },
        dataType: 'json',
        success: function(data) {
          if (data.id) {
            getBillWithBillId(data.bill_id);
          } else {
            $('#content').html(
              '<div class="error">Sorry! Something went wrong.</div>');
          }
        },
        error: function(xhr, text, error) {
          console.log(error);
        }
      }
    );

  }

  function leaveBill(bill_id) {
    $('#content').html('Leaving...');
    $.ajax({
        type: 'POST',
        url: '/api/leave_bill',
        data: {
          bill_id: bill_id,
          token: Showgoer.db.authResponse.accessToken
        },
        dataType: 'json',
        success: function(data) {
          getLocation(null);
        },
        error: function(xhr, text, error) {
          console.log(error);
        }
      }
    );

  }
  function getBillWithBillId(bill_id) {
    $('#content').html('Getting show info...');
    $.ajax({
        type: 'GET',
        url: '/api/bill',
        data: {
          bill_id: bill_id
        },
        dataType: 'json',
        success: function(data) {
          console.log(data);
          if (data.id) {
            showBillInfoWithBill(data);
          } else {
            $('#content').html(
              '<div class="error">Sorry! Something went wrong.</div>');
          }
        },
        error: function(xhr, text, error) {
          console.log(error);
        }
      }
    );

  }
  // VIEW
  function showVenuesMenuWithVenues(venues, location) {
    output = '';
    if (venues.length) {
      output += '<div class="screen" id="select-venue">';
      output += '<h2>Select a Venue to Check In</h2>';
      output += '<ul>';
      for (venue_id in venues) {
        venue = venues[venue_id];
        output += '<li>';
        output +=
          '<a href="#" onclick="checkInToVenue(' + venue.id + ', ' +
          location.coords.latitude + ', ' + location.coords.longitude +')">';
        output += venue.name;
        output += '</a>';
        output += '</li>';
      }
      output += '</ul>';
      output += '</div>';
    } else {
      output += '<div class="error">';
      output += 'Sorry! You\'re not around any Showgoer venues!';
      output += '</div>';
    }
    $('#content').html(output);
  }

  function showBillInfoWithBill(bill) {
    output = '';
    output += '<div class="screen" id="bill">';
    output += '<h2>' + bill.venue.name + '</h2>';
    output += '<div class="audience">';
    output += bill.audience_size + ' ' +
      (bill.audience_size == 1?'person':'people') + ' here';
    output += '</div>';
    output += '<h3>Tonight\'s Show</h3>';
    output +=
      '<a href="#" id="refresh" onclick="getBillWithBillId(' +
      bill.id+')">Refresh</a>';
    output += '<ul>';
    for (perf_id in bill.performances) {
      performance = bill.performances[perf_id];
      if (performance.now_playing) {
        output += '<li class="nowplaying">';
      } else {
        output += '<li>';
      }
      output += '<img src="https://graph.facebook.com/' +
        performance.band.page_fbid + '/picture" />';
      output += '<a target="_blank" href="http://facebook.com/' +
        performance.band.page_fbid +
        '">' + performance.band.name + '</a>';
      output += '</li>';
    }
    output += '</ul>';
    output += '<a href="#" id="leave" onclick="leaveBill(' +
      bill.id + ')">I\'m Peacing Out</a>';
    output += '</div>';
    $('#content').html(output);
  }
