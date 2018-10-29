#!/bin/bash
rm dist/tipcars-*.html
php bin/console.php generate:car-chart https://www.tipcars.com/ford-c-max/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/ford-focus/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/honda-civic/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/honda-accord/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/hyundai-i30/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/kia-ceed/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/mazda-3/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/mazda-6/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/opel-astra/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/peugeot-308/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/renault-megane/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/skoda-fabia/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/skoda-octavia/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/skoda-rapid/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/skoda-superb/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/toyota-auris/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/toyota-avensis/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/volkswagen-golf/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/volkswagen-passat/ 2008 -p 99
php bin/console.php generate:car-chart https://www.tipcars.com/volkswagen-polo/ 2008 -p 99
if  [[ $1 = "-u" ]]; then
    surge -d brm.surge.sh -p ./dist/
fi
