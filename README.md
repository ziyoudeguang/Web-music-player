# 🎵 音乐播放器

一个纯前端 + 轻量 PHP 后端的网页音乐播放器。前端为单文件 `index.html`（无需构建、无依赖），后端提供两个接口：扫描服务器音乐目录生成歌单、按需获取歌词，播放时浏览器直接流式请求音频文件。

## 效果图片
演示网站：https://music.ziyoudeguang.cn ，网站有时会关闭，因为服务器是我家的一台电脑，一般开启时间为9：00-22：00

<img width="1918" height="924" alt="01019e618be23e34da6e25f9e9f5c688" src="https://github.com/user-attachments/assets/87d61bdd-7bb5-42c2-8a41-bbc4a137400b" />

## 功能特性

- 在使用edge和chorme浏览器时播放器支持灵动岛！还有实时歌词显示！
- 🎧 自动扫描服务器 `music/` 目录，支持 `mp3`、`flac`、`wav`、`m4a`、`ogg`
- 📋 右侧可拖拽宽度、可展开/收起的歌单列表
- 🔍 歌单内置搜索框，随歌单展开/收起一起显示或隐藏，可按歌名/歌手实时过滤
- 🎤 滚动歌词显示（依赖歌词接口，无歌词时会提示"暂无歌词"），可以用鼠标滚轮查看歌词，无操作5秒后自动回滚
- 🔁 三种播放模式：顺序播放 / 随机播放 / 单曲循环
- 🔊 音量调节、播放进度、缓冲卡顿检测（缓冲超时自动切下一首）
- ⌨️ 快捷键支持
- 🐞 内置调试信息面板，方便排查加载/播放问题
- 在使用edge和chorme浏览器时播放器<mark>支持灵动岛<mark> ！还有实时歌词显示！

## 项目结构

```
.
├── index.html          # 前端播放器页面（唯一前端文件，直接部署即可访问）
├── music_list.php      # 后端接口：扫描 music/ 目录，返回歌单 JSON
├── get_lrc.php          # 后端接口：按文件名返回歌词
├── music/               # 音乐文件存放目录（自行放入音频文件）
└── musiclrc/             # 音乐歌词文件存放目录（自行放入 .lrc 歌词文件）
```

## 部署方式

