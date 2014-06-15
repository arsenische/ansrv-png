<?php
// Created by arseniche, feel free to modify/use as you like

$maxLen = 200;

function cached_get_contents($url, $maxLen)
{
  $cache = new Memcache;
  if ($cache->connect('localhost', 11211))
  {
    if ($res = $cache->get(md5($url)))
      return $res;
    $cacheConnected = true;
  }
  else
    $cacheConnected = false;
  $ctx = stream_context_create(array('http' => array('timeout' => 1)));
  $res = file_get_contents($url, false, $ctx, -1, $maxLen);
  if ($cacheConnected)
    $cache->set(md5($url), $res, false, 60);
  return $res;
}

if (isset($_REQUEST["help"]))
{
  function Example($url)
  {
    $url = "http://ansrv.com/png?".$url;
    return "<a href='$url' style='font-size:0.8em;'>$url</a> returns  <img src='$url'>";
  }
  ?>
  <html>
  <head>
  <title>ansrv.com/png help page</title>
  <script type="text/javascript">

    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-2973328-12']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();

  </script>
  </head>
  <body style='background-color:#EEEEAA;color:#AA0000;font-family:courier new;'>
  <p>This is a simple text-to-image service. You can use it to obscure your email address or to show dynamic content on static pages, blog or forum posts.</p>
  <p>Usage:</p>

  <ul>
  <li>http://ansrv.com/png?help</li>
  <li>http://ansrv.com/png?s=&lt;your text&gt;[&c=&lt;font color&gt;][&b=&lt;background color&gt;][&size=&lt;font size&gt;]</li>
  </ul>

  <p>Arguments:</p>
  <ul>
  <li><b>s</b> - string, which is to be rendered on the image (up to <?php echo $maxLen; ?> chars).<br/>
  If <b>s</b> starts with "http://" or "https://" then it will be interpreted as url of the string to be rendered. Examples:
  <ul>
  <li><?php echo Example("s=test");?></li>
  <li><?php echo Example("s=http://blockexplorer.com/q/getblockcount");?></li>
  </ul>
  </li>

  <li><b>c</b> - font color (6 hex chars). Example:
  <ul>
  <li><?php echo Example("s=test&c=ff0000");?></li>
  </li>
  </ul>

  <li><b>b</b> - background color (6 hex chars). Example:
  <ul>
  <li><?php echo Example("s=test&b=cccccc");?></li>
  </li>
  </ul>

  <li><b>size</b> - size (1-5). Examples:
  <ul>
  <?php for ($i=1; $i<=5; $i++) echo "<li>".Example("s=test&size=$i")."</li>"; ?>
  </ul>

  </ul>

  <p>Obscuring email:

  <?php 
    $s = "<img src='http://ansrv.com/png?s=anon'/><img src='http://ansrv.com/png?s=@spam.su'/>"; 
    echo "<span style='border:1px dotted;font-size:0.8em;'>".htmlentities($s)."</span> will be shown as ";
    echo $s; 
  ?>
  </p>

  <p>Displaying dynamic content on the static page:

  <?php 
    $s = "<img src='http://ansrv.com/png?s=Donated%20'/><img src='http://ansrv.com/png?s=http://blockexplorer.com/q/getreceivedbyaddress/19uryzRmxaCRZDoGNk9ykKsqQyZBm1u1o7/1'/><img src='http://ansrv.com/png?s= btc'/>"; 
    echo "<div style='border: 1px dotted;font-size:0.8em'>".htmlentities($s)."</div><br/> will be shown as ";
    echo $s; 
  ?>
  </p>
  <p>

  Please donate to <a href='http://blockexplorer.com/address/19uryzRmxaCRZDoGNk9ykKsqQyZBm1u1o7'>19uryzRmxaCRZDoGNk9ykKsqQyZBm1u1o7</a> to see this number changed ;) <a href='https://bitcointalk.org/index.php?topic=42005.msg51140'>Discuss</a>

  </p>
  </body>
  </html>
  <?php
  die();
};

$text = false;

if (isset($_REQUEST['s']))
  $text = $_REQUEST['s'];
else
  $text = "No text specified. Try http://ansrv.com/png?help";

if ( (substr($text, 0, 7)=="http://") || (substr($text, 0, 8)=="https://") )
  {
    $url = $text;
    $text = cached_get_contents($text, $maxLen);
    if ($text===false)
      $text = $url;
  }

if (strlen($text)>$maxLen) $text = substr($text, 0, $maxLen);


$renderer = new Renderer();

if (isset($_REQUEST['c']))
  $renderer->color = $_REQUEST['c'];

if (isset($_REQUEST['b']))
  $renderer->backgroundColor = $_REQUEST['b'];

if (isset($_REQUEST['size']))
  $renderer->fontSize = $_REQUEST['size'];

$renderer->Render($text);

class Renderer {
  public $width  = 0;
  public $height = 0;
  public $color = "000000";
  public $backgroundColor = "FFFFFF";

  public $fontSize = 2;

  public $gdImg;

  private function GetColor($color)
  {
    $r = hexdec($color[0].$color[1]);
    $g = hexdec($color[2].$color[3]);
    $b = hexdec($color[4].$color[5]);

    return imagecolorallocate($this->gdImg, $r, $g, $b);
  }

  public function Render($text)
  {
    header("Cache-Control: private, max-age=60, pre-check=60");
    header("Pragma: private");
    header("Expires: " . date(DATE_RFC822,strtotime("+60 seconds")));

    if ($this->width == 0)
      $this->width = strlen($text)*imagefontwidth($this->fontSize);

    if ($this->height == 0)
      $this->height = imagefontheight($this->fontSize) + 1;

    $this->gdImg = imagecreatetruecolor($this->width, $this->height);
    $this->gdBackgroundColor = $this->GetColor($this->backgroundColor);
    $this->gdColor           = $this->GetColor($this->color);

    imagefilledrectangle($this->gdImg, 0, 0, $this->width, $this->height, $this->gdBackgroundColor);
    imagestring($this->gdImg, $this->fontSize, 1, 1, $text, $this->gdColor);
    header("Content-type: image/png");
    imagepng($this->gdImg);
    imagedestroy($this->gdImg);
  }
}

?>
