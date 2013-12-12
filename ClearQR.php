<?php

namespace drewjw81;

/*
  #
  # ClearQR for PHP5 & Imagick - v0.1 By: Andrew Winter
  #
  # Based on QRcode image PHP scripts  version 0.50g (C)2000-2005, Y.Swetake
  #
  #
  #  This program outputs a PNG FORMAT Imagick Image Object "QRcode model 2"
  #  You cannot use a several functions of QRcode in this version.
  #  See README.txt .
  #
  #  This version supports QRcode model2 version 1-40.
  #
  #  This program requires PHP5.3x+ & Imagick.
  #
  # THIS SOFTWARE IS PROVIDED BY Andrew Winter "AS IS" AND ANY EXPRESS OR
  # IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
  # OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
  # IN NO EVENT SHALL Y.Swetake OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
  # INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  # (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  # LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED
  # AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
  # OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
  # USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
  #
  #
  # QR Code data capacity[6]
  # 	Numeric only 	Max. 7,089 characters
  # 	Alphanumeric 	Max. 4,296 characters
  # 	Binary (8 bits) 	Max. 2,953 bytes
  # 	Kanji/Kana 	Max. 1,817 characters
  # Error correction capacity
  # 	Level L 	7% of codewords can be restored.
  # 	Level M 	15% of codewords can be restored.
  # 	Level Q 	25% of codewords can be restored.
  # 	Level H 	30% of codewords can be restored.
  #
  # Data String Notes
  #   For Phone Numbers - "TEL:###-###-####"
  #	  For SMS - "SMSTO:###-###-####:MESSAGE"  (note: messages are limited to 160 characters on SMS
  #	  Text - "your text here"
  #
  #
  #	DoCoMo Types (Not always supported by Older and Non-DoCoMo phones
  #	-----------------------------------------------------------------
  #	Email with subject and body - MATMSG:TO:awinter@daimonsoft.com;SUB:subject;BODY:message;;
  #	Bookmark - MEBKM:TITLE:title;URL:http://www.example.com;;
  #	Contact information - MECARD:N:Smith,John;ADR:76 9th Avenue, 4th Floor, New York, NY 10011;TEL:+12125551212;EMAIL:jsmith@example.com;;
  #
  # QR Code is registered trademarks of DENSO WAVE INCORPORATED in JAPAN and other countries.
 */

class ClearQR {

    protected $dataPath;
    protected $imagePath;
    protected $versionUpperLimit;
    protected $fillColor;
    protected $strokeColor;
    protected $blockImage;
    protected $overlayBlockImage;
    protected $width;

    public function __construct($dataPath, $imagePath, $versionUpperLimit = 40) {
        $this->dataPath = $dataPath;
        $this->imagePath = $imagePath;
        $this->versionUpperLimit = $versionUpperLimit;  //upper limit for version
        $this->fillColor = "black";
        $this->strokeColor = "black";
        $this->blockImage = false;
    }

    public function setColor($fillColorHex, $strokeColorHex = NULL) {
        $this->fillColor = $fillColorHex;
        if ($strokeColorHex) {
            $this->strokeColor = $strokeColorHex;
        } else {
            $this->strokeColor = $fillColorHex;
        }
    }

