<?php
require 'random.php';

// 获取图像的unit数据
function imageToUnitData($file){
  // 读取图像的数据
  $temp = getimagesize($file);

  // print_r($temp);

  switch ($temp['mime']) {
    case 'image/gif':
      $image = imagecreatefromgif($file);
      break;
    case 'image/jpeg':
      $image = imagecreatefromjpeg($file);
      break;
    case 'image/png':
      $image = imagecreatefrompng($file);
      break;
    default:
      return false;
      break;
  }

  // 去掉图像8倍像素的边界尺寸
  $size[0] = floor($temp[0] / 8) * 8;
  $size[1] = floor($temp[1] / 8) * 8;

  // 创建图片尺寸宽度$size[0]， 高度为$size[1]的图像
  $canvas = imagecreatetruecolor($size[0], $size[1]);
  $color = imagecolorallocate($canvas, 255, 255, 255);
  // 定义填充画布
  imagefill($canvas, 0, 0, $color);
  // 把原图像拷贝到新图像 不能使用imagecopyresized 会改变图像质量，多用于生成缩略图
  imagecopy($canvas, $image, 0, 0, 0, 0, $size[0], $size[1]);

  $unit = array();

  for ($y = 0; $y < imagesy($canvas); $y++) {
    for ($x = 0; $x < imagesx($canvas); $x++) {
      // 取得颜色像素的索引值
      $unit[] = imagecolorat($canvas, $x, $y);

      // $index = imagecolorat($canvas, $x, $y);
      // $colors = imagecolorsforindex($canvas, $index);
      // print_r($colors);
      // [r, g, b, a]
    }
  }
  // 返回重置后的图像对像
  return array('data' => $unit, 'width' => $size[0], 'height' => $size[1], 'mime' => $temp['mime']);
}

// 传入RGBA数据构建图像对像
function createImageForUnitData($data){
  //生成真彩图片
  $canvas = imagecreatetruecolor($data['width'],$data['height']);
  $color = imagecolorallocate($canvas, 255, 255, 255);
  //填充背景-从左上角开始填充
  imagefill($canvas, 0, 0, $color);

  foreach ($data['data'] as $k => $v) {
    $r = ($v >> 16) & 0xFF;
    $g = ($v >> 8) & 0xFF;
    $b = $v & 0xFF;

    $point = imagecolorallocate($canvas, $r, $g, $b);

    // 计算坐标位
    $x = $k % $data['width'];
    $y = floor($k / $data['width']);

    // 根据颜色点绘制图案
    ImageFilledRectangle($canvas, $x, $y, $x,$y, $point);
  }

  return array('width' => $data['width'], 'height' => $data['height'], 'data' => $canvas, 'mime' => $data['mime']);
}

class codec{
  private $srcPath;
  private $unitdata;
  private $seed;

  function __construct($srcPath, $seed){
    $this->srcPath = $srcPath;
    $this->seed = $seed;
    $this->unitdata = imageToUnitData($srcPath);

    // print_r($this->unitdata);
    // exit();
  }

  // 图像加密
  public function encrypt(){
    $image = $this->unitdata;
    // 根据随机函数得到随机序列
    $random = new random($this->seed);

    $index = $random->sequence($image['width'] * $image['height']);
    // print_r($list);

    // 根据随机序列置乱图像像素
    foreach ($image['data'] as $key => $value) {
      $block[$index[$key]] =$value;
    }

    // $dist = array();
    // for ($y = 0; $y < $image['height']; $y++) {
    //   for ($x = 0; $x < $image['width']; $x++) {
    //     // 提取随机数--在种子不变的情况下随机数的顺序是不变的
    //
    //     // 计算目的地图像的坐标
    //     $nx = $index[$x * $y] % 86;
    //     $ny = floor($index[$x * $y] / 688);
    //
    //     // 执行回调函数
    //     // $this->block($dist,$x,$y,$image['data'],$nx,$ny);
    //
    //
    //
    //
    //     $dist[] = $image['data'][];
    //   }
    // }
    //
    // print_r($dist);
    // exit();

    // 生成加密后的图像对像
    $image['data'] = $block;
    return createImageForUnitData($image);
  }

  public function block($dist,$dx, $dy, $src, $sx, $sy){
    $ids = ($dy * 688 + $dx) * 8;
    $iss = ($sy * 688 + $sx);

    $temp = array();
    for ($y = 0; $y < 8; $y++) {
      for ($i = 0; $i < 8; $i++) {
        // echo $iss+$i."\n\r";
        $dist[$ids + $i] = $src[$iss + $i];
      }
      $ids += 688;
      $iss += 688;
    }

    return $dist;
  }

  // 图像解密
  public function decrypt(){
    $image = $this->unitdata;
    // 根据随机函数得到随机序列
    $random = new random($this->seed);

    $index = $random->sequence($image['width'] * $image['height']);
    // print_r($list);

    // 根据随机序列置乱图像像素
    foreach ($image['data'] as $key => $value) {
      $block[$key] =$image['data'][$index[$key]];
    }

    // 生成加密后的图像对像
    $image['data'] = $block;
    return createImageForUnitData($image);
  }
}

// 图片加密解密操作方法
class Crypt{
  private $srcPath;
  private $method;
  private $dstPath;
  private $seed;

  function __construct($srcPath, $method, $dstPath = '', $seed = ''){
    $this->srcPath = $srcPath;
    $this->method = $method;
    $this->dstPath = $dstPath;
    $this->seed = $seed;
    // 检查文件路径

    // 检查操作方法
    if( !in_array($this->method,['encrypt','decrypt']) ){
      return 'action method is error, use encrypt or decrypt please!';
    }

    // 如果是加密 seed为空，生成seed
    if( $this->method == 'encrypt' && empty($this->seed) ){
      $this->seed = 'S'.time();
    }

    // 如果是解密 seed为空，提示错误
    if( $this->method == 'decrypt' && empty($this->seed)){
      return "Invalid seed string, please check!";
    }


  }

  public function seeder(){
    return $this->seed;
  }

  public function dataUrl(){
    $codec = new codec($this->srcPath, $this->seed);

    if ($this->method == 'encrypt') {
      $_temp = $codec->encrypt();
    } else {
      $_temp = $codec->decrypt();
    }

    return base64_encode($_temp['data']);
  }

  public function local(){
    $codec = new codec($this->srcPath, $this->seed);

    if ($this->method == 'encrypt') {
      $_temp = $codec->encrypt();
    } else {
      $_temp = $codec->decrypt();
    }

    // print_r($_temp);
    // imagepng($_temp['data'], $this->dstPath);
    // exit();

    switch ($_temp['mime']) {
      case 'image/gif':
        imagegif($_temp['data'], $this->dstPath);
        break;
      case 'image/png':
        imagepng($_temp['data'], $this->dstPath);
        break;
      default:
        imagejpeg($_temp['data'], $this->dstPath, 100);
        break;
    }
  }
}
