<?php
 /**
 * plgContentAECShowPlan - Plugin to show payments plans for users without access rights on content/category
 *
 * @author      Thiemo Borger
 * @copyright   (c) Thiemo Borger
 * @package     AEC - Account Control Expiration - Joomla 1.5 Plugins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version     1.0
 * @date		2015-05-12
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

$app = JFactory::getApplication();
$app->registerEvent( 'onAfterRoute', 'plgContentAECShowPlan' );

class plgContentAECShowPlan extends JPlugin
{
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
	}	

	public function onAfterRoute()
	{
		if ( strpos( JPATH_BASE, '/administrator' ) ) {
			return true;
		}

		if ( file_exists( JPATH_ROOT."/components/com_acctexp/acctexp.class.php" ) ) {
			$this->handlePlanRedirect();
		}
	}

	/**
	 * Redirect user without payment plan if acess is denied
	 */
	public function handlePlanRedirect()
	{
		$uri	= JFactory::getURI();

		$task	= JRequest::getVar( 'task' );
		$option	= JRequest::getVar( 'option' );
		$view	= JRequest::getVar( 'view' );
		$id		= JRequest::getVar( 'id' );
		$return = JRequest::getVar( 'return' );

		
		if (( $option == 'com_content' ) && (( $view == 'article' ))) {		
			$database = JFactory::getDBO();
			$database->setQuery( "SELECT access, catid FROM #__content WHERE id=".$database->quote($id)."");
			$access_group_article = $database->loadObject();
			
			$database->setQuery( "SELECT access FROM #__categories WHERE id=".$access_group_article->catid."");
			$access_group_category = $database->loadObject();		

			$user = JFactory::getUser();
			//$groups = $user->getAuthorisedViewLevels();
			$allowedViewLevels = JAccess::getAuthorisedViewLevels($user->id);     

			$access_article = true;
			$access_catgory = true;
			
			if(!in_array($access_group_article->access, $allowedViewLevels)){
				$access_article = false;
			}
			
			if(!in_array($access_group_category->access, $allowedViewLevels)) {
				$access_article = false;
			}
			if ((!$access_article) || (!$access_catgory))
			{
				$error = new stdClass();
				$error->code = 403;
				if (!$access_article)
				{
					$this->redirectNotAllowed( $error, $access_group_article->access, null);  
				}
				if (!$access_catgory)
				{
					$this->redirectNotAllowed( $error, null, $access_group_category->access);  
				}
			}
		}				
	}

	/**
	 * @param stdClass $error
	 */
	public function redirectNotAllowed( $error, $article_access_id, $cat_access_id  )
	{
		if ( $error->code == 403 ) {
			$app = JFactory::getApplication();
			if ($article_access_id != null) {
				$app->redirect( JURI::base() . 'index.php?option=com_acctexp&task=NotAllowed&article_access='.$article_access_id.'' );
			} elseif ($cat_access_id != null) {
				$app->redirect( JURI::base() . 'index.php?option=com_acctexp&task=NotAllowed&cat_access='.$cat_access_id.'' );
			} else {
				$app->redirect( JURI::base() . 'index.php?option=com_acctexp&task=NotAllowed' );
			}
			
		} else {
			JError::customErrorPage( $error );
		}
	}

}
