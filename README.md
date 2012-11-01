# H&O Shell Import

With this module it is very easy to run a profile through the shell. This method is meant to be used to automate the
Dataflow profiles. This module doesn't work with the new ImportExport module.

### Usage example:
```SHELL
cd /path/to/magento/shell/
php import.php -action exec -profile 1234
```

You can find the ID for the profiles:
- `Admin Panel > Import/Export > Dataflow - Profiles`
- `Admin Panel > Import/Export > Dataflow - Advanced Profiles`


#### Example automatic import script that is called by a regular cron:

```shell
# Path to shell directory so we have a good starting point.
SHELLDIR=`echo $0 | sed 's/import\.sh//g'`

echo Extract csv from the archive...
unzip "$SHELLDIR"../media/archive.zip -d "$SHELLDIR"../var/import/files
mv "$SHELLDIR"../var/import/files/IMPORT.CSV "$SHELLDIR"../var/import/import.csv
rm -rf "$SHELLDIR"../var/import/files

echo Change index mode to Manual Update...
php "$SHELLDIR"indexer.php --mode-manual catalog_product_attribute
php "$SHELLDIR"indexer.php --mode-manual catalog_product_price
php "$SHELLDIR"indexer.php --mode-manual catalogsearch_fulltext
php "$SHELLDIR"indexer.php --mode-manual cataloginventory_stock
php "$SHELLDIR"indexer.php --mode-manual catalog_url
php "$SHELLDIR"indexer.php --mode-manual catalog_product_flat
php "$SHELLDIR"indexer.php --mode-manual catalog_category_flat
php "$SHELLDIR"indexer.php --mode-manual catalog_category_product

echo Empty cache directory...
rm -rf "$SHELLDIR"../var/cache/

echo Importing...
php "$SHELLDIR"import.php -action exec -profile 9

echo Reindexing...
php "$SHELLDIR"indexer.php -reindexall

echo Change index mode to Update on Save...
php "$SHELLDIR"indexer.php --mode-realtime catalog_product_attribute
php "$SHELLDIR"indexer.php --mode-realtime catalog_product_price
php "$SHELLDIR"indexer.php --mode-realtime catalogsearch_fulltext
php "$SHELLDIR"indexer.php --mode-realtime cataloginventory_stock
php "$SHELLDIR"indexer.php --mode-realtime catalog_url
php "$SHELLDIR"indexer.php --mode-realtime catalog_product_flat
php "$SHELLDIR"indexer.php --mode-realtime catalog_category_flat
php "$SHELLDIR"indexer.php --mode-realtime catalog_category_product

echo Empty cache directory...
rm -rf "$SHELLDIR"../var/cache/

echo Done
```