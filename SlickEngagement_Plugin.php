<?php
declare(strict_types=1);
namespace Slickstream;

// NOTE: All inline JS scripts embedded by the plugin need to have the string `slickstream` somewhere in them;
// This allows the string `slickstream` to be used in WP-Rocket lazy load exclusions; ideally $scriptClass is added to each script

require_once 'SlickEngagement_Widgets.php';
require_once 'SlickEngagement_OptionsManager.php';
require_once 'SlickEngagement_PageBootData.php';
require_once 'SlickEngagement_Utils.php';

class SlickEngagement_Plugin extends OptionsManager  {
    private const PLUGIN_VERSION = '2.0.3';
    private const DEFAULT_SERVER_URL = 'app.slickstream.com';
    private string $scriptClass = 'slickstream-script';
    private string $serverUrlBase;
    private string $siteCode;
    private Utils $utils;

    public function __construct() {
       parent::__construct();
       $this->siteCode = rawurlencode(substr(trim($this->getOption('SiteCode', '')), 0, 9));
       $this->serverUrlBase = "https://" . $this->getOption('SlickServerUrl', self::DEFAULT_SERVER_URL);
       $this->utils = Utils::getInstance();
    }

    private function echoComment($comment, $echoToConsole = true, $debugOnly = true): void {
        $this->utils->echoComment($comment, $echoToConsole, $debugOnly);
    }

