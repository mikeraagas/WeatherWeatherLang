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
	const CHIKKA_ID        = '05751850a2d15858d8e5ac77676dfe26cdcd30bf24e3b6e6b737e34c7b40641c';
	const CHIKKA_KEY       = '9abf43b9287a1252b5f3699fdd9a55c97112ccc0f6c0bbc3c9e68f1cd4e8fd82';
	const CHIKKA_SHORTCODE = '29290652'; 

	const FORECAST_KEY = '4fc0cc27e4f20f7d6449d30a36b728bd';

	const INVALID_REQUEST  = 'Invalid request format please try again later. Thank you';

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title = 'Weather Weather lang';
	protected $_class = 'home';
	protected $_template = '/index.phtml';

	protected $_mobileNumber = null;
	protected $_requestId    = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		if (isset($_POST['message_type'])) {
			if ($_POST['message_type'] == 'incoming') {
				$message 	  = $_POST['message'];
				$shortCode    = $_POST['shortcode'];
				$timeStamp    = $_POST['timestamp'];
				$requestId    = $_POST['request_id'];
				
				$this->_mobileNumber = $_POST['mobile_number'];
				$this->_requestId    = $_POST['request_id'];

				// save to database
				// $settings = array(
				// 	'message_text'      => $message,
				// 	'message_number'    => $this->_mobileNumber,
				// 	'message_shortcode' => $shortCode,
				// 	'message_timestamp' => $timeStamp,
				// 	'message_requestid' => $requestId,
				// 	'message_created'   => time(),
				// 	'message_updated'   => time());

				// $this->_db->insertRow('message', $settings);

				// set reply message
				$replyMessage = '';

				// sample valid request WEATHER<space>NAME/ADDRESS/AGE send to 29290652
				// check if invalid request

				// get WEATHER text
				$first = substr($message, 0, 7);
				if ($first != 'WEATHER') {
					$this->_sendSms(self::INVALID_REQUEST);
				}

				// check if valid request user info is valid
				$info  = substr($message, 8);
				$count = substr_count($info, '/');

				// if request user info is not valid
				if ($count != 2) { $this->_sendSms(self::INVALID_REQUEST); }

				// if valid request
				// get and send weather forecast
				$userInfo    = explode('/', $info);
				$userName    = $userInfo[0];
				$userAddress = $userInfo[1];
				$userAge     = $userInfo[2];

				$message = $this->_setForecastMessage($userAddress);
				if ($message == '') { $message = self::INVALID_REQUEST; }

				front()->output($message);
				exit;

				$this->_sendSms($message);
			}

			if ($_POST['message_type'] == 'outgoing') {
				front()->output($_POST);
				exit;
			}
		}

		front()->output('error');
		exit;

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _setForecastMessage($location) {
		$data = array(
			'address' => $location,
			'sensor'  => false);

		$query   = http_build_query($data);
		$url     = 'http://maps.googleapis.com/maps/api/geocode/json?'.$query;
		$message = '';
		
		$curl = Eden_Curl::i();
		$response = $curl->setUrl($url)
			->setHeaders('Content-type', 'application/json')
			->getJsonResponse();

		if (isset($response['results'])) {
			if (empty($response['results']) && $response['status'] == 'ZERO RESULTS') {
				$this->_sendSms(self::INVALID_REQUEST);
			}

			$lat = $response['results'][0]['geometry']['location']['lat'];
			$lng = $response['results'][0]['geometry']['location']['lng'];

			$coordinates = array(
				'lat' => $lat,
				'lng' => $lng);

			$message = $this->_getForecast($coordinates);
		}

		return $message;
	}

	protected function _getForecast($coordinates) {
		$lat = $coordinates['lat'];
		$lng = $coordinates['lng'];

		$curl = Eden_Curl::i();
		$url  = 'https://api.forecast.io/forecast/'.self::FORECAST_KEY.'/'.$lat.','.$lng;

		$response = $curl->setUrl($url)
			->setHeaders('Content-type', 'application/json')
			->getJsonResponse();

		// set weather forecast
		$currentWeather = $response['currently'];
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

		$forecast = array('Current: '.$currentForecast, 'Hourly: '.$hourlyForecast, 'Daily: '.$dailyForecast);
		$message = implode(', ', $forecast);
		$message .= ' Weather Forecast from: WEATHER.IO';

		return $message;
	}

	protected function _sendSms($message) {
		$url      = 'https://post.chikka.com/smsapi/request';
		$uniqueId = uniqid();

		$fields = array(
			'message_type'  => 'REPLY',
			'mobile_number' => $this->_mobileNumber,
			'shortcode'     => self::CHIKKA_SHORTCODE,
			'request_id'    => $this->_requestId,
			'message_id'    => $uniqueId,
			'message'       => $message,
			'request_cost'  => 'FREE',
			'client_id'     => self::CHIKKA_ID,
			'secret_key'    => self::CHIKKA_KEY);

		$postFields = http_build_query($fields);

		$curl = Eden_Curl::i();

		$result = $curl->setUrl($url)
			->setHeaders('Content-type', 'application/json')
	        ->setPost(true)
			->setPostFields($postFields)
			->getJsonResponse();

		front()->output($result);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
