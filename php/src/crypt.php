<?php
require 'random.php';

class codec{
  private $resource;
  private $seed;

  function __construct($resource, $seed){
    $this->resource = $resource;
    $this->seed = $seed;
  }

  private function getImageBlock(){
      list($width, $height) = getimagesize($this->resource);

      $count['width'] = floor($width / 8);
      $count['height'] = floor($height / 8);

      $block = [];

      // 计算每个块的坐标
      for($i=0; $i< $count['height']; $i++){
          // 计算y的坐标
          for($j=0; $j < $count['width']; $j++){
              $block[] = [
                  'x' => $j * 8,
                  'y' => $i * 8
              ];
          }
      }
      return array('width'=>$count['width'] * 8, 'height'=>$count['height'] * 8, 'data'=>$block);
  }

  // 创建新的图像
  private function mergeBlock($random){
      $temp = getimagesize($this->resource);

      switch ($temp['mime']) {
        case 'image/gif':
          $origin_data = imagecreatefromgif($this->resource);
          break;
        case 'image/jpeg':
          $origin_data = imagecreatefromjpeg($this->resource);
          break;
        case 'image/png':
          $origin_data = imagecreatefrompng($this->resource);
          break;
        default:
          return false;
          break;
      }

      $canvas = imagecreatetruecolor(floor($temp[0] / 8) * 8, floor($temp[1] / 8) * 8);
      $color = imagecolorallocate($canvas, 255, 255, 255); // 为真彩色画布创建白色背景，再设置为透明
      imagefill($canvas, 0, 0, $color);
      // 把原始图像的信息拷贝全部拷贝到新图像上
      // imagecopyresampled($canvas, $origin_data, 0, 0, 0, 0, $width, $height, $width, $height);

      foreach($random as $v){
          // $thumb = ImageCreateTrueColor(8, 8);
          // imagecopy($thumb, $origin_data, 0, 0, $v['x'], $v['y'], 8, 8);

          // 循环合并8X8的图像到画布上
          // print_r($v);
          imagecopymerge($canvas, $origin_data, $v['nx'], $v['ny'], $v['x'], $v['y'], 8, 8, 100);
      }

      return $canvas;
  }

  public function encrypt(){
    $image = $this->getImageBlock();

    $count['width'] = floor($image['width'] / 8);
    $count['height'] = floor($image['height'] / 8);

    $random = new random($this->seed);
    $length = $count['width'] * $count['height'];

    $index = $random->sequence($length);

    $origin_block = $image['data'];

    $random_block = [];

    for ($i = 0; $i < count($index); $i++) {
      $random_block[] = [
        'x' => $origin_block[$i]['x'],
        'y' => $origin_block[$i]['y'],
        // 根据随机数定位取坐标，
        'nx' => $origin_block[$index[$i]]['x'] ,
        'ny' => $origin_block[$index[$i]]['y'],
      ];
    }
    // return $random_block;
    return $this->mergeBlock($random_block);

    // imagepng($canvas, $destination);
  }

  public function decrypt(){
    $image = $this->getImageBlock();

    $count['width'] = floor($image['width'] / 8);
    $count['height'] = floor($image['height'] / 8);

    $random = new random($this->seed);
    $length = $count['width'] * $count['height'];

    $index = $random->sequence($length);
    $index = array_flip($index);

    $origin_block = $image['data'];

    $random_block = [];

    for ($i = 0; $i < count($index); $i++) {
      $random_block[] = [
        'x' => $origin_block[$i]['x'],
        'y' => $origin_block[$i]['y'],
        // 根据随机数定位取坐标，
        'nx' => $origin_block[$index[$i]]['x'],
        'ny' => $origin_block[$index[$i]]['y'],
      ];
    }

    // return $random_block;
    return $this->mergeBlock($random_block);

    // imagejpeg($canvas, $destination, 100);
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

    return base64_encode($_temp);
  }

  public function local(){
    $codec = new codec($this->srcPath, $this->seed);

    if ($this->method == 'encrypt') {
      $_temp = $codec->encrypt();
    } else {
      $_temp = $codec->decrypt();
    }

    switch ($_temp['mime']) {
      case 'image/gif':
        imagegif($_temp, $this->dstPath);
        break;
      case 'image/png':
        imagepng($_temp, $this->dstPath);
        break;
      default:
        imagejpeg($_temp, $this->dstPath, 100);
        break;
    }
  }
}
