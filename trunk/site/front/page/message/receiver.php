<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Front_Page_Message_Receiver extends Front_Page {
	/* Constants
	-------------------------------*/
	const CHIKKA_ID    = '05751850a2d15858d8e5ac77676dfe26cdcd30bf24e3b6e6b737e34c7b40641c';
	const CHIKKA_KEY   = '9abf43b9287a1252b5f3699fdd9a55c97112ccc0f6c0bbc3c9e68f1cd4e8fd82';
	const FORECAST_KEY = '4fc0cc27e4f20f7d6449d30a36b728bd';

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title = 'Weather Weather lang';
	protected $_class = 'home';
	protected $_template = '/index.phtml';
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$coordinates = array(
			'lat' => '14.584027',
			'lng' => '121.056992');

		$this->_getForecast($coordinates);

		if (isset($_POST['message_type'])) {
			$messageType = $_POST['messageType'];

			if ($messageType == 'incoming') {
				$message 	  = $_POST['message'];
				$mobileNumber = $_POST['mobile_number'];
				$shortCode    = $_POST['shortcode'];
				$timeStamp    = $_POST['timestamp'];
				$requestId    = $_POST['request_id'];

				// save to database
				$settings = array(
					'message_text'      => $message,
					'message_number'    => $mobileNumber,
					'message_shortcode' => $shortcode,
					'message_timestamp' => $timeStamp,
					'message_requestid' => $requestId,
					'message_created'   => time(),
					'message_updated'   => time());

				$this->_db->insertRow('message', $settings);

				// sample valid request WEATHER<space>NAME/ADDRESS/AGE send to 29290652
				// check if invalid request

				// get WEATHER text
				$first = substr($message, 0, 6);
				if ($first != 'WEATHER') { $this->_replyInvalid(); }

				// check if valid request user info is valid
				$info  = substr($message, 7, -1);
				$count = substr_count($info, '/');

				if ($count != 2) { $this->_replyInvalid(); }

				$userInfo    = explode('/', $string);
				$userName    = $userInfo[0];
				$userAddress = $userInfo[1];
				$userAge     = $userInfo[2];

				$this->_replyForecast($userAddress);
			}
		}

		header('Location: /');
		exit;

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _replyForecast($location) {
		$data = array(
			'address' => $location,
			'sensor'  => false);

		$query = http_build_query($data);
		$url   = 'http://maps.googleapis.com/maps/api/geocode/json?'.$query;
		
		$curl = Eden_Curl::i();
		$response = $curl->setUrl($url)
			->setHeaders('Content-type', 'application/json')
			->getJsonResponse();

		if (isset($response['result'])) {
			if (empty($response['result']) && $response['status'] == 'ZERO RESULTS') {
				$this->_replyInvalid();
			}

			$lat = $response['results'][0]['geometry']['lat'];
			$lng = $response['results'][0]['geometry']['lng'];

			$coordinates = array(
				'lat' => $lat,
				'lng' => $lng);

			$this->_getForecast($coordinates);
		}

		// send http get using curl
		
	}

	protected function _replyInvalid() {

	}

	protected function _getForecast($coordinates) {
		$lat = $coordinates['lat'];
		$lng = $coordinates['lng'];

		$curl = Eden_Curl::i();
		$url  = 'https://api.forecast.io/forecast/'.self::FORECAST_KEY.'/'.$lat.','.$lng;

		// front()->output($url);
		// exit;

		$response = $curl->setUrl($url)
			->setHeaders('Content-type', 'application/json')
			->getJsonResponse();

		// set weather forecast
		$currentWeather = $response['current'];
		$dailyWeather   = $response['daily'];
		$hourlyWeather  = $response['hourly'];
		$timezone       = $response['timezone'];
		$offset         = $response['offset'];

		// get current and next hour weather based on offset
		$currentHour = $hourlyWeather['data'][$offset];
		$nextHour    = $hourlyWeather['data'][$offset + 1];
		$nextDay     = $dailyWeather['data'][2];

		// hourly and daily summary
		$hourly = $hourlyWeather['summary'];
		$daily  = $dailyWeather['summary'];

		// set forecast message
		$currentForecast = $currentHour['summary'];
		$hourlyForecast  = $hourly;
		$dailyForecast   = $daily;

		$forecast = array('current: '.$currentForecast, 'hourly: '.$hourlyForecast, 'daily: '.$dailyForecast);
		$message = implode(', ', $forecast);
		$message .= '\n';
		$message .= 'from: WEATHER.IO';

		



		front()->output($response);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
