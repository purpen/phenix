<?php
/**
 * 读取图片的exif元数据
 *
 */
class Doggy_Util_Exif {
    private static $fields=array(
    				 'Make' => array('description' => "相机厂商",   'type' => 'text'),
                     'Model' => array('description' => "相机型号", 'type' => 'text'),
                     'ImageType' => array('description' =>  "图像类型", 'type' => 'text'),
                     'ImageDescription' => array('description' => "图像描述", 'type' => 'text'),
                     'FileSize' => array('description' => "文件大小" , 'type' => 'number'),
                     'DateTime' => array('description' => "修改时间", 'type' => 'date'),
                     'DateTimeOriginal' => array('description' => "拍摄时间", 'type' => 'date'),
                     'DateTimeDigitized' => array('description' => "数字化时间", 'type' => 'date'),
                     'ExifImageWidth' => array('description' => "宽度", 'type' => 'number'),
                     'ExifImageLength' => array('description' => "高度", 'type' => 'number'),
                     'xResolution' => array('description' => "X分辨率", 'type' => 'number'),
                     'yResolution' => array('description' => "Y分辨率", 'type' => 'number'),
                     'ShutterSpeedValue' => array('description' => "快门速度", 'type' => 'number'),
                     'ExposureTime' => array('description' => "曝光时间", 'type' => 'number'),
                     'FocalLength' => array('description' => "焦距", 'type' => 'number'),
                     'FocalLengthIn35mmFilm' => array('description' => "35mm等价焦距", 'type' => 'number'),
                     'ApertureValue' => array('description' => "光圈", 'type' => 'number'),
                     'FNumber' => array('description' => "F-Number", 'type' => 'number'),
                     'ISOSpeedRatings' => array('description' => "ISO", 'type' => 'number'),
                     'ExposureBiasValue' => array('description' => "曝光补偿", 'type' => 'number'),
                     'ExposureMode' => array('description' => "曝光模式", 'type' => 'number'),
                     'MeteringMode' => array('description' => "测光模式", 'type' => 'number'),
                     'Flash' => array('description' => "闪光灯", 'type' => 'number'),
                     'UserComment' => array('description' => "用户注释", 'type' => 'text'),
                     'ColorSpace' => array('description' => "色彩空间", 'type' => 'number'),
                     'SensingMethod' => array('description' => "Sensing Method", 'type' => 'number'),
                     'WhiteBalance' => array('description' => "白平衡", 'type' => 'number'),
                     'Orientation' => array('description' => "Camera Orientation", 'type' => 'number'),
                     'Copyright' => array('description' => "版权信息", 'type' => 'text'),
                     'Artist' => array('description' => "Artist", 'type' => 'text')
    );
    

	/**
     * Get the EXIF data from an image, process it, and return it.
     *
     * @param string $$imageFile
     * @return array Array of EXIF attributes.
     */
    public static function getExifData($imageFile){
        $data = self::getExifRawData($imageFile);
        $result = array();
        foreach ($data as $k=>$v){
            $result[$k] = array('title'=>self::getExifFieldDescription($k),'value'=>self::getExifFieldValue($k,$v));        
        }
        return $result;
    }

    /**
     * 
     * @param string $imageFile
     * @return array
     */
    public static function getExifRawData($imageFile){
        $exif = @exif_read_data($imageFile, 0, false);
        // See if we got any attributes back.
        $results = array();
        if ($exif) {
            foreach (self::$fields as $field => $data) {
                $value = isset($exif[$field]) ? $exif[$field] : '';
                
                // Don't store empty fields.
                if ($value === '') {
                    continue;
                }

                // If the field is a date field, convert the value to a
                // timestamp.
                if ($data['type'] == 'date') {
                    @list($ymd, $hms) = explode(' ', $value, 2);
                    @list($year, $month, $day) = explode(':', $ymd, 3);
                    if (!empty($hms) && !empty($year) && !empty($month) && !empty($day)) {
                        $time = "$month/$day/$year $hms";
                        $value = strtotime($time);
                    }
                }

                $results[$field] = $value;
            }
        }

        return $results;
    }
    /**
     * 转换exif field的描述性的文字
     */
    public static function getExifFieldDescription($field){
        return isset(self::$fields[$field]['description']) ? self::$fields[$field]['description']: $field;
    }
    /**
     * Convert an exif field into human-readable form.
     *
     * @param string $field  The name of the field to translate.
     * @param string $data   The data value to translate.
     *
     * @return string  The converted data.
     */
    public static function getExifFieldValue($field, $data){
        
        switch ($field) {
        case 'ExposureMode':
            switch ($data) {
            case 0:
                return "Easy shooting";
            case 1:
                return "Program";
            case 2:
                return "Tv-priority";
            case 3:
                return "Av-priority";
            case 4:
                return "Manual";
            case 5:
                return "A-DEP";
            }
            break;

        case 'XResolution':
        case 'YResolution':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                return sprintf("%d 像素", $n);
            }
            break;

        case 'ExifImageWidth':
        case 'ExifImageLength':
            return sprintf("%d 像素", $data);

        case 'Orientation':
            switch ($data) {
            case 1:
                return sprintf("Normal (O deg)");
            case 2:
                return sprintf("Mirrored");
            case 3:
                return sprintf("Upsidedown");
            case 4:
                return sprintf("Upsidedown Mirrored");
            case 5:
                return sprintf("90 deg CW Mirrored");
            case 6:
                return sprintf("90 deg CCW");
            case 7:
                return sprintf("90 deg CCW Mirrored");
            case 8:
                return sprintf("90 deg CW");
            }
            break;

        case 'ExposureTime':
            return sprintf("%d 秒", $data);

        case 'ShutterSpeedValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                $data = $n / $d;
                $data = exp($data * log(2));
                if ($data > 1) {
                    $data = floor($data);
                }
                if ($data < 1) {
                    $data = round(1 / $data);
                } else {
                    $data = '1/' . $data;
                }
                return sprintf("%d 秒", $data);
            }
            break;

