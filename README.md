# [H&O](http://www.h-o.nl) Shell Import

With this module it is very easy to run a profile through the shell. This method is meant to be used to automate the
Dataflow profiles. This module doesn't work with the new ImportExport module.

### Requirements
- It is build om Magento, so you should have a working Magento installation ;)
- The script is ran through the shell, so that means that you should have shell access.
- Make sure you are allowed to access the shell through php and can run PHP there. We use [`shell_exec`](http://nl3.php.net/shell_exec)
to import a batch (with the normal browser import, that is done using AJAX).

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

### Help, I'm having a problem!
1. Does the script work in the browser? No? It is a problem in the import, you should fix that first.
2. Does your server has the right amount of memory available and has access to the shell_exec method?
3. Take a look at the previous issues: https://github.com/ho-nl/Ho_ShellImport/issues?page=1&state=closed
4. If that doesn't help, create an issue yourself

### About us
We at H&O build high quality [magento webshops](https://www.h-o.nl/magento-webshops) with a focus on technique. We like sharing our knowledge and interacting with the magento community and making e-commerce even greater.
