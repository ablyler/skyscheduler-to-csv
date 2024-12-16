<?php

$options = getopt('', [
    'domain:',
    'username:',
    'start_date::',
    'end_date::',
    'help'
]);

if (isset($options['help'])) {
    print_usage();
    exit(0);
}

// Required arguments
if (empty($options['domain']) || empty($options['username'])) {
    fwrite(STDERR, "Error: --domain and --username are required.\n");
    print_usage();
    exit(1);
}

$domain = $options['domain'];
$username = $options['username'];

// Optional arguments with defaults
$start_date = $options['start_date'] ?? '2/28/1900';
$end_date = $options['end_date'] ?? '3/28/2050';

// Prompt for password
fwrite(STDOUT, "Enter password for $username@$domain: ");
$password = read_input_line();
if (empty($password)) {
    fwrite(STDERR, "Error: Password cannot be empty.\n");
    exit(1);
}

// Construct URI
$uri = '/FlightLog?SDate=' . urlencode($start_date) . '&EDate=' . urlencode($end_date);

// Fetch flight log HTML
$html = http_request($domain, $uri);

if ($html === false) {
    fwrite(STDERR, "Error: Failed to fetch the flight log from $domain.\n");
    exit(1);
}

// Parse HTML using DOMDocument
$dom = new DOMDocument();
libxml_use_internal_errors(true);  // Suppress HTML parsing warnings
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Select only the flight data rows, excluding header, totals, and remark rows
$rows = $xpath->query("//table[@class='autocolor']/tr[
    not(@id='headers') 
    and not(@id='pageTotal') 
    and not(@id='forward') 
    and not(@id='total')
    and not(@class='remark')
]");

// output to stdout
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fwrite($output, "\xEF\xBB\xBF");

// Print the CSV header
fputcsv($output, [
    "Date", "Aircraft", "From", "To", "No Instr App", "No Ldg", "Airplane SEL", 
    "Airplane MEL", "Cross Country", "Day", "Night", "Actual Instrument", 
    "Simulated Instrument", "Simulator", "Dual Received", "Pilot In Command", 
    "Total Duration", "Remarks"
], ",", '"', "\\");

foreach ($rows as $row) {
    // Extract relevant columns, skipping the first "edit" link column
    $columns = $xpath->query("td[position() > 1]", $row);
    $fields = [];

    foreach ($columns as $column) {
        $value = trim(preg_replace('/\s+/', ' ', $column->textContent));
        $fields[] = ($value === '&nbsp;' || $value === '') ? '' : $value;
    }

    // Ensure we have at least 17 fields (the flight data columns)
    if (count($fields) < 17) {
        continue;
    }

    // Find the first following remark row
    $remarkRows = $xpath->query("following-sibling::tr[@class='remark'][1]", $row);
    $remarks = '';

    if ($remarkRows->length > 0) {
        $remarkRow = $remarkRows->item(0);
        // Remarks are in the second td of the remark row
        $remarkTd = $xpath->query("td[2]", $remarkRow);
        if ($remarkTd->length > 0) {
            $remarks = trim($remarkTd->item(0)->textContent);
        }
    }

    $fields[] = $remarks; // Append remarks to the end

    // Output CSV row
    fputcsv($output, $fields, ",", '"', "\\");
}

fclose($output);

function http_request($domain, $uri, $post_fields = null)
{
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:118.0) Gecko/20100101 Firefox/118.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
    ];

    $ch = curl_init('http://' . $domain . $uri);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookiefile.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookiefile.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($post_fields !== null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    }

    return curl_exec($ch);
}

function print_usage()
{
    fwrite(STDOUT, "Usage: php skyscheduler-to-csv.php --domain DOMAIN --username USERNAME [--start_date \"MM/DD/YYYY\"] [--end_date \"MM/DD/YYYY\"] [--output OUTPUTFILE] [--help]\n");
    fwrite(STDOUT, "\n");
    fwrite(STDOUT, "Required arguments:\n");
    fwrite(STDOUT, "  --domain      The domain of the SkyManager site (e.g. umflyers.skymanager.com)\n");
    fwrite(STDOUT, "  --username    The username for SkyManager\n");
    fwrite(STDOUT, "  (The password will be prompted for securely)\n");
    fwrite(STDOUT, "\n");
    fwrite(STDOUT, "Optional arguments:\n");
    fwrite(STDOUT, "  --start_date  Start date for the flight log (default: 2/28/1900)\n");
    fwrite(STDOUT, "  --end_date    End date for the flight log (default: 3/28/2050)\n");
    fwrite(STDOUT, "  --help        Display this help message\n");
    fwrite(STDOUT, "\n");
}

function read_input_line()
{
    if (function_exists('readline')) {
        return rtrim(readline(""));
    } else {
        return rtrim(fgets(STDIN));
    }
}