最新版：[点击进入下载页面](https://github.com/ziyoudeguang/Web-music-player/releases)

**1. 解压压缩包到网站目录**

**2. 打开 `index.html`，更改第 746 行的歌曲列表 API 的 URL**

在文件中搜索 `const API_URL`：（代码在第746行，图片没更新）

<img width="939" height="279" alt="API_URL 位置示意图" src="https://github.com/user-attachments/assets/7047f03b-79f0-48e4-972f-769445b76aa3" />

再更改第 1068 行的歌词 API 的 URL，搜索 `const apiUrl`：（代码在第1068行，图片没更新）

<img width="917" height="271" alt="apiUrl 位置示意图" src="https://github.com/user-attachments/assets/f47d97c1-a739-4f71-865a-1060cafb8cb2" />

> 可以上网搜索申请免费域名的教程，然后用 Cloudflare 托管域名，也是免费的。

**3. 上传歌曲到 `music/` 目录**

支持 `mp3`、`flac`、`wav`、`m4a`、`ogg` 格式。再上传这首歌曲对应的歌词 `.lrc` 文件到 `musiclrc/`（不上传也可以，不影响播放）。

> **要求**：歌词文件名需与所对应的音乐文件名完全相同。
>
> 例如：音乐文件名是 `中国人能飞-揽佬SKAI ISYOURGOD、Chalky Wong.mp3`，对应的歌词文件名就应为 `中国人能飞-揽佬SKAI ISYOURGOD、Chalky Wong.lrc`。

**4. 用浏览器访问 `index.html` 所在的 URL 即可自动加载歌单并开始播放**

### 服务器要求

- PHP 7.0+（用到了 `scandir`、`pathinfo`、匿名函数排序等基础特性，无特殊扩展依赖），作者本人使用的 PHP 版本是 7.4
- 音乐目录 `music/` 需要 Web 服务器有读取权限，且能被外部直接通过 URL 访问（用于 `<audio>` 标签流式播放）
- 建议带宽充足，因为需要传输音频，否则会造成播放卡顿，或一直显示"缓冲中..."

## 后端接口说明

### `music_list.php`

扫描 `music/` 目录并返回歌单 JSON，链接会根据当前请求的协议、域名自动生成，**无需手动修改域名**，换域名/端口/本地调试都能自动适配。

请求方式：`GET music_list.php`

返回示例：

```json
{
  "total": 2,
  "music": [
    {
      "url": "https://你的域名/music/示例歌曲.flac",
      "title": "示例歌曲",
      "artist": "未知艺术家",
      "format": "FLAC",
      "filename": "示例歌曲.flac"
    }
  ]
}
```

字段说明：

| 字段 | 说明 |
|---|---|
| `url` | 可直接播放的音频地址（自动拼接当前域名） |
| `title` | 文件名（去掉扩展名） |
| `artist` | 目前固定为"未知艺术家"，如需展示真实歌手信息，可扩展为解析音频文件 ID3 标签 |
| `format` | 大写的文件扩展名，如 `MP3`/`FLAC` |
| `filename` | 原始文件名（含扩展名），用于请求歌词接口等 |


## 灵动岛

- 灵动岛目前仅支持edge浏览器和google chorme的手机浏览器，苹果的safari暂不清楚
[最新版下载](https://github.com/ziyoudeguang/Web-music-player/releases)
下面是我的小米14（澎湃OS3.0.303.0）的演示图：

收起灵动岛样式：
<img width="1200" height="267" alt="cf946d0f4797725471bcd61f11882766" src="https://github.com/user-attachments/assets/b6a8156c-e18c-448a-8327-ddea46b1957d" />
展开灵动岛样式：
<img width="1200" height="897" alt="6ef608783f651c583dd63f5bb2ea03e0" src="https://github.com/user-attachments/assets/9ef65f95-9884-499a-a4cf-bd1bcc8e805c" />

系统（软/硬件）要求：
*除苹果和google手机外，其余的系统自带浏览器均不支持上岛
- 苹果 —— 灵动岛：硬件要求：iPhone 14 Pro / 14 Pro Max 起的所有 Pro 系列，以及 iPhone 15 起的非 Pro 系列（15、15 Plus、16、16 Plus、17 等）。系统要求： iOS 16.1 及以上。
- 荣耀 —— 灵动胶囊：系统要求 MagicOS 8.0 及以上（部分新机已是 MagicOS 9.0/10.0）。
- vivo —— 原子通知 / 原子岛：支持情况尚不清楚
- 华为 —— 实况窗：系统自带浏览器不支持上岛，系统要求：HarmonyOS 4.0及以上
- 小米 —— 超级岛：澎湃OS 3及以上，17pro及以上有妙想背屏功能的尚不清楚能不能上背屏
- Android：Android16+
- OPPO / 三星 / 魅族及其他机型：支持情况尚不清楚

## 电脑播放界面

现在部署[最新的版本](https://github.com/ziyoudeguang/Web-music-player/releases)即可在windoes电脑调节音量时显示音乐名字，实时歌词等信息，功能有暂停，上一首，下一首，图示如下：
<img width="512" height="151" alt="0adb9929647cc778bf4147a5833569fc" src="https://github.com/user-attachments/assets/a85df88e-f588-4de9-a820-9a7309a07600" />
- 系统要求：windoes 10及以上


## 前端说明（`index.html`）

- 播放器只依赖浏览器原生 `<audio>` 元素和原生 JS，未使用任何第三方框架或 CDN 资源，打开即用
- 歌单、搜索、播放控制等所有交互逻辑均内嵌在 `<script>` 标签中，无需额外构建步骤
- `API_URL` 变量指向 `music_list.php` 的完整地址，如部署路径不同，需在 `index.html` 顶部脚本区手动修改这一行

### 快捷键

| 按键 | 功能 |
|---|---|
| `空格` | 播放 / 暂停 |
| `Alt + F7` | 播放 / 暂停（备用快捷键） |
| `Alt + F6` | 上一首 |
| `Alt + F8` | 下一首 |
| `F8` | 显示 / 隐藏调试信息面板 |

## 常见问题

**Q: 播放速度慢/卡顿是不是因为歌单是通过接口获取的？**

不是。歌单接口只返回一小段 JSON 文本（歌曲信息），只在打开页面时请求一次；实际播放时浏览器 `<audio>` 标签会直接向音频文件地址发起流式请求，两者互不影响。播放速度主要取决于服务器带宽、与用户的物理距离，以及是否使用了 CDN。

**Q: 提示"音乐目录不存在"？**

检查 `music_list.php` 同级目录下是否存在 `music/` 文件夹，且 Web 服务器进程对该目录有读取权限。

**Q: 歌词一直显示"暂无歌词"？**

说明 `get_lrc.php` 未部署或未返回有效歌词数据，播放功能不受影响。

**Q: 怎么联系我？**

- QQ：2533765959
- 邮箱：2533765959@qq.com / curtain@ziyoudeguang.cn

## 感谢

感谢 Deepseek 老师和 Claude
