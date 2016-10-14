<?php
/**
 * MyCitySelector
 * @author Konstantin Kutsevalov
 * @version 2.0.0
 */

defined('_JEXEC') or die(header('HTTP/1.0 403 Forbidden') . 'Restricted access');

require_once __DIR__ . '/../helpers/form/formHelper.php';
require_once __DIR__ . '/../helpers/mvc/JxController.php';
require_once __DIR__ . '/../helpers/mvc/JxView.php';

use adamasantares\jxmvc\JxController;
use adamasantares\jxmvc\JxView;

class FieldsController extends JxController
{

    /**
     * @inheritdoc
     */
    public function assets()
    {
        return [
            ['css', 'url' => 'components/com_mycityselector/admin-style.css'],
            ['js', 'url' => 'components/com_mycityselector/admin-scripts.js', 'defer' => true],
        ];
    }


    /**
     * @inheritdoc
     */
    public function sidebarMenuItems()
    {
        $sidebar = [
            'default' => JText::_('COM_MYCITYSELECTOR_COUNTRIES'), //'country'
            'province' => JText::_('COM_MYCITYSELECTOR_PROVINCES'),
            'city' => JText::_('COM_MYCITYSELECTOR_CITIES'),
            'fields' => JText::_('COM_mycityselector_field')
        ];
        if (JFactory::getConfig()->get('debug') == 1) {
            $sidebar['dev'] = 'DEV TOOLS';
        }
        return $sidebar;
    }


    /**
     * List of items
     */
    public function actionIndex()
    {
        JToolBarHelper::title(JText::_('COM_MYCITYSELECTOR_NAME'), 'big-ico');
        JToolBarHelper::addNew();
        JToolBarHelper::publishList();
        JToolBarHelper::unpublishList();
        //TODO Добавить проверку прав доступа
//        if ($canDo->get('core.admin') || $canDo->get('core.options'))
//        {
        JToolbarHelper::preferences('com_mycityselector');
//            JToolbarHelper::divider();
//        }
        JToolBarHelper::custom('drop', 'delete', 'delete', JText::_('COM_MYCITYSELECTOR_ITEM_DELETE'));
        $model = $this->getModel('fields');
        // sorting
        $this->setStateFromRequest('order_by', $model->filter_fields);
        $listOrder = $this->getState('order_by', 'name');
        $this->setStateFromRequest('order_direction', ['asc', 'desc']);
        $listDirection = $this->getState('order_direction', 'asc');
        $model->setOrder($listOrder, $listDirection);
        $this->render('list', [
            'items' => $model->getItems(),
            'pagination' => $model->getPagination(),
            'listOrder' => $listOrder,
            'listDirection' => $listDirection
        ]);
    }


    /**
     * List of items for popup window from editor
     */
    public function actionPopup()
    {
        $model = $this->getModel('fields');
        $this->render('list_popup', [
            'items' => $model->getItems(),
            'pagination' => $model->getPagination(),
        ]);
    }


    /**
     * Add new item
     */
    public function actionAdd()
    {
        $model = $this->getModel('fields');
        /* @var $model fieldsModel */
        $data = $model->getDefaultData();
        JToolBarHelper::title(JText::_('COM_MYCITYSELECTOR_NAME') . ' - ' . JText::_('COM_MYCITYSELECTOR_ITEM_ADDING'), 'big-ico');
        JToolBarHelper::apply('save');
        JToolBarHelper::save('saveandclose');
        JToolBarHelper::save2new('saveandnew');
        JToolBarHelper::cancel('index');
        $this->render('edit', [
            'model' => $model,
            'data' => $data,
        ]);
    }


