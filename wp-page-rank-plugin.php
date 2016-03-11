<?php
/*
Plugin Name: Wp Page Rank Plugin
Description: This plugin returns the google page rank and alexa page rank for the given set of urls. Collectively it calculates
the page rank of a given url by combining both the google and alexa rank.
Version: 1.0
Author: Gopinath Shiva
Author URI: https://facebook.com/gopinath.shiva
Skype: gopinathshiva
*/

//to calculate google page rank
class GooglePageRank{
	public function get_google_pagerank($url) {
		$query="http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=".$this->CheckHash($this->HashURL($url)). "&features=Rank&q=info:".$url."&num=100&filter=0";
		$curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL,$query);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    $data = curl_exec($curl);
	    curl_close($curl);
		$pos = strpos($data, "Rank_");
		if($pos === false){} else{
			$pagerank = substr($data, $pos + 9);
			return $pagerank;
		}
	}

	public function get_google_pageranks($urls){

		$mh = curl_multi_init();
		$curl_handlers = array();

		for($i=0;$i<count($urls);$i++){
			$url = $urls[$i];
			$query="http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=".$this->CheckHash($this->HashURL($url)). "&features=Rank&q=info:".$url."&num=100&filter=0";
			$ch = curl_init($query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$curl_handlers[] = $ch;
			curl_multi_add_handle($mh, $ch);
		}

		// execute all queries simultaneously, and continue when all are complete
		$running = null;
		do {
		  curl_multi_exec($mh, $running);
		} while ($running);

		// all of our requests are done, we can now access the results
		$page_ranks = array();
		for($i = 0; $i<count($curl_handlers); $i++){
			$response = curl_multi_getcontent($curl_handlers[$i]);
			$pos = strpos($response, "Rank_");
			if($pos === false){
				$page_ranks[] = 0;
			} else{
				$pagerank = substr($response, $pos + 9);
				$page_ranks[] = $pagerank;
			}
		}
		return $page_ranks;
	}


	public function StrToNum($Str, $Check, $Magic){
		$Int32Unit = 4294967296; // 2^32
		$length = strlen($Str);
		for ($i = 0; $i < $length; $i++) {
			$Check *= $Magic;
			if ($Check >= $Int32Unit) {
				$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
				$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
			}
			$Check += ord($Str{$i});
		}
		return $Check;
	}

	public function HashURL($String){
		$Check1 = $this->StrToNum($String, 0x1505, 0x21);
		$Check2 = $this->StrToNum($String, 0, 0x1003F);
		$Check1 >>= 2;
		$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
		$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
		$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);
		$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
		$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
		return ($T1 | $T2);
	}

	public function CheckHash($Hashnum){
		$CheckByte = 0;
		$Flag = 0;
		$HashStr = sprintf('%u', $Hashnum) ;
		$length = strlen($HashStr);
		for ($i = $length - 1; $i >= 0; $i --) {
			$Re = $HashStr{$i};
			if (1 === ($Flag % 2)) {
				$Re += $Re;
				$Re = (int)($Re / 10) + ($Re % 10);
			}
			$CheckByte += $Re;
			$Flag ++;
		}
		$CheckByte %= 10;
		if (0 !== $CheckByte) {
			$CheckByte = 10 - $CheckByte;
			if (1 === ($Flag % 2) ) {
				if (1 === ($CheckByte % 2)) {
					$CheckByte += 9;
				}
					$CheckByte >>= 1;
			}
		}
		return '7'.$CheckByte.$HashStr;
	}
}

add_action('get_wp_google_pagerank','get_wp_google_pagerank_cb');

function get_wp_google_pagerank_cb($webUrl){
    //$webUrl=$_POST['website_url'];
    $objGooglePageRank = new GooglePageRank();
    $result =  "$webUrl has Google PageRank:". $objGooglePageRank->get_google_pagerank($webUrl);
    echo $result;
}

add_action( 'admin_menu', 'wp_page_rank_admin_menu' );
add_action('get_wp_page_rank_url_ranks','get_wp_page_rank_url_ranks_cb');

function wp_page_rank_admin_menu() {
    //creating plugin sub-menu under settings menu
	$wp_page_rank_plugin_page = add_options_page( 'Wp Page Rank', 'WordPress Page Rank', 'manage_options', 'wp-page-rank-plugin.php', 'wp_page_rank_plugin_admin_page', 'dashicons-tickets');
    //call register settings function
	add_action( 'admin_init', 'register_wp_page_rank_plugin_settings' );
    add_action( 'load-' . $wp_page_rank_plugin_page, 'wp_page_rank_plugin_page_enqueue_scripts' );
}

function wp_page_rank_plugin_page_enqueue_scripts(){
    wp_enqueue_style( 'custom_wp_admin_css', plugins_url('wp-page-rank-plugin/css/admin-style.css'),__FILE__);
    wp_enqueue_script('my_script', plugins_url('js/admin-script.js', __FILE__),array("jquery"),null,true);
}

function register_wp_page_rank_plugin_settings(){
    //register plugin settings to store urls
	register_setting( 'wp-page-rank-urls', 'wp_page_rank_urls_name' );
}

function wp_page_rank_plugin_admin_page(){
    ?>
    <div class="wrap">
    <h2>WordPress Page Rank Plugin</h2>
    <?php do_action('get_wp_page_rank_url_ranks'); ?>
    <h3>Add Page Rank Urls below:</h3>
    <?php
    $urls = get_option('wp_page_rank_urls_name');
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'wp-page-rank-urls' ); ?>
        <?php do_settings_sections( 'wp-page-rank-urls' ); ?>
        <table id='wp-page-rank-url-table' class="form-table">
            <?php if(empty($urls)){ ?>
                <tr valign="top">
                <td><input required pattern="^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?" type="url" name="wp_page_rank_urls_name[]" value="" /></td>
                <td><button type='button' id='wp-page-rank-delete-url'>Delete Url</button></td>
                </tr>
            <?php }else{
                foreach($urls as $key => $url) { ?>
                    <tr valign="top">
                    <td><input required pattern="^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?" type="url" name="wp_page_rank_urls_name[]" value="<?php echo $url ?>" /></td>
                    <td><button type='button' id='wp-page-rank-delete-url'>Delete Url</button></td>
                    </tr>
                <?php }
            } ?>
        </table>
        <button type='button' id='wp-page-rank-add-url'>Add Url</button>
        <?php submit_button(); ?>
    </form>
    </div>
<?php }

function get_wp_page_rank_url_ranks_cb(){

	$urls = get_option('wp_page_rank_urls_name');

	if(empty($urls)){return;}

	$objGooglePageRank = new GooglePageRank();
	$page_ranks = $objGooglePageRank->get_google_pageranks($urls);
	for($i = 0; $i < count($page_ranks); $i++){
		echo $urls[$i]." has Google PageRank:". $page_ranks[$i];
	}

	echo getAlexaRank('http://coffeecupweb.com');
}

//PHP Script to Fetch Alexa Rank
function getAlexaRank($url){
	$xml = simplexml_load_file('http://data.alexa.com/data?cli=10&dat=snbamz&url='.$url);
	$rank=isset($xml->SD[1]->POPULARITY)?$xml->SD[1]->POPULARITY->attributes()->TEXT:0;
	return $rank;
}

?>
