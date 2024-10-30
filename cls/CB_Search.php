<?php
/**
 * An abstract class that provides search/retrieval and error handling for the searches. 
 * The class also provides inherited functionality to transform the response document 
 * using xsl. Global search options should be set using the static setOption. I.e. for 
 * the partner or project code filters.
 *
 * @author Mark Oliver Brawn
 * @copyright Orangeleaf Systems Ltd
 * @version 1.0.0
 */
class CB_Search
{
	/**
	 * Error constant - Could not connect to the API
	 * 
	 */
	const E_HTTP_ERROR = 1;
	/**
	 * Error constant - Response could be loaded into a dom document
	 *
	 */
	const E_INVALID_XML = 2;
	/**
	 * Error constant - Transformation xsl file not found
	 *
	 */
	const E_XSLT_FILE_NOT_FOUND = 3;
	/**
	 * Error constant - Transformation xsl file contains invalid XML
	 *
	 */
	const E_XSLT_XML_INVALID = 4;
	/**
	 * Error constant - (Warning really) about the loading on the server
	 *
	 */
	const E_LOADING_HIGH = 5;
	/**
	 * An array of errors raised
	 *
	 * @var array
	 */
	public $errors;
	/**
	 * The RAW response document
	 *
	 * @var DOMDocument
	 */
	public $response;
	/**
	 * Check the server load average
	 *
	 */
	private function checkloading()
	{
		if(!function_exists('sys_getloadavg'))
		{
			function sys_getloadavg()
			{
			    $str = substr(strrchr(shell_exec("uptime"),":"),1);
			    $avs = array_map("trim",explode(",",$str));			
			    return $avs;
			}
		}
		$load = sys_getloadavg();
		ob_start();
		var_dump($load);
		$m = ob_get_clean();
		if ($load[0] > 80) 
		{
			mail('mark@orangeleaf.com','Warning! High Load Average on Collections Press',$m);
			$this->error(CB_Search::E_LOADING_HIGH,__CLASS__,__LINE__,__FILE__,'Server loading is at : '.$load[0],false);
		    //header('HTTP/1.1 503 Too busy, try again later');
		    //die('Server too busy. Please try again later.');
		}
	}
	/**
	 * Raise an error
	 *
	 * @param integer $code The error code (one of the E_ constants above)
	 * @param string $class The class that generated the error
	 * @param integer $line The line that generated the error
	 * @param string $file The file that generated the error
	 * @param string $message An additional error message to include
	 * @param boolean $die Whether to exit
	 */
	protected function error($code,$class,$line,$file,$message='',$die=false)
	{
		$this->errors[] = array(
			'code'=>$code,
			'class'=>$class,
			'line'=>$line,
			'file'=>$file,
			'message'=>$message
		);
		
		
		$logfile = dirname(dirname(__FILE__)).'/logs/CB_Search.log';
		if(!is_writable($logfile))
		{
			mail('mark@orangeleaf.com','Error on Collections Press','The log file at '.$logfile.' is not writable.');
		}
		if(!file_exists($logfile))
		{			
			file_put_contents($logfile,"Date\tIP\t\tDie\tCode\tClass\tLine\tFile\tMessage\r\n-----------------------------------------------\r\n");
		}
		
		$ip = getenv('REMOTE_ADDR');
		file_put_contents($logfile,date('Y-m-d H:i:s')."$ip\t\t$die\t$code\t$class\t$line\t$file\t$message\r\n",FILE_APPEND);
		
		//mail('mark@orangeleaf.com','Error on Collections Press',date('Y-m-d H:i:s')."\t$die\t$code\t$class\t$line\t$file\t$message\r\n");
		
		
		if($die)
		{
			die();
		}
		//echo '<pre>';
		//print_r($this->errors);
		//echo '</pre>';
	}
	/**
	 * Query the repository. All derived classes should use this method to 
	 * query the respository.
	 *
	 * @param string $url The entry point URL
	 * 
	 * @return DOMDocument
	 */
	protected function submit($url)
	{			
		$url.= '&referrer='.$_SERVER['HTTP_HOST'];
				
		$this->checkloading();				
		
		$content ='';
		$this->response = new DOMDocument('1.0', 'UTF-8');
		$this->response->preserveWhiteSpace = false;
		
		try
		{		
			if(!@$this->response->load($url))
			{
				$this->error(CB_Search::E_INVALID_XML,__CLASS__,__LINE__,__FILE__,'Invalid response from: '.$url,true);
				unset($this->response);
			}
			//$this->response->loadXML($url);
		}
		catch (Exception $e) 
		{
			// Couldn't load response, raise an error
			$this->error(CB_Search::E_HTTP_ERROR,__CLASS__,__LINE__,__FILE__,'Couldn\'t load response from: '.$url.' Exception: '.$e,true);
			unset($this->response);
		}
		
		if(isset($_GET['show_feed_url']))
		{
			echo $url;
		}
		
		//print $this->response->documentElement->nodeName;
	}
	/**
	 * Class destructor
	 *
	 */
	public function __destruct()
	{
		$this->response = null;
	}
}
?>