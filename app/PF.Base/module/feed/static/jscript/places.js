/* Implements Google Places into the Feed to achieve a Check-In, it also checks for existing Pages first */

//New Place JS
$Core.Feed = $Core.FeedPlace =
  {
    /*For some reason a user may not want to fetch places from Google (only from Pages) or in case the admin has not set the Google API Key which is required */
    bUseGoogle: true,

    bGoogleReady: false,

    /* We store the maps and other information related to maps that only show when hovering over their locations in the feed entries */
    aHoverPlaces: {},

    /* Here we store the places gotten from Google and Pages. This array is reset as the user moves away from the found place */
    aPlaces: [],
    aPlacesKey: [],

    /* The id of the div that will display the map of the current location */
    sMapId: 'js_feed_check_in_map',

    /* This is the button that will trigger loading the autocomplete and the map*/
    sButtonId: 'btn_display_check_in',

    /* Google requires the key to be passed so we store it here*/
    sGoogleKey: '',

    /* Google's Geocoder object */
    gGeoCoder: {},

    /* Google's marker in the map */
    gMarker: {},

    gSearch: {},

    gAutocomplete: undefined,

    /* If the browser does not support Navigator we can get the latitude and longitude using the IPInfoDBKey */
    sIPInfoDbKey: '',

    /* Google object holding my location*/
    gMyLatLng: undefined,

    /* Avoid collisions */
    sLastSearch: '',

    /* This is the google map object, we can control the map from this variable */
    gMap: {},

    /* The current status can be:
     * 0 => Uninitialized
     * 1 => Waiting for location from IPInfoDb
     * 2 => Ready to query Google and server
     */
    iStatus: 0,
    /*
      The current initial id
     */
    sCurrentIndex: '',
    /* Prepare our location. If we have the location of the user in the database this function is called After gMyLatLng has been defined. */
    init: function (bForce) {

      // create feed map
      this.generateFeedMap();
      var _this = $Core.FeedPlace;
      //No more init
      if (!bCheckinInit) {
        var map = $('#' + _this.matchIdIndex(_this.sMapId));

        if (!$('#js_activity_feed_edit_form').length) {
          map.css({height: '300px'});
        }
        map.hide();
        $(_this.matchIdIndex('#js_location_input') + ',' + _this.matchIdIndex('#js_add_location')).hide();
      }
      var oButton = $('#' + _this.matchIdIndex(_this.sButtonId));
      if (_this.sGoogleKey.length < 1) {
        oButton.addClass('is_loading');
        return;
      }
      oButton.removeClass('is_loading');
      oButton.off('click').on('click',function () {
        oButton.addClass('is_loading');
        var mapIndex = $(this).data('map-index');
        _this.sCurrentIndex = mapIndex !== undefined ? mapIndex : _this.sCurrentIndex;
        bCheckinInit = true;

        if ($(this).closest('#js_activity_feed_edit_form').length) {
          $('#' + _this.matchIdIndex(_this.sMapId)).hide();
        }

        $('.js_feed_compose_tagging').hide('fast');
        $('.js_feed_compose_schedule').hide('fast');
        $('.js_btn_display_with_friend').removeClass('is_active');
        $('.js_btn_display_with_schedule').removeClass('is_active');
        if (typeof _this.gMyLatLng != 'undefined') {
          _this.createMap();
          _this.showMap();
          return;
        }
        $(_this).on('mapCreated', function () {
          _this.showMap();
        });
        _this.getVisitorLocation();

        return false;
      });


      $(_this.matchIdIndex('#hdn_location_name')).on('focus', function () {
          var _this = $Core.FeedPlace;
          $(_this.matchIdIndex('#js_feed_check_in_map') + ',' + _this.matchIdIndex('#js_add_location_suggestions')).show();
          google.maps.event.trigger(_this.gMap[_this.sCurrentIndex], 'resize');
          _this.gMap[_this.sCurrentIndex].setCenter($Core.FeedPlace.gMyLatLng);
        }).on('keyup', function () {
          var sName = $(this).val(), _this = $Core.FeedPlace;

          if ($(this).val().length < 3) {
            return;
          }
          if (_this.sLastSearch === sName) {
            return;
          }

          for (var i in _this.aPlaces) {
            if (typeof _this.aPlaces[i]['is_auto_suggested'] != 'undefined' && _this.aPlaces[i]['is_auto_suggested']) {
              _this.aPlacesKey.splice($.inArray(_this.aPlaces[i]['place_id'], _this.aPlacesKey), 1);
              _this.aPlaces.splice(i, 1);
            }

          }

          _this.sLastSearch = sName;
          _this.gSearch[_this.sCurrentIndex].nearbySearch(
            {
              /*//bounds: _this.gBounds,*/
              location: _this.gMyLatLng,
              radius: 6000,
              keyword: sName
              //	rankBy: google.maps.places.RankBy.DISTANCE
            },
            function (results, status) {
              if (status === google.maps.places.PlacesServiceStatus.OK) {
                results.map(function (oResult) {
                  if ($.inArray(oResult['place_id'], _this.aPlacesKey) == '-1') {
                    oResult['is_auto_suggested'] = true;
                    _this.aPlaces.push(oResult);
                    _this.aPlacesKey.push(oResult['place_id']);
                  }
                });
              }

              _this.displaySuggestions();
            });
        })
        .on('focus', function () {
          if ($($Core.FeedPlace.matchIdIndex('#js_add_location_suggestions')).is(':visible') !== true) {
            $($Core.FeedPlace.matchIdIndex('#js_add_location_suggestions')).show();
          }
        })
        .on('click', function () {
          /* Needed if they are selecting text */
          google.maps.event.trigger($Core.FeedPlace[_this.sCurrentIndex], 'resize');
        });


      $(_this).on('gotVisitorLocationFeedPlace', function () {
        _this.createMap();
      });

    },
    matchIdIndex: function (id, initIndex) {
      return id + (typeof initIndex !== "undefined" ? initIndex :  this.sCurrentIndex);
    },
    generateFeedMap: function () {
      PF.waitUntil(function () {
        return typeof google !== 'undefined' && typeof google.maps === 'object';
      }, function () {
        $('[data-component="pf_map"]:not(\'.built\')').each(function () {
          var th = $(this);

          if (typeof th.data('lat') === 'undefined' || typeof th.data('lng') === 'undefined' || typeof th.data('id') === 'undefined') {
            return;
          }

          var gLatLng = new google.maps.LatLng(th.data('lat'), th.data('lng'));
          var oMapOptions = {
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: gLatLng,
            streetViewControl: false,
            disableDefaultUI: true
          };

          $Core.FeedPlace.aHoverPlaces[th.data('id')] = {
            map: new google.maps.Map(document.getElementById(th.data('id')), oMapOptions),
            geometry: {location: gLatLng}
          };

          /* Build the marker */
          $Core.FeedPlace.gMarker[$Core.FeedPlace.sCurrentIndex] = new google.maps.Marker({
            map: $Core.FeedPlace.aHoverPlaces[th.data('id')]['map'],
            position: gLatLng,
            draggable: false,
            animation: google.maps.Animation.DROP
          });

          google.maps.event.trigger($Core.FeedPlace.aHoverPlaces[th.data('id')]['map'], 'resize');

          // creation complete
          th.addClass('built');
        });

        PF.event.on('before_cache_current_body', function () {
          $('[data-component="pf_map"]').removeClass('built');
        });
      });
    },

    /* This function is called after a map exists ($Core.FeedPlace.createMap() has been executed), it only shows it like when clicking the button */
    showMap: function () {
      var _this = this, initIndex = _this.sCurrentIndex;
      if (typeof google == 'undefined' || typeof google.maps === "undefined") {
        _this.iTimeShowMap = setTimeout(_this.showMap, 1000);
        return;
      }

      if (typeof _this.iTimeShowMap != 'undefined') {
        clearTimeout(_this.iTimeShowMap);
      }


      var gTempLat = false;
      $(_this.matchIdIndex('#li_location_name') + ',' + _this.matchIdIndex('#js_location_input') + ',' + _this.matchIdIndex('#hdn_location_name') + ',' + _this.matchIdIndex('#js_add_location') + ',' + _this.matchIdIndex('#js_add_location_suggestions') + ',' + _this.matchIdIndex('#js_feed_check_in_map') + ', #' + _this.matchIdIndex(_this.sMapId)).show(400);
      $(_this.matchIdIndex('#js_location_input')).closest('form').find('.activity_feed_form_button_position').hide(400);
      setTimeout(
        function () {
          $('#' + _this.matchIdIndex(_this.sMapId)).css({height:'300px', overflow: 'hidden', width: '100%'});

          if (!!gTempLat === true) {
            return;
          }
          else {
            gTempLat = true;
          }

          $Core.FeedPlace.getNewLocations(true);
          $(_this.matchIdIndex('#hdn_location_name')).trigger('focus');
          google.maps.event.trigger(_this.gMap[initIndex], 'resize');
          _this.gMap[initIndex].setCenter(_this.gMyLatLng);
        }, 400
      );
      $('#' + _this.matchIdIndex(_this.sButtonId, initIndex)).removeClass('is_loading');
    },

    getVisitorLocation: function (sFunction) {
      var _this = $Core.FeedPlace, initIndex = _this.sCurrentIndex;
      $( _this.matchIdIndex('#js_add_location', initIndex) + ',' + _this.matchIdIndex('#js_add_location_suggestions')).show();
      if (typeof _this.gMyLatLng != 'undefined') {
        if (typeof sFunction == 'function') {
          sFunction();
        }
        /* We already have a location */
        return false;
      }
      /* Get the visitors location */
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (oPos) {
            if (!oPos.coords.latitude && !oPos.coords.longitude) {
              return;
            }
            _this.gMyLatLng = new google.maps.LatLng(oPos.coords.latitude, oPos.coords.longitude);
            $(_this).trigger('gotVisitorLocationFeedPlace');
            $.ajaxCall('user.saveMyLatLng', 'lat=' + oPos.coords.latitude + '&lng=' + oPos.coords.longitude);
          },
          function () {
            _this.getLocationWithoutHtml5(sFunction);
          }
        );
      }
      else {
        _this.getLocationWithoutHtml5();
      }
    },

    getLocationWithoutHtml5: function (sFunction) {
      /* Get visitor's city  */
      var sCookieLocation = getCookie('core_places_location');
      if (sCookieLocation != null) {
        var aLocation = sCookieLocation.split(',');
        this.gMyLatLng = new google.maps.LatLng(aLocation[0], aLocation[1]);
        $(this).trigger('gotVisitorLocationFeedPlace');
      }
      else {
        var sParams = 'section=feed';
        switch (sFunction) {
          case 'showMap':
            sParams += '&callback=$Core.FeedPlace.showMap&initIndex=' + this.sCurrentIndex;
            break;
          case 'createMap':
            sParams += '&callback=$Core.FeedPlace.createMap&initIndex=' + this.sCurrentIndex;
            break;
        }
        $.ajaxCall('core.getMyCity', sParams);
      }
    },

    /* Called from the template when we have the location of the visitor */
    setVisitorLocation: function (fLat, fLng) {
      return false;
      // this.gMyLatLng = new google.maps.LatLng(fLat, fLng);
      // $(this).trigger('gotVisitorLocationFeedPlace');
    },

    createMap: function () {
      /* Creating map */
      var _this = this,
        initIndex = this.sCurrentIndex,
      mapId = _this.matchIdIndex(_this.sMapId);
      if (typeof _this.gMyLatLng == 'undefined') {
        return;
      }

      if (!$('#' + mapId).length) {
        return;
      }
      /* Build the map*/
      var oMapOptions =
        {
          zoom: 13,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          center: _this.gMyLatLng,
          streetViewControl: false,
          scrollWheel: false
        };

      $(_this.matchIdIndex('#js_add_location') + ',' + _this.matchIdIndex('#js_feed_check_in_map') + ',#' + mapId).show(400);
      setTimeout(function () {
        _this.gMap[initIndex] = new google.maps.Map(document.getElementById(mapId), oMapOptions);
        /* Create the search object*/
        _this.gSearch[initIndex] = new google.maps.places.PlacesService(_this.gMap[initIndex]);
        /* Build the marker */
        _this.gMarker[initIndex] = new google.maps.Marker({
          map: _this.gMap[initIndex],
          position: _this.gMyLatLng,
          draggable: true,
          animation: google.maps.Animation.DROP
        });
        _this.initMarkerEvent(initIndex);
        //Hide suggestion
        google.maps.event.addListener(_this.gMap[initIndex], 'dragstart', function() {
          $(_this.matchIdIndex('#js_add_location_suggestions', initIndex)).hide();
        });
        google.maps.event.addListener(_this.gMap[initIndex], 'click', function(event) {
          $(_this.matchIdIndex('#js_add_location_suggestions', initIndex)).hide();
          //Remove old mark
          _this.gMarker[initIndex].setMap(null);
          _this.gMarker[initIndex] = new google.maps.Marker({
            map: _this.gMap[initIndex],
            position: event.latLng,
            draggable: true,
            animation: google.maps.Animation.DROP
          });
          _this.gMyLatLng = event.latLng;
          _this.gMap[initIndex].panTo(_this.gMyLatLng);
          _this.getNewLocations(false, true);
          _this.initMarkerEvent(initIndex);

          //Get location info and select it
          var geocoder = new google.maps.Geocoder();
          geocoder.geocode({location: _this.gMyLatLng}, function (result, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK && result.length) {
              if (typeof _this.gSearch[initIndex] == 'undefined') {
                _this.gSearch[initIndex] = new google.maps.places.PlacesService(this.gMap[initIndex]);
              }
              _this.gSearch[initIndex].getDetails({
                placeId: result[0].place_id
              }, function (result, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                  _this.aPlaces.unshift(result);
                  _this.chooseLocation(result.place_id, initIndex, true);
                }
              })
            }
          })
        });
      }, 400);

      /* We need the name of the city to pre-populate the input */
      _this.gGeoCoder = new google.maps.Geocoder();
      _this.gGeoCoder.geocode({'latLng': _this.gMyLatLng}, function (oResults, iStatus) {
        if (iStatus === google.maps.GeocoderStatus.OK && oResults[1] && !$(_this.matchIdIndex('#hdn_location_name')).val()) {
          $(_this.matchIdIndex('#hdn_location_name')).val(oResults[1].formatted_address);
          $(_this.matchIdIndex('#val_location_name')).val(oResults[1].formatted_address);
          $(_this.matchIdIndex('#val_location_latlng')).val(oResults[1].geometry.location.lat() + ',' + oResults[1].geometry.location.lng());

          oResults[1]['default_location'] = true;

          _this.oDefaultPlace = oResults[1];
          _this.oDefaultPlace.name = oResults[1].formatted_address;
          _this.oDefaultPlace.id = Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
        }
      });

      _this.gBounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(_this.gMyLatLng.lat() - 1, _this.gMyLatLng.lng()),
        new google.maps.LatLng(_this.gMyLatLng.lat(), _this.gMyLatLng.lng() + 1)
      );

      /* At this point gMyLatLng must exist */
      $.ajaxCall('feed.loadEstablishments', 'latitude=' + _this.gMyLatLng.lat() + '&longitude=' + _this.gMyLatLng.lng());

      $(_this).trigger('mapCreated');
    },
    initMarkerEvent: function (initIndex) {
      var _this = this;
      /* Now attach an event for the marker */
      google.maps.event.addListener(_this.gMarker[initIndex], 'mouseup', function () {
        /* Refresh gMyLatLng*/
        _this.gMyLatLng = new google.maps.LatLng(_this.gMarker[initIndex].getPosition().lat(), _this.gMarker[initIndex].getPosition().lng());

        /* Center the map */
        _this.gMap[initIndex].panTo(_this.gMyLatLng);

        _this.getNewLocations();
      });
    },
    getHash: function (sStr) {
      var iHash = 0;
      if (!sStr.length) return iHash;
      for (var i = 0; i < sStr.length; i++) {
        iHash = ((iHash << 5) - iHash) + sStr.charCodeAt(i);
      }
      return iHash;
    },

    /* Populates and displays the div to show establishments given the current position as defined by gMyLatLng.
     * it checks all of the items in aPlaces and gets the 10 nearer gMyLatLng, places the name of the city first.*/
    displaySuggestions: function (bIgnoreSuggestion) {
      var sOut = '', _this = this;

      _this.aPlaces.reverse();

      _this.aPlaces.map(function (oPlace) {
        sOut += '<div class="js_div_place" onmouseover="$Core.FeedPlace.hintPlace(\'' + oPlace['place_id'] + '\', \''+ _this.sCurrentIndex +'\');" onclick="$Core.FeedPlace.chooseLocation(\'' + oPlace['place_id'] + '\', \''+ _this.sCurrentIndex +'\');">';
        sOut += '<div class="js_div_place_name"><strong>' + oPlace['name'] + '</strong></div>';
        if (typeof oPlace['vicinity'] != 'undefined') {
          sOut += '<div class="js_div_place_vicinity">&nbsp;-&nbsp;<i>' + oPlace['vicinity'] + '</i></div>';
        }
        sOut += '</div>';
      });

      $(_this.matchIdIndex('#js_add_location_suggestions')).html(sOut).css({'z-index': 900, 'max-height': '150px'});
      if (!bIgnoreSuggestion) {
        $(_this.matchIdIndex('#js_add_location_suggestions')).show();
      }
    },

    /* Move the marker and pan the map to a location */
    hintPlace: function (sId, sIndex) {
      var _this = this;
      _this.aPlaces.map(function (oPlace) {
        if (oPlace['place_id'].toString() === sId.toString()) {
          _this.gMap[sIndex].panTo(oPlace['geometry']['location']);
          _this.gMarker[sIndex].setPosition(oPlace['geometry']['location']);
        }
      });

    },

    /* Visually accepts a suggestion and sets the internal value for the form*/
    chooseLocation: function (id, index, noClose) {
      var oPlace = false, _this = this;
      this.aPlaces.map(function (oCheck, i) {
        if (oCheck['place_id'].toString() === id.toString()) {
          _this.aPlaces.splice(i, 1);
          _this.aPlaces.unshift(oCheck);
          _this.aPlaces.reverse();
          _this.displaySuggestions(noClose);
          oPlace = oCheck;
          return false;
        }
      });
      if (!oPlace) {
        return false;
      }
      if (typeof oPlace['latitude'] != 'undefined') {
        $(_this.matchIdIndex('#val_location_latlng', index)).val(oPlace['latitude'] + ',' + oPlace['longitude']).trigger('change');
      }
      else if (typeof oPlace['geometry'] != 'undefined') {
        $(_this.matchIdIndex('#val_location_latlng', index)).val(oPlace['geometry']['location'].lat() + ',' + oPlace['geometry']['location'].lng()).trigger('change');
      }
      $(_this.matchIdIndex('#hdn_location_name', index) + ',' + _this.matchIdIndex('#val_location_name', index)).val(oPlace['name']);
      $(_this.matchIdIndex('.js_location_feedback', index)).html(oTranslations['at_location'].replace('{location}', oPlace['name'])).show().addClass('active');
      if (!noClose) {
        $('#' + _this.matchIdIndex(_this.sButtonId, index)).removeClass('is_active');
        $(_this.matchIdIndex('#js_add_location_suggestions', index) + ',' + _this.matchIdIndex('#js_feed_check_in_map', index) + ',' + _this.matchIdIndex('#js_location_input', index)).hide();
        $('.activity_feed_form_button_position').show();
        if ($sCurrentForm && ['global_attachment_photo', 'custom'].indexOf($sCurrentForm) !== -1) {
          $('.activity_feed_form_holder .js_location_feedback').addClass('hide');
          if (!$('#js_activity_feed_edit_form').length) {
            $('.activity_feed_form_button_status_info').show();
          }
        }
      }
    },

    /* Adds New places to the $Core.FeedPlace.aPlaces array by scannig the existing items before adding a new one,
     * Receives a string in json format, called from an ajax response. The second parameter is an optional callback function */
    storePlaces: function (jPlaces, oCallback) {
      var oPlaces = JSON.parse(jPlaces), _this = this;
      $(oPlaces).each(function (iPlace, oNewPlace) {
        var bAddPage = true;
        _this.aPlaces.map(function (oFeedPlace) {
          if (typeof oFeedPlace['page_id'] != 'undefined' && oFeedPlace['page_id'] == oNewPlace['page_id']) {
            /* its a page that we already added*/
            bAddPage = false;
          }
        });

        if (bAddPage) {
          if (typeof oNewPlace['place_id'] == 'undefined') {
            oNewPlace['place_id'] = Math.round(1000000 * Math.random());
            oNewPlace['geometry']['location'] = new google.maps.LatLng(oNewPlace['geometry']['latitude'], oNewPlace['geometry']['longitude']);
          }

          _this.aPlaces.push(oNewPlace);
          _this.aPlacesKey.push(oNewPlace['place_id']);
        }
      });

      if (typeof oCallback == 'function') {
        oCallback();
      }
    },

    /* Ajax call to get more locations, needs to be called after a marker exists */
    getNewLocations: function (bAuto, bNoDisplaySuggest) {
      if (typeof this.gSearch[this.sCurrentIndex] == 'undefined') {
        this.gSearch[this.sCurrentIndex] = new google.maps.places.PlacesService(this.gMap[this.sCurrentIndex]);
      }
      var aTemp = [];
      this.aPlaces.map(function (oPlace) {
        if (typeof oPlace['page_id'] != 'undefined') aTemp.push(oPlace);
      });

      this.aPlaces = aTemp;

      var sOut = '';

      this.gSearch[this.sCurrentIndex].nearbySearch({
        location: $Core.FeedPlace.gMyLatLng,
        radius: '500'
      }, function (aResults, iStatus) {
        if (iStatus == google.maps.places.PlacesServiceStatus.OK) {
          for (var i = 0; i < aResults.length; i++) {
            if (typeof bAuto == 'boolean' && bAuto == true) {
              aResults[i]['is_auto_suggested'] = true;
            }
            $Core.FeedPlace.aPlaces.push(aResults[i]);
            $Core.FeedPlace.aPlacesKey.push(aResults[i]['place_id']);
          }
        }
        $Core.FeedPlace.displaySuggestions(bNoDisplaySuggest);
      });
    },

    /* Does'nt have to be exact or in any specific measure, just needs to reliably tell *a* distance*/
    getDistanceFromPoints: function (oPlace1, oPlace2) {
      var xs = Math.pow((oPlace2['latitude'] - oPlace1['latitude']), 2);
      var ys = Math.pow((oPlace2['longitude'] - oPlace1['longitude']), 2);

      return Math.sqrt(xs + ys);
    },

    googleReady: function (sGoogleKey, initIndex) {
      this.sCurrentIndex = initIndex;
      if (this.bGoogleReady) {
        this.init();
        return false;
      }
      if (typeof google === 'object' && typeof google.maps === 'object') {
        this.bGoogleReady = true;
        this.init();
        return false;
      }
      var script = document.createElement("script");
      script.type = "text/javascript";
      script.src = "https://maps.google.com/maps/api/js?libraries=places&key=" + sGoogleKey + "&language=" + getParam('sLanguage') + "&callback=$Core.FeedPlace.init";
      document.head.appendChild(script);
      $Core.FeedPlace.bGoogleReady = true;
    },

    showHoverMap: function (fLat, fLng, oObj) {
      if (typeof google === 'undefined' || typeof google.maps === "undefined") {
        return false;
      }
      /* Check if this item already has a map */
      if ($(oObj).siblings('.js_location_map').length > 0) {
        $(oObj).siblings('.js_location_map').show();
        /* Trigger the resize to avoid visual glitches */
        google.maps.event.trigger($Core.FeedPlace.aHoverPlaces[$(oObj).siblings('.js_location_map').attr('id')], 'resize');
        return;
      }

      var sId = 'js_map_' + Math.floor(Math.random() * 100000);

      var sInfoWindow = '<div class="js_location_map" id="' + sId + '"></div>';

      /* Load the map */
      $(oObj).after(sInfoWindow);

      var gLatLng = new google.maps.LatLng(fLat, fLng);
      var oMapOptions =
        {
          zoom: 13,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          center: gLatLng,
          streetViewControl: false,
          disableDefaultUI: true
        };

      $Core.FeedPlace.aHoverPlaces[sId] = {
        map: new google.maps.Map(document.getElementById(sId), oMapOptions),
        geometry: {location: gLatLng}
      };

      /* Build the marker */
      $Core.FeedPlace.gMarker = new google.maps.Marker({
        map: $Core.FeedPlace.aHoverPlaces[sId]['map'],
        position: gLatLng,
        draggable: true,
        animation: google.maps.Animation.DROP
      });

      google.maps.event.trigger($Core.FeedPlace.aHoverPlaces[sId]['map'], 'resize');
      $(oObj).off('mouseout').on('mouseout', function () {
        $('#' + sId).hide();
      });
    },

    resetLocation: function () {
      $.ajaxCall('core.getMyCity', 'section=feed&saveLocation=1&initIndex=' + this.sCurrentIndex);
      $(this.matchIdIndex('#hdn_location_name')).val(oTranslations['loading']);
    },

    cancelCheckIn: function (index, bClose) {
      /* Visually hide everything */
      $(this.matchIdIndex('#js_add_location', index) + ',' + this.matchIdIndex('#js_location_input', index)).hide();
      $('.activity_feed_form_button_position').show();
      $(this.matchIdIndex('#btn_display_check_in', index)).removeClass('is_active');
      if (!bClose) {
        $(this.matchIdIndex('.js_location_feedback', index)).html('').removeClass('active');
        $(this.matchIdIndex('#hdn_location_name', index) + ',' + this.matchIdIndex('#val_location_name', index) + ',' + this.matchIdIndex('#val_location_latlng', index)).val('').trigger('change');
      }
    },
    quickInit: function() {
      if (!$('#js_activity_feed_form').length
        && $('[data-component="pf_map"]').length
        && oParams['sGoogleApiKey']) {
        if (!this.bGoogleReady) {
          bCheckinInit = false;
          this.googleReady(oParams['sGoogleApiKey']);
        } else {
          this.generateFeedMap();
        }
      }
    }
  };
