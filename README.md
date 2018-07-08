# BlueWE_MT
超高速创世神系列
<b>已知bug</b><br>
<code>
 <br>
会导致被操作的区块上面的实体丢失
 <br>
 被操作的区块上面的光照问题
 <br>
</code>

<a href="https://www.bilibili.com/video/av14524120/">观看第一次测试版演示视频戳我(哔哩哔哩)</a><br>
<b>关于使得游戏中Subchunks重新渲染的问题</b><br>该BUG的出现是由于MOJANG智障，不是我的BUG<br>如果遇到SubChunk渲染有问题(地图中间突然变透明什么什么的)<br>请退出重进游戏即可<br>本插件有自动刷新SubChunk的功能，可以缓解这个BUG(不是完全解决)<br>如要使用,请在pocketmine.yml中编辑以下配置项，会自动开启。<br>
<code>
network:
 batch-threshold: -1
</code><br>
<code>
chunk-sending:
 cache-chunks: false
</code>
