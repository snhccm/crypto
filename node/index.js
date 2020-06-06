const Crypt = require('./src/crypt');

try {
  // 实例化类 -- 加密 注意数字字符串和数字是不一样的
  let cryption = new Crypt("./static/1.jpg", 'encrypt');
  // 实例化类 -- 解密
  // let cryption = new Crypt("./static/1.png", 'decrypt', '', 'S1590858922608');

  // 载入图像为异步操作需要使用Promise, 完成后再处理相关的操作
  cryption.exec().then(()=>{
    // 返回种子字符串
    console.log('seeder:' + cryption.seeder());

    // 返回base64图像数据
    // console.log('图像数据:' + cryption.dataUrl());

    // 写入图像到文件
    cryption.local();
  })
} catch (e) {
  console.log(e);
}
