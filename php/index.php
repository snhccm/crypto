<?php

require './src/crypt.php';

// 加密
// $cryption = new Crypt("./static/origin.jpg", 'encrypt', './static/encrypt.jpg', 'random');
// 解密
$cryption = new Crypt("./static/encrypt.jpg", 'decrypt', './static/decrypt.jpg', 'random');
// 返回种子字符串
// echo $cryption->seeder();
// 返回base64图像
// echo $cryption->dataUrl();
// 保存图像到文件
$cryption->local();
