<?php 
declare(strict_types=1);
namespace Slickstream;

require_once 'SlickEngagement_Utils.php';

class PageBootData extends OptionsManager {
    private const PAGE_BOOT_DATA_DEFAULT_TTL = 60 * MINUTE_IN_SECONDS;
    private const URL_TO_PAGE_GROUP_ID_TTL = 12 * HOUR_IN_SECONDS;
    private string $scriptClass;
    private ?string $pageGroupId;
    private ?object $pageBootData;
    private ?string $pageGroupTransientName;
    private ?string $pageGroupIdTransientName;
    private string $siteCode;
    private string $serverUrlBase;
    private string $urlPath;
    private Utils $utils;

    public function __construct($serverUrlBase, $siteCode, $scriptClass) {
        parent::__construct();
        $this->scriptClass = $scriptClass;
        $this->serverUrlBase = $serverUrlBase;
        $this->siteCode = addslashes(substr($siteCode, 0, 9));
        $this->utils = Utils::getInstance();
        $this->urlPath = $this->getCurrentUrlPath();
        $this->pageGroupIdTransientName = 'slick_page_group_id_' . md5($_SERVER['SERVER_NAME'] . $this->urlPath);
        $this->pageGroupId = $this->getPageGroupId();
        $this->pageGroupTransientName = $this->getPageGroupTransientName();
        $this->pageBootData = $this->getPageBootData();
    }

    private function echoComment($comment, $echoToConsole = true, $debugOnly = true): void {
        $this->utils->echoComment($comment, $echoToConsole, $debugOnly);
    }

    private function getPageBootDataForDevice(): object {
        if (isset($this->pageBootData->v2)) {
            if ($this->utils->isMobile() && isset($this->pageBootData->v2->phone)) {
              return $this->pageBootData->v2->phone;
            }
            return $this->pageBootData->v2->desktop;
        }
        return $this->pageBootData;
    }

    private function echoClsData(): void {
        $deviceBootData = $this->getPageBootDataForDevice();
    
        $filmstripConfig = $deviceBootData->filmstrip ?? '';
        $dcmConfig = $deviceBootData->inlineSearch ?? '';
        $emailCapConfig = $deviceBootData->emailCapture ?? '';
    
        if (!empty($filmstripConfig) || !empty($dcmConfig) || !empty($emailCapConfig)) {
            $filmstripStr = empty($filmstripConfig) ? '' : json_encode($filmstripConfig);
            $dcmStr = empty($dcmConfig) ? '' : json_encode($dcmConfig);
            $emailCapStr = empty($emailCapConfig) ? '' : json_encode($emailCapConfig);
    
            $this->echoComment('CLS Insertion:', false, false);
    
            echo "<script>\n";
            echo "\"use strict\";(async(e,t,n)=>{const o=\"slickstream\";const i=e?JSON.parse(e):null;const r=t?JSON.parse(t):null;const c=n?JSON.parse(n):null;if(i||r||c){const e=async()=>{if(document.body){if(i){m(i.selector,i.position||\"after selector\",\"slick-film-strip\",i.minHeight||72,i.margin||i.marginLegacy||\"10px auto\")}if(r){r.forEach((e=>{if(e.selector){m(e.selector,e.position||\"after selector\",\"slick-inline-search-panel\",e.minHeight||350,e.margin||e.marginLegacy||\"50px 15px\",e.id)}}))}if(c){s(c)}return}window.requestAnimationFrame(e)};window.requestAnimationFrame(e)}const s=async e=>{const t=\"slick-on-page\";try{if(document.querySelector(`.\${t}`)){return}const n=l()?e.minHeightMobile||220:e.minHeight||200;if(e.cssSelector){m(e.cssSelector,\"before selector\",t,n,\"\",undefined)}else{a(e.pLocation||3,t,n)}}catch(e){console.log(\"plugin\",\"error\",o,`Failed to inject \${t}`)}};const a=async(e,t,n)=>{const o=document.createElement(\"div\");o.classList.add(t);o.classList.add(\"cls-inserted\");o.style.minHeight=n+\"px\";const i=document.querySelectorAll(\"article p\");if((i===null||i===void 0?void 0:i.length)>=e){const t=i[e-1];t.insertAdjacentElement(\"afterend\",o);return o}const r=document.querySelectorAll(\"section.wp-block-template-part div.entry-content p\");if((r===null||r===void 0?void 0:r.length)>=e){const t=r[e-1];t.insertAdjacentElement(\"afterend\",o);return o}return null};const l=()=>{const e=navigator.userAgent;const t=/Tablet|iPad|Playbook|Nook|webOS|Kindle|Android (?!.*Mobile).*Safari/i.test(e);const n=/Mobi|iP(hone|od)|Opera Mini/i.test(e);return n&&!t};const d=async(e,t)=>{const n=Date.now();while(true){const o=document.querySelector(e);if(o){return o}const i=Date.now();if(i-n>=t){throw new Error(\"Timeout\")}await u(200)}};const u=async e=>new Promise((t=>{setTimeout(t,e)}));const m=async(e,t,n,i,r,c)=>{try{const o=await d(e,5e3);const s=c?document.querySelector(`.\${n}[data-config=\"\${c}\"]`):document.querySelector(`.\${n}`);if(o&&!s){const e=document.createElement(\"div\");e.style.minHeight=i+\"px\";e.style.margin=r;e.classList.add(n);e.classList.add(\"cls-inserted\");if(c){e.dataset.config=c}switch(t){case\"after selector\":o.insertAdjacentElement(\"afterend\",e);break;case\"before selector\":o.insertAdjacentElement(\"beforebegin\",e);break;case\"first child of selector\":o.insertAdjacentElement(\"afterbegin\",e);break;case\"last child of selector\":o.insertAdjacentElement(\"beforeend\",e);break}return e}}catch(t){console.log(\"plugin\",\"error\",o,`Failed to inject \${n} for selector \${e}`)}return false}})\n";
            echo "('" . addslashes($filmstripStr) . "','" . addslashes($dcmStr) . "','" . addslashes($emailCapStr) . "');" . "\n";
            echo "\n</script>\n";
    
            $this->echoComment('END CLS Insertion', false, false);
        }
    }