    public function setBlockImage($imagePath, $overlay = false) {
        $this->blockImage = new \Imagick($imagePath);
        if ($this->blockImage->getImageWidth() > 50 || $this->blockImage->getImageHeight() > 50) {
            $this->blockImage->resizeImage(50, 50, \Imagick::FILTER_POINT, 1);
        }
        $this->overlayBlockImage = $overlay;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    public function generateImage($dataString, $ecc = 'M', $version = 0, $structure_n = NULL, $structure_m = NULL, $parity = NULL, $originaldata = NULL) {
        $dataString = rawurldecode($dataString);
        $data_length = strlen($dataString);
        if ($data_length <= 0) {
            trigger_error("QRcode : Empty data string.", E_USER_ERROR);
            exit;
        }

        $data_counter = 0;
        if ($structure_n > 1 && $structure_n <= 16 && $structure_m > 0 && $structure_m <= 16) {

            $data_value[0] = 3;
            $data_bits[0] = 4;

            $data_value[1] = $structure_m - 1;
            $data_bits[1] = 4;

            $data_value[2] = $structure_n - 1;
            $data_bits[2] = 4;


            $originaldata_length = strlen($originaldata);
            if ($originaldata_length > 1) {
                $parity = 0;
                $i = 0;
                while ($i < $originaldata_length) {
                    $parity = ($parity ^ ord(substr($originaldata, $i, 1)));
                    $i++;
                }
            }

            $data_value[3] = $parity;
            $data_bits[3] = 8;

            $data_counter = 4;
        }

        $data_bits[$data_counter] = 4;

        /*  --- determine encode mode */

        if (preg_match('/[^0-9]/', dataString)) {
            if (preg_match('#[^0-9A-Z $*%+\-./:]#', $dataString)) {

                /*  --- 8bit byte mode */

                $codeword_num_plus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
                    8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8);

                $data_value[$data_counter] = 4;
                $data_counter++;
                $data_value[$data_counter] = $data_length;
                $data_bits[$data_counter] = 8;   /* #version 1-9 */
                $codeword_num_counter_value = $data_counter;

                $data_counter++;
                $i = 0;
                while ($i < $data_length) {
                    $data_value[$data_counter] = ord(substr($dataString, $i, 1));
                    $data_bits[$data_counter] = 8;
                    $data_counter++;
                    $i++;
                }
            } else {

                /* ---- alphanumeric mode */

                $codeword_num_plus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
                    4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4);

                $data_value[$data_counter] = 2;
                $data_counter++;
                $data_value[$data_counter] = $data_length;
                $data_bits[$data_counter] = 9;  /* #version 1-9 */
                $codeword_num_counter_value = $data_counter;


                $alphanumeric_character_hash = array("0" => 0, "1" => 1, "2" => 2, "3" => 3, "4" => 4,
                    "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "A" => 10, "B" => 11, "C" => 12, "D" => 13, "E" => 14,
                    "F" => 15, "G" => 16, "H" => 17, "I" => 18, "J" => 19, "K" => 20, "L" => 21, "M" => 22, "N" => 23,
                    "O" => 24, "P" => 25, "Q" => 26, "R" => 27, "S" => 28, "T" => 29, "U" => 30, "V" => 31,
                    "W" => 32, "X" => 33, "Y" => 34, "Z" => 35, " " => 36, "$" => 37, "%" => 38, "*" => 39,
                    "+" => 40, "-" => 41, "." => 42, "/" => 43, ":" => 44);

                $i = 0;
                $data_counter++;
                while ($i < $data_length) {
                    if (($i % 2) == 0) {
                        $data_value[$data_counter] = $alphanumeric_character_hash[substr($dataString, $i, 1)];
                        $data_bits[$data_counter] = 6;
                    } else {
                        $data_value[$data_counter] = $data_value[$data_counter] * 45 + $alphanumeric_character_hash[substr($dataString, $i, 1)];
                        $data_bits[$data_counter] = 11;
                        $data_counter++;
                    }
                    $i++;
                }
            }
        } else {

            /* ---- numeric mode */

            $codeword_num_plus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
                4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4);

            $data_value[$data_counter] = 1;
            $data_counter++;
            $data_value[$data_counter] = $data_length;
            $data_bits[$data_counter] = 10;   /* #version 1-9 */
            $codeword_num_counter_value = $data_counter;

