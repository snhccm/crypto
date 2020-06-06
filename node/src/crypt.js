const fs = require('fs');
const path = require('path');
const { loadImage, createCanvas, createImageData } = require('canvas');
const { RandomSequence } = require('./random');

// 块随机置乱 -- 基类
class Codec {
  constructor (imgData, seeder) {
    this._imgData = imgData
    this._seeder = seeder;
  }
  // 加密，返回加密后的imgData
  encrypt () {}
  // 解密，返回解密后的imgData
  decrypt () {}
}

// 块随机置乱 -- 由于JPEG是分成8x8的块在块内压缩，分成8x8块处理可以避免压缩再解密造成的高频噪声
class ShuffleBlockCodec extends Codec {
  encrypt () {
    // 从原图像0点坐标拷贝新图像随机坐标
    return this._doCommon((result, blockX, blockY, newBlockX, newBlockY) =>
      this._copyBlock(result, newBlockX, newBlockY, this._imgData, blockX, blockY)
    )
  }

  decrypt () {
    // 从加密图片的随机坐标拷贝图像按顺序排列
    return this._doCommon((result, blockX, blockY, newBlockX, newBlockY) =>
      this._copyBlock(result, blockX, blockY, this._imgData, newBlockX, newBlockY)
    )
  }

  _doCommon (handleCopy) {
    // 尺寸不是8的倍数则去掉边界
    let blockWidth = Math.floor(this._imgData.width / 8)
    let blockHeight = Math.floor(this._imgData.height / 8)

    // 创建新空白图像
    let result = createImageData(blockWidth * 8, blockHeight * 8)
    // let result = createImageData(new Uint8ClampedArray(blockWidth * blockHeight * 4), blockWidth)

    // 采用图片的宽高乘积定义随机数长度，并传入种子字符串
    let seq = new RandomSequence(blockWidth * blockHeight, this._seeder)

    for (let blockY = 0; blockY < blockHeight; blockY++) {
      for (let blockX = 0; blockX < blockWidth; blockX++) {
        // 提取随机数--在种子不变的情况下随机数的顺序是不变的
        let index = seq.next()

        // 计算目的地图像的坐标
        let newBlockX = index % blockWidth
        let newBlockY = Math.floor(index / blockWidth)

        // 执行回调函数
        handleCopy(result, blockX, blockY, newBlockX, newBlockY)
      }
    }
    return result
  }

  _copyBlock (dstImgData, dstBlockX, dstBlockY, srcImgData, srcBlockX, srcBlockY) {

    let iDstStart = (dstBlockY * dstImgData.width + dstBlockX) * 8 * 4
    let iSrcStart = (srcBlockY * srcImgData.width + srcBlockX) * 8 * 4
    
    for (let y = 0; y < 8; y++) {
      for (let i = 0; i < 8 * 4; i++) {
        dstImgData.data[iDstStart + i] = srcImgData.data[iSrcStart + i]
      }
      iDstStart += dstImgData.width * 4
      iSrcStart += srcImgData.width * 4
    }
  }
}

// 原始图像和Canvas转换
class CanvasHandle {
  constructor(imgData){
    this._imgData = imgData
    this._canvas = createCanvas(this._imgData.width, this._imgData.height);
    this._ctx = this._canvas.getContext('2d');
  }

  // 输出DataUrl格式的数据
  dataUrl(){
    this._ctx.putImageData(this._imgData, 0, 0);
    return this._canvas.toDataURL();
  }

  // 输出ImageData格式的数据
  imageData(){
    this._ctx.drawImage(this._imgData, 0, 0);
    return this._ctx.getImageData(0, 0, this._canvas.width, this._canvas.height);
  }

}

// 图片的加密解密
class Crypt {
  constructor(srcPath, method, dstPath = '', seeder = ''){
    this._srcPath = srcPath;
    this._method = method;
    this._dstPath = dstPath;
    this._seeder = seeder;

    // 验证原始图片路径是否正确
    fs.exists(this._srcPath, function (exists) {
      if(!exists) throw 'Invalid file path, please check!';
    });

    // 验证选项参数
    if(!(this._method == 'encrypt' || this._method == 'decrypt')){
      throw 'action method is error, use encrypt or decrypt please!' ;
    }

    // 验证目标地址
    if(this._dstPath == ''){
      // 如果没有定义目标路径，输出路径经采用原路径，输出文件名采用原名称+加密解密方法名
      let srcdir = path.dirname(this._srcPath);

      // 获取扩展名
      this._dstPath =  srcdir + '/' + this._method + path.extname(this._srcPath);

      // console.log(this._dstPath);
    }else{
      // 验证路径是否存在，不存在创建
      console.log('11111111111111111');
    }

    // 验证加密种子字符串， 如果没有黙认生成
    if(this._seeder == '' && this._method == 'encrypt'){
      this._seeder = 'S' + new Date().getTime();
    }
    // 如果是解密，没有种子提示错误
    if(this._seeder == '' && this._method == 'decrypt'){
      throw 'Invalid seed string, please check!';
    }
  }

  // 执行异步加载图片并加解密处理
  exec(){
    return new Promise((resolve, reject) => {
      loadImage(this._srcPath).then( result => {
        this._srcImageData = result;
        this._handle();
        resolve();
      }).catch(error => {
        throw error
      })
    });
  }

  // 原始图片加密解密处理
  _handle(){
      // 将图片转为canvas对象，输出imageData格式的数据
      let srcHandle = new CanvasHandle(this._srcImageData);
      console.log(srcHandle.imageData());
      // console.log(srcHandle.imageData());
      let codec = new ShuffleBlockCodec(srcHandle.imageData(), this._seeder);
      let imageData = '';

      if(this._method == 'encrypt'){
        // 加密图片数据
        imageData = codec.encrypt();
      }else{
        // 解密图片数据
        imageData = codec.decrypt();
      }

      // 根据加密的数据创建Canvas文件
      let dstHandle = new CanvasHandle(imageData);
      this._dstImageData = dstHandle.dataUrl();
  }

  // 返回种子字符串
  seeder(){
    return this._seeder;
  }

  // 返回图片的DataUrl
  dataUrl(){
    return this._dstImageData;
  }

  // 加密解密后的文件本地化
  local(){
    let temp = this._dstImageData.replace(/^data:image\/\w+;base64,/, "");
    let dataBuffer = new Buffer.from(temp, 'base64');

    fs.writeFile(this._dstPath, dataBuffer, ()=>{});
  }
}

module.exports = Crypt;