    private function getPageBootData(): ?object {
        if (!$this->pageGroupId || !$this->siteCode || !$this->urlPath || 
            !$this->pageGroupIdTransientName || !$this->pageGroupTransientName) {

            $this->echoComment('getPageBootData Error: Missing Required Data; Skipping Page Boot Data. Details:');
            $this->echoComment('pageGroupId: ' . $this->pageGroupId ?? 'null');
            $this->echoComment('siteCode: ' . $this->siteCode ?? 'null');
            $this->echoComment('urlPath: ' . $this->urlPath ?? 'null');
            $this->echoComment('pageGroupIdTransientName: ' . $this->pageGroupIdTransientName ?? 'null');
            $this->echoComment('pageGroupTransientName: ' . $this->pageGroupTransientName ?? 'null');
            return null;
        }

        $noTransientPageBootData = (
            false === (
                $pageBootData = get_transient($this->pageGroupTransientName)
            )
        );

        if ($noTransientPageBootData) {
            $pageBootData = $this->fetchPageBootData();
            if ($pageBootData) {
                $pageBootDataTtl = $pageBootData->wpPluginTtl ?? self::PAGE_BOOT_DATA_DEFAULT_TTL;
                set_transient($this->pageGroupTransientName, $pageBootData, $pageBootDataTtl);
                $this->echoComment("Stored Page Boot Data in Transient Cache Using Key: $this->pageGroupTransientName for $pageBootDataTtl Seconds.");
            } else {
                $this->echoComment("ERROR: Unable to Fetch Page Boot Data from Server");
                return null;
            }            
        }
        $this->echoComment("Retrieved Page Boot Data from from Transient Cache for Page Group ID: $this->pageGroupId from Key: $this->pageGroupTransientName");
        return $pageBootData;
    }

    // Fetch the Page Boot Data Object by Site Code and Page Group ID from the server
    private function fetchPageBootData(): ?object {
        $this->echoComment("Fetching Page Boot Data From Server");
        
        if (!$this->siteCode) {
            $this->echoComment("fetchPageBootData Error: Missing Site Code");
            return null;
        }
        if (!$this->pageGroupId) {
            $this->echoComment("fetchPageBootData Error: Missing Page Group ID");
            return null;
        }

        $protocol = ($_SERVER['HTTPS'] === 'on') ? 'https' : 'http'; 
        $pageUrl = $protocol . '://' .$_SERVER['SERVER_NAME'] . $this->urlPath;
        $pageBootDataUrl = $this->serverUrlBase . '/d/page-boot-data?site=' . rawurlencode($this->siteCode) . '&url=' . rawurlencode($pageUrl);
        return $this->utils->fetchRemoteObject($pageBootDataUrl);
    }
    
