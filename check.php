<?php
	if(isset($_GET['info']))
	{
		phpinfo();
		die();
	}
?>
<html>
	<head>
		<title>Symphony Server check</title>
		<style type="text/css">
			html { font-family: monospace; background: #888; }
			body { width: 400px; position: absolute; left: 50%; margin-left: -210px; top: 100px; background: #fff; padding: 10px;
				-webkit-box-shadow: 0px 3px 6px rgba(0, 0, 0, .5);
				-moz-box-shadow: 0px 3px 6px rgba(0, 0, 0, .5);
				box-shadow: 0px 3px 6px rgba(0, 0, 0, .5);
				-webkit-border-radius: 5px;
				-moz-border-radius: 5px;
				border-radius: 5px; 
			}
			h1 { text-align: center; }
			h1 span { font-size: 12pt; color: #888; }
			table { border-collapse: collapse; width: 100%; }
			th, td { text-align: left; padding-right: 2em; border: 1px solid #ccc; padding: 3px 20px 3px 3px; }
			th { background: #eee; }
			td.ok { color: #080; font-weight: bold; background: #dfd; }
			td.wrong { color: #800; font-weight: bold; background: #fdd; }
			em { color: #888; }
			p { text-align: center; }
			p a { color: #888; text-decoration: none; }
		</style>
	</head>
	<body>
		<h1>Symphony Server check <span>v1.0</span></h1>
		<table>
			<tr><td colspan="3">Required:</td></tr>
			<tr>
				<th>Description</th>
				<th>Value</th>
				<th>Result</th>
			</tr>
			<?php
				function addRow($name, $value, $ok)
				{
					$class = $ok ? 'ok' : 'wrong';
					echo '<tr><th>'.$name.':</th><td>'.$value.'</td><td class="'.$class.'">'.ucfirst($class).'</td></tr>';
				}			
				
				// Check PHP version:
				$phpVersion = PHP_VERSION;
				addRow('PHP Version', PHP_VERSION, version_compare(PHP_VERSION, '5.2.0') >= 0);
				
				// Check safe mode:
				$safeMode = ini_get('safe_mode');
				addRow('Safe mode', $safeMode ? 'yes' : 'no', !$safeMode);
				
				// Check LibXML:
				$xsl = extension_loaded('XSL');
				addRow('XSL', $xsl ? 'yes' : 'no', $xsl);
				
				// MySQL Version:
				@$output = shell_exec('mysql -V'); 
				preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
				$mysqlVersion = $version[0];
				
				if($mysqlVersion == false) {
					// Retrieve the MySQL version on a more brute way, by loading the phpinfo()-function:
					ob_start();
					phpinfo();
					$content = ob_get_contents();
					ob_end_clean();					
					$start = explode("<h2><a name=\"module_mysql\">mysql</a></h2>",$content,1000); 
					if(count($start) < 2){ 
						$mysqlVersion = 'not installed or not detected';
						if($safeMode) {
							$mysqlVersion.= '<br /><em>(probably because safe_mode is on)</em>';
						}
					} else { 
						$again = explode("<tr><td class=\"e\">Client API version </td><td class=\"v\">",$start[1],1000); 
						$last_time = explode(" </td></tr>",$again[1],1000); 
						$mysqlVersion = $last_time[0];
					} 
				}
				
				addRow('MySQL Version', $mysqlVersion, version_compare($mysqlVersion, '4.1') >= 0);
				
				// Server:
				$serverSoftware = $_SERVER['SERVER_SOFTWARE'];
				
				$apache = preg_match('/apache/i', $serverSoftware);
				$litespeed = preg_match('/litespeed/i', $serverSoftware);
				if($apache)
				{
					addRow('Server', 'yes: Apache', true);
				} elseif($litespeed) {
					addRow('Server', 'yes: Litespeed', true);
				} else {
					addRow('Server', 'no', false);	
				}
				
				// Mod_rewrite:
				if (function_exists('apache_get_modules')) {
					$modules = apache_get_modules();
					$mod_rewrite = in_array('mod_rewrite', $modules);
				} else {
					$mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
				}
				if($mod_rewrite == false)
				{
					// Last chance: check the phpinfo()-function:
					ob_start();
					phpinfo(INFO_MODULES);
					$content = ob_get_contents();
					ob_end_clean();
					$mod_rewrite = strpos($content, 'mod_rewrite') !== false;
				}
				addRow('Mod_rewrite', $mod_rewrite ? 'yes' : 'no', $mod_rewrite);
				if($mod_rewrite == false) {
					echo '
						<tr>
							<td colspan="3">
								<em>Please note that due to security reasons it\'s not always possible to detect mod_rewrite, so it might be enabled but can\'t be detected.</em>
							</td>
						</tr>
					';
				}
			?>
			<tr><td colspan="3"><br />Optional, not required:</td></tr>
			<tr>
				<th>Description</th>
				<th>Value</th>
				<th>Result</th>
			</tr>
			<?php
				// Optional:
			
				// GD:
				$gd = extension_loaded('GD');
				addRow('GD', $gd ? 'yes' : 'no', $gd);
				
				// EXSLT:
				if($xsl)
				{
					$proc = new XSLTProcessor();
					$exslt = $proc->hasExsltSupport();
				} else {
					$exslt = false;
				}
				addRow('EXSLT', $exslt ? 'yes' : 'no', $exslt);
				
				$zip = class_exists('ZipArchive');
				// ZIP:
				// $zip = extension_loaded('ZIP');
				addRow('ZipArchive', $zip ? 'yes' : 'no', $zip);
			?>
		</table>
		<p>
			<a href="?info">show phpinfo()</a>
		</p>
	</body>
</html>