    private function echoDebugCLS(): void {
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
        script.src = 'https://unpkg.com/web-vitals/dist/web-vitals.attribution.iife.js';
        script.onload = function () {
            webVitals.onCLS(clsDataCallback);
        };
        document.head.appendChild(script);
        })();
        </script>
        JSDOC;
    }

    private function getCurrentTimestampByTimeZone($timezone): string {
        $timestamp = time();
        $dt = new \DateTime('now', new \DateTimeZone($timezone));
        $dt->setTimestamp($timestamp);
        return $dt->format('n/j/Y, g:i:s A');
    }

    private function getTaxTerms($post, $taxonomyName): array {
        $taxTerms = [];
        $terms = get_the_terms($post, $taxonomyName);
    
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
    
    private function createLdJsonTaxElement($taxonomy, $taxTerms): object {
        return (object) [
            'name' => $taxonomy->name,
            'label' => $taxonomy->label,
            'description' => $taxonomy->description,
            'terms' => $taxTerms,
        ];
    }

    private function echoWpRocketDetection(): void {
        echo <<<JSBLOCK
        <script class='$this->scriptClass'>
        (function() {
            const slickstreamRocketPluginScripts = document.querySelectorAll('script.$this->scriptClass[type=rocketlazyloadscript]');
            const slickstreamRocketExternalScripts = document.querySelectorAll('script[type=rocketlazyloadscript][src*="app.slickstream.com"]');
            if (slickstreamRocketPluginScripts.length > 0 || slickstreamRocketExternalScripts.length > 0) {
                console.warn('[slickstream]' + ['Slickstream scripts. This ', 'may cause undesirable behavior, ', 'such as increased CLS scores.',' WP-Rocket is deferring one or more ',].sort().join(''));
            }
        })();
        </script>
        JSBLOCK;
    }

    private function consoleLogAbTestData(): void {
    echo <<<JSBLOCK
    <script class='$this->scriptClass'>
    "use strict";(async()=>{var e,t;const o=window.\$slickBoot=window.\$slickBoot||{};const n="[slickstream] ";const s="color: red";const a="color: yellow";if(!o.d){console.warn(`%c\${n}Slickstream page boot data not found.`,a);return}const r=(e=o.d)===null||e===void 0?void 0:e.abTests;const i=(t=o.d)===null||t===void 0?void 0:t.siteCode;if(!o){console.warn(`%c\${n}Slickstream config data not found; Slickstream is likely not installed on this site.`,a);return}if(!i){console.warn(`%c\${n}Could not determine Slickstream siteCode for this page.`,a);return}if(o.d.bestBy<Date.now()){console.warn(`%c\${n}WARNING: Slicktream page config data is stale. Please reload the page to fetch up-to-date config data.`,a)}if(!r||Array.isArray(r)&&r.length===0){console.info(`%c\${n}There are no Slickstream A/B tests running currently.`,s)}else{console.info(`%c\${n}A/B TEST(S) FOR SLICKSTREAM ARE RUNNING. \\n\\nHere are the details:`,s);const e=e=>{var t;const o=localStorage.getItem("slick-ab");const n=o&&JSON.parse(o)||{value:false};return{"Feature being Tested":e.feature,"Is the A/B test running on this site?":!((t=e===null||e===void 0?void 0:e.excludeSites)===null||t===void 0?void 0:t.includes(i))?"yes":"no","Am I in the test group (feature disabled)?":n.value===true?"yes":"no","Percentage of Users this feature is ENABLED For":e.fraction,"Percentage of Users this feature is DISABLED For":100-e.fraction,"Start Date":new Date(e.startDate).toString(),"End Date":new Date(e.endDate).toString(),"Current Time":(new Date).toString()}};r.forEach((t=>{console.table(e(t))}))}})();
    </script>
    JSBLOCK;
    }

    function getPageType(): string {
        if (is_front_page() || is_home()) {
            return 'home';
        } 
        if (is_category()) {
            return 'category';
        } 
        if (is_tag()) {
            return 'tag';
        } 
        if (is_singular('post')) {
            return 'post';
        } 
        if (is_singular('page')) {
            return 'page';
        }
        return 'other';
    }
    
    function addMeta($property, $content): void {
        $property = (string) $property;
        $content = (string) $content;
        echo '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . 
        '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "\" />\n";    }
    
    function handleCategoryMeta($ldJsonPost): void {
        $term = get_queried_object();
        if (isset($term->slug)) {
            $this->addMeta('slick:category', "$term->slug:$term->name");
            $ldJsonPost->category = (object)[
                '@id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name
            ];
        }
    }
    
    function handleTagMeta($ldJsonPost): void {
        $term = get_queried_object();
        if (isset($term->slug)) {
            $this->addMeta('slick:tag', "$term->slug:$term->name");
            $ldJsonPost->tag = (object)[
                '@id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name
            ];
        }
    }
    
    function handleSingularMeta($post, &$ldJsonPost): void {
        if (is_singular('post')) {
            $this->addMeta('slick:group', 'post');
        }
    
        $this->handleCategories($post, $ldJsonPost);
        $this->handleTags($post, $ldJsonPost);
        $this->handleTaxonomies($post, $ldJsonPost);
    }
    
    function handleCategories($post, &$ldJsonPost): void {
        $categories = get_the_category();
        if (empty($categories)) {
            return;
        }
    
        $ldJsonCategoryElements = [];
        foreach ($categories as $category) {
            if (isset($category->slug) && $category->slug !== 'uncategorized') {
                $this->addMeta('slick:category', $category->slug . ':' . $this->utils->removeSemicolons($category->name));
                $ldJsonCategoryElements[] = $this->buildCategoryElement($category);
            }
        }
    
        if (!empty($ldJsonCategoryElements)) {
            $ldJsonPost->categories = $ldJsonCategoryElements;
        }
    }
    
    function buildCategoryElement($category): object {
        $ldJsonParents = [];
        $used = [$category->cat_ID];
        $parentCatId = $category->category_parent;

        while ($parentCatId && count($used) < 8 && !in_array($parentCatId, $used)) {
            $parentCat = get_category($parentCatId);
            if ($parentCat instanceof WPError || $parentCat === null) {
                continue;
            }
            if ($parentCat && is_object($parentCat) && isset($parentCat->slug) && $parentCat->slug !== 'uncategorized') {
                $parentCat = (object) $parentCat; //To placate WPError warnings
                $this->addMeta(';', $parentCat->slug . ':' . $this->utils->removeSemicolons($parentCat->name));
                $ldJsonParents[] = (object)[
                    '@type' => 'CategoryParent',
                    '@id' => $parentCat->cat_ID,
                    'slug' => $parentCat->slug,
                    'name' => $this->utils->removeSemicolons($parentCat->name)
                ];
            }
            $used[] = $parentCatId;
            if (!is_wp_error($parentCat)) {
                $parentCatId = $parentCat->category_parent;
            } else {
                continue;
            }
        }
    
        return (object)[
            '@id' => $category->cat_ID,
            'slug' => $category->slug,
            'name' => $this->utils->removeSemicolons($category->name),
            'parents' => $ldJsonParents
        ];
    }
    
    function handleTags($post, &$ldJsonPost): void {
        $tags = get_the_tags();
        if (empty($tags)) {
            return;
        }
        $ldJsonTags = array_map(function($tag) {
            return $tag->name;
        }, $tags);
    
        if (!empty($ldJsonTags)) {
            $ldJsonPost->tags = $ldJsonTags;
        }
    }
    
    function handleTaxonomies($post, &$ldJsonPost): void {
        $taxonomies = get_object_taxonomies($post, 'objects');
        if (empty($taxonomies)) return;
    
        $ldJsonTaxonomies = [];
        foreach ($taxonomies as $taxonomy) {
            if (empty($taxonomy->_builtin) && $taxonomy->public) {
                $taxTerms = $this->getTaxTerms($post, $taxonomy->name);
                if (!empty($taxTerms)) {
                    $ldJsonTaxonomies[] = $this->createLdJsonTaxElement($taxonomy, $taxTerms);
                }
            }
        }
    
        if (!empty($ldJsonTaxonomies)) {
            $ldJsonPost->taxonomies = $ldJsonTaxonomies;
        }
    }
    
    function buildLdJsonPost($post): object {
        $ldJsonPost = (object)[
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
            'pageType' => $this->getPageType(),
            'postType' => $post->post_type
        ];
    
        return $ldJsonPost;
    }

    public function echoBootLoader(): void {
        $this->echoComment("Bootloader:", false, false);
        echo "<script class='$this->scriptClass'>'use strict';\n";
        echo "(async(e,t)=>{if(location.search.indexOf(\"no-slick\")>=0){return}let s;const a=()=>performance.now();let c=window.\$slickBoot=window.\$slickBoot||{};c.rt=e;c._es=a();c.ev=\"2.0.1\";c.l=async(e,t)=>{try{let c=0;if(!s&&\"caches\"in self){s=await caches.open(\"slickstream-code\")}if(s){let o=await s.match(e);if(!o){c=a();await s.add(e);o=await s.match(e);if(o&&!o.ok){o=undefined;s.delete(e)}}if(o){const e=o.headers.get(\"x-slickstream-consent\");return{t:c,d:t?await o.blob():await o.json(),c:e||\"na\"}}}}catch(e){console.log(e)}return{}};const o=e=>new Request(e,{cache:\"no-store\"});if(!c.d||c.d.bestBy<Date.now()){const s=o(`\${e}/d/page-boot-data?site=\${t}&url=\${encodeURIComponent(location.href.split(\"#\")[0])}`);let{t:i,d:n,c:l}=await c.l(s);if(n){if(n.bestBy<Date.now()){n=undefined}else if(i){c._bd=i;c.c=l}}if(!n){c._bd=a();const e=await fetch(s);const t=e.headers.get(\"x-slickstream-consent\");c.c=t||\"na\";n=await e.json()}if(n){c.d=n;c.s=\"embed\"}}if(c.d){let e=c.d.bootUrl;const{t:t,d:s}=await c.l(o(e),true);if(s){c.bo=e=URL.createObjectURL(s);if(t){c._bf=t}}else{c._bf=a()}const i=document.createElement(\"script\");i.className=\"slickstream-script\";i.src=e;document.head.appendChild(i)}else{console.log(\"[slickstream] Boot failed\")}})\n";
        echo '("' . addslashes($this->serverUrlBase) . '","' . addslashes($this->siteCode) . "\");\n";
        echo "</script>\n";
        $this->echoComment("END Bootloader", false, false);
    }

    private function echoVersionMetaTag(): void {
        echo "\n<meta property='slick:wpversion' content='" . self::PLUGIN_VERSION . "' />\n";
    }

    //Outputs debug info, meta tags, page boot data, and other page metadata into the page header
    public function addSlickPageHeader(): void {
        global $post;

        $this->echoPageGenerationTimestamp();

        if (!$this->siteCode) {
            $this->echoComment("ERROR: Site Code missing from Plugin Settings; Slickstream services are disabled", true, false);
            return;
        }

        $pageBootData = new PageBootData($this->serverUrlBase, $this->siteCode, $this->scriptClass);
        $pageBootData->handlePageBootData();
        $this->echoVersionMetaTag();
        $this->echoBootLoader();    
        $this->echoPageMetadata($post);
        $this->outputDebugInfo();
        $this->echoWpRocketDetection();
    }
    
    private function echoPageGenerationTimestamp(): void {
        $timezone = 'America/New_York';
        $shortTimezone = 'EST';
        $this->echoComment("Page Generated at: " . $this->getCurrentTimeStampByTimeZone($timezone) . " $shortTimezone", true, false);
        echo "\t\t<script>console.info(`[slickstream] Current timestamp: \${(new Date).toLocaleString('en-US', { timeZone: '$timezone' })} $shortTimezone`);</script>\n";
    }
    
    private function echoPageMetadata($post): void {
        $this->echoComment("Page Metadata:", false, false);
        
        $ldJsonElements = [];
        array_push($ldJsonElements, $this->getLdJsonPluginData(), $this->getLdJsonSiteData());
        
        if (!empty($post)) {
            $ldJsonPost = $this->buildLdJsonPost($post);
            $this->processPostMetadata($post, $ldJsonPost);
            array_push($ldJsonElements, $ldJsonPost);
        }
    
        $ldJson = (object) [
            '@context' => 'https://slickstream.com',
            '@graph' => $ldJsonElements,
        ];
        echo '<script type="application/x-slickstream+json">' . json_encode($ldJson, JSON_UNESCAPED_SLASHES) . "</script>\n";
        $this->echoComment("END Page Metadata", false, false);
    }
    
    private function getLdJsonPluginData(): object {
        return (object) [
            '@type' => 'Plugin',
            'version' => self::PLUGIN_VERSION,
        ];
    }
    
    private function getLdJsonSiteData(): object {
        return (object) [
            '@type' => 'Site',
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'description' => get_bloginfo('description'),
            'atomUrl' => get_bloginfo('atom_url'),
            'rtl' => is_rtl(),
        ];
    }
    
    private function processPostMetadata($post, &$ldJsonPost): void {
        $this->addMeta('slick:wppostid', $post->ID);
        
        if (has_post_thumbnail($post)) {
            $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'single-post-thumbnail');
            if (!empty($images)) {
                $this->addMeta('slick:featured_image', $images[0]);
                $ldJsonPost->featured_image = $images[0];
            }
        }
    
        $authorName = get_the_author_meta('display_name');
        if (!empty($authorName)) {
            $ldJsonPost->author = $authorName;
        }
    
        $this->handlePostTypeMeta($post, $ldJsonPost);
    }
    
    private function handlePostTypeMeta($post, &$ldJsonPost): void
    {
        switch (true) {
            case is_category():
                $this->addMeta('slick:group', 'category');
                $this->handleCategoryMeta($ldJsonPost);
                break;
    
            case is_tag():
                $this->addMeta('slick:group', 'tag');
                $this->handleTagMeta($ldJsonPost);
                break;
    
            case is_singular(['post', 'page']):
                $this->handleSingularMeta($post, $ldJsonPost);
                break;
        }
    }
    
    private function outputDebugInfo(): void {
        if (!$this->utils->isDebugModeEnabled()) {
            return;
        }
        $this->consoleLogAbTestData();
        $this->echoDebugCLS();
    }
}