    public function handlePageBootData(): void {
        if (wp_get_environment_type() === 'local' && !$this->utils->isDebugModeEnabled()) {
            $this->echoComment('Local Environment Detected; Skipping Page Boot Data');
            return;
        }

        // If `delete-boot=1` is passed as a query param, delete the stored page boot data
        $this->handleDeletePageBootData();
        
        // If `slick-boot=1` is passed as a query param, force a re-fetch of the boot data from the server
        // If `slick-boot=0` is passed as a query param, skip fetching boot data from the server
        $slickBootParam = $this->utils->getQueryParamByName('slick-boot');
        $forceFetchBootData = ($slickBootParam === '1');
        $dontLoadBootData = ($slickBootParam === '0');

        if ($forceFetchBootData) {
            $this->pageBootData = $this->fetchPageBootData();
        } else if ($dontLoadBootData) {
            $this->echoComment('Skipping Page Boot Data and CLS Data Output');
            return;
        }
    
        if ($this->pageBootData) {
            $this->echoSlickBootJs();
            $this->echoClsData();
        } else {
            $this->echoComment('No Page Boot Data Available; Front-end will Fetch it Instead');
        }
    }

    private function getCurrentUrlPath(): string {
        $parsedUrl = parse_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $path = '';

        if (isset($parsedUrl['path'])) {
            $path = ($parsedUrl['path'] === '/') ? '/' : 
                rtrim($parsedUrl['path'], '/');
        }
        
        return $path;
    }

    // Fetch a single page URL path to Page Group ID from the server by site code and URL path
    private function fetchPageGroupId(): ?string {
        $this->echoComment("Fetching Page Group ID From Server");
        
        if (!$this->siteCode) {
            $this->echoComment("fetchPageBootData Error: Missing Site Code");
            return null;
        }

        $urlPathToPageGroupIdUrl = "{$this->serverUrlBase}/d/url-page-group?site={$this->siteCode}&url={$this->urlPath}";
        return $this->utils->fetchRemoteObject($urlPathToPageGroupIdUrl, 1, 'text');
    }

    private function getPageGroupId(): ?string {
        $noTransientPageGroupIdExists = (
            false === (
                $pageGroupId = get_transient($this->pageGroupIdTransientName)
            )
        );

        if ($noTransientPageGroupIdExists) {
            $pageGroupId = $this->fetchPageGroupId();
            if ($pageGroupId) {
                set_transient($this->pageGroupIdTransientName, $pageGroupId, self::URL_TO_PAGE_GROUP_ID_TTL);
                $this->echoComment("Successfully Cached Page Group ID With Key: $this->pageGroupIdTransientName for URL Path: $this->urlPath");
            } else {
                $this->echoComment("Failed to Fetch Page Group ID for URL Path: $this->urlPath");
                return null;
            }
        } 
        $this->echoComment("Retrieved Page Group ID: '{$pageGroupId}' from Transient Cache from Key: $this->pageGroupIdTransientName");
        return $pageGroupId;
    }

    private function echoSlickBootJs(): void {
        $pageBootDataJson = json_encode($this->pageBootData);

        if (null === $pageBootDataJson || json_last_error() !== JSON_ERROR_NONE) {
            $this->echoComment('Error Encoding Page Boot Data JSON');
            return;
        }

        $this->echoComment('Page Boot Data:', false, false);

        echo <<<JSBLOCK
        <script class='$this->scriptClass'>
        (function() {
            "slickstream";
            const win = window;
            win.\$slickBoot = win.\$slickBoot || {};
            win.\$slickBoot.d = $pageBootDataJson;
            win.\$slickBoot.s = 'plugin';
            win.\$slickBoot._bd = performance.now();
        })();
        </script>\n
        JSBLOCK;

        $this->echoComment('END Page Boot Data', false, false);
    }

    private function getPageGroupTransientName(): ?string {
        if (!$this->pageGroupId) {
            return null;
        }
        return 'slick_page_group_' . md5($_SERVER['SERVER_NAME'] . $this->pageGroupId);
    }

    private function handleDeletePageBootData(): void {
        $deleteTransientParam = $this->utils->getQueryParamByName('delete-boot');
        $shouldDeleteTransientData = ($deleteTransientParam === '1');

        if (!$shouldDeleteTransientData) {
            return;
        }

        $this->echoComment("Deleting Page Boot Data From Cache With Key: $this->pageGroupTransientName");
        $deleteComment = (false === delete_transient($this->pageGroupTransientName)) ? 
            "Nothing to do--Page Boot Data Not Found in Cache" : "Page Boot Data Transient Deleted Successfully";
        $this->echoComment($deleteComment);

        $this->echoComment("Deleting Page Group ID From Cache With Key: $this->pageGroupIdTransientName");
        $deleteComment = (false === delete_transient($this->pageGroupIdTransientName)) ? 
            "Nothing to do--Page Group ID Not Found in Cache" : "Page Group ID Transient Deleted Successfully";
        $this->echoComment($deleteComment);
    }
}