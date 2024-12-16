# skyscheduler-to-csv

Script to convert SkyScheduler or SkyManager flight log entries to CSV format that can be imported into ForeFlight.

## Overview

This tool allows you to:
- Export flight logs from SkyScheduler/SkyManager systems
- Generate a CSV file compatible with ForeFlight's logbook import feature
- Preserve all flight details including remarks, instrument time, and landing counts

## Importing to ForeFlight

After generating the CSV file, you can import it into your ForeFlight logbook following the instructions in the [ForeFlight Logbook Import Guide](https://support.foreflight.com/hc/en-us/articles/215641157-How-do-I-import-my-digital-logbook-into-my-ForeFlight-account).

The exported CSV matches ForeFlight's required format for proper import of all flight data.

## Usage

```bash
php skyscheduler-to-csv.php --domain <domain> --username <username>
```