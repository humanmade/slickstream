<?php
//NOTE: All inline JS scripts embedded by the plugin need to have the string `slickstream` somewhere in them;
//  This allows the string `slickstream` to be used in WP-Rocket lazy load exclusions

include_once 'SlickEngagement_LifeCycle.php';
include_once 'SlickEngagement_Widgets.php';

define('PLUGIN_VERSION', '1.4.4');

define("GENESIS_AFTER_HEADER_POSTS", "After header on posts (for Genesis themes)");
define("GENESIS_BEFORE_CONTENT_POSTS", "Before content on posts (for Genesis themes)");
define("GENESIS_AFTER_CONTENT", "After content (for Genesis themes)");
define("GENESIS_BEFORE_FOOTER", "Before footer (for Genesis themes)");

class SlickEngagement_Plugin extends SlickEngagement_LifeCycle
{
    private $consoleOutput = "";
    private $scriptClass = 'slickstream-script';
    const defaultServerUrl = 'https://app.slickstream.com';
    /**
     * @return array of option meta data.
     */
    public function getOptionMetaData()
    {
        $options = array(
            'SiteCode' => array(__('Site Code', 'slick-engagement')),
            'SlickServerUrl' => array(__('Service URL (optional)', 'slick-engagement')),
        );

        if (function_exists('genesis')) {
            $options = array_merge($options, [
                'InsertFilmstrip' => array(__('Insert filmstrip', 'slick-engagement'), 'None', GENESIS_AFTER_HEADER_POSTS, GENESIS_BEFORE_CONTENT_POSTS),
                'InsertSearchPanel' => array(__('Insert inline search panel', 'slick-engagement'), 'None', GENESIS_AFTER_CONTENT, GENESIS_BEFORE_FOOTER),
            ]);
        }

        return $options;
    }

    protected function addOptionsFromArray($options)
    {
        foreach ($options as $key => $arr) {
            if (is_array($arr) && count($arr) > 1) {
                $this->addOption($key, $arr[1]);
            }
        }
    }

