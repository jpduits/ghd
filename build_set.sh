#!/bin/bash
echo "Get medium project 1001..1666"
php ghdataset search:repositories java 1001..1666 10 "2020-12-01" > dump.txt

echo "Wait..."
sleep 60

echo "Get medium project 1667..2333"
php ghdataset search:repositories java 1667..2333 10 "2020-12-01" >> dump.txt

echo "Wait..."
sleep 60

echo "Get medium project 2334..3000"
php ghdataset search:repositories java 2334..3000 10 "2020-12-01" >> dump.txt

echo "Wait..."
sleep 60

echo "Get low project 0..333"
php ghdataset search:repositories java 0..333 5 "2020-12-01" >> dump.txt

echo "Get low project 10..333"
php ghdataset search:repositories java 10..333 5 "2020-12-01" >> dump.txt

echo "Wait..."
sleep 60

echo "Get low project 334..666"
php ghdataset search:repositories java 334..666 10 "2020-12-01" >> dump.txt

echo "Wait..."
sleep 60

echo "Get low project 667..1000"
php ghdataset search:repositories java 667..1000 10 "2020-12-01" >> dump.txt

echo "Done!"
exit 0
