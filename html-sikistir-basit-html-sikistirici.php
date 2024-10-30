<?php 

/*
 Plugin Name: wordpress html sıkıştırıcı
 Plugin URI: https://odevvebilim.com/
 Description: wordpress html sıkıştırıcı, HTML'yi küçültmek için özellik sağlar, Eklenti ayarları yoktur ve aktif etmeniz yeterlidir. Tek seferde herşeyi otomatik yapar..
 Author: güven doğu demir
 Version: 1.0
 Author URI: https://odevvebilim.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Minify HTML

class sik_HTML_Compression
{
    // Settings
    protected $sik_compress_css = true;
    protected $sik_compress_js = false;
    protected $sik_info_comment = false;
    protected $sik_remove_comments = true;

    // Variables
    protected $html;
    public function __construct($html)
    {
   	 if (!empty($html))
   	 {
   		 $this->sik_parseHTML($html);
   	 }
    }
    public function __toString()
    {
   	 return $this->html;
    }
    protected function sik_bottomComment($raw, $compressed)
    {
   	 $raw = strlen($raw);
   	 $compressed = strlen($compressed);
   	 
   	 $savings = ($raw-$compressed) / $raw * 100;
   	 
   	 $savings = round($savings, 2);
   	 
   	 return '<!--HTML compressed, size saved '.$savings.'%. From '.$raw.' bytes, now '.$compressed.' bytes-->';
    }
    protected function sik_minifyHTML($html)
    {
   	 $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
   	 preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
   	 $overriding = false;
   	 $raw_tag = false;
   	 // Variable reused for output
   	 $html = '';
   	 foreach ($matches as $token)
   	 {
   		 $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
   		 
   		 $content = $token[0];
   		 
   		 if (is_null($tag))
   		 {
   			 if ( !empty($token['script']) )
   			 {
   				 $strip = $this->sik_compress_js;
   			 }
   			 else if ( !empty($token['style']) )
   			 {
   				 $strip = $this->sik_compress_css;
   			 }
   			 else if ($content == '<!--wp-html-compression no compression-->')
   			 {
   				 $overriding = !$overriding;
   				 
   				 // Don't print the comment
   				 continue;
   			 }
   			 else if ($this->sik_remove_comments)
   			 {
   				 if (!$overriding && $raw_tag != 'textarea')
   				 {
   					 // Remove any HTML comments, except MSIE conditional comments
   					 $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
   				 }
   			 }
   		 }
   		 else
   		 {
   			 if ($tag == 'pre' || $tag == 'textarea')
   			 {
   				 $raw_tag = $tag;
   			 }
   			 else if ($tag == '/pre' || $tag == '/textarea')
   			 {
   				 $raw_tag = false;
   			 }
   			 else
   			 {
   				 if ($raw_tag || $overriding)
   				 {
   					 $strip = false;
   				 }
   				 else
   				 {
   					 $strip = true;
   					 
   					 // Remove any empty attributes, except:
   					 // action, alt, content, src
   					 $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
   					 
   					 // Remove any space before the end of self-closing XHTML tags
   					 // JavaScript excluded
   					 $content = str_replace(' />', '/>', $content);
   				 }
   			 }
   		 }
   		 
   		 if ($strip)
   		 {
   			 $content = $this->sik_removeWhiteSpace($content);
   		 }
   		 
   		 $html .= $content;
   	 }
   	 
   	 return $html;
    }
   	 
    public function sik_parseHTML($html)
    {
   	 $this->html = $this->sik_minifyHTML($html);
   	 
   	 if ($this->sik_info_comment)
   	 {
   		 $this->html .= "\n" . $this->sik_bottomComment($html, $this->html);
   	 }
    }
    
    protected function sik_removeWhiteSpace($str)
    {
   	 $str = str_replace("\t", ' ', $str);
   	 $str = str_replace("\n",  '', $str);
   	 $str = str_replace("\r",  '', $str);
   	 
   	 while (stristr($str, '  '))
   	 {
   		 $str = str_replace('  ', ' ', $str);
   	 }
   	 
   	 return $str;
    }
}

function sik_wp_html_compression_finish($html)
{
	$html = new sik_HTML_Compression($html);
    return sik_remove_html_comments($html);
}

function sik_remove_html_comments($content = '') {
	return preg_replace('/<!--(.|\s)*?-->/', '', $content);
}

function sik_wp_html_compression_start()
{
    ob_start('sik_wp_html_compression_finish');
}
add_action('wp', 'sik_wp_html_compression_start');