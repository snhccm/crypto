<?php
class random{
  private $rngstate = [];

  // 初始化传入种子
  function __construct($seed){
    // 校验种子是否数字或字符串
    if(is_numeric($seed)){
      $seed = intval($seed);
    }else{
      $seed = $this->hashCode($seed);
    }
    // echo "传入种子的值：".$seed."\n\r";

    $this->rngstate[0] = $seed & 0xFFFF;
    $this->rngstate[1] = $seed >> 16;

    // echo "初始种子参数：[{$this->rngstate[0]},{$this->rngstate[1]}]\n\r";
    // echo "---------------------------------------\n\r";
  }

  // 随机数种子播种
  private function random(){
    $a = (bcmul(18030, $this->rngstate[0] & 0xFFFF) + ($this->rngstate[0] >> 16)) | 0;
    $this->rngstate[0] = $a;

    $b = (bcmul(36969, $this->rngstate[1] & 0xFFFF) + ($this->rngstate[1] >> 16)) | 0;
    $this->rngstate[1] = $b;

    // echo "1.运算种子参数:[{$this->rngstate[0]},{$this->rngstate[1]}]\n\r";

    $x = ($this->arrows($a, 16) + ($b & 0xFFFF)) | 0;

    // echo "2.种子参数乘积:".$x."\n\r";

    $left = (string)($x < 0 ? ($x + 0x100000000) : $x);
    $right = '2.3283064365386962890625';

    // echo "3.随机种子左右乘数：[".$left.",".$right."]\n\r";

    $cs = (string)bcmul($left, $right, 7);
    // 偏移小数点并保留小数点17位
    return bcdiv($cs, '10000000000', 17);
  }

  // 将字符串转换成整数  公开可以独立使用
  public function hashCode ($str) {
    $hash = 0;
    for($i=0;$i<strlen($str);$i++){
      $hash = ($hash * 31 + ord($str[$i])) & 0xFFFFFFFF;
    }
    return $hash;
  }

  //左位移  公开可以独立使用
  public function arrows($v, $n){
    $t = ($v & 0xFFFFFFFF) << ($n & 0x1F);
    return $t & 0x80000000 ? $t | 0xFFFFFFFF00000000 : $t & 0xFFFFFFFF;
  }

  // 获取随机数算法 公开可以独立使用 获取一个随机数
  public function randint($min, $max){
    $temp = $this->random();
    // echo "4.生成的随机种子:".$temp."\n\r";
    // echo "---------------------------------------\n\r";
    return floor($min + $temp * ($max - $min + 1));
  }

  // 返回随机数序列 公开可以独立使用 获取固定数量的随机数序列
  public function sequence($length){
    $list =[];
    // 先生成一个0到$lenght的数组
    for ($i = 0; $i < $length; $i++) {
      $list[$i] = $i;
    }

    // 生成随机数数组
    for ($i=0; $i < $length; $i++) {
      $index = $this->randint($i, $length-1);

      // 临时保存存第随机个索引的值
      $temp = $list[$index];

      // 将循环索引的值赋给索引随机的值
      $list[$index] = $list[$i];
      $list[$i] = $temp;
    }

    return $list;
  }
}
// //
// $random = new random('random');
// //
// $list = $random->sequence(20);
// echo "生成的随机序列:".json_encode($list)."\n\r";
