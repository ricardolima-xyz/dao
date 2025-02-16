
# Checking if phpunit binary exists. If not, download it
if [[ ! -f phpunit ]]
then
    wget -O phpunit https://phar.phpunit.de/phpunit-9.phar --no-check-certificate
    chmod +x phpunit
fi

# Executing phpunit
php phpunit --testdox --colors=always --configuration ./phpunit.xml