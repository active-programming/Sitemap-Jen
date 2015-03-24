<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 * @package    sitemapjen
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

// Базовый контроллер компонента

class SitemapjenController extends JControllerLegacy {
	
	protected $input = null;
	
    function __construct(){
        parent::__construct();
        // регистрируем операции  $this->registerTask(операция,метод_контроллера)
		// прим.: если название метода и task совпадают, то регистрировать не нужно, метод вызывается автоматически
		// кроме того этим же способом можно переопределить методы, вызываемые стандартными кнопками, например
		// $this->registerTask( 'apply', 'save' );	после чего стандартная кнопка Применить(apply), будет вызывать метод ::save() вместо ::apply()
		// но ИМХО правильнее переопределять саму кнопку, так: JToolBarHelper::save( 'save_options' );
        $this->registerTask( 'default',  'display' );
        $this->registerTask( 'save_options', 'saveOptions' );
        $this->registerTask( 'to_ignore', 'addIgnore' );
        $this->registerTask( 'clear_links', 'clearLinks' );
		// стили и скрипты
		$document = JFactory::getDocument();
		$document->addStyleSheet( JURI::base().'components/com_sitemapjen/admin-style.css' );
		$document->addScript( JURI::base().'components/com_sitemapjen/admin-scripts.js' );
		// подпункты в горизонтальной панели
		$this->input = JFactory::getApplication()->input;
		$current = $this->input->getCmd( 'task', 'default');
		$links = array( 'default'=>'Ссылки', 'generate'=>'Сканер и генератор', 'options'=>'Настройки', 'help'=>'Справка' );
		foreach( $links as $task=>$name ){
			JSubMenuHelper::addEntry( $name, 'index.php?option=com_sitemapjen&task='.$task,	($current==$task) );
		}
    }
	
	// ссылки
	function display( $cachable=false, $urlparams=Array() ){
		$view = $this->getView( 'default', 'html' );	// получим view по имени (./views/[$viewName]/view.html.php)
		$model	= $this->getModel( 'default' );	// указываем, какую модель использовать (./models/[$modelName].php)
		$view->setModel( $model, true );
		$view->setLayout( 'default' );	// выбираем шаблон (./views/[$viewName]/tmpl/[$tmplName].php)
		$view->display();	// отображаем шаблон
	}
	

	// Страница генерации sitemap
	function generate(){
		$view = $this->getView( 'generate', 'html' );
		$model = $this->getModel( 'generate' );
		$view->setModel( $model, true );
		$view->setLayout( 'generate' );
		$opt = $this->getModel( 'options' );
		$options = $opt->getOptions();
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/';
		$inWork = 0;
		$mode = 0;
		$log = '';
		// пытаемся прочитать статус текущей задачи, если она есть
		if( $options['task_status'] == 'in_work' && $options['task_action'] == 'scan' ){ // если идет процесс, устанавливаем спец параметр для запуска ajax запросов
			// сканирование
			$inWork = 1;
			if( stripos($options['task_url'],$_SERVER['SERVER_NAME']) !== false ){
				$url = $options['task_url'];
			}
			$mode = $options['task_action']=='scan' ? 1 : 2;
			if( is_file(JPATH_COMPONENT.'/'.'cron-log.txt') ){
				$log = file_get_contents( JPATH_COMPONENT.'/'.'cron-log.txt' );
				file_put_contents( JPATH_COMPONENT.'/'.'cron-log.txt', '' ); // очищаем старые логи, более они не актуальны
			}
		}elseif( $options['task_status'] == 'in_work' && $options['task_action'] == 'generate' ){
			// генерация...как бэ
		}else{
			$log = '<div class="line">Последний запуск: '.str_replace( ' ', ' в ', @$options['last_starttime'] ).'</div>';
		}
		$noLinks = true;
		if( $model->getLinksCount() > 0 ){
			$noLinks = false;
		}
		$view->assign( 'inWork', $inWork );
		$view->assign( 'noLinks', $noLinks );
		$view->assign( 'url', $url );
		$view->assign( 'mode', $mode );
		$view->assign( 'log', $log );
		$view->display();
	}
	
	
	// настройки
	function options(){
		$view = $this->getView( 'options', 'html' );
		$model = $this->getModel( 'options' );
		$view->setModel( $model, true );
		$view->setLayout( 'options' );
		$view->display();
	}
	
	
	// сохранить настройки
	function saveOptions(){
		// проверка токена (вставляется в форму из view)
		JRequest::checkToken() or jexit( 'Invalid Token' );
		// получаем объект модели для работы с данными
		$model	= $this->getModel( 'options' );
		$result = $model->saveOptions();
		// редирект
		if( $result == true ){
			$this->setRedirect( 'index.php?option=com_sitemapjen&task=options', 'Настройки сохранены' );
		}else{
			$this->setRedirect( 'index.php?option=com_sitemapjen&task=options', 'Ошибка сохранения!', 'error' );
		}
	}
	
	
	function help(){
		$view = $this->getView( 'help', 'html' );
		$view->setLayout( 'help' );
		$view->display();
	}

	
	function addIgnore(){
		JRequest::checkToken() or jexit( 'Invalid Token' );
		$optMod = $this->getModel( 'options' );
		$options = $optMod->getOptions();
		$model = $this->getModel( 'default' );
		$ids = $this->input->get( 'cid', array(), 'array' );
		$options['ignore_list'] = $model->addIgnore( $ids, $options['ignore_list'] );
		$optMod->setOption( 'ignore_list', $options['ignore_list'] );
		$page = $this->input->get( 'page', 0 );
		$this->setRedirect( 'index.php?option=com_sitemapjen&task=default&page='.$page, 'Добавлено в исключения', '' );
	}
	
	
	// удалить все ссылки
	function clearLinks(){
		$model = &$this->getModel( 'default' );
		$model->removeLinks();
		$this->setRedirect( 'index.php?option=com_sitemapjen&task=default', 'Ссылки удалены', '' );
	}
	
}