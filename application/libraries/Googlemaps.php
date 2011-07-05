<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Google Static Maps API V2 Class
 *
 * Displays a Google Map
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		BIOSTALL (Steve Marks)
 * @link		http://biostall.com/codeigniter-google-static-maps-v2-api-library
 * @docs		http://biostall.com/wp-content/uploads/2010/07/Google_Maps_V3_API_Documentation.pdf
 */
 
class Googlemaps {
	
	var $center						= "";						// Sets the default center location (lat/long co-ordinate or address) of the map image. Required if markers not present. If blank and markers exist will default center
	var $https						= FALSE;					// If set to TRUE will load the API over HTTPS, allowing you to utilize the API within your HTTPS secure application
	var $image_format				= "";						// The image format output. Values accepted are 'png8', 'png' (default), 'png32', 'gif', 'jpg', 'jpg-baseline'
	var $include_img_tag			= TRUE;						// If set to TRUE will include the full <img> tag. If FALSE just the image source will be returned
	var $language					= "";						// Defines the language to use for display of labels on map tiles. Note that this parameter is only supported for some country tiles
	var $map_id						= "map_canvas";				// The ID of the image that is output containing the map image
	var $map_height					= 500;						// The height of the map image in pixels
	var $map_type					= "";						// The default map type. Values accepted are 'roadmap' (default), 'satellite', 'terrain' or 'hybrid'
	var $map_width					= 500;						// The width of the map image in pixels
	var $sensor						= FALSE;					// Set to TRUE if being used on a device that can detect a users location
	var	$version					= "2";						// Version of the static API being used. Not currently used in the library
	var $zoom						= "";						// The default zoom level of the map image. Required if markers not present. If blank and markers exist will default zoom to include all markers
	
	var	$markers					= array();					// An array used by the library to store the markers as they are produced
	var	$paths						= array();					// An array used by the library to store the paths as they are produced
	
	function Googlemaps($config = array())
	{
		if (count($config) > 0)
		{
			$this->initialize($config);
		}

		log_message('debug', "Google Static Maps Class Initialized");
	}

	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$this->$key = $val;
			}
		}
		
		if ($this->sensor) { $this->sensor = "true"; }else{ $this->sensor = "false"; }
		
	}
	
	function add_marker($params = array())
	{
		
		$marker = array();
		
		$marker['position'] = '';								// The position (lat/long co-ordinate or address) at which the marker will appear
		$marker['size'] = '';									// The size of the icon to use for the marker. If no size is set the marker will appear in its default size.
		$marker['color'] = '';									// The color of the icon to use for the marker. A hex value (eg. #990000 or a word; black, brown, green, purple, yellow, blue, gray, orange, red, white)
		$marker['label'] = '';									// A single uppercase alphanumeric character (A-Z or 0-9) to show on the markers icon. Tiny and small markers are not compatible with this option
		$marker['icon_url'] = '';								// Instead of using the default marker icons you can specify a URL to a custom icon image. Limited to 4096 pixels and five unique custom icons per request
		$marker['icon_shadow'] = TRUE;							// If using a custom icon this option turns on and off the shadow. If TRUE the shadow will be made based on the image's visible region and its opacity/transparency.
		
		$marker_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($marker[$key])) {
			
				$marker[$key] = $value;
				
			}
			
		}
		
		if ($marker['position']!="") {
			
			if ($marker['icon_url']=="") {
				if ($marker['size']!="") { $marker_output .= 'size:'.$marker['size'].'|'; }
				if ($marker['color']!="") { 
					if (substr($marker['color'], 0, 1)=="#") { // convert hex value
						$marker['color'] = '0x'.substr($marker['color'], 1, 6);
					}
					$marker_output .= 'color:'.$marker['color'].'|'; 
				}
				if (strlen($marker['label'])==1) { $marker_output .= 'label:'.$marker['label'].'|'; }
			}else{
				$marker_output .= 'icon:'.$marker['icon_url'].'|';
				if (!$marker['icon_shadow']) {
					$marker_output .= 'shadow:false|';
				}
			}
			$marker_output .= $marker['position'];
			 
		}
		
		array_push($this->markers, $marker_output);
	
	}
	
	function add_path($params = array())
	{
		
		$path = array();
		
		$path['positions'] = array();							// The array of two or more positions (lat/long co-ordinate or address) at which the path will appear
		$path['weight'] = '';									// The thickness of the path in pixels. Defaults to 5 pixels
		$path['color'] = '';									// The color of the icon to use for the marker. A hex value (eg. #990000, or #FFFFCCFF or a word; black, brown, green, purple, yellow, blue, gray, orange, red, white)
		$path['fillcolor'] = '';								// Indicates the path is a polygonal area and specifies the fill color within that area. The array of locations positions need not be a "closed" loop; the Static Map server will automatically join the first and last points. Note, however, that any stroke on the exterior of the filled area will not be closed unless you specifically provide the same beginning and end location.
		
		$path_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($path[$key])) {
			
				$path[$key] = $value;
				
			}
			
		}
		
		if (count($path['positions'])>=2) {
			
			if ($path['weight']!="") { $path_output .= 'weight:'.$path['weight'].'|'; }
			if ($path['color']!="") { 
				if (substr($path['color'], 0, 1)=="#") { // convert hex value
					$path['color'] = '0x'.substr($path['color'], 1, 8);
				}
				$path_output .= 'color:'.$path['color'].'|'; 
			}
			if ($path['fillcolor']!="") { 
				if (substr($path['fillcolor'], 0, 1)=="#") { // convert hex value
					$path['fillcolor'] = '0x'.substr($path['fillcolor'], 1, 8);
				}
				$path_output .= 'fillcolor:'.$path['fillcolor'].'|'; 
			}
			
			foreach ($path['positions'] as $position) {
				$path_output .= $position.'|';
			}
			$path_output = rtrim($path_output, "|");
			 
		}
		
		array_push($this->paths, $path_output);
	
	}
	
	function create_map()
	{
	
		$this->output = '';
		$this->parameters = array();
		
		if ($this->https) { $apiLocation = 'https://maps.googleapis'; }else{ $apiLocation = 'http://maps.google'; }
		
		$this->parameters['sensor'] = $this->sensor;
		$this->parameters['size'] = $this->map_width.'x'.$this->map_height;
		if ($this->center!="") { $this->parameters['center'] = $this->center; }
		if ($this->zoom!="") { $this->parameters['zoom'] = $this->zoom; }
		if ($this->image_format!="") { $this->parameters['format'] = $this->image_format; }
		if ($this->map_type!="") { $this->parameters['maptype'] = $this->map_type; }
		if ($this->language!="") { $this->parameters['language'] = $this->language; }
		
		$this->output .= $apiLocation.'.com/maps/api/staticmap?';
		
		foreach ($this->parameters as $key=>$value) {
			$this->output .= $key.'='.urlencode($value).'&';
		}
		
		$this->output = rtrim($this->output, "&");
		
		// add markers
		if (count($this->markers)) {
			$this->output .= '&';
			foreach ($this->markers as $marker) {
				$this->output .= "markers=".urlencode($marker).'&';
			}
			$this->output = rtrim($this->output, "&");
		}	
		//
		
		// add paths
		if (count($this->paths)) {
			$this->output .= '&';
			foreach ($this->paths as $path) {
				$this->output .= "path=".urlencode($path).'&';
			}
			$this->output = rtrim($this->output, "&");
		}	
		//
		
		if ($this->include_img_tag) { 
			$this->output = '<img src="'.$this->output.'" id="'.$this->map_id.'" alt="Google Map" />'; 
		}
		
		return $this->output;
	
	}
	
}

?>