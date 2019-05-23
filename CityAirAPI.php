<?php

	class CityAirAPI
	{
		private $baseUrl = 'https://cityair.io/backend-api/request.php?map=/DevicesApi/';
		public  $login;
		public  $password;

		private function getData($url, $args = null) {
			$options = [
			  CURLOPT_URL            => $url,
			  //			  CURLOPT_USERPWD => "$this->login:$this->password",
			  //			  CURLOPT_XOAUTH2_BEARER => "$this->token",
			  CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Accept: application/json'
			  ],
			  CURLOPT_CUSTOMREQUEST  => 'POST',
			  CURLOPT_RETURNTRANSFER => true,
			];
			if ($args) {
				$data                        = [
				  "Auth"   => [
					"User" => $this->login,
					"Pwd"  => $this->password
				  ],
				  "Filter" => $args,
				];
				$json_args                   = json_encode($data);
				$options[CURLOPT_POSTFIELDS] = $json_args;
			}

			$ch = curl_init();
			curl_setopt_array($ch, $options);

			$content = curl_exec($ch);
			$status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			$out = json_decode($content);

			if ($status > 399 || $out->IsError) {
				throw new Exception("Exception $status: $content");
			}
			return $out;
		}

		public function getAllDevices() {
			$url      = $this->baseUrl . 'GetDevices';
			$data     = [
			  "DeviceId" => ''
			];
			$response = $this->getData($url, $data);
			return $response;
		}

		public function getDeviceById($deviceId) {
			$devices = $this->getAllDevices();
			foreach ($devices->Result->Devices as $device) {
				if ($device->DeviceId == $deviceId) {
					$ourDevice = $device;
					break;
				}
			}
			if ($ourDevice) {
				foreach ($ourDevice->DeviceTagIds as $tagId) {
					foreach ($devices->Result->DeviceTags as $deviceTag) {
						if ($deviceTag->DeviceTagId == $tagId) {
							$tags[] = $deviceTag->Title;
							continue 2;
						}
					}
				}
				$ourDevice->DeviceTags = $tags;
				unset ($ourDevice->DeviceTagIds);

				foreach ($devices->Result->DeviceSources as $deviceSource) {
					if ($deviceSource->SourceId == $ourDevice->SourceId) {
						$ourDevice->Source = $deviceSource;
						break;
					}
				}
				unset ($ourDevice->SourceId);
				return $ourDevice;
			} else {
				return false;
			}
		}

		public function getPackets($args) {
			$url      = $this->baseUrl . 'GetPackets';
			$response = $this->getData($url, $args);
			return $response;
		}
	}