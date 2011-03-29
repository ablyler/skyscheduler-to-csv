<?php

define('SKYSCHEDULER_DOMAIN', ''); // example: umflyers.skyscheduler.com
define('USERNAME', '');
define('PASSWORD', '');

// login
http_request('/?ReturnUrl=%2fFlightLog.aspx%3fSDate%3d2%252f28%252f1900%26EDate%3d3%252f28%252f2050', 'ctl00%24ctl00%24ctl00%24ctl03=ctl00%24ctl00%24ctl00%24body%24bannerbody%24rightcolumn%24ctl01%7C&__LASTFOCUS=&ctl00_ctl00_ctl00_ctl03_HiddenField=&__EVENTTARGET=ctl00%24ctl00%24ctl00%24body%24bannerbody%24rightcolumn%24Login&__EVENTARGUMENT=&__VIEWSTATE=%2FwEPDwUKMTYyMTkxNDIxMGQYAQUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFgEFOGN0bDAwJGN0bDAwJGN0bDAwJGJvZHkkYmFubmVyYm9keSRyaWdodGNvbHVtbiRSZW1lbWJlck1l%2B37f3%2Fbu6UZqkThPJ9OC84WiiDk%3D&__EVENTVALIDATION=%2FwEWBQKpgafZCwLIjrzlAwLD5tLDAgLCi4zCBwLUlYL6Bks%2FT65VkfgZs%2FuZuT%2B9swfNROb9&ctl00%24ctl00%24ctl00%24body%24bannerbody%24rightcolumn%24Username=' . urlencode(USERNAME) . '&ctl00%24ctl00%24ctl00%24body%24bannerbody%24rightcolumn%24Password=' . urlencode(PASSWORD) . '&__ASYNCPOST=true&');

// obtain the html of the flight log
$html = http_request('/FlightLog.aspx?SDate=2%2f28%2f1900&EDate=3%2f28%2f2050');

// replace line breaks with spaces
$html = str_replace('<br>', ' ', $html);

// obtain all the log entries
preg_match_all('/edit<\/a><\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<td align="center">([^<]*)<\/td>\s+<\/tr>(\s+<tr class="small [^\"]+">\s+<td>&nbsp\;<\/td>\s+<td colspan=4>)?([^<]*)?/', $html, $matches, PREG_SET_ORDER);

// output the header
echo "date\taircraft\tfrom\tto\tinstr_app\tldg\tairplane_sel\tairplane_mel\tcross_country\tday\tnight\tactual_instrument\tsimulated_instrument\tsimulator\tdual_received\tpilot_in_command\ttotal_duration\tremarks\n";

// parse each log entires
foreach ($matches as &$fields)
{
	// remove the field that contains the entire string of HTML
	unset($fields[0]);

	// hack to remove field that contains html
	unset($fields[18]);

	// clean up the format of each fields
	foreach ($fields as &$field)
	{
		$field = trim($field);
		$field = str_replace('&nbsp;', ' ', $field);
		$field = preg_replace('/\s+/', ' ', $field);
	}

	// seperate the fields by tabs
	echo implode("\t", $fields) . "\n";
}

function http_request($uri, $post_fields = null)
{
	$headers = array(
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_7) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.16 Safari/534.24',
		'Accept: application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
		'Accept-Language: en-US,en;q=0.8',
		'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	);

	$ch = curl_init('http://' . SKYSCHEDULER_DOMAIN . $uri);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookiefile.txt'); 
	curl_setopt($ch, CURLOPT_COOKIEJAR,  '/tmp/cookiefile.txt');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if ($post_fields !== null)
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
	}

	return curl_exec($ch);
}