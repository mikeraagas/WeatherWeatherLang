$(function() {
	var c = function() {}, public = c.prototype;

	/* Public Properties
	-------------------------------*/
	public.key = '4fc0cc27e4f20f7d6449d30a36b728bd';

	/* Private Properties
	-------------------------------*/
	var $ = jQuery;

	/* Loader
	-------------------------------*/
	public.__load = c.load = function() {
		return new c();
	};

	/* Construct
	-------------------------------*/
	public.__construct = function() {};

	/* Public Methods
	-------------------------------*/
	public.setForecast = function(location) {
		var self = this;

		// get location coordinates
		this.setLocation(location, function(data) {
			var response = JSON.parse(data),
				status   = response.status,
				result   = response.result;

			// set lat and lng
			var coordinates = {
				lat : result.lat,
				lng : result.lng
			};

			if (status == 'error') {
				alert('Invalid Address');
				return false;
			}

			// set location map
			self.setMap(location, coordinates);

			// set weather forecast
			self.getWeather(coordinates, function(response) {
				var weather = response;

				// current weather template
				self.setWeatherInfo(location, coordinates, response);

				console.log(response);
			});
		});
	};

	/**
	 * Get latitude and longitude 
	 * from specified location
	 *
	 * @param string
	 * @return function
	 */
	public.setLocation = function(location, callback) {
		var self = this,
			url = 'http://maps.googleapis.com/maps/api/geocode/json';

		$.get(url, { address : location, sensor : false }, function(response) {
			var place = response.results[0];

			if (response.results.length == 0 || response.status == 'ZERO RESULTS') {
				var error = {
					status : 'error',
					result : 'zero results'
				};

				callback(JSON.stringify(error));
				return false;
			}

			var coordinates = {
				lat : place.geometry.location.lat,
				lng : place.geometry.location.lng
			};

			var success = {
				status : 'success',
				result : coordinates 
			};

			callback(JSON.stringify(success));
		});
	};

	/**
	 * Set location map 
	 * using google maps API
	 *
	 * @param string
	 * @return void
	 */
	public.setMap = function(location, coordinates) {
		var self = this,
			lat  = coordinates.lat,
			lng  = coordinates.lng;

		var myLatLng = new google.maps.LatLng(lat, lng);
		var mapOptions = {
			zoom: 14,
			center: myLatLng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};

		var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
		
		// add marker
		var marker = new google.maps.Marker({
			position  : myLatLng, 
			map       : map, 
			draggable : true
		});

		var infowindow = new google.maps.InfoWindow();
		infowindow.setContent(location);
		infowindow.open(map, marker);

		// Get Latitude, Longtitude and Address on Drag Marker
		google.maps.event.addListener(
			marker,
			'mouseup',
			function() {
				var lat = marker.position.lat();
				var lng = marker.position.lng();

				$('input#lat').val(lat);
				$('input#lng').val(lng);

				
				$.get('http://maps.googleapis.com/maps/api/geocode/json',
					{ latlng : lat + ',' + lng, sensor : false }, 
					function(response) {
						var address  = response.results[0];
							street 	 = address.address_components[0].long_name,
							city 	 = address.address_components[2].long_name,
							state 	 = address.address_components[3].long_name,
							country  = address.address_components[4].long_name,
							location = street+' '+city+' '+state+' '+country;

						infowindow.setContent(location);
						infowindow.open(map, marker);

						// set input text location
						$('input#location').val(location);

						// set forecast
						self.setForecast(location);
				});				
			}
		);
	};

	/**
	 * Get weather report
	 * using forecast.io API
	 *
	 * @param string
	 * @return function
	 */
	public.getWeather = function(coordinates, callback) {
		var lat = coordinates.lat,
			lng = coordinates.lng,
			url = 'https://api.forecast.io/forecast/' + this.key + '/' + lat + ',' + lng;

		$.ajax({
			url      : url,
			dataType : 'jsonp',
			success  : function(response) {
				callback(response);
			} 
		});
	};

	/**
	 * Set Current Weather Forecast
	 * to template
	 *
	 * @param string
	 * @return void
	 */
	public.setWeatherInfo = function(location, coordinates, weather) {
		var currentWeather = weather.currently,
			dailyWeather   = weather.daily,
			hourlyWeather  = weather.hourly,
			timezone       = weather.timezone,
			offset         = weather.offset;

		// get current and next hour weather based on offset
		var currentHour = hourlyWeather.data[offset],
			nextHour    = hourlyWeather.data[offset + 1],
			nextDay     = dailyWeather.data[2];

		// hourly and daily summary
		var hourly = hourlyWeather.summary,
			daily  = dailyWeather.summary;

		// current weather forecast template
		var currentTemplate  = $('#forecast-template').html(),
			currentContainer = $('.forecast-container');

		// get current weather forecast information
		var currentHourTime       = currentHour['time'],
			currentHourSummary    = currentHour.summary,
			currentHourTemp       = currentHour.temperature,
			currentHourHumidity   = currentHour.humidity,
			currentHourCloud      = currentHour.cloudCover,
			currentHourVisibility = currentHour.visibility,
			currentHourWind       = currentHour.windSpeed;
			nextHourSummary       = nextHour.summary,
			nextDaySummary        = nextDay.summary;

		// format date and time
		// var dateTime = dateTimeFormat(currentTime); 

		// replace values from current weather template
		var tpl = currentTemplate.replace('[LOCATION]', location),
			tpl = tpl.replace('[TIME]', currentHourTime),
			tpl = tpl.replace('[SUMMARY]', currentHourSummary),
			tpl = tpl.replace('[TEMP]', currentHourTemp),
			tpl = tpl.replace('[HUMIDITY]', currentHourHumidity),
			tpl = tpl.replace('[CLOUD]', currentHourCloud),
			tpl = tpl.replace('[WIND]', currentHourWind);
			tpl = tpl.replace('[LAT]', coordinates.lat),
			tpl = tpl.replace('[LNG]', coordinates.lng),
			tpl = tpl.replace('[NEXT HOUR]', nextHourSummary),
			tpl = tpl.replace('[NEXT DAY]', nextDaySummary),
			tpl = tpl.replace('[HOURLY]', hourly),
			tpl = tpl.replace('[DAILY]', daily);

		// remove template if exist
		currentContainer.find('.forecast-template').remove();

		// append to current weather container
		currentContainer.append(tpl);
	}

	/* Private Methods
	-------------------------------*/
	var dateTimeFormat = function(currentTime) {
		console.log(currentTime);

		var monthNames = new Array(
			'January', 'February', 'March', 
			'April', 'May', 'June', 'July', 'August', 'September', 
			'October', 'November', 'December');

		var d     = new Date(parts[2], parts[1]-1, parts[0]),
			date  = currentTime.getDate(),
			month = currentTime.getMonth(),
			year  = currentTime.getFullYear();

		var formattedTime = time.toTimeString();
		var formattedDate = month + ' ' + date + ', ' + year + ' ' + formattedTime;

		return formattedDate;
	};

	/* Adaptor
	-------------------------------*/
	var weather = c.load();

	/* Events
	-------------------------------*/
	$('form.weather-form').submit(function(e) {
		e.preventDefault();

		var location = $('input#location').val();
		weather.setForecast(location);
	});

});