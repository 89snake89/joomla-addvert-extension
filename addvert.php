<?php
// No direct access.
defined('_JEXEC') or die;

class plgContentAddvert extends JPlugin
{
	var $addvertTag = "addvert-button";
	protected $button_layout = "";
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.5
	 * @note        A differenza delle versioni precedenti di Joomla! il costruttore non ha piu' il nome della classe
	 *              ma e' il metodo __construct
	 */
	public function __construct(& $subject, $config){
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	/**
	 * Aboutme prepare content method
	 *
	 * Questo metodo viene chiamato da Joomla! prima che il contenuto venga visualizzato
	 *
	 * @param   oggetto contenente l'articolo    The article object.  Note $article->text is also available
	 * @param   oggetto contenente i parametri del plugin
	 */
	function onContentPrepare($context, &$row, &$params, $page = 0){
		$app = JFactory::getApplication();
		
		$addvert_id = $this->params->get('addvert-id');
		$addvert_secret = $this->params->get('addvert-secret');
		$ecommerce_id = $this->params->get('ecommerce-id');
		$this->button_layout = $this->params->get('button-dimension');
		
		$document = JFactory::getDocument();
		
		$document->setMetaData("addvert:type", "product");
		$document->setMetaData("addvert:ecommerce_id", $ecommerce_id);
		
		$document->setMetaData("addvert:price", "price");
		$document->setMetaData("addvert:tag", "tag");
		
		// cerco l'espressione {addvert-button} all'interno del content
		$regex = "/{".$this->addvertTag."}/i";
		preg_match_all($regex,$row->text,$matches);
	
		// Numero di occorrenze
		$count = count($matches[0]);
	
		// Se non ci sono occorrenze della stringa {addvert-button}
		// termino l'esecuzione del plugin
		if(!$count) return;
		
		// Preparo l'output
		$plg_output = $this->getButtonHtml();
		// Elaboro i tag del plugin
		$row->text = preg_replace( $regex, $plg_output , $row->text );
	}
	
	public function getButtonHtml(){
		//Recupero l'url di addvert dalle impostazione del plugin
		$script_url = $this->params->get('addvert-website-url');
		return '
		<script type="text/javascript">
		(function() {
		var js = document.createElement(\'script\'); js.type = \'text/javascript\'; js.async = true;
		js.src = \'' . $script_url . '\';
		var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(js, s);
		})();
		</script>
		<div class="addvert-btn" data-width="450" data-layout="' . $this->button_layout . '"></div>';
	}
	
	function onAfterRender() {
		$element_url = '/administrator/';
		$current_url = $_SERVER ['REQUEST_URI'];
		global $mainframe;
		$buffer = JResponse::getBody ();
		if (strstr ( $current_url, $element_url )) {
			;
		} else {
			
			$document = &JFactory::getDocument ();
			$app = JFactory::getApplication ();
			
			$hdog_title_tmp = $document->getTitle();
			$hdog_title = '<meta property="og:title" content="' . $hdog_title_tmp . '"/>';
			$hdog_base = $document->getBase ();
			$hdog_url = '<meta property="og:url" content="' . $hdog_base . '"/>';
			
			// get thumbnail image
			$hdog_image_tmp = $this->params->get ( 'hdog_image' );
			$hdog_image = '<meta property="og:image" content="' . JURI::base () . $hdog_image_tmp . '" />';
			
			// check article view
			$view = JRequest::getVar ( 'view' );
			if ($view == "article") {
				$articlesModel = JModel::getInstance ( 'ContentModelArticle' );
				$articleId = JRequest::getInt ( 'id', 0 );
				$hdog_image_thumb = '<meta property="og:image" content="' . $articleId . '" />';
				$article = $articlesModel->getItem ( $articleId );
				$imagesCode = $article->images;
				$thumbCode = str_replace ( "\/", "/", $imagesCode );
				$thumb = explode ( '"', $thumbCode );
				$thumb_img = $thumb [03];
				if (! empty ( $thumb_img )) {
					$hdog_image_thumb = '<meta property="og:image" content="' . JURI::base () . $thumb_img . '" />';
				} else {
					$hdog_image_thumb = '<meta property="og:image" content="' . JURI::base () . $hdog_image_tmp . '" />';
				}
			}
			// check category view
			if ($view == "category") {
				$categoriesModel = JModel::getInstance ( 'ContentModelCategories' );
				$category = JRequest::getVar ( 'id', 0 );
				$db = JFactory::getDbo ();
				$query = $db->getQuery ( true );
				$query->select ( 'alias' );
				$query->from ( '#__categories' );
				$query->where ( 'id=$category' );
				$document->setMetaData("addvert:category", "category");
				$db->setQuery ( $query );
				$results = $db->loadObjectList ();
			}
			
			$hdog_image_thumb = '<meta property="og:image" content="' . $categoryId . '" />';
			if (! empty ( $thumb_img )) {
				$hdog_image_thumb = '<meta property="og:image" content="' . JURI::base () . $thumb_img . '" />';
			} else {
				$hdog_image_thumb = '<meta property="og:image" content="' . JURI::base () . $hdog_image_tmp . '" />';
			}
			
			// get meta description
			$hdog_desc_tmp = $document->getDescription ();
			$hdog_desc = '<meta property="og:description" content="' . $hdog_desc_tmp . '" />';
			
			// render all to screen
			$hdog_all = $hdog_title . $hdog_url . $hdog_image_thumb . $hdog_desc;
			$use_admin = $this->params->get ( 'use_admin' );
			if ($use_admin == '1') {
				$hdog_all = $hdog_all;
			}
			$buffer = str_replace ( '<html xmlns="http://www.w3.org/1999/xhtml"', '<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" ', $buffer );
			$buffer = str_replace ( '</title>', '</title>' . $hdog_all, $buffer );
			
			JResponse::setBody ( $buffer );
			return true;
		}
		
	}
}