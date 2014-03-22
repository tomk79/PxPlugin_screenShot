<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "screenShot"
 */
class pxplugin_screenShot_register_pxcommand extends px_bases_pxcommand{

	private $path_plugin_conf_dir;
	private $path_plugin_data_dir;
	private $target_path_list = array();
	private $target_device_list = array();

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$command = $this->get_command();

		$this->path_plugin_conf_dir = $this->px->realpath_plugin_ramdata_dir('screenShot');
		$this->path_plugin_data_dir = $this->px->realpath_plugin_private_cache_dir('screenShot');

		$this->target_path_list = $this->load_target_path_list();
		$this->target_device_list = $this->load_target_device_list();

		switch( $command[2] ){
			case 'run':
				$this->execute();
				break;
			case 'data_preview':
				$this->page_data_preview();
				break;
			case 'save_config':
				$this->page_save_config();
				break;
			default:
				$this->page_homepage();
				break;
		}
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function page_homepage(){
		$command = $this->get_command();
		$src = '';
		ob_start(); ?>

<div class="unit">
	<p>プロジェクト『<?php print t::h($this->px->get_conf('project.name')); ?>』のページのスクリーンショットを作成します。</p>
	<p>この機能は、コマンドラインから実行します。MacOSX上で動作します。Windowsでは使えません。</p>
</div><!-- /.unit -->

<h2>Step 1 : サーバーに phantomjs をインストールする</h2>
<p>次のコマンドを実行して、サーバーに phantomjs をインストールしてください。</p>

<div class="unit">
	<div class="code"><pre><code>$ npm install phantomjs
</code></pre></div>
</div>


<h2>Step 2 : デバイスの定義と対象のページを指定する</h2>
<div class="unit">
	<form action="?" method="post">
	<p>
		対象のデバイスについての情報を設定してください。<br />
		1行に1デバイスの情報を設定します。デバイスは「画面幅(数字):ユーザーエージェント名」の書式で表現します。<br />
	</p>
	<p><textarea name="target_device_list" style="width:100%; height:5em;"><?php print t::h( $this->get_device_list_string() ); ?></textarea></p>
	<p>
		次に、スクリーンショットを撮りたい対象のパスを指定してください。<br />
		この欄を空白に設定すると、サイトマップに定義されたすべてのページが対象になります。<br />
	</p>
	<p><textarea name="target_paths" style="width:100%; height:10em;"><?php print t::h( implode("\n",$this->target_path_list) ); ?></textarea></p>
	<p>
		それぞれ入力欄に指定したら、「保存する」ボタンをクリックします。<br />
	</p>
	<p class="center"><button style="width:100%;">保存する</button></p>
	<div>
		<input type="hidden" name="PX" value="plugins.screenShot.save_config" />
		<input type="hidden" name="mode" value="execute" />
	</div>
	</form>
</div>

<h2>Step 3 : コマンドを実行する</h2>
<p>screenShotは、次のコマンドから実行します。このコマンドは、<strong>この Pickles Framework が動作しているサーバー上で直接動作する</strong>ように設計されています。</p>
<div class="code"><textarea readonly="readonly">php <?php print t::h($_SERVER['SCRIPT_FILENAME']).' PX=plugins.screenShot.run url_base='.t::h('http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->theme()->href('/')); ?></textarea></div>

<h2>Step 4 : 完成されたPDFをダウンロードする</h2>
<p>
	コマンドの処理が完了したら、<a href="?PX=plugins.screenShot.data_preview&amp;path=print.pdf" target="_blank">PDFファイルを入手</a>できます。<br />
</p>

<?php
		$src .= ob_get_clean();

		print $this->html_template($src);
		exit;
	}

	/**
	 * 対象のパスの一覧をロードする
	 */
	private function load_target_path_list(){
		if( !is_file($this->path_plugin_conf_dir.'targetlist.txt') || !is_readable($this->path_plugin_conf_dir.'targetlist.txt') ){
			return array();
		}
		$list = file( $this->path_plugin_conf_dir.'targetlist.txt' );
		$rtn = array();
		foreach( $list as $row ){
			$row = trim($row);
			if( !strlen($row) ){continue;}
			array_push( $rtn, $row );
		}
		return $rtn;
	}

	/**
	 * 対象のデバイスの一覧をロードする
	 */
	private function load_target_device_list(){
		if( !is_file($this->path_plugin_conf_dir.'devicelist.txt') || !is_readable($this->path_plugin_conf_dir.'devicelist.txt') ){
			return array();
		}
		$list = file( $this->path_plugin_conf_dir.'devicelist.txt' );
		$rtn = array();
		foreach( $list as $row ){
			$row = trim($row);
			if( !strlen($row) ){continue;}
			$tmp = explode(':',$row);
			array_push( $rtn, array('width'=>intval(trim($tmp[0])),'ua'=>trim($tmp[1])) );
		}
/*
		$rtn = array();
		array_push( $rtn, array('width'=>1024,'ua'=>'GoogleChrome') );
		// array_push( $rtn, array('width'=>800,'ua'=>'iPad') );
		array_push( $rtn, array('width'=>320,'ua'=>'iPhone') );
*/
		return $rtn;
	}

	/**
	 * 書きだしたデータをプレビューする
	 */
	private function page_data_preview(){
		$path_plugin_data_dir = $this->path_plugin_data_dir;
		$path = $this->px->req()->get_param('path');
		$path = preg_replace( '/\.+/', '.', $path );
		$path = preg_replace( '/^\/+/', '', $path );
		$realpath_content = $path_plugin_data_dir.$path;
		if( !is_file( $realpath_content ) ){
			@header('Content-type: text/html');
			ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
<title>PxFW: File not found.</title>
</head>
<body>
<p>PxFW: File not found.</p>
</body>
</html>
<?php
			print ob_get_clean();
			exit;
		}
		$ext = strtolower( $this->px->dbh()->get_extension( $realpath_content ) );
		switch( $ext ){
			case 'htm':
			case 'html':
				@header('Content-type: text/html'); break;
			case 'gif':
				@header('Content-type: image/gif'); break;
			case 'png':
				@header('Content-type: image/png'); break;
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				@header('Content-type: image/jpeg'); break;
			case 'txt':
				@header('Content-type: text/plain');
			case 'xml':
				@header('Content-type: application/xml');
			case 'pdf':
				@header('Content-type: application/pdf');
		}
		readfile($realpath_content);
		exit;
	}

	/**
	 * Execute PX Command "publish".
	 * @access private
	 * @return null
	 */
	private function page_save_config(){
		if( $this->px->req()->get_param('mode') == 'execute' ){
			if( !$this->px->dbh()->file_overwrite( $this->path_plugin_conf_dir.'targetlist.txt', $this->px->req()->get_param('target_paths') ) ){
				print $this->html_template('<p class="error">失敗しました。</p>');
				exit;
			}
			if( !$this->px->dbh()->file_overwrite( $this->path_plugin_conf_dir.'devicelist.txt', $this->px->req()->get_param('target_device_list') ) ){
				print $this->html_template('<p class="error">失敗しました。</p>');
				exit;
			}
		}
		return $this->px->redirect('?PX=plugins.screenShot');
	}

	/**
	 * Execute PX Command "publish".
	 * @access private
	 * @return null
	 */
	private function execute(){
		$command = $this->get_command();
		while( ob_end_clean() );
		@header('Content-type: text/plain');
		error_reporting(0);
		print ''.$command[0].'.'.$command[1].' | Pickles Framework (version:'.$this->px->get_version().')'."\n";
		print 'project "'.$this->px->get_conf('project.name').'" ('.$this->px->get_conf('project.id').')'."\n";
		print '------'."\n";
		print 'PX command "'.$command[0].'.'.$command[1].'" executed.'."\n";
		print ''.date('Y-m-d H:i:s')."\n";
		if( !$this->px->req()->is_cmd() ){
			print 'Sorry, Web API Access is not supported.'."\n";
			exit;
		}

		$path_plugin_data_dir = $this->path_plugin_data_dir;
		foreach( $this->px->dbh()->ls($path_plugin_data_dir) as $basename ){
			print 'rm '.$path_plugin_data_dir.$basename.' ';
			if( $this->px->dbh()->rm($path_plugin_data_dir.$basename) ){
				print "success."."\n";
			}else{
				print "FAILED."."\n";
			}
		}
		$this->px->dbh()->mkdir( $path_plugin_data_dir.'img/' );

		$url_base = $this->px->req()->get_param('url_base');
		$url_base = preg_replace( '/\/+$/', '', $url_base );

		$page_list = $this->get_target_pages();
		foreach( $page_list as $row ){
			$url = $url_base.$this->px->theme()->href( $row );
			print '* '.$url."\n";
			$page_info = null;
			if( preg_match( '/^https?\:\/\//s', $row ) ){
				$url = $row;
			}else{
				$page_info = $this->px->site()->get_page_info( $row );
			}
			if( is_null($page_info) ){
				print '  [ERROR] undefined page.'."\n";
				$page_info = array();
				$page_info['id'] = '---';
				$page_info['path'] = $row;
				$page_info['title'] = 'undefined page';
			}

			$html_src = '';
			$html_src .= '<!-- '.t::text2html($url).' -->'."\n";
			$html_src .= '<div class="page_unit">'."\n";
			$html_src .= '<h2>'.t::h($url).'</h2>'."\n";
			$html_src .= '<table class="def" style="width:100%;margin-bottom:1em;">'."\n";
			$html_src .= '<thead><tr>'."\n";
			$html_src .= '<th>site name</th>'."\n";
			$html_src .= '<th>page ID</th>'."\n";
			$html_src .= '<th>path</th>'."\n";
			$html_src .= '<th>page title</th>'."\n";
			$html_src .= '<th>date</th>'."\n";
			$html_src .= '</tr></thead>'."\n";
			$html_src .= '<tr>'."\n";
			$html_src .= '<td>'.t::h($this->px->get_conf('project.name')).'('.t::h($this->px->get_conf('project.id')).')</td>'."\n";
			$html_src .= '<td>'.t::h($page_info['id']).'</td>'."\n";
			$html_src .= '<td>'.t::h($page_info['path']).'</td>'."\n";
			$html_src .= '<td>'.t::h($page_info['title']).'</td>'."\n";
			$html_src .= '<td>'.t::h(date('Y-m-d H:i:s')).'</td>'."\n";
			$html_src .= '</tr>'."\n";
			$html_src .= '</table>'."\n";
			$html_src .= '<table class="page_capture">'."\n";
			$html_src .= '<tr>'."\n";
			foreach( $this->get_device_list() as $device_info ){
				print '    '.$device_info['width'].' ...';
				$cmd = $this->mk_cmd_screenshot( $url, $path_plugin_data_dir.'img/'.md5($url).'_'.$device_info['width'].'.png', $device_info['width'], 600, $device_info['ua'] );
				$result = $this->px->dbh()->get_cmd_stdout( $cmd );
				$html_src .= '<td><img src="?PX=plugins.screenShot.data_preview&path=img/'.md5($url).'_'.$device_info['width'].'.png" alt="" /></td>'."\n";
				print ' done.'."\n";
			}

			$html_src .= '</tr>'."\n";
			$html_src .= '</table>'."\n";
			$html_src .= '</div><!-- /.page_unit -->'."\n";
			error_log( $html_src, 3, $path_plugin_data_dir.'_tmp_html.txt' );
		}

		$fin = '';
		$fin .= '<html>'."\n";
		$fin .= '<head>'."\n";
		$fin .= '<title>preview '.t::h( date('Y-m-d H:i:s') ).'</title>'."\n";
		ob_start();
?>
<style type="text/css">
*{
	font-size:xx-small;
}
.page_unit{
	page-break-before: always;
}
.page_unit:first-child{
	page-break-before: auto;
}
table.def {
  border: none;
  border-collapse: collapse;
  text-align: left;
}
table.def th,
table.def td {
  border: 1px solid #999999;
  background: #ffffff;
  padding: 10px;
}
table.def th {
  background: #e7e7e7;
}
table.def thead th,
table.def tfoot th {
  background: #d9d9d9;
  text-align: center;
}
table.def thead td,
table.def tfoot td {
  background: #eeeeee;
}
table.page_capture{
	width:100%;
}
table.page_capture td{
	vertical-align:top;
	text-align:center;
	padding:2pt;
}
img{
	max-width:60%;
	max-height:100%;
}
</style>
<?php
		$fin .= ob_get_clean();
		$fin .= '</head>'."\n";
		$fin .= '<body>'."\n";
		$fin .= $this->px->dbh()->file_get_contents($path_plugin_data_dir.'_tmp_html.txt');
		$fin .= '</body>'."\n";
		$fin .= '</html>';
		$this->px->dbh()->file_overwrite( $path_plugin_data_dir.'preview.html', $fin );
		unlink($path_plugin_data_dir.'_tmp_html.txt');

		print '------'."\n";

		// making PDF
		print 'making PDF ...'."\n";
		$this->output_print_pdf();
		print 'done.'."\n";

		print '------'."\n";
		print ''.date('Y-m-d H:i:s')."\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * 端末情報の一覧を取得
	 */
	private function get_device_list(){
		if( is_array($this->target_device_list) && count($this->target_device_list) ){
			return $this->target_device_list;
		}
		$rtn = array();
		array_push( $rtn, array('width'=>1024,'ua'=>'GoogleChrome') );
		array_push( $rtn, array('width'=>320,'ua'=>'iPhone') );
		return $rtn;
	}
	/**
	 * 端末情報の一覧を文字列で取得
	 */
	private function get_device_list_string(){
		$device_list = $this->get_device_list();
		$rtn = '';
		foreach( $device_list as $row ){
			$rtn .= $row['width'].':'.$row['ua']."\n";
		}
		return $rtn;
	}

	/**
	 * 対象ページの一覧を作成
	 */
	private function get_target_pages(){
		if( is_array($this->target_path_list) && count($this->target_path_list) ){
			// 指定があったらそれを使用
			return $this->target_path_list;
		}

		$sitemap = $this->px->site()->get_sitemap();
		$rtn = array();
		foreach( $sitemap as $page_info ){
			array_push( $rtn, $page_info['path'] );
		}
		return $rtn;
	}

	/**
	 * PDF を作成する
	 */
	private function output_print_pdf(){
		$url = '';
		$path_plugin_data_dir = $this->path_plugin_data_dir;
		$url_base = $this->px->req()->get_param('url_base');
		$url = $this->px->href_self();
		$url = preg_replace('/^\/+/', '', $url);
		$url = $url_base.$url.'?PX=plugins.screenShot.data_preview&path=preview.html';
		$cmd = $this->mk_cmd_pdf( $url, $path_plugin_data_dir.'print.pdf' );
		$result = $this->px->dbh()->get_cmd_stdout( $cmd );
		return true;
	}

	/**
	 * キャプチャ生成コマンドを生成する
	 */
	private function mk_cmd_screenshot( $url, $output, $width, $height, $user_agent ){
		$cmd_phantomjs = 'phantomjs';
		$cmd_phantom_webpage = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir').'plugins/screenShot/libs/phantom/_phantom_capture.js' );

		$cmd = escapeshellarg($cmd_phantomjs)
			.' '.escapeshellarg($cmd_phantom_webpage)
			.' '.escapeshellarg($url)
			.' '.escapeshellarg($output)
			.' '.escapeshellarg($width)
			.' '.escapeshellarg($height)
			.' '.escapeshellarg($user_agent)
		;
		return $cmd;
	}

	/**
	 * PDF生成コマンドを生成する
	 */
	private function mk_cmd_pdf( $url, $output, $width, $height, $user_agent ){
		$cmd_phantomjs = 'phantomjs';
		$cmd_phantom_webpage = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir').'plugins/screenShot/libs/phantom/_phantom_pdf.js' );

		$cmd = escapeshellarg($cmd_phantomjs)
			.' '.escapeshellarg($cmd_phantom_webpage)
			.' '.escapeshellarg($url)
			.' '.escapeshellarg($output)
			.' '.escapeshellarg($width)
			.' '.escapeshellarg($height)
			.' '.escapeshellarg($user_agent)
		;
		return $cmd;
	}
}

?>