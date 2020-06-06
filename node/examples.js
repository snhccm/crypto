// // 导入加密解密模块
// const Crypt = require('./src/crypt');
//
// // 实例化类
// let cryption = new Crypt("输入图片路径", '加密或解密', '输出图片路径', '加解密种子');
//
// // 载入图像为异步操作需要使用Promise, 完成后再处理相关的操作
// cryption.exec().then(()=>{
//   // 返回种子字符串
//   console.log('seeder:' + cryption.seeder());
//
//   // 返回base64图像数据
//   console.log('图像数据:' + cryption.dataUrl());
//
//   // 写入图像到文件
//   cryption.local();
// })
