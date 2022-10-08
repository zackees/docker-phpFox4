$Core.Gmap = {
  inputLocationSection: 'js-location_input_section',
  locationID: 'js-location_input',
  latID: 'js-location_lat',
  lngID: 'js-location_lng',
  mapPreviewID: 'js-location_map',
  mapViewID: 'js-map',
  countryID: 'js-location_country_iso',
  provinceID: 'js-location_country_child_id',
  searchLocationID: 'js-map_view_auto_location',
  currentLocationID: 'js-map_current_location',
  oMarkerLocation: null,
  isGoogleReady: false,
  isGoogleError: false,
  oMap: null,
  sItemType: null,
  isSearching: false,
  oInitParams: null,
  aMarkers: {},
  aInfoWindow: {},
  oShowingItems: [],
  oBounds: null,
  oCenter: null,
  sCurrentQuery: '',
  sCurrentTextSearch: '',
  iCurrentZoom: 15,
  iDefaultZoom: 15,
  isFirstTime: true,
  mapMarkerIcon: null,
  mapActiveMarkerIcon: null,
  sView: '',
  init: function () {
    if (!this.isGoogleReady) {
      return false;
    }
    $('.' + this.inputLocationSection).each(function () {
      $Core.Gmap.loadAutoSearch($(this));
    });
  },
  loadAutoSearch: function (sectionEle) {
    var inputEle = sectionEle.find('.' + this.locationID),
      mapPreviewEle = sectionEle.find('.' + this.mapPreviewID),
      latEle = sectionEle.find('.' + this.latID),
      lngEle = sectionEle.find('.' + this.lngID),
      countryEle = sectionEle.find('.' + this.countryID),
      autocomplete = new google.maps.places.Autocomplete(inputEle[0]);
    var location = null, showMap = false;
    if (latEle.val() && lngEle.val()) {
      location = {lat: parseFloat(latEle.val()), lng: parseFloat(lngEle.val())}
      showMap = true;
    }
    var map = new google.maps.Map(mapPreviewEle[0], {
      zoom: this.iDefaultZoom,
      center: location
    });
    var marker = new google.maps.Marker({
      map: map,
      position: location,
      anchorPoint: new google.maps.Point(0, -29)
    });
    autocomplete.bindTo('bounds', map);
    autocomplete.setFields(['address_components', 'geometry', 'icon', 'name']);
    inputEle.on('change', function () {
      if ($(this).val() === '') {
        mapPreviewEle.hide().css('margin-top', '0');
        sectionEle.find('.' + $Core.Gmap.latID).val('');
        sectionEle.find('.' + $Core.Gmap.lngID).val('');
      }
    });
    inputEle.on('keydown', function (event) {
      if (event.keyCode === 13) {
        event.preventDefault();
        return false;
      }
    });
    if (showMap) {
      mapPreviewEle.show().css('height', '250px').css('margin-top', '10px');
      map.setZoom(this.iCurrentZoom);
    }
    autocomplete.addListener('place_changed', function () {
      marker.setVisible(false);
      var place = autocomplete.getPlace();
      if (!place.geometry) {
        return false;
      }
      mapPreviewEle.show().css('height', '250px').css('margin-top', '10px');
      // If the place has a geometry, then present it on a map.
      if (place.geometry.viewport) {
        map.fitBounds(place.geometry.viewport);
      } else {
        map.setCenter(place.geometry.location);
        map.setZoom($Core.Gmap.oMap.iCurrentZoom);
      }
      if (place.address_components.length && countryEle.length) {
        for (var i = 0; i < place.address_components.length; i++) {
          var addressType = place.address_components[i].types[0];
          if (addressType === "country") {
            countryEle.val(place.address_components[i].short_name);
          }
        }
      }
      latEle.val(place.geometry.location.lat());
      lngEle.val(place.geometry.location.lng());
      marker.setPosition(place.geometry.location);
      marker.setVisible(true);
    })
  },
  initGoogle: function (sCallback, oCallbackParams) {
    var sGoogleKey = getParam('sGoogleApiKey');
    if (getParam('iMapDefaultZoom')) {
      this.iDefaultZoom = parseInt(getParam('iMapDefaultZoom'));
    }
    if (!sGoogleKey) {
      if (!this.isGoogleError) {
        $('#' + this.mapViewID).css('margin', '16px').html('<div class="error_message m-4">' + oTranslations['error_when_load_map_view_missing_google_api_key'] + '</div>');
        this.isGoogleError = true;
      }
      return false;
    }
    if (typeof this[sCallback] !== 'function') {
      return false;
    }
    this.oInitParams = oCallbackParams;
    if (this.isGoogleReady || (typeof google === 'object' && typeof google.maps === 'object')) {
      this.isGoogleReady = true;
      this[sCallback](oCallbackParams);
      return false;
    }
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = "https://maps.google.com/maps/api/js?libraries=places&key=" + sGoogleKey + "&language=" + getParam('sLanguage') + "&callback=$Core.Gmap." + sCallback;
    document.body.appendChild(script);
    $Core.Gmap.isGoogleReady = true;
  },
  initMapView: function (oParams) {
    try {
      if (!this.isGoogleReady) {
        return false;
      }
      this.sItemType = oParams ? (oParams.type ? oParams.type : null) : (this.oInitParams ? (this.oInitParams.type ? this.oInitParams.type : null) : null);
      if (!this.sItemType || !this.isFirstTime) {
        return false; //Can determine type to search
      }
      if ((/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) || (window.matchMedia('(max-width: 1024px)').matches)) {
        $('body').addClass('map-mobile');
      } else {
        $('body').addClass('map-collapse');
      }
      $(window).resize(function () {
        $Core.Gmap.initMapLayoutPosition();
        $Core.Gmap.loadCssStyle();
        if (window.matchMedia('(max-width: 1024px)').matches) {
          $('body').addClass('map-mobile');
        } else {
          $('body').removeClass('map-mobile');
        }
      });

      var href = parse_url(window.location.href);
      var params = JSON.parse('{"' + decodeURI(href.query).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g, '":"') + '"}');
      if (typeof params['view'] !== "undefined") {
        this.sView = params['view'];
        delete params['view'];
      }
      if (typeof params['search[search]'] !== "undefined") {
        this.sCurrentTextSearch = 'search[search]=' + params['search[search]'];
        delete params['search[search]'];
      }
      delete params['type'];
      this.sCurrentQuery = $.param(params);

      var styles = [
        {
          featureType: "poi",
          stylers: [
            {visibility: "off"}
          ]
        }
      ];
      this.oMap = new google.maps.Map(document.getElementById(this.mapViewID), {
        zoom: $Core.Gmap.iDefaultZoom,
        mapTypeControl: true,
        mapTypeControlOptions: {
          position: google.maps.ControlPosition.BOTTOM_CENTER
        },
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        styles: styles
      });
      this.iCurrentZoom = this.oMap.getZoom();
      var infoWindow = new google.maps.InfoWindow;
      if (navigator.geolocation) {
        this.getCurrentLocation();
        this.initAutoSearchMapView();
      } else {
        // Browser doesn't support Geolocation
        this.handleLocationError(false);
      }
      google.maps.event.addListener(this.oMap, 'bounds_changed', function () {
        if (!$Core.Gmap.isSearching) {
          $Core.Gmap.isSearching = true;
        } else {
          return false;
        }
        setTimeout(function () {
          $Core.Gmap.isSearching = false;
        }, 1000);
        $Core.Gmap.searchItemsOnMap($Core.Gmap.isFirstTime);
        $Core.Gmap.isFirstTime = false;
      });
      google.maps.event.addListener(this.oMap, 'dragend', function () {
        $Core.Gmap.searchItemsOnMap();
        $Core.Gmap.hasChangedLocation();
      });
      google.maps.event.addListener(this.oMap, 'zoom_changed', function () {
        $Core.Gmap.searchItemsOnMap(true);
        $Core.Gmap.iCurrentZoom = $Core.Gmap.oMap.getZoom();
      });
      this.initMapLayoutPosition();
    } catch (e) {
      console.warn('Error when load Google Map', e);
    }
  },
  initAutoSearchMapView: function () {
    if ($('#' + this.currentLocationID).length) {
      $(document).on('click', '#' + this.currentLocationID, function () {
        $Core.Gmap.getCurrentLocation();
        return false;
      });
    }
    if ($('#' + this.searchLocationID).length) {
      var mapViewSearch = new google.maps.places.Autocomplete(document.getElementById(this.searchLocationID));
      mapViewSearch.addListener('place_changed', function () {
        var place = mapViewSearch.getPlace();
        if (!place.geometry) {
          return false;
        }
        // If the place has a geometry, then present it on a map.
        if (place.geometry.viewport) {
          $Core.Gmap.oMap.fitBounds(place.geometry.viewport);
        } else {
          $Core.Gmap.oMap.setCenter(place.geometry.location);
        }
        $Core.Gmap.oMap.setZoom($Core.Gmap.iCurrentZoom);
        $Core.Gmap.updateMarkerLocation(place.geometry.location, place.name);
        $Core.Gmap.hasChangedLocation();
      });
    }
  },
  hasChangedLocation: function () {
    if ($('#' + $Core.Gmap.currentLocationID).length) {
      $('#' + $Core.Gmap.currentLocationID).removeClass('active').html('<i class="ico ico-compass-o"></i>');
    }
  },
  getCurrentLocation: function () {
    if (!navigator.geolocation) return false;
    navigator.geolocation.getCurrentPosition(function (position) {
      var pos = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      $Core.Gmap.updateCenterLocation(pos);
      $Core.Gmap.oMap.setCenter(pos);
      $Core.Gmap.oMap.setZoom($Core.Gmap.iDefaultZoom);
      $Core.Gmap.updateMarkerLocation(pos, '');
      $('#' + $Core.Gmap.currentLocationID).addClass('active').html('<i class="ico ico-compass"></i>');
    }, function () {
      $Core.Gmap.handleLocationError(true);
      $Core.Gmap.oMap.setZoom(5);
      $Core.Gmap.oMap.setCenter(new google.maps.LatLng(39, -98));
    });
  },
  updateMarkerLocation: function (position, title) {
    if ($Core.Gmap.oMarkerLocation == null) {
      $Core.Gmap.oMarkerLocation = new google.maps.Marker({
        position: position,
        map: $Core.Gmap.oMap,
        title: title
      });
    }
    else {
      $Core.Gmap.oMarkerLocation.setPosition(position);
      $Core.Gmap.oMarkerLocation.setTitle(title);
    }
  },
  updateCenterLocation: function (pos) {
    if ($('#' + $Core.Gmap.searchLocationID).length) {
      var geocoder = new google.maps.Geocoder;
      geocoder.geocode({
        'location': {lat: pos.lat, lng: pos.lng}
      }, function (results, status) {
        if (status === 'OK') {
          if (results[0]) {
            $('#' + $Core.Gmap.searchLocationID).val(results[0].formatted_address);
          } else {
            $('#' + $Core.Gmap.searchLocationID).val(lat + ',' + lng);
          }
        }
      });
    }
  },
  initMapLayoutPosition: function () {
    var containerOffset = $('#main').offset();
    if ($('.core-map-container').length) {
      $('.core-map-container').css("top", containerOffset.top);
    }
  },
  searchItemsOnMap: function (force, searchQuery, isTextSearch) {
    var bounds = this.oMap.getBounds(), center = this.oMap.getCenter();
    if (!bounds) return false;
    if (!force && !this.predictSearchCondition(bounds.toJSON(), center)) {
      return false;
    }
    if (typeof searchQuery !== "undefined") {
      if (isTextSearch) {
        this.sCurrentTextSearch = typeof searchQuery === 'object' ? $.param(searchQuery) : searchQuery;
      } else {
        var sQuery = typeof searchQuery === 'object' ? $.param(searchQuery) : searchQuery;
        sQuery = decodeURI(sQuery);
        sQuery = sQuery.replace(/&type=[^&]*|&bounds\[south\]=[^&]*|&bounds\[west\]=[^&]*|&bounds\[north\]=[^&]*|&bounds\[east\]=[^&]*|&zoom=[^&]*/g, '');
        this.sCurrentQuery = sQuery;
      }
    }
    //Set last search bounds and center
    this.oBounds = bounds.toJSON();
    this.oCenter = center;
    //Hide all info window
    $('.gm-ui-hover-effect').click();
    $.ajaxCall('core.searchItemsMapView', $.param({
      type: this.sItemType,
      bounds: bounds.toJSON(),
      zoom: this.oMap.getZoom()
    }) + '&' + this.sCurrentQuery + '&view=' + this.sView + '&' + this.sCurrentTextSearch, 'GET');
    $('body').addClass('map-loading');
  },
  loadCssStyle: function () {
    var positionMap = parseInt($('.core-map-container').css('top'), 10);
    if ($('.core-map-search-bar').length) {
      $('.core-map-search-bar').css("top", positionMap);
    }
    setTimeout(function () {
      $('body').removeClass('map-loading');
    }, 500);
    $Core.Gmap.collapseMapButton();
  },
  predictSearchCondition: function (newBounds, newCenter) {
    if (!this.oMap) return false;
    if (this.oCenter && this.oBounds && newBounds && newCenter) {
      //Old center
      var oldCenter = this.oCenter, centerLat = oldCenter.lat(), centerLng = oldCenter.lng();
      var oldBounds = this.oBounds, newCenterLat = newCenter.lat(), newCenterLng = newCenter.lng();
      var difLat = newCenterLat > centerLat ? newCenterLat - centerLat : centerLat - newCenterLat;
      var difLng = newCenterLng > centerLng ? newCenterLng - centerLng : centerLng - newCenterLng;
      if (oldBounds && newBounds) {
        var latLimitNorth = (oldBounds.north - centerLat) / 2,
          latLimitSouth = (centerLat - oldBounds.south) / 2,
          lngLimitWest = (centerLng - oldBounds.west) / 2,
          lngLimitEast = (oldBounds.east - centerLng) / 2;
        if (difLat >= latLimitSouth || difLat >= latLimitNorth || difLng >= lngLimitWest || difLng >= lngLimitEast) {
          return true;
        }
      }
    } else {
      return true;
    }
    return false;
  },
  handleLocationError: function (browserHasGeolocation) {
    console.warn(browserHasGeolocation ?
      'Error: The Geolocation service failed.' :
      'Error: Your browser doesn\'t support geolocation.');
  },
  setMarkersOnMap: function (oMarkers) {
    try {
      if (!this.isGoogleReady || !this.oMap) {
        return false;
      }
      for (var i in this.aMarkers) {
        if (this.oShowingItems.indexOf(i) === -1) {
          this.aMarkers[i].setMap(null);
        }
      }
      for (var i in oMarkers) {
        var marker = oMarkers[i];
        if (this.aMarkers.hasOwnProperty(marker.id)) {
          if (this.aMarkers[marker.id].getMap() === null) {
            this.aMarkers[marker.id].setMap(this.oMap);
            this.changeMarkerIcon(this.aMarkers[marker.id]);
          }
        } else {
          this.aInfoWindow[marker.id] = new google.maps.InfoWindow({
            content: marker.info_window || marker.title,
            maxWidth: 250
          });
          this.aInfoWindow[marker.id].addListener('closeclick', function () {
            $('#js-map_item_' + marker.id).removeClass('active');
            $Core.Gmap.changeMarkerIcon($Core.Gmap.aMarkers[marker.id]);
          });
          this.aMarkers[marker.id] = new google.maps.Marker({
            position: {lat: parseFloat(marker.lat), lng: parseFloat(marker.lng)},
            map: this.oMap,
            title: marker.title
          });
          this.changeMarkerIcon(this.aMarkers[marker.id]);
          this.aMarkers[marker.id].addListener('click', function () {
            var eListing = $('.js_core_map_item_listing');
            var eItem = $('#js-map_item_' + marker.id);

            $Core.Gmap.showInfoWindow(marker.id);
            if (eListing.hasClass('mCustomScrollbar')) {
              eListing.mCustomScrollbar("scrollTo", eItem);
            } else {
              if (eItem.position().top < 0 || (eItem.position().top + eItem.outerHeight()) > eListing.outerHeight()) {
                eListing.animate({
                  scrollTop: eItem.position().top + eListing.scrollTop()
                }, 500);
              }
            }
            $Core.Gmap.changeMarkerIcon(this, true);
            $('body').addClass('map-collapse map-collapse-part');
          });
        }
      }
    } catch (e) {
      console.warn('Error set Marker on Map', e);
    }
  },
  showInfoWindow: function (id) {
    if (!this.aInfoWindow[id] || !this.aMarkers[id] || !this.oMap) {
      return false;
    }
    if ($(".dropdown.open").length || $('#js-map_item_' + id).hasClass("active")) {
      return false;
    }
    for (var i in this.aInfoWindow) {
      this.aInfoWindow[i].close();
    }
    this.aInfoWindow[id].open(this.oMap, this.aMarkers[id]);
    //Reset markers index again
    for (var i in this.aMarkers) {
      this.aMarkers[i].setOptions({zIndex: 1});
      this.changeMarkerIcon(this.aMarkers[i]);
    }
    //Set current marker highest
    this.aMarkers[id].setOptions({
      zIndex: 2
    });
    this.changeMarkerIcon(this.aMarkers[id], true);

    $('.js-gmap_item_card_view').removeClass('active');
    $('#js-map_item_' + id).addClass('active');

  },
  clearAllMarkers: function () {
    for (var i in this.aMarkers) {
      this.aMarkers[i].setMap(null);
    }
  },
  addEntryMapView: function (oParams) {
    var eleId = oParams ? (oParams.eleId ? oParams.eleId : null) : (this.oInitParams ? (this.oInitParams.eleId ? this.oInitParams.eleId : null) : null);
    if (!eleId) return false;
    var ele = $(eleId);
    if (ele.length) {
      ele.addClass('dont-unbind').addClass('dont-unbind-children');
      var lat = parseFloat(ele.data('lat')), lng = parseFloat(ele.data('lng')), width = ele.data('width'),
        height = ele.data('height');
      var location = new google.maps.LatLng(lat, lng);
      var map = new google.maps.Map(ele[0], {
        zoom: 15,
        center: location
      });
      var marker = new google.maps.Marker({
        map: map,
        position: location,
        anchorPoint: new google.maps.Point(0, -29)
      });
      marker.setVisible(true);
      ele.css('height', height).css('width', width);
    }
    return true;
  },
  collapseMapButton: function () {
    var toggleButton = $('.js_core_map_button_toggle_collapse');
    var maxHeight = $('.core-map-listing-container .item-header-title').height() + 32;
    var eContainer = $('.core-map-listing-container');
    toggleButton.off('click').on('click', function () {
      if (!$('body').hasClass('map-mobile')) {
        if ($('body').hasClass('map-collapse')) {
          eContainer.css("max-height", maxHeight).css("overflow", "hidden");
        } else {
          eContainer.css("max-height", "").css("overflow", "");
        }
      } else {
        eContainer.css("max-height", "").css("overflow", "");
      }
      $('body').removeClass('map-collapse-part');
      $('body').toggleClass('map-collapse');
    });
  },
  setMapMarker: function (icon, activeIcon) {
    if (icon) {
      this.mapMarkerIcon = icon;
    }
    if (activeIcon) {
      this.mapActiveMarkerIcon = activeIcon;
    }
    return true;
  },
  changeMarkerIcon: function (oMarker, isActive) {
    if (typeof oMarker.setIcon !== 'function') {
      return false;
    }
    if (isActive) {
      if (this.mapActiveMarkerIcon) {
        oMarker.setIcon({
          url: this.mapActiveMarkerIcon,
          scaledSize: new google.maps.Size(35, 35)
        });
      } else {
        oMarker.setIcon();
      }
    } else {
      if (this.mapMarkerIcon) {
        oMarker.setIcon({
          url: this.mapMarkerIcon,
          scaledSize: new google.maps.Size(25, 25)
        });
      } else {
        oMarker.setIcon();
      }
    }
    return true;
  }
};

$Ready(function () {
  if (!$('.' + $Core.Gmap.inputLocationSection).length) {
    return;
  }
  $Core.Gmap.initGoogle('init');
});