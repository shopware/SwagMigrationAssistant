#! /bin/bash

dirname='./tests/testData'

# check if test data already exists
if [ -d "$dirname" ]; then
    echo "Test data already exists. Skipping download."
else
    git clone git@gitlab.shopware.com:shopware/6/services/testdata.git tests/testData
fi

# find connection string in .env file
filename='../../../.env'
n=1
while read line; do
if [[ $line = DATABASE_URL=* ]]
then
    connectionString=$line
fi
n=$((n+1))
done < $filename

echo "Connection String: $connectionString"

# split connection string like DATABASE_URL="mysql://root:root@mysql:3306/shopware"
IFS=':' read -r -a parts <<< "$connectionString"

user=$(echo "${parts[1]}" | sed "s/\/\///g")
password=$(echo "${parts[2]}" | sed 's/@.*//')
host=$(echo "${parts[2]}" | sed 's/.*@//')

echo "MySQL host: $host"
echo "MySQL user: $user"

# import test data
mysql -u"$user" -p"$password" --host "$host" < tests/testData/Migration/sw55.sql