            $i = 0;
            $data_counter++;
            while ($i < $data_length) {
                if (($i % 3) == 0) {
                    $data_value[$data_counter] = substr($dataString, $i, 1);
                    $data_bits[$data_counter] = 4;
                } else {
                    $data_value[$data_counter] = $data_value[$data_counter] * 10 + substr($dataString, $i, 1);
                    if (($i % 3) == 1) {
                        $data_bits[$data_counter] = 7;
                    } else {
                        $data_bits[$data_counter] = 10;
                        $data_counter++;
                    }
                }
                $i++;
            }
        }
        if (@$data_bits[$data_counter] > 0) {
            $data_counter++;
        }
        $i = 0;
        $total_data_bits = 0;
        while ($i < $data_counter) {
            $total_data_bits+=$data_bits[$i];
            $i++;
        }


        $ecc_character_hash = array(
            "L" => "1",
            "l" => "1",
            "M" => "0",
            "m" => "0",
            "Q" => "3",
            "q" => "3",
            "H" => "2",
            "h" => "2"
        );

        $ec = @$ecc_character_hash[$ecc];

        if (!$ec) {
            $ec = 0;
        }

        $max_data_bits_array = array(
            0, 128, 224, 352, 512, 688, 864, 992, 1232, 1456, 1728,
            2032, 2320, 2672, 2920, 3320, 3624, 4056, 4504, 5016, 5352,
            5712, 6256, 6880, 7312, 8000, 8496, 9024, 9544, 10136, 10984,
            11640, 12328, 13048, 13800, 14496, 15312, 15936, 16816, 17728, 18672,
            152, 272, 440, 640, 864, 1088, 1248, 1552, 1856, 2192,
            2592, 2960, 3424, 3688, 4184, 4712, 5176, 5768, 6360, 6888,
            7456, 8048, 8752, 9392, 10208, 10960, 11744, 12248, 13048, 13880,
            14744, 15640, 16568, 17528, 18448, 19472, 20528, 21616, 22496, 23648,
            72, 128, 208, 288, 368, 480, 528, 688, 800, 976,
            1120, 1264, 1440, 1576, 1784, 2024, 2264, 2504, 2728, 3080,
            3248, 3536, 3712, 4112, 4304, 4768, 5024, 5288, 5608, 5960,
            6344, 6760, 7208, 7688, 7888, 8432, 8768, 9136, 9776, 10208,
            104, 176, 272, 384, 496, 608, 704, 880, 1056, 1232,
            1440, 1648, 1952, 2088, 2360, 2600, 2936, 3176, 3560, 3880,
            4096, 4544, 4912, 5312, 5744, 6032, 6464, 6968, 7288, 7880,
            8264, 8920, 9368, 9848, 10288, 10832, 11408, 12016, 12656, 13328
        );
        /* #--- auto version select */
        if (!$version) {
            $i = 1 + 40 * $ec;
            $j = $i + 39;
            $version = 1;
            while ($i <= $j) {
                if (($max_data_bits_array[$i]) >= $total_data_bits + $codeword_num_plus[$version]) {
                    $max_data_bits = $max_data_bits_array[$i];
                    break;
                }
                $i++;
                $version++;
            }
        } else {
            $max_data_bits = $max_data_bits_array[$version + 40 * $ec];
        }

        if ($version > $this->versionUpperLimit) {
            trigger_error("QRcode : too large version.", E_USER_ERROR);
        }

        $total_data_bits+=$codeword_num_plus[$version];
        $data_bits[$codeword_num_counter_value]+=$codeword_num_plus[$version];

        $max_codewords_array = array(0, 26, 44, 70, 100, 134, 172, 196, 242,
            292, 346, 404, 466, 532, 581, 655, 733, 815, 901, 991, 1085, 1156,
            1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185, 2323, 2465,
            2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706);

        $max_codewords = $max_codewords_array[$version];
        $max_modules_1side = 17 + ($version << 2);

        $matrix_remain_bit = array(0, 0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3,
            4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0);

        /* ---- read version ECC data file */

        $byte_num = $matrix_remain_bit[$version] + ($max_codewords << 3);
        $filename = $this->dataPath . "/qrv" . $version . "_" . $ec . ".dat";
        $fp1 = fopen($filename, "rb");
        $matx = fread($fp1, $byte_num);
        $maty = fread($fp1, $byte_num);
        $masks = fread($fp1, $byte_num);
        $fi_x = fread($fp1, 15);
        $fi_y = fread($fp1, 15);
        $rs_ecc_codewords = ord(fread($fp1, 1));
        $rso = fread($fp1, 128);
        fclose($fp1);

        $matrix_x_array = unpack("C*", $matx);
        $matrix_y_array = unpack("C*", $maty);
        $mask_array = unpack("C*", $masks);

        $rs_block_order = unpack("C*", $rso);

        $format_information_x2 = unpack("C*", $fi_x);
        $format_information_y2 = unpack("C*", $fi_y);

        $format_information_x1 = array(0, 1, 2, 3, 4, 5, 7, 8, 8, 8, 8, 8, 8, 8, 8);
        $format_information_y1 = array(8, 8, 8, 8, 8, 8, 8, 8, 7, 5, 4, 3, 2, 1, 0);

        $max_data_codewords = ($max_data_bits >> 3);

        $filename = $this->dataPath . "/rsc" . $rs_ecc_codewords . ".dat";
        $fp0 = fopen($filename, "rb");
        $i = 0;
        while ($i < 256) {
            $rs_cal_table_array[$i] = fread($fp0, $rs_ecc_codewords);
            $i++;
        }
        fclose($fp0);

        /*  --- set terminator */

        if ($total_data_bits <= $max_data_bits - 4) {
            $data_value[$data_counter] = 0;
            $data_bits[$data_counter] = 4;
        } else {
            if ($total_data_bits < $max_data_bits) {
                $data_value[$data_counter] = 0;
                $data_bits[$data_counter] = $max_data_bits - $total_data_bits;
            } else {
                if ($total_data_bits > $max_data_bits) {
                    if ($version < $this->versionUpperLimit) {
                        return $this->generateImage($dataString, $ecc, $version+1, $structure_n, $structure_m, $parity, $originaldata);
                    }else{
                        trigger_error("QRcode : Overflow error", E_USER_ERROR);
                        exit;
                    }
                }
            }
        }

        /* ----divide data by 8bit */

        $i = 0;
        $codewords_counter = 0;
        $codewords[0] = 0;
        $remaining_bits = 8;

        while ($i <= $data_counter) {
            $buffer = @$data_value[$i];
            $buffer_bits = @$data_bits[$i];

            $flag = 1;
            while ($flag) {
                if ($remaining_bits > $buffer_bits) {
                    $codewords[$codewords_counter] = ((@$codewords[$codewords_counter] << $buffer_bits) | $buffer);
                    $remaining_bits-=$buffer_bits;
                    $flag = 0;
                } else {
                    $buffer_bits-=$remaining_bits;
                    $codewords[$codewords_counter] = (($codewords[$codewords_counter] << $remaining_bits) | ($buffer >> $buffer_bits));

                    if ($buffer_bits == 0) {
                        $flag = 0;
                    } else {
                        $buffer = ($buffer & ((1 << $buffer_bits) - 1) );
                        $flag = 1;
                    }

                    $codewords_counter++;
                    if ($codewords_counter < $max_data_codewords - 1) {
                        $codewords[$codewords_counter] = 0;
                    }
                    $remaining_bits = 8;
                }
            }
            $i++;
        }
        if ($remaining_bits != 8) {
            $codewords[$codewords_counter] = $codewords[$codewords_counter] << $remaining_bits;
        } else {
            $codewords_counter--;
        }

        /* ----  set padding character */

        if ($codewords_counter < $max_data_codewords - 1) {
            $flag = 1;
            while ($codewords_counter < $max_data_codewords - 1) {
                $codewords_counter++;
                if ($flag == 1) {
                    $codewords[$codewords_counter] = 236;
                } else {
                    $codewords[$codewords_counter] = 17;
                }
                $flag = $flag * (-1);
            }
        }

        /* ---- RS-ECC prepare */

        $i = 0;
        $j = 0;
        $rs_block_number = 0;
        $rs_temp[0] = "";

        while ($i < $max_data_codewords) {

            $rs_temp[$rs_block_number].=chr($codewords[$i]);
            $j++;

            if ($j >= $rs_block_order[$rs_block_number + 1] - $rs_ecc_codewords) {
                $j = 0;
                $rs_block_number++;
                $rs_temp[$rs_block_number] = "";
            }
            $i++;
        }


        /*
          #
          # RS-ECC main
          #
         */

        $rs_block_number = 0;
        $rs_block_order_num = count($rs_block_order);

        while ($rs_block_number < $rs_block_order_num) {

            $rs_codewords = $rs_block_order[$rs_block_number + 1];
            $rs_data_codewords = $rs_codewords - $rs_ecc_codewords;

            $rstemp = $rs_temp[$rs_block_number] . str_repeat(chr(0), $rs_ecc_codewords);
            $padding_data = str_repeat(chr(0), $rs_data_codewords);

            $j = $rs_data_codewords;
            while ($j > 0) {
                $first = ord(substr($rstemp, 0, 1));

                if ($first) {
                    $left_chr = substr($rstemp, 1);
                    $cal = $rs_cal_table_array[$first] . $padding_data;
                    $rstemp = $left_chr ^ $cal;
                } else {
                    $rstemp = substr($rstemp, 1);
                }

                $j--;
            }

            $codewords = array_merge($codewords, unpack("C*", $rstemp));

            $rs_block_number++;
        }

        /* ---- flash matrix */

        $i = 0;
        while ($i < $max_modules_1side) {
            $j = 0;
            while ($j < $max_modules_1side) {
                $matrix_content[$j][$i] = 0;
                $j++;
            }
            $i++;
        }

        /* --- attach data */

        $i = 0;
        while ($i < $max_codewords) {
            $codeword_i = $codewords[$i];
            $j = 8;
            while ($j >= 1) {
                $codeword_bits_number = ($i << 3) + $j;
                $matrix_content[$matrix_x_array[$codeword_bits_number]][$matrix_y_array[$codeword_bits_number]] = ((255 * ($codeword_i & 1)) ^ $mask_array[$codeword_bits_number] );
                $codeword_i = $codeword_i >> 1;
                $j--;
            }
            $i++;
        }

        $matrix_remain = $matrix_remain_bit[$version];
        while ($matrix_remain) {
            $remain_bit_temp = $matrix_remain + ( $max_codewords << 3);
            $matrix_content[$matrix_x_array[$remain_bit_temp]][$matrix_y_array[$remain_bit_temp]] = ( 255 ^ $mask_array[$remain_bit_temp] );
            $matrix_remain--;
        }

        #--- mask select

        $min_demerit_score = 0;
        $hor_master = "";
        $ver_master = "";
        $k = 0;
        while ($k < $max_modules_1side) {
            $l = 0;
            while ($l < $max_modules_1side) {
                $hor_master = $hor_master . chr($matrix_content[$l][$k]);
                $ver_master = $ver_master . chr($matrix_content[$k][$l]);
                $l++;
            }
            $k++;
        }
        $i = 0;
        $all_matrix = $max_modules_1side * $max_modules_1side;
        while ($i < 8) {
            $demerit_n1 = 0;
            $ptn_temp = array();
            $bit = 1 << $i;
            $bit_r = (~$bit) & 255;
            $bit_mask = str_repeat(chr($bit), $all_matrix);
            $hor = $hor_master & $bit_mask;
            $ver = $ver_master & $bit_mask;

            $ver_shift1 = $ver . str_repeat(chr(170), $max_modules_1side);
            $ver_shift2 = str_repeat(chr(170), $max_modules_1side) . $ver;
            $ver_shift1_0 = $ver . str_repeat(chr(0), $max_modules_1side);
            $ver_shift2_0 = str_repeat(chr(0), $max_modules_1side) . $ver;
            $ver_or = chunk_split(~($ver_shift1 | $ver_shift2), $max_modules_1side, chr(170));
            $ver_and = chunk_split(~($ver_shift1_0 & $ver_shift2_0), $max_modules_1side, chr(170));

            $hor = chunk_split(~$hor, $max_modules_1side, chr(170));
            $ver = chunk_split(~$ver, $max_modules_1side, chr(170));
            $hor = $hor . chr(170) . $ver;

            $n1_search = "/" . str_repeat(chr(255), 5) . "+|" . str_repeat(chr($bit_r), 5) . "+/";
            $n3_search = chr($bit_r) . chr(255) . chr($bit_r) . chr($bit_r) . chr($bit_r) . chr(255) . chr($bit_r);

            $demerit_n3 = substr_count($hor, $n3_search) * 40;
            $demerit_n4 = floor(abs(( (100 * (substr_count($ver, chr($bit_r)) / ($byte_num)) ) - 50) / 5)) * 10;


            $n2_search1 = "/" . chr($bit_r) . chr($bit_r) . "+/";
            $n2_search2 = "/" . chr(255) . chr(255) . "+/";
            $demerit_n2 = 0;
            preg_match_all($n2_search1, $ver_and, $ptn_temp);
            foreach ($ptn_temp[0] as $str_temp) {
                $demerit_n2+= ( strlen($str_temp) - 1);
            }
            $ptn_temp = array();
            preg_match_all($n2_search2, $ver_or, $ptn_temp);
            foreach ($ptn_temp[0] as $str_temp) {
                $demerit_n2+= ( strlen($str_temp) - 1);
            }
            $demerit_n2*=3;

            $ptn_temp = array();

            preg_match_all($n1_search, $hor, $ptn_temp);
            foreach ($ptn_temp[0] as $str_temp) {
                $demerit_n1+= ( strlen($str_temp) - 2);
            }

            $demerit_score = $demerit_n1 + $demerit_n2 + $demerit_n3 + $demerit_n4;

            if ($demerit_score <= $min_demerit_score || $i == 0) {
                $mask_number = $i;
                $min_demerit_score = $demerit_score;
            }

            $i++;
        }

        $mask_content = 1 << $mask_number;

        # --- format information

        $format_information_value = (($ec << 3) | $mask_number);
        $format_information_array = array("101010000010010", "101000100100101",
            "101111001111100", "101101101001011", "100010111111001", "100000011001110",
            "100111110010111", "100101010100000", "111011111000100", "111001011110011",
            "111110110101010", "111100010011101", "110011000101111", "110001100011000",
            "110110001000001", "110100101110110", "001011010001001", "001001110111110",
            "001110011100111", "001100111010000", "000011101100010", "000001001010101",
            "000110100001100", "000100000111011", "011010101011111", "011000001101000",
            "011111100110001", "011101000000110", "010010010110100", "010000110000011",
            "010111011011010", "010101111101101");
        $i = 0;
        while ($i < 15) {
            $content = substr($format_information_array[$format_information_value], $i, 1);

            $matrix_content[$format_information_x1[$i]][$format_information_y1[$i]] = $content * 255;
            $matrix_content[$format_information_x2[$i + 1]][$format_information_y2[$i + 1]] = $content * 255;
            $i++;
        }


        $mib = $max_modules_1side + 8;

        $this->imagePath = $this->imagePath . "/qrv" . $version . ".png";

        $image = new \Imagick($this->imagePath);
        $draw = new \ImagickDraw();

        if ($this->fillColor != "black") {
            $clut = new \Imagick();
            $clut->newImage(1, 1, new \ImagickPixel($this->fillColor));
            $image->clutImage($clut);
            $clut->destroy();
            unset($clut);
        }
        $draw->setFillColor(new \ImagickPixel($this->fillColor));
        $draw->setStrokeColor(new \ImagickPixel($this->strokeColor));

        $i = 4;
        $mxe = 4 + $max_modules_1side;
        $ii = 0;
        while ($i < $mxe) {
            $j = 4;
            $jj = 0;
            while ($j < $mxe) {
                if ($matrix_content[$ii][$jj] & $mask_content) {
                    $draw->point($i, $j);
                }
                $j++;
                $jj++;
            }
            $i++;
            $ii++;
        }

        $image->drawImage($draw);

        if ($this->blockImage) {
            $blockSize = $this->blockImage->getImageWidth();
            $image->resizeImage($mib * $blockSize, $mib * $blockSize, \Imagick::FILTER_POINT, 1);

            $testPixel = new \ImagickPixel($this->fillColor);
            for ($x = 0; $x < $image->getImageWidth(); $x+=$blockSize) {
                for ($y = 0; $y < $image->getImageHeight(); $y+=$blockSize) {
                    if ($testPixel->isSimilar($image->getImagePixelColor($x, $y), 1)) {
                        $image->compositeImage($this->blockImage, ($this->overlayBlockImage) ? \Imagick::COMPOSITE_OVER : \Imagick::COMPOSITE_REPLACE, $x, $y);
                    }
                }
            }
        }

        $geo = $image->getImageGeometry();
        if(!empty($this->width) && $geo['width'] <= $this->width ){

            if (!$this->width) {
                $this->width  = 0;
            } else if($this->width  < $image->getImageWidth()) {
                $this->width  = $image->getImageWidth();
            }
            $remaining_pixels = $this->width  % $geo['width'];

            $fit_width = $this->width  - $remaining_pixels;

            $image->resizeImage($fit_width,$fit_width, \Imagick::FILTER_POINT, 1); //FILTER_POINT

            $tmp = new \Imagick();
            $tmp->newImage($this->width , $this->width , 'none', 'png');
            $tmp->compositeImage($image, \Imagick::COMPOSITE_OVER, floor($remaining_pixels/2), floor($remaining_pixels/2));
            $image->destroy();
            $image = &$tmp;
        }


        return $image;
    }

}

?>