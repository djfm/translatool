<?php

namespace GitHub;

class Client
{
	private function github_query($path)
	{
		if($path[0]!='/')$path = "/$path";
		$url = "https://api.github.com$path";
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$body = curl_exec($ch);
		curl_close($ch);

		if($body)
		{
			return json_decode($body, true);
		}
		else
		{
			return false;
		}
	}

	public function dir($owner, $repo, $path='/')
	{
		return $this->github_query("/repos/$owner/$repo/contents/$path");
	}
}

