# heraclitus
Simple code to track Nigerian Stock Exchange (NSE) daily trades.

You have to set up a cron job to scrape the stock data.
Please use responsively (at least mon-fri by 4pm).

0 16 * * 1-5 /usr/bin/wget --quiet --output-document=/dev/null http://path-to-heraclitus/cron.php?interval=daily

See demo at https://olibe.nu/apps/heraclitus
