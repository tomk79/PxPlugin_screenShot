<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "screenShot"
 */
class pxplugin_screenShot_register_pxcommand extends px_bases_pxcommand{


	/**
	 * コンストラクタ
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$command = $this->get_command();

		switch( $command[2] ){
			case 'run':
				$this->execute();
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		$src .= '<div class="unit">'."\n";
		$src .= '	<p>プロジェクト『'.t::h($this->px->get_conf('project.name')).'』のページのスクリーンショットを作成します。</p>'."\n";
		$src .= '	<p>この機能は、コマンドラインから実行します。MacOSX上で動作します。Windowsでは使えません。</p>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";

		$src .= '<div class="topic_box">'."\n";
		$src .= '   <p>screenShotは、次のコマンドから実行できます。</p>'."\n";
		$src .= '   <dl>'."\n";
		$src .= '		<dt>"php" コマンドが使える場合</dt>'."\n";
		$src .= '       	<dd>$ php '.t::h($_SERVER['SCRIPT_FILENAME']).' PX=plugins.screenShot.run host='.t::h('http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->theme()->href('/')).'</dd>'."\n";
		$src .= '   </dl>'."\n";
		$src .= '</div><!-- /.topic_box -->'."\n";

		print $this->html_template($src);
		exit;
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
		if( !$this->px->req()->is_cmd() ){
			print 'Sorry, GUI is not supported.'."\n";
			exit;
		}

		$path_plugin_data_dir = $this->px->realpath_plugin_ramdata_dir('screenShot');
		foreach( $this->px->dbh()->ls($path_plugin_data_dir) as $basename ){
			print 'rm '.$path_plugin_data_dir.$basename.' ';
			if( $this->px->dbh()->rm($path_plugin_data_dir.$basename) ){
				print "success."."\n";
			}else{
				print "FAILED."."\n";
			}
		}
		$this->px->dbh()->mkdir( $path_plugin_data_dir.'img/' );

		$host = $this->px->req()->get_param('host');
		$host = preg_replace( '/\/+$/', '', $host );

		$page_list = $this->get_target_pages();
		foreach( $page_list as $row ){
			$url = $host.$this->px->theme()->href( $row['id'] );
			print '* '.$url."\n";

			$html_src = '';
			$html_src .= '<!-- '.t::text2html($url).' -->'."\n";
			$html_src .= '<div class="page_unit">'."\n";
			$html_src .= '<h2>'.t::data2text($url).'</h2>'."\n";
			$html_src .= '<table>'."\n";
			$html_src .= '<tr>'."\n";
			foreach( $this->get_device_list() as $device_info ){
				$cmd = $this->mk_cmd_screenshot( $url, $path_plugin_data_dir.'img/'.md5($url).'_'.$device_info['width'].'.png', $device_info['width'], 100, $device_info['ua'] );
				$result = $this->px->dbh()->get_cmd_stdout( $cmd );
				$html_src .= '<td><img src="./img/'.md5($url).'_'.$device_info['width'].'.png" alt="" /></td>'."\n";
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
	page-break-after: always;
}
table{
	width:100%;
}
table td{
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



		print 'exit.'."\n";
		exit;
	}

	/**
	 * 端末情報の一覧を取得
	 */
	private function get_device_list(){
		$rtn = array();
		array_push( $rtn, array('width'=>1024,'ua'=>'GoogleChrome') );
		array_push( $rtn, array('width'=>800,'ua'=>'iPad') );
		array_push( $rtn, array('width'=>320,'ua'=>'iPhone') );
		return $rtn;
	}

	/**
	 * 対象ページの一覧を作成
	 */
	private function get_target_pages(){
		return $this->px->site()->get_sitemap();
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
}

?>