        case 'ApertureValue':
        case 'MaxApertureValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                $data = $n / $d;
                $data = exp(($data * log(2)) / 2);

                // Precision is 1 digit.
                $data = round($data, 1);
                return 'f ' . $data;
            }
            break;

        case 'FocalLength':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                return $n . ' mm';
            }
            break;

        case 'FNumber':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d != 0) {
                    return 'f ' . round($n / $d, 1);
                }
            }
            break;

        case 'ExposureBiasValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($n == 0) {
                    return '0 EV';
                } else {
                    return $data . ' EV';
                }
            }
            break;

        case 'MeteringMode':
            switch ($data) {
            case 0: return "未知";
            case 1: return "平均";
            case 2: return "中央重点平均测光";//Center Weighted Average
            case 3: return "点测";//Spot
            case 4: return "分区";//Multi-Spot
            case 5: return "评估";//Multi-Segment
            case 6: return "局部";//Partial
            case 255: return "其他";//Other
            }
            break;

        case 'WhiteBalance':
            switch ($data) {
            case 0: return '自动';//Auto
            case 1: return "Sunny";
            case 2: return "Cloudy";
            case 3: return "Tungsten";
            case 4: return "Fluorescent";
            case 5: return "Flash";
            case 6: return "Custom";
            case 129: return "Manual";
            }
            break;

        case 'FocalLength':
            return $data . ' mm';

        case 'FocalLengthIn35mmFilm':
            return $data . ' mm';

        case 'Flash':
            switch ($data) {
            case 0: return "No Flash";
            case 1: return "Flash";
            case 5: return "Flash, strobe return light not detected";
            case 7: return "Flash, strobe return light detected";
            case 9: return "Compulsory Flash";
            case 13: return "Compulsory Flash, Return light not detected";
            case 15: return "Compulsory Flash, Return light detected";
            case 16: return "No Flash";
            case 24: return "No Flash";
            case 25: return "Flash, Auto-Mode";
            case 29: return "Flash, Auto-Mode, Return light not detected";
            case 31: return "Flash, Auto-Mode, Return light detected";
            case 32: return "No Flash";
            case 65: return "Red Eye";
            case 69: return "Red Eye, Return light not detected";
            case 71: return "Red Eye, Return light detected";
            case 73: return "Red Eye, Compulsory Flash";
            case 77: return "Red Eye, Compulsory Flash, Return light not detected";
            case 79: return "Red Eye, Compulsory Flash, Return light detected";
            case 89: return "Red Eye, Auto-Mode";
            case 93: return "Red Eye, Auto-Mode, Return light not detected";
            case 95: return "Red Eye, Auto-Mode, Return light detected";
            }
            break;

        case 'FileSize':
            return sprintf("%d 字节", $data);

        case 'SensingMethod':
            switch ($data) {
            case 1: return "Not defined";
            case 2: return "One Chip Color Area Sensor";
            case 3: return "Two Chip Color Area Sensor";
            case 4: return "Three Chip Color Area Sensor";
            case 5: return "Color Sequential Area Sensor";
            case 7: return "Trilinear Sensor";
            case 8: return "Color Sequential Linear Sensor";
            }
            break;

        case 'ColorSpace':
            switch ($data) {
            case 1:
                return "sRGB";
                break;
            default:
                return "Uncalibrated";
            }

        case 'DateTime':
        case 'DateTimeOriginal':
        case 'DateTimeDigitized':
            return date('m/d/Y h:i:s O', $data);

        default:
            return $data;
        }
    }
}
/**vim:sw=4 et ts=4 **/
?>