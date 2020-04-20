This is no longer being maintained. If you are using this I recomend you checkout something like https://github.com/chillerlan/php-qrcode

# ClearQR for PHP5 & Imagick

Based on QRcode image PHP scripts  version 0.50g (C)2000-2005, Y.Swetake

## Introduction
This program outputs a PNG FORMAT Imagick Image Object "QRcode model 2"
You cannot use several functions of QRcode in this version.
See README.txt .

This version supports QRcode model2 version 1-40.

This program requires PHP5.3x+ & Imagick.


## QR Code data capacity[6]
* Numeric only 	Max. 7,089 characters
* Alphanumeric 	Max. 4,296 characters
* Binary (8 bits) 	Max. 2,953 bytes
* Kanji/Kana 	Max. 1,817 characters

## Error correction capacity
*  Level L 	7% of codewords can be restored.
* Level M 	15% of codewords can be restored.
*  Level Q 	25% of codewords can be restored.
* Level H 	30% of codewords can be restored.

## Data String Examples
* URL

    http://www.example.com

* For Phone Numbers

    TEL:###-###-####

* For SMS (note: messages are limited to 160 characters on SMS

    SMSTO:###-###-####:MESSAGE

* Text

    your text here


DoCoMo Types (Not always supported by Older and Non-DoCoMo phones
-----------------------------------------------------------------
* Email with subject and body

    MATMSG:TO:drewjw81@example.com;SUB:subject;BODY:message;;

* Bookmark

    MEBKM:TITLE:title;URL:http://www.example.com;;

* Contact information

    MECARD:N:Smith,John;ADR:76 9th Avenue, 4th Floor, New York, NY 10011;TEL:+12125551212;EMAIL:jsmith@example.com;;



## Example of block replacement

![Example of Block Image](https://raw.github.com/drewjw81/clear-qr/master/qr.png)

QR Code is registered trademarks of DENSO WAVE INCORPORATED in JAPAN and other countries.
