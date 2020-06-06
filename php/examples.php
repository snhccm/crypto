<?php
// 导入加密解密模块
require './src/crypt.php';

// 实例化类
$cryption = new Crypt("输入图片路径", '加密或解密', '输出图片路径', '加解密种子');

// 返回种子字符串
$cryption->seeder();
// 返回base64图像
$cryption->dataUrl();
// 保存图像到文件
$cryption->local();
