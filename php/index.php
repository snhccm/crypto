<?php

require './src/crypt.php';

// 实例化类
// $cryption = new Crypt("./static/origin.jpg", 'encrypt', './static/encrypt.jpg', 'random');
$cryption = new Crypt("./static/encrypt.jpg", 'decrypt', './static/decrypt.jpg', 'random');

// 返回种子字符串
// echo $cryption->seeder();
// 返回base64图像
echo $cryption->dataUrl();
// 保存图像到文件
// $cryption->local();