    /**
     * Edit form for item
     * @param   boolean $cache If true, the view output will be cached
     * @param   array $urlParams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     */
    public function actionUpdate()
    {
        $model = $this->getModel('fields');
        /* @var $model fieldsModel */
        $id = intval($this->input->getCmd('id'));
        if (!empty($_POST['cid'])) {
            $id = intval($_POST['cid'][0]);
        }
        $data = $model->getItem($id);
        JToolBarHelper::title(JText::_('COM_MYCITYSELECTOR_NAME') . ' - ' . JText::_('COM_MYCITYSELECTOR_ITEM_EDITING') . ': ' . $data['name'], 'big-ico');
        if (!empty($data)) {
            JToolBarHelper::apply('save');
            JToolBarHelper::save('saveandclose');
            JToolBarHelper::save2new('saveandnew');
            JToolBarHelper::cancel('index');
            $this->render('edit', [
                'model' => $model,
                'data' => $data,
            ]);
        } else {
            JToolBarHelper::addNew();
            $this->render('not_found', []);
        }
    }


    /**
     * Save an item and return to form
     */
    public function actionSave()
    {
        $this->save('update');
    }


    /**
     * Save an item and return to list items
     */
    public function actionSaveAndClose()
    {
        $this->save();
    }


    /**
     * Save an item and create new item (redirect)
     */
    public function actionSaveAndNew()
    {
        $this->save('add');
    }


    /**
     * Save item
     */
    private function save($redirectTo = 'index')
    {
        $page = 0;
        $url = '';
        $model = $this->getModel('fields');
        $id = $model->saveItem($_POST);
        if (!$id) {
            // error
            $this->setMessage(JText::_('COM_MYCITYSELECTOR_HELLO_SAVE_ERROR'), 'error');
        } else {
            switch ($redirectTo) {
                case 'add':
                    $url .= '&task=' . $redirectTo;
                    break;
                case 'update':
                    $url .= '&task=' . $redirectTo . '&id=' . $id;
                    break;
                default:
                    $url .= '&page=' . $page;
            }
        }
        $this->setMessage(JText::_('COM_MYCITYSELECTOR_FORM_SAVED'), 'message');
        $this->redirect('index.php?option=' . $this->_component . '&controller=' . $this->_id . $url);
    }


    /**
     * Drop field
     */
    public function actionDrop()
    {
        $page = $this->input->getCmd('page', 0);
        $fields = $this->getModel('fields');
        // drop fields
        $fields->dropItems($_POST['cid']);
        $this->setMessage(JText::_('COM_MYCITYSELECTOR_MESSAGE_DELETED'));

        $this->redirect('index.php?option=' . $this->_component . '&controller=' . $this->_id . '&page=' . $page);
    }


    /**
     * Publish items
     */
    public function actionPublish()
    {
        $page = $this->input->getCmd('page', 0);
        $model = $this->getModel('fields');
        if (!empty($_POST['cid'])) {
            $model->publishItems($_POST['cid'], 1);
        }
        $this->redirect('index.php?option=' . $this->_component . '&controller=' . $this->_id . '&page=' . $page);
    }


    /**
     * UnPublish items
     */
    public function actionUnPublish()
    {
        $page = $this->input->getCmd('page', 0);
        $model = $this->getModel('fields');
        if (!empty($_POST['cid'])) {
            $model->publishItems($_POST['cid'], 0);
        }
        $this->redirect('index.php?option=' . $this->_component . '&controller=' . $this->_id . '&page=' . $page);
    }


    /**
     * Save new order of items
     */
    public function actionSaveOrdering()
    {
        $responce = ['status' => '200', 'debug_get' => $_GET];
        $order = empty($_GET['order']) ? [] : $_GET['order'];
        if (!empty($order)) {
            /* @var $model fieldsModel */
            $model = $this->getModel('fields');
            $listOrder = $this->getState('order_by', 'name');
            $listDirection = $this->getState('order_direction', 'asc');
            $model->setOrder($listOrder, $listDirection);
            $model->saveOrdering($order);
        }
        exit(json_encode($responce));
    }


    public function actionGetForm() {
        $this->render('form');
    }

    public function actionDeleteFieldValue() {
        $model = $this->getModel('fields');
        $id = JFactory::getApplication()->input->get('id');
        $model->deleteFieldValue($id);

    }

}