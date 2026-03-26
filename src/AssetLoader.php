<?php

class AssetLoader
{
	private string $ConfigDir;
	private string $StackDir;
	private string $VersionDir;

    private array $defaultConfig;
    private array $assets = [];
    private array $enabled = [];

    private array $css = [];
    private array $js = [];
    private array $inlineVars = [];

    private string $resourceServer;
    
    private array $verbose = [];
    private array $errors = [];

    public function __construct(string $configPath = null)
    {
    	$this->ConfigDir  =  __DIR__ . '/../config';
    	$this->StackDir   =  $this->ConfigDir . '/stacks';
    	$this->VersionDir =  $this->ConfigDir . '/versions';

    	if($configPath === null) {
    		$configPath = $this->ConfigDir . '/default.json';
    	}
    	
        $this->defaultConfig = $this->getJSON($configPath);

        if (!$this->defaultConfig) {
            throw new Exception("Invalid default config");
        }

        $this->resourceServer = rtrim($this->defaultConfig['resource_server'], '/');
        
        if($this->defaultConfig['error_reporting']) {
        	error_reporting(E_ALL & ~E_NOTICE);
        	ini_set('error_reporting', E_ALL & ~E_NOTICE);
        } else {
        	error_reporting(0);
        	ini_set('error_reporting', 0);
        }      

        $this->init();
    }
    
    private function getJSON(string $path): array
    {
    	if(!file_exists($path) or !is_readable($path)) {
    		throw new Exception("Invalid file: $path");
    	}
    	
    	$json = json_decode(file_get_contents($path), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
        	throw new Exception("JSON error in $path: " . json_last_error_msg());
        }
        
        return $json;
    }

    private function init(): void
    {
    	if(array_key_exists('stack', $this->defaultConfig)) {
    		$stackfile = $this->StackDir . '/' . $this->defaultConfig['stack'] . '.json';
    		$stack = $this->getJSON($stackfile);
     		if(array_key_exists('assets', $stack)) {
    			$this->loadAssets($stack['assets']);
    		} else {
    			throw new Exception("No assets defined in stack $stackfile");
    		}
    		if(array_key_exists('enabled', $stack)) {
    			$this->enableAssets($stack['enabled']);
    		}
    	}
    }
    
    private function loadAssets(array $assets): void
    {
    	foreach($assets as $aname => $aversion) {
    		$assetfile = $this->VersionDir . '/' . $aname . '-' . $aversion . '.json';
    		$this->assets[$aname] = $this->getJSON($assetfile);    	
    	}   	
    }
    
    private function enableAssets(array $list): void
    {
    	foreach($list as $aname) {
    		if(array_key_exists($aname, $this->assets) and !array_key_exists($aname, $this->enabled)) {
    				$this->enabled[] = $aname; 	
    		}
    	}   	
    }
    
    private function getAssetBaseURI(string $asset): string
    {
    	$uri = "";
    	
    	if(array_key_exists($asset, $this->assets)) {
    		$a = $this->assets[$asset];
    		
    		if(array_key_exists('base_uri', $a)) {
    			return $a['base_uri'];
    		}
    		
    		if(array_key_exists('resource_server', $a)) {
    			$uri .= $a['resource_server'];
    		} else {
    			$uri .= $this->resourceServer;
    		}
    		
    		if(array_key_exists('path', $a)) {
    			$uri .= $a['path'];
    		}
    		if(array_key_exists('version', $a)) {
    			$uri .= "/".$a['version'];
    		}
    		
    		$this->assets[$asset]['base_uri'] = $uri;
    	}
    	
    	return $uri;
    }
    
    private function renderAssets(string $type): string
    {
    	if($type == 'css') {
    		$r = '<link rel="stylesheet" href="';
    		$e = " />\n";
    	} elseif ($type == 'js') {
    		$r = '<script src="';
    		$e = "></script>\n";
    	} else {
    		throw new Exception("Asset type '".$type."' is unknown!");
    		return null;    	
    	}
    	
    	$html = "";
    	
    	foreach ($this->assets as $aname => $adata) {
    		if(!in_array($aname, $this->enabled)) continue;
    		
    		if(array_key_exists($type, $adata)) {
    			$html .= "<!-- ".htmlspecialchars($aname)." ". $type . " -->\n";
    			$html .= $r . $this->getAssetBaseURI($aname) . '/' . $adata[$type]['src'].'"';
    			if(array_key_exists('integrity', $adata[$type])) {
    				$html .= ' integrity="'.$adata[$type]['integrity'].'"';
    			}
    			$html .= ' crossorigin="anonymous"'.$e;
    		}
    	}
    	
    	return $html;
    }

    public function add(string $assetName): self
    {
        if (!isset($this->assets[$assetName])) {
            throw new Exception("Asset '$assetName' not defined");
        }

        $asset = $this->assets[$assetName];

        if (isset($asset['css'])) {
            $this->css[$assetName] = $this->resourceServer . $asset['css'];
        }

        if (isset($asset['js'])) {
            $this->js[$assetName] = $this->resourceServer . $asset['js'];
        }

        return $this;
    }

    public function addInlineVar(string $key, $value): self
    {
        $this->inlineVars[$key] = $value;
        return $this;
    }

    public function renderHead(string $title = ''): string
    {
        $html = "<!DOCTYPE html>\n<html lang=\"de\">\n<head>\n";
        $html .= "<meta charset=\"utf-8\">\n";
        $html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
        $html .= "<title>" . htmlspecialchars($title) . "</title>\n";
        $html .= $this->renderAssets('css');
        $html .= "</head>\n<body>\n";

        return $html;
    }

    public function renderFoot(): string
    {
        $html = "";
        $html .= $this->renderAssets('js');

        if (!empty($this->inlineVars)) {
            $html .= "<script>\n";
            foreach ($this->inlineVars as $k => $v) {
                $v = json_encode($v);
                $html .= "var $k = $v;\n";
            }
            $html .= "</script>\n";
        }

        $html .= "</body>\n</html>";

        return $html;
    }
}