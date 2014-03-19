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
		$src .= '       	<dd>$ php '.t::h($_SERVER['SCRIPT_FILENAME']).' PX=plugins.screenShot.run host='.t::h($_SERVER['HTTP_HOST']).'</dd>'."\n";
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
		print 'PX command "'.$command[0].'" executed.'."\n";
		if( !$this->px->req()->is_cmd() ){
			print 'Sorry, GUI is not supported.'."\n";
			exit;
		}
		print 'exit.'."\n";
		exit;
	}

}

?>