    protected function initOptions()
    {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            $this->addOptionsFromArray($options);
        }
    }

    public function getPluginDisplayName()
    {
        return 'Slickstream Engagement';
    }

    protected function getMainPluginFileName()
    {
        return 'slick-engagement.php';
    }

    protected function installDatabaseTables()
    {
    }

    protected function unInstallDatabaseTables()
    {
    }

    public function upgrade()
    {
    }

    public function addActionsAndFilters()
    {
        // Add options administration page
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Add Actions & Filters
        add_action('wp_head', array(&$this, 'addSlickPageHeader'));

        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));

        // Register short codes
        add_shortcode('slick-film-strip', array($this, 'doFilmStripShortcode'));
        add_shortcode('slick-game', array($this, 'doGameShortcode'));
        // add_shortcode('slick-next-up', array($this, 'doNextUpShortcode'));
        add_shortcode('slick-grid', array($this, 'doSlickGridShortcode'));
        add_shortcode('slick-story', array($this, 'doSlickStoryShortcode'));
        add_shortcode('slick-story-carousel', array($this, 'doSlickStoryCarouselShortcode'));
        add_shortcode('slick-story-explorer', array($this, 'doSlickStoryExplorerShortcode'));

        // Register AJAX hooks
        // Ensure pages can be configured with categories and tags
        add_action('init', array(&$this, 'add_taxonomies_to_pages'));

        // Register cron hook for processing.
        add_action( 'slickstream_fetch_boot_data', array( &$this, 'process_fetch_boot_data' ), 10, 3 );

        $prefix = is_network_admin() ? 'network_admin_' : '';
        $plugin_file = plugin_basename($this->getPluginDir() . DIRECTORY_SEPARATOR . $this->getMainPluginFileName()); //plugin_basename( $this->getMainPluginFileName() );
        // $this->guildLog('Adding filter ' . "{$prefix}plugin_action_links_{$plugin_file}");
        add_filter("{$prefix}plugin_action_links_{$plugin_file}", array(&$this, 'onActionLinks'));

        add_filter('rocket_delay_js_exclusions', array(&$this, 'np_wp_rocket__exclude_from_delay_js'));

        $this->maybe_add_genesis_hooks();
    }

    private function maybe_add_genesis_hooks() {
        $insertFilmstrip = $this->getOption('InsertFilmstrip', 'None');
        if ($insertFilmstrip === GENESIS_AFTER_HEADER_POSTS) {
            add_action('genesis_after_header', array($this, 'insert_film_strip_markup'), 15);
        } else if ($insertFilmstrip === GENESIS_BEFORE_CONTENT_POSTS) {
            add_action('genesis_before_content', array($this, 'insert_film_strip_markup'), 15);
        }

        $InsertSearchPanel = $this->getOption('InsertSearchPanel', 'None');
        if ($InsertSearchPanel === GENESIS_AFTER_CONTENT) {
            add_action('genesis_after_content', array($this, 'insert_inline_search_panel_markup'), 15);
        } else if ($InsertSearchPanel === GENESIS_BEFORE_FOOTER) {
            add_action('genesis_before_footer', array($this, 'insert_inline_search_panel_markup'), 15);
        }
    }

    public function insert_film_strip_markup()
    {
        if (is_singular('post')) {
            echo '<div style="min-height:72px;margin:10px auto" class="slick-film-strip"></div>';
        }
    }

    public function insert_inline_search_panel_markup()
    {
        if (is_singular('post')) {
            echo "\n" . '<style>.slick-inline-search-panel { margin: 50px 15px; min-height: 428px; } @media (max-width: 600px) { .slick-inline-search-panel { min-height: 334px; } } </style>' . "\n";
            echo '<div class="slick-inline-search-panel" data-config="_default"></div>' . "\n";
        }
    }

    // Exclude scripts from JS delay in WP-Rocket.
    public function np_wp_rocket__exclude_from_delay_js($excluded_strings = array())
    {
        // MUST ESCAPE PERIODS AND PARENTHESES!
        $excluded_strings[] = "slickstream";
        $excluded_strings[] = "Slickstream";
        $excluded_strings[] = "SLICKSTREAM";
        $excluded_strings[] = "ads\.adthrive\.com";
        return $excluded_strings;
    }

    public function onActionLinks($links)
    {
        // $this->guildLog('onActionLinks ' . admin_url('options-general.php?page=SlickEngagement_PluginSettings'));
        $mylinks = array('<a href="' . admin_url('options-general.php?page=SlickEngagement_PluginSettings') . '">Settings</a>');
        return array_merge($links, $mylinks);
    }

    public function doFilmStripShortcode()
    {
        return '<div class="slick-widget slick-film-strip slick-shortcode"></div>';
    }

    public function doGameShortcode()
    {
        return '<div class="slick-widget slick-game-panel slick-shortcode"></div>';
    }

    // public function doNextUpShortcode()
    // {
    //     return '<div class="slick-widget slick-next-up slick-shortcode"></div>';
    // }

    public function doSlickGridShortcode($attrs, $content, $tag)
    {
        extract(shortcode_atts(array('id' => ''), $attrs));
        if (isset($id)) {
            return '<div class="slick-content-grid" data-config="' . trim($id) . '"></div>' . "\n";
        } else {
            return '<div class="slick-content-grid"></div>' . "\n";
        }
    }

    public function doSlickStoryCarouselShortcode()
    {
        return '<style>.slick-story-carousel {min-height: 324px;} @media (max-width: 600px) {.slick-story-carousel {min-height: 224px;}}</style>' . "\n" . '<div class="slick-widget slick-story-carousel slick-shortcode"></div>';
    }

    public function doSlickStoryExplorerShortcode()
    {
        return '<div class="slick-widget slick-story-explorer slick-shortcode"></div>';
    }

    public function doSlickStoryShortcode($attrs, $content, $tag)
    {
        extract(shortcode_atts(array('src' => ''), $attrs));
        // We want to support different styles of short-code arguments:
        //   old-style: https://:channelid.stories.slickstream.com/d/story/:channelid/:storyid
        //   story page URL: https://stories.slickstream.com/:channelid/story/:storyid
        //   new-style: :channelid/:storyid
        $oldStyleRegex = '/^https\:\/\/([^\/]+)\/d\/story\/([^\/]+)\/([^\/]+)$/i';
        $revisedStyleRegex = '/^https\:\/\/([^\/]+)\/([^\/]+)\/d\/story\/([^\/]+)$/i';
        $storyPageRegex = '/^https\:\/\/([^\/]+)\/([^\/]+)\/story\/([^\/]+)$/i';
        $newStyleRegex = '/^([^\/]+)\/([^\/]+)$/i';
        $domain = "stories.slickstream.com";
        $slickServerUrl = $this->getOption('SlickServerUrl', $defaultServerUrl);
        if (preg_match('/\-staging\.slickstream/', $slickServerUrl)) {
            $domain = "stories-staging.slickstream.com";
        }
        $channelid = "nochannel";
        $storyid = "";
        $webStoryUrl = "";
        if (preg_match_all($oldStyleRegex, $src, $matches)) {
            $domain = $matches[1][0];
            $channelid = $matches[2][0];
            $storyid = $matches[3][0];
            $webStoryUrl = $this->getSlickstreamWebStoryUrl($domain, $channelid, $storyid);
        } else if (preg_match_all($revisedStyleRegex, $src, $matches)) {
            $domain = $matches[1][0];
            $channelid = $matches[2][0];
            $storyid = $matches[3][0];
            $webStoryUrl = $this->getSlickstreamWebStoryUrl($domain, $channelid, $storyid);
        } else if (preg_match_all($storyPageRegex, $src, $matches)) {
            $domain = $matches[1][0];
            $channelid = $matches[2][0];
            $storyid = $matches[3][0];
            $webStoryUrl = $this->getSlickstreamWebStoryUrl($domain, $channelid, $storyid);
        } else if (preg_match_all($newStyleRegex, $src, $matches)) {
            $channelid = $matches[1][0];
            $storyid = $matches[2][0];
            $webStoryUrl = $this->getSlickstreamWebStoryUrl($domain, $channelid, $storyid);
        } else {
            $webStoryUrl = $src;
        }
        $output = '';
        if (!empty($webStoryUrl)) {
            if (empty($storyId)) {
                $storyId = $this->getStoryIdFromUrl($webStoryUrl);
            }
            $output .= '<slick-webstory-player id="story-' . $storyId . '">' . "\n";
            $output .= '  <a href="' . $webStoryUrl . '"></a>' . "\n";
            $output .= '</slick-webstory-player>' . "\n";
        }
        return $output;
    }

    public function getStoryIdFromUrl($url)
    {
        if (strpos($url, 'slickstream.com') !== false && strpos($url, '/d/webstory') !== false) {
            $parts = explode('/', $url);
            if (count($parts) > 1) {
                if (!empty($parts[count($parts) - 1])) {
                    return $parts[count($parts) - 1];
                }
            }
        }
        return substr(hash('md5', $url), 0, 5);
    }

    public function getSlickstreamWebStoryUrl($domain, $channelId, $storyId)
    {
        return 'https://' . $domain . '/' . $channelId . '/d/webstory/' . $storyId;
    }

    public function add_taxonomies_to_pages()
    {
        register_taxonomy_for_object_type('post_tag', 'page');
        register_taxonomy_for_object_type('category', 'page');
    }

    /* determine whether post has a featured image, if not, find the first image inside the post content, $size passes the thumbnail size, $url determines whether to return a URL or a full image tag*/
    /* adapted from http://www.amberweinberg.com/wordpress-find-featured-image-or-first-image-in-post-find-dimensions-id-by-url/ */
    public function getPostImage($post)
    {
        ob_start();
        ob_end_clean();

        //If there's a featured image, show it
        if (has_post_thumbnail($post)) {
            $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'single-post-thumbnail');
            return $images[0];
        } else {
            $content = $post->post_content;
            $first_img = '';
            $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
            $first_img = $matches[1][0];

            //No featured image, so we get the first image inside the post content
            if ($first_img) {
                return $first_img;
            } else {
                return null;
            }
        }
    }

    public function removeSemicolons($value)
    {
        return str_replace(';', ' ', $value);
    }

    // Fetches the Page Boot Data from the server
    //TODO: if we find that `/page-boot-data` requests are reduced enough to always use origin, we can add headers to avoid hitting CloudFlare
    private function fetchBootData( $siteCode )
    {
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $page_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $remote = self::defaultServerUrl . '/d/page-boot-data?site=' . $siteCode . '&url=' . rawurlencode($page_url);
        $headers = array('referer' => home_url());
        $this->echoSlickstreamComment("Fetch endpoint: " . $remote);
        $this->echoSlickstreamComment("Headers: " . json_encode($headers));
        $response = wp_remote_get($remote, array('timeout' => 2, 'headers' => $headers));
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_text = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            return json_decode($response_text);
        } else {
            error_log( "Error fetching boot data: " . print_r( $response_text, true ) );
            return null;
        }
    }

    private function echoSlickBootJs($boot_data_obj) {
        $boot_data_json = json_encode($boot_data_obj);

        if (false === $boot_data_json) {
            $this->echoSlickstreamComment('Error encoding page boot data JSON');
            return;
        }

        $this->echoSlickstreamComment('Page Boot Data:', false);

        echo <<<JSBLOCK
        <script class='$this->scriptClass'>
        (function() {
            "slickstream";
            const win = window;
            win.\$slickBoot = win.\$slickBoot || {};
            win.\$slickBoot.d = ${boot_data_json};
            win.\$slickBoot.s = 'plugin';
            win.\$slickBoot._bd = performance.now();
        })();
        </script>\n
        JSBLOCK;

        $this->echoSlickstreamComment('END Page Boot Data', false);
    }

    private function isMobile() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
          $user_agent = $_SERVER['HTTP_USER_AGENT'];
          $excluded = preg_match('/Tablet|iPad|Playbook|Nook|webOS|Kindle|Android (?!.*Mobile).*Safari/i', $user_agent);
          $mobile = preg_match('/Mobi|iP(hone|od)|Opera Mini/i', $user_agent);
          return $mobile && !$excluded;
        }
        return false;
    }

    private function getBootDataForDevice($boot_data_obj) {
        if (isset($boot_data_obj->v2)) {
            $boot_data_obj_v2 = $boot_data_obj->v2;
            if (isset($boot_data_obj_v2->phone) && $this->isMobile()) {
              return $boot_data_obj_v2->phone;
            }
            return $boot_data_obj_v2->desktop;
        }
        return $boot_data_obj;
    }

    private function echoClsData($boot_data_obj)
    {
        $device_boot_data = $this->getBootDataForDevice($boot_data_obj);

        $filmstrip_config = isset($device_boot_data->filmstrip) ? $device_boot_data->filmstrip : '';
        $dcm_config = isset($device_boot_data->inlineSearch) ? $device_boot_data->inlineSearch : '';
        $ec_config = isset($device_boot_data->emailCapture) ? $device_boot_data->emailCapture : '';

        // from 1.2.5 settings
        $filmstrip_margin = $this->getOption('ReserveFilmstripMargin', '');
        $dcm_margin = $this->getOption('ReserveDCMMargin', '');
        if (!empty($filmstrip_config) && !empty($filmstrip_margin)) {
            $filmstrip_config->marginLegacy = $filmstrip_margin;
        }
        if (!empty($dcm_config) && !empty($dcm_margin)) {
            foreach ($dcm_config as $config) {
              $config->marginLegacy = $dcm_margin;
            }
        }

        if (!empty($filmstrip_config) || !empty($dcm_config) || !empty($ec_config)) {
            $filmstrip_str = empty($filmstrip_config) ? '' :  json_encode($filmstrip_config);
            $dcm_str = empty($dcm_config) ? '' :  json_encode($dcm_config);
            $ec_str = empty($ec_config) ? '' :  json_encode($ec_config);

            $this->echoSlickstreamComment('CLS Insertion:', false);

            echo "<script>\n";
            echo '"use strict";(async(e,t,n)=>{const o="slickstream";const i=e?JSON.parse(e):null;const r=t?JSON.parse(t):null;const c=n?JSON.parse(n):null;if(i||r||c){const e=async()=>{if(document.body){if(i){m(i.selector,i.position||"after selector","slick-film-strip",i.minHeight||72,i.margin||i.marginLegacy||"10px auto")}if(r){r.forEach((e=>{if(e.selector){m(e.selector,e.position||"after selector","slick-inline-search-panel",e.minHeight||350,e.margin||e.marginLegacy||"50px 15px",e.id)}}))}if(c){s(c)}return}window.requestAnimationFrame(e)};window.requestAnimationFrame(e)}const s=async e=>{const t="slick-on-page";try{if(document.querySelector(`.${t}`)){return}const n=l()?e.minHeightMobile||220:e.minHeight||200;if(e.cssSelector){m(e.cssSelector,"before selector",t,n,"",undefined)}else{a(e.pLocation||3,t,n)}}catch(e){console.log("plugin","error",o,`Failed to inject ${t}`)}};const a=async(e,t,n)=>{const o=document.createElement("div");o.classList.add(t);o.classList.add("cls-inserted");o.style.minHeight=n+"px";const i=document.querySelectorAll("article p");if((i===null||i===void 0?void 0:i.length)>=e){const t=i[e-1];t.insertAdjacentElement("afterend",o);return o}const r=document.querySelectorAll("section.wp-block-template-part div.entry-content p");if((r===null||r===void 0?void 0:r.length)>=e){const t=r[e-1];t.insertAdjacentElement("afterend",o);return o}return null};const l=()=>{const e=navigator.userAgent;const t=/Tablet|iPad|Playbook|Nook|webOS|Kindle|Android (?!.*Mobile).*Safari/i.test(e);const n=/Mobi|iP(hone|od)|Opera Mini/i.test(e);return n&&!t};const d=async(e,t)=>{const n=Date.now();while(true){const o=document.querySelector(e);if(o){return o}const i=Date.now();if(i-n>=t){throw new Error("Timeout")}await u(200)}};const u=async e=>new Promise((t=>{setTimeout(t,e)}));const m=async(e,t,n,i,r,c)=>{try{const o=await d(e,5e3);const s=c?document.querySelector(`.${n}[data-config="${c}"]`):document.querySelector(`.${n}`);if(o&&!s){const e=document.createElement("div");e.style.minHeight=i+"px";e.style.margin=r;e.classList.add(n);e.classList.add("cls-inserted");if(c){e.dataset.config=c}switch(t){case"after selector":o.insertAdjacentElement("afterend",e);break;case"before selector":o.insertAdjacentElement("beforebegin",e);break;case"first child of selector":o.insertAdjacentElement("afterbegin",e);break;case"last child of selector":o.insertAdjacentElement("beforeend",e);break}return e}}catch(t){console.log("plugin","error",o,`Failed to inject ${n} for selector ${e}`)}return false}})' . "\n";
            echo "('" . addslashes($filmstrip_str) . "','" . addslashes($dcm_str) . "','" . addslashes($ec_str) . "');" . "\n";
            echo "\n</script>\n";

            $this->echoSlickstreamComment('END CLS Insertion', false);
        }
    }

    //Returns a name for the "page boot data" transient
    private function getTransientName()
    {
        defined( 'PAGE_BOOT_DATA_TRANSIENT_PREFIX' ) || DEFINE( 'PAGE_BOOT_DATA_TRANSIENT_PREFIX', 'slick_page_boot_' );
        // Use get_the_permalink() instead of $_SERVER['REQUEST_URI'] to avoid issues with:
        // query strings, hashed URLs, comment pagination etc. Creates less overhead due to being
        // called only on the "parent" page instead of every variation of the page.
        //$normalized_url = $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0];
        $normalized_url = get_the_permalink();
        return PAGE_BOOT_DATA_TRANSIENT_PREFIX . md5($normalized_url);
    }

    private function isDebugModeEnabled()
    {
        $debugModeParam = $this->getQueryParamByName('slickdebug');
        return $debugModeParam === "1";
    }

    private function echoSlickstreamComment($comment, $echoToConsole = true)
    {
        echo "<!-- [slickstream] " . $comment . " -->\n";

        if ($echoToConsole) {
            $this->consoleOutput .= $comment . "\n";
        }
    }

    private function debugCLS()
    {
        echo <<<JSDOC
        <script class='$this->scriptClass'>
        (function () {
        const slickBanner = "[slickstream]";
        const clsDataCallback = (clsData) => {
            if (typeof(clsData.value) !== "number" || !clsData.attribution) {
                console.info('Invalid CLS data object.');
                return;
            }
            console.info(`\${slickBanner} The CLS score on this page is: \${clsData.value.toFixed(3)}, which is considered \${clsData.rating}`);
            if (clsData.value.toFixed(3) > 0.000) {
                console.info(`\${slickBanner} The element that contributed the most CLS is:`);
                console.info(clsData.attribution.largestShiftSource.node);
                console.table(clsData.attribution);
            }
        };

        console.info(`\${slickBanner} Monitoring for CLS...`);
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/web-vitals@3/dist/web-vitals.attribution.iife.js';
        script.onload = function () {
            webVitals.onCLS(clsDataCallback);
            //webVitals.onFID(console.info);
            //webVitals.onLCP(console.info);
        };
        document.head.appendChild(script);
        })();
        </script>
        JSDOC;
    }

    private function echoConsoleOutput()
    {
        if ($this->consoleOutput !== "")
        {
            echo "<script class='$this->scriptClass'>console.info(`[slickstream]\n$this->consoleOutput`)</script>\n";
        }
    }

    //TODO: Add functionality to detect if the page was cached (by a CDN or otherwise)
    private function getCurrentTimestampByTimeZone($tz_name)
    {
        $timestamp = time();
        $dt = new DateTime('now', new DateTimeZone($tz_name));
        $dt->setTimestamp($timestamp);
        return $dt->format('n/j/Y, g:i:s A');
    }

    // Delete transient boot data if `&delete-boot=1` is passed in
    private function handleDeleteBootData()
    {
        $delete_transient_param = $this->getQueryParamByName('delete-boot');
        $delete_transient_data = ($delete_transient_param === '1');

        if (!$delete_transient_data) {
            return;
        }

        $this->echoSlickstreamComment("Deleting page boot data from cache");
        $comment = (false === delete_transient($this->getTransientName())) ?
            "Nothing to do--page not found in cache" :
            "Page boot data deleted successfully";
        $this->echoSlickstreamComment($comment);
    }

    private function getQueryParamByName($param_name)
    {
        $param_val = isset($_GET[$param_name]) ? $_GET[$param_name] : null;
        return $param_val;
    }

    private function echoPageBootData()
    {
        $siteCode = substr(trim($this->getOption('SiteCode')), 0, 9);

        if (!$siteCode) {
            $this->echoSlickstreamComment("ERROR: Site Code missing from Plugin Settings; Slickstream services are disabled");
            return;
        }

        global $wp;

        $transient_name = $this->getTransientName(); // Name for WP Transient Cache API Key
        $transient_name_ttl = $transient_name . '_ttl'; // Name for WP Transient Cache TTL Key

        // If `delete-boot=1` is passed as a query param, delete the stored page boot data
        $this->handleDeleteBootData();

        $no_transient_data = false === ($boot_data_obj = get_transient($transient_name)); //get_transient returns `false` if the key doesn't exist
        $is_expired = get_transient( $transient_name_ttl ) < time();

        // If `slick-boot=1` is passed as a query param, force a re-fetch of the boot data from the server
        // If `slick-boot=0` is passed as a query param, skip fetching boot data from the server
        $slick_boot_param = $this->getQueryParamByName('slick-boot');
        $force_fetch_boot_data = ($slick_boot_param === '1');
        $dont_load_boot_data = ($slick_boot_param === '0');
        $slick_boot_param_not_set = ($slick_boot_param === null);

        if ( $dont_load_boot_data ) {
            $this->echoSlickstreamComment("Skipping page boot data and CLS output");
            return;
        }

        if ( ( $force_fetch_boot_data || $no_transient_data || $is_expired ) ) {
            if ( ! wp_next_scheduled( 'slickstream_fetch_boot_data', [ $siteCode, $transient_name, $transient_name_ttl ] ) ) {
                $this->echoSlickstreamComment( 'Requesting page boot data via cron.' );
                wp_schedule_single_event( time(), 'slickstream_fetch_boot_data', [ $siteCode, $transient_name, $transient_name_ttl ] );
            }
        } else {
            $this->echoSlickstreamComment("Using cached page boot data: " . $transient_name);
        }

        $this->echoSlickBootJs($boot_data_obj);
        $this->echoClsData($boot_data_obj);
    }

    //Plugin-based A/B Testing JS Logic
    private function getAbTestJs()
    {

        $jsBlock = <<<JSBLOCK
        window.slickAbTestResult = function(percentEnabled, recalculate = false, testName = 'embed') {
        const win = window;
        const storage = win.localStorage;
        const targetPercentEnabled = parseInt(percentEnabled);

        if (isNaN(targetPercentEnabled)) {
            return new Error("Invalid enabled percentage");
        }

        let enableSlickFeature;
        const abTestStorageKey = `slickab-\${testName}-\${targetPercentEnabled}`;
        const storedOnOffVal = storage.getItem(abTestStorageKey);

        const percentKey = `slickAbTestPercent-\${testName}`;
        const storedPercentVal = parseInt(storage.getItem(percentKey));

        if (recalculate === true || !storedOnOffVal || storedPercentVal !== targetPercentEnabled) {
            enableSlickFeature = (Math.random() * 100) <= targetPercentEnabled;
            storage.setItem(abTestStorageKey, enableSlickFeature);
            storage.setItem(percentKey, targetPercentEnabled);
        } else {
            enableSlickFeature = storage.getItem(abTestStorageKey) === 'true';
        }

        const abGroupVal = `slk\${testName}\${targetPercentEnabled}`;
        const featureOnOff = enableSlickFeature ? "on" : "off";
        win.adthrive = win.adthrive || {};
        win.adthrive.cmd = win.adthrive.cmd || [];
        win.adthrive.cmd.push(() => { win.adthrive.config.abGroup.set(abGroupVal, featureOnOff); });

        return enableSlickFeature;
        };
        JSBLOCK;

        return $jsBlock;
    }

    private function get_tax_terms($post, $taxonomy_name) {
        $taxTerms = array();
        $terms = get_the_terms($post, $taxonomy_name);

        if (empty($terms)) {
            return $taxTerms;
        }

        foreach ($terms as $term) {
            $termObject = (object) [
                '@id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
            array_push($taxTerms, $termObject);
        }

        return $taxTerms;
    }

    private function create_ldJsonTaxElement($taxonomy, $taxTerms) {
        return (object) [
            'name' => $taxonomy->name,
            'label' => $taxonomy->label,
            'description' => $taxonomy->description,
            'terms' => $taxTerms,
        ];
    }

    private function echoWpRocketDetection()
    {
        echo <<<JSBLOCK
        <script class='$this->scriptClass'>
        (function() {
            const slickstreamRocketPluginScripts = document.querySelectorAll('script.$this->scriptClass[type=rocketlazyloadscript]');
            const slickstreamRocketExternalScripts = document.querySelectorAll('script[type=rocketlazyloadscript][src*="app.slickstream.com"]');
            if (slickstreamRocketPluginScripts.length > 0 || slickstreamRocketExternalScripts.length > 0) {
                console.warn('[slickstream] WARNING: WP-Rocket is deferring one or more Slickstream scripts. This may cause undesirable behavior, such as increased CLS scores.');
            }
        })();
        </script>
        JSBLOCK;
    }

    //Outputs AB Test Config Data to the Console
    private function consoleLogAbTestData()
    {
    echo <<<JSBLOCK
    <script class='$this->scriptClass'>
    "use strict";(async()=>{var e,t;const o=window.\$slickBoot=window.\$slickBoot||{};const n="[SLICKSTREAM] ";const s="color: red";const a="color: yellow";if(!o.d){console.warn(`%c${n}Slickstream page boot data not found.`,a);return}const r=(e=o.d)===null||e===void 0?void 0:e.abTests;const i=(t=o.d)===null||t===void 0?void 0:t.siteCode;if(!o){console.warn(`%c${n}Slickstream config data not found; Slickstream is likely not installed on this site.`,a);return}if(!i){console.warn(`%c${n}Could not determine Slickstream siteCode for this page.`,a);return}if(o.d.bestBy<Date.now()){console.warn(`%c${n}WARNING: Slicktream config data is stale (older than 15 minutes). Please reload the page to fetch up-to-date data.`,a)}if(!r||Array.isArray(r)&&r.length===0){console.info(`%c${n}There are no Slickstream A/B tests running currently.`,s)}else{console.info(`%c${n}A/B TEST(S) FOR SLICKSTREAM ARE RUNNING. \\n\\nHere are the details:`,s);const e=e=>{var t;const o=localStorage.getItem("slick-ab");const n=o&&JSON.parse(o)||{value:false};return{"Feature being Tested":e.feature,"Is the A/B test running on this site?":!((t=e===null||e===void 0?void 0:e.excludeSites)===null||t===void 0?void 0:t.includes(i))?"yes":"no","Am I in the test group (feature disabled)?":n.value===true?"yes":"no","Percentage of Users this feature is ENABLED For":e.fraction,"Percentage of Users this feature is DISABLED For":100-e.fraction,"Start Date":new Date(e.startDate).toString(),"End Date":new Date(e.endDate).toString(),"Current Time":(new Date).toString()}};r.forEach((t=>{console.table(e(t))}))}})();
    </script>
JSBLOCK;
    }

    //TODO: Clean this up / migrate to SR functions
    public function addSlickPageHeader()
    {
        // @TODO: Confirm that this doesn't cause issues with Slickstream itself.
        if (is_404()) {
            return;
        }

        global $post;
        echo "\n";
        $this->echoSlickstreamComment("Page Generated at: " . $this->getCurrentTimeStampByTimeZone('UTC') . " UTC");
        $this->consoleOutput .= "Current timestamp: \${(new Date).toLocaleString('en-US', { timeZone: 'UTC' })} UTC\n\n";
        $this->echoPageBootData();

        echo "\n" . '<meta property="slick:wpversion" content="' . PLUGIN_VERSION . '" />' . "\n";
        $siteCode = substr(trim($this->getOption('SiteCode')), 0, 9);

        if ($siteCode) {
            $adThriveAbTest = false;
            $serverUrl = trim($this->getOption('SlickServerUrl', self::defaultServerUrl));
            //NOTE: for WP Plugin-based A/B Tests, the SlickServerUrl option is overloaded, hence the weird usage here
            if (substr($serverUrl, 0, 11) === 'adthrive-ab') {
                $pieces = explode(" ", $serverUrl);
                $serverUrl = self::defaultServerUrl;
                $adThriveAbTest = true;
                $enabledPercent = (count($pieces) > 1) ? intval($pieces[1]) : 100;
            }

            $this->echoSlickstreamComment("Bootloader:", false);
            echo "<script class='$this->scriptClass' >\n";
            echo "'use strict';\n";
            if ($adThriveAbTest) {
                echo $this->getAbTestJs();
                echo "if (window.slickAbTestResult(" . $enabledPercent . ")) {\n";
            }
            echo '(async(e,t)=>{if(location.search.indexOf("no-slick")>=0){return}let s;const a=()=>performance.now();let c=window.$slickBoot=window.$slickBoot||{};c.rt=e;c._es=a();c.ev="2.0.1";c.l=async(e,t)=>{try{let c=0;if(!s&&"caches"in self){s=await caches.open("slickstream-code")}if(s){let o=await s.match(e);if(!o){c=a();await s.add(e);o=await s.match(e);if(o&&!o.ok){o=undefined;s.delete(e)}}if(o){const e=o.headers.get("x-slickstream-consent");return{t:c,d:t?await o.blob():await o.json(),c:e||"na"}}}}catch(e){console.log(e)}return{}};const o=e=>new Request(e,{cache:"no-store"});if(!c.d||c.d.bestBy<Date.now()){const s=o(`${e}/d/page-boot-data?site=${t}&url=${encodeURIComponent(location.href.split("#")[0])}`);let{t:i,d:n,c:l}=await c.l(s);if(n){if(n.bestBy<Date.now()){n=undefined}else if(i){c._bd=i;c.c=l}}if(!n){c._bd=a();const e=await fetch(s);const t=e.headers.get("x-slickstream-consent");c.c=t||"na";n=await e.json()}if(n){c.d=n;c.s="embed"}}if(c.d){let e=c.d.bootUrl;const{t:t,d:s}=await c.l(o(e),true);if(s){c.bo=e=URL.createObjectURL(s);if(t){c._bf=t}}else{c._bf=a()}const i=document.createElement("script");i.className="slickstream-script";i.src=e;document.head.appendChild(i)}else{console.log("[slickstream] Boot failed")}})' . "\n";
            echo '("' . $serverUrl . '","' . $siteCode . '");' . "\n";
            if ($adThriveAbTest) {
                echo "}\n";
            }
            echo "</script>\n";
            $this->echoSlickstreamComment("END Bootloader", false);
        }

        $this->echoSlickstreamComment("Page Metadata:", false);

        //TODO: Move this out into SR functions and cache the output
        $ldJsonElements = array();

        $ldJsonPlugin = (object) [
            '@type' => 'Plugin',
            'version' => PLUGIN_VERSION,
        ];

        array_push($ldJsonElements, $ldJsonPlugin);

        $ldJsonSite = (object) [
            '@type' => 'Site',
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'description' => get_bloginfo('description'),
            'atomUrl' => get_bloginfo('atom_url'),
            'rtl' => is_rtl(),
        ];
        array_push($ldJsonElements, $ldJsonSite);

        if (!empty($post)) {
            $pageType = 'post';
            if (is_front_page() || is_home()) {
                $pageType = 'home';
            } else if (is_category()) {
                $pageType = 'category';
            } else if (is_tag()) {
                $pageType = 'tag';
            } else if (is_singular('post')) {
                $pageType = 'post';
            } else if (is_singular('page')) {
                $pageType = 'page';
            } else {
                $pageType = 'other';
            }
            $ldJsonPost = (object) [
                '@type' => 'WebPage',
                '@id' => $post->ID,
                'isFront' => is_front_page(),
                'isHome' => is_home(),
                'isCategory' => is_category(),
                'isTag' => is_tag(),
                'isSingular' => is_singular(),
                'date' => get_the_time('c'),
                'modified' => get_the_modified_time('c'),
                'title' => $post->post_title,
                'pageType' => $pageType,
                'postType' => $post->post_type,
            ];
            echo '<meta property="slick:wppostid" content="' . $post->ID . '" />' . "\n";
            if (has_post_thumbnail($post)) {
                $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'single-post-thumbnail');
                if (!empty($images)) {
                    echo '<meta property="slick:featured_image" content="' . $images[0] . '" />' . "\n";
                    $ldJsonPost->featured_image = $images[0];
                }
            }
            $authorName = get_the_author_meta('display_name');
            if (!empty($authorName)) {
                $ldJsonPost->author = $authorName;
            }
            if (is_category()) {
                echo '<meta property="slick:group" content="category" />' . "\n";
                $term = get_queried_object();
                if (isset($term->slug)) {
                    echo '<meta property="slick:category" content="' . $term->slug . ':' . $term->name . '" />' . "\n";
                    $ldJsonCategory = (object) [
                        '@id' => $term->term_id,
                        'slug' => $term->slug,
                        'name' => $term->name,
                    ];
                    $ldJsonPost->category = $ldJsonCategory;
                }
            } else if (is_tag()) {
                echo '<meta property="slick:group" content="tag" />' . "\n";
                $term = get_queried_object();
                if (isset($term->slug)) {
                    echo '<meta property="slick:tag" content="' . $term->slug . ':' . $term->name . '" />' . "\n";
                    $ldJsonTag = (object) [
                        '@id' => $term->term_id,
                        'slug' => $term->slug,
                        'name' => $term->name,
                    ];
                    $ldJsonPost->tag = $ldJsonTag;
                }
            } else if (is_singular(['post', 'page'])) {
                if (is_singular('post')) {
                    echo '<meta property="slick:group" content="post" />' . "\n";
                }
                $categories = get_the_category();
                if (!empty($categories)) {
                    $ldJsonCategoryElements = array();
                    foreach ($categories as $category) {
                        if (isset($category->slug) && $category->slug !== 'uncategorized') {
                            echo '<meta property="slick:category" content="' . $category->slug . ':' . $this->removeSemicolons($category->name);
                            $used = [$category->cat_ID];
                            $count = 0;
                            $parentCatId = $category->category_parent;
                            $ldJsonParents = array();
                            while ($parentCatId && $count < 8 && !in_array($parentCatId, $used)) {
                                $parentCat = get_category($parentCatId);
                                if (isset($parentCat->slug) && $parentCat->slug !== 'uncategorized') {
                                    echo ';' . $parentCat->slug . ':' . $this->removeSemicolons($parentCat->name);
                                    $parentCatId = $parentCat->cat_ID;
                                    $ldJsonParent = (object) [
                                        '@type' => 'CategoryParent',
                                        '@id' => $parentCat->cat_ID,
                                        'slug' => $parentCat->slug,
                                        'name' => $this->removeSemicolons($parentCat->name),
                                    ];
                                    array_push($ldJsonParents, $ldJsonParent);
                                } else {
                                    break;
                                }
                                array_push($used, $parentCatId);
                                $count = $count + 1;
                            }
                            echo '" />' . "\n";
                            $ldJsonCategoryElement = (object) [
                                '@id' => $category->cat_ID,
                                'slug' => $category->slug,
                                'name' => $this->removeSemicolons($category->name),
                                'parents' => $ldJsonParents,
                            ];
                            array_push($ldJsonCategoryElements, $ldJsonCategoryElement);
                        }
                    }
                    if (!empty($ldJsonCategoryElements)) {
                        $ldJsonPost->categories = $ldJsonCategoryElements;
                    }
                }

                $tags = get_the_tags();
                if (!empty($tags)) {
                    $ldJsonTags = array();
                    foreach ($tags as $tag) {
                        if (isset($tag->name)) {
                            array_push($ldJsonTags, $tag->name);
                        }
                    }
                    if (!empty($ldJsonTags)) {
                        $ldJsonPost->tags = $ldJsonTags;
                    }
                }

                $ldJsonTaxonomies = array();
                $taxonomies = get_object_taxonomies($post, 'objects');
                if (!empty($taxonomies)) {
                    foreach ($taxonomies as $taxonomy) {
                        if (empty($taxonomy->_builtin) && $taxonomy->public) {
                            $taxTerms = $this->get_tax_terms($post, $taxonomy->name);

                            if (!empty($taxTerms)) {
                                $ldJsonTaxElement = $this->create_ldJsonTaxElement($taxonomy, $taxTerms);
                                array_push($ldJsonTaxonomies, $ldJsonTaxElement);
                            }
                        }
                    }
                }
                if (class_exists('WPRM_Recipe_Manager')) {
                    // $postTypeArgs = array('public' => true, '_builtin' => false);
                    // $ldJsonPost->postTypes = get_post_types($postTypeArgs, 'names', 'and');
                    $recipes = WPRM_Recipe_Manager::get_recipe_ids_from_post($post->ID);
                    // $ldJsonPost->recipes = $recipes;
                    if (!empty($recipes)) {
                        // $recipe = WPRM_Recipe_Manager::get_recipe($recipes[0]);
                        // $ldJsonPost->recipe = $recipe;
                        // $ldJsonPost->recipeId = $recipes[0];
                        $recipeTaxonomies = get_object_taxonomies('wprm_recipe', 'objects');
                        // $ldJsonPost->recipeTaxonomies = $recipeTaxonomies;
                        if (!empty($recipeTaxonomies)) {
                            foreach ($recipeTaxonomies as $taxonomy) {
                                if (empty($taxonomy->_builtin) && $taxonomy->public) {
                                    $taxTerms = array();
                                    $terms = get_the_terms($recipes[0], $taxonomy->name);
                                    // $lsJsonPost->taxTerms = $terms;
                                    if (!empty($terms)) {
                                        foreach ($terms as $term) {
                                            $termObject = (object) [
                                                '@id' => $term->term_id,
                                                'name' => $term->name,
                                                'slug' => $term->slug,
                                            ];
                                            array_push($taxTerms, $termObject);
                                        }
                                        $ldJsonTaxElement = (object) [
                                            'name' => $taxonomy->name,
                                            'label' => $taxonomy->label,
                                            'description' => $taxonomy->description,
                                            'terms' => $taxTerms,
                                        ];
                                        array_push($ldJsonTaxonomies, $ldJsonTaxElement);
                                    }
                                }
                            }
                        }
                    }
                }
                $ldJsonPost->taxonomies = $ldJsonTaxonomies;
            }
            array_push($ldJsonElements, $ldJsonPost);
        }
        $ldJson = (object) [
            '@context' => 'https://slickstream.com',
            '@graph' => $ldJsonElements,
        ];
        echo '<script type="application/x-slickstream+json">' . json_encode($ldJson, JSON_UNESCAPED_SLASHES) . "</script>\n";
        $this->echoSlickstreamComment("END Page Metadata", false);

        if ($this->isDebugModeEnabled())
        {
            $this->consoleLogAbTestData();
            $this->debugCLS();
            $this->echoConsoleOutput();
        }
        $this->echoWpRocketDetection();
    }

    /**
     * Process the fetch boot data request.
     *
     * @param string $siteCode The site code.
     * @param string $transient_name The transient name.
     * @param string $transient_name_ttl The transient name for the TTL.
     *
     * @return void
     */
    public function process_fetch_boot_data( string $siteCode, string $transient_name, string $transient_name_ttl ): void {
        // Slickstream locally will always return a 403 error. To combat this, set a transient with an empty object.
        if ( wp_get_environment_type() === 'local' ) {
            set_transient( $transient_name, wp_json_encode( new stdClass ), 0 );
            set_transient( $transient_name_ttl, time() + ( 15 * MINUTE_IN_SECONDS ), 0);
            return;
        }

        $boot_data_obj = $this->fetchBootData( $siteCode );  // Most time consuming. So bypassed with stale while revalidate.
        if ( ! $boot_data_obj ) {
            // If the boot data is not fetched, reset the ttl to 15 minutes to retry fetching the boot data.
            set_transient( $transient_name_ttl, time() + ( 15 * MINUTE_IN_SECONDS ), 0);
            return;
        }

        set_transient( $transient_name, $boot_data_obj, 0 ); // Set with no expiration, the cache will be invalidated by the TTL through cron.
        set_transient( $transient_name_ttl, time() + ( 15 * MINUTE_IN_SECONDS ), 0 );
    }
}
