<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Game AdminPanel (АдминПанель)
 *
 * 
 *
 * @package		Game AdminPanel
 * @author		Nikita Kuznetsov (ET-NiK)
 * @copyright	Copyright (c) 2013, Nikita Kuznetsov (http://hldm.org)
 * @license		http://gameap.ru/license.html
 * @link		http://gameap.ru
 * @filesource
*/
class Users extends CI_Model {
	
	/* Данные авторизованного пользователя */
	var $auth_id 		= false;
	var $auth_login 	= false;
    
    var $auth_privileges 			= array();	// Базовые привилегии
    var $auth_servers_privileges 	= array();	// Привилегии на отдельные серверы
    var $auth_data 					= array();	// Данные пользователя
    
    /* Данные пользователя */
    var $user_id 		= false;
    var $user_login		= false;
    var $user_password	= false;
    
    var $tpl_data;							// Данные для шаблона
    
    var $user_privileges 		= array();	// Базовые привилегии
    var $servers_privileges 	= array();	// Привилегии на отдельные серверы
    var $user_data 				= array();	// Данные пользователя
    
    /* Списки и пользователи которые получает авторизованный пользователь */
    var $users_list = array();				// Список пользователей

    /* Все базовые привилегии */
    var $all_user_privileges = array(
			'srv'					=> '{lang_base_privileges_srv}',					// Привилегии на серверы
			'srv_global' 			=> '{lang_base_privileges_srv_global}',				// Глобальные серверные права
			'srv_start' 			=> '{lang_base_privileges_srv_start}',				// Запуск серверов
			'srv_stop' 				=> '{lang_base_privileges_srv_stop}',				// Остановка серверов
			'srv_restart' 			=> '{lang_base_privileges_srv_restart}',			// Перезапуск серверов
			
			'usr'					=> '{lang_base_privileges_usr}',					// Привилегии на пользователей
			'usr_create' 			=> '{lang_base_privileges_usr_create}',				// Создание пользователей
			'usr_edit' 				=> '{lang_base_privileges_usr_edit}',				// Редактирование пользователей
			'usr_edit_privileges' 	=> '{lang_base_privileges_usr_edit_privileges}',	// Редактирование привилегий пользователей
			'usr_delete' 			=> '{lang_base_privileges_usr_delete}',				// Удаление пользователей
	);
    
    // Все серверные привилегии
    var $all_privileges = array(
			'VIEW' 					=> '{lang_servers_privileges_view}',				// Отображение сервера в списке
            'RCON_SEND' 			=> '{lang_servers_privileges_rcon_send}',			// Отправка ркон команд
            'CHANGE_RCON' 			=> '{lang_servers_privileges_change_rcon}',			// Смена пароля
            'FAST_RCON' 			=> '{lang_servers_privileges_fast_rcon}',			// Fast rcon
            'PLAYERS_KICK' 			=> '{lang_servers_privileges_players_kick}',		// Кик игроков
            'PLAYERS_BAN' 			=> '{lang_servers_privileges_players_ban}',			// Бан игроков
            'PLAYERS_CH_NAME' 		=> '{lang_servers_privileges_players_chname}',		// Смена имени игрокам
            'CHANGE_MAP' 			=> '{lang_servers_privileges_change_map}',			// Смена карты
            'SERVER_START' 			=> '{lang_servers_privileges_start}',				// Старт сервера
            'SERVER_STOP' 			=> '{lang_servers_privileges_stop}',				// Остановка сервера
            'SERVER_RESTART' 		=> '{lang_servers_privileges_restart}',				// Перезапуск сервера
            'SERVER_SOFT_RESTART' 	=> '{lang_servers_privileges_soft_restart}',		// Мягкий перезапуск
            'SERVER_CHAT_MSG' 		=> '{lang_servers_privileges_chat_msg}',			// Сообщение в чат
            'SERVER_SET_PASSWORD' 	=> '{lang_servers_privileges_set_password}',		// Задание пароля на сервер
            'SERVER_UPDATE' 		=> '{lang_servers_privileges_update}',				// Обновление сервера
            'SERVER_SETTINGS' 		=> '{lang_servers_privileges_settings}',			// Настройки
            'CONSOLE_VIEW' 			=> '{lang_servers_privileges_console_view}',		// Просмотр консоли
            'TASK_MANAGE' 			=> '{lang_servers_privileges_task_manage}',			// Управление заданиями
            'UPLOAD_CONTENTS' 		=> '{lang_servers_privileges_upload_contents}',		// Загрузка контента
            'CHANGE_CONFIG'			=> '{lang_servers_privileges_change_config}',		// Редактирование конфигов
            'LOGS_VIEW' 			=> '{lang_servers_privileges_log_view}',			// Просмотр логов
     );

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        
        $this->load->helper('safety');
        $this->load->library('encrypt');
    }


    // ----------------------------------------------------------------

    /**
     * Проверка пользователя
     * 
     * @return bool
    */
    function check_user()
    {
        $user_id = safesql($this->input->cookie('user_id', true));
        $user_hash = safesql($this->input->cookie('hash', true));
        
        if($user_id && $user_hash) {
            $query = $this->db->get_where('users', array('id' => $user_id, 'hash' => $user_hash), 1);
            $this->auth_data = $query->row_array();
        } else {
            return false;
        }

        if ($query->num_rows > 0) {
			
			$this->auth_id = $user_id;
			$this->auth_login = $this->auth_data['login'];
			$this->auth_data['balance'] = (int)$this->encrypt->decode($this->auth_data['balance']);
			
			/* Костыль */
			$this->user_id = $user_id;
			$this->user_login = $this->auth_data['login'];
			/*--------*/
			
			/* Получение базовых привилегий */
            if(!$this->auth_privileges = json_decode($this->auth_data['privileges'], true)) {
				foreach($this->all_user_privileges as $key => $value) {
					/* 
					 * key в привилегиях меньше 3х знаков
					 * используется для обозначения категории
					*/
					if (strlen($key) > 3) {
						$this->auth_privileges[$key] = 0;
					}
				}
			}
			
			/* Костыль */
			$this->user_privileges = $this->auth_privileges;
			/*--------*/
		
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Авторизация пользователя
     * Проверка логина и пароля
    */
    function user_auth($user_login = '', $user_password = '')
    {
        if(!$user_login OR !$user_password){
            return false;
        }
        
        $user_login = safesql($user_login);
        $user_password = safesql($user_password);
        
        $query = $this->db->get_where('users', array('login' => $user_login), 1);
        
        if ($query->num_rows > 0) {
            
            $this->user_data = $query->row_array();
            $user_data = $this->user_data;
            
            $this->load->model('password');
            $password_md5 = $this->password->encryption($user_password, $this->user_data);
            
        } else {
            return false;
        }
        
        // Проверка пароля
        if($password_md5 == $this->user_data['password']){
            
            $this->auth_id 		= $user_data['id'];
            $this->auth_login 	= $user_data['login'];
            $this->auth_data 	= $user_data;
            $this->auth_data['balance'] = (int)$this->encrypt->decode($user_data['balance']);
            
            
            return $this->auth_id;
        } else {
            return false;
        }  
    }
    
    
    // ----------------------------------------------------------------

    /**
     * Получает данные пользователя
     * 
     * @param int       - id пользователя
     * @param bool      - записать данные в $this->user_data
     * @param bool      - если true то данные привиление не будут получены
     * @return array
    */
    function get_user_data($user_id = false, $to_this = false, $no_get_privileges = false){
        
        if(!$user_id){
            return false;
        }
        
        if(is_array($user_id)) {
			$where = $user_id;
		} else {
			$where = array('id' => $user_id);
		}

        $query = $this->db->get_where('users', $where, 1);
        $user_data = $query->row_array();
        
        if (!$user_data) {
			return false;
		}
		
		$user_data['balance'] = (int)$this->encrypt->decode($user_data['balance']);

        if(!$no_get_privileges) {
			
			if(!$user_privileges = json_decode($user_data['privileges'], true)) {
				foreach($this->all_user_privileges as $key => $value) {
					
					/* 
					 * key в привилегиях меньше 3х знаков
					 * используется для обозначения категории
					*/
					if(strlen($key) > 3) {
						$this->user_privileges[$key] = 0;
					}
				}
				
				$user_privileges = $this->user_privileges;
			}
			
            //$query_users_privileges = $this->db->get_where('users_privileges', array('user_id' => $user_data['id']), 1);
            //$user_privileges = $query_users_privileges->row_array();
        } else {
            $user_privileges = array();
        }
        
        if ($to_this) {
			/* Сохранять значения в $this->****  */
            //$this->user_id = $user_data['id'];
            //$this->user_login = $user_data['login'];
            
            $this->user_data = $user_data;
            $this->user_privileges = $user_privileges;
        }
        
        return array_merge($user_data, $user_privileges);
    }

    // ----------------------------------------------------------------

    /**
     * Добавление нового пользователя 
     * 
     * @param array
     * @return bool
    */
    function add_user($user_data)
    {
        if($this->db->insert('users', $user_data)){
			return true;
		}else{
			return false;
		}
    }
    
    function delete_user($id)
    {
		if($this->db->delete('users', array('id' => $id))){
			return true;
		}else{
			return false;
		}
	}
    
    // ----------------------------------------------------------------

    /**
     * Редактирование пользователя
     * 
     * @param array - новые данные
     * @param string - id пользователя, либо массив с where
     * @return bool
    */
    function update_user($user_data, $user_id = false)
    {
        if(!$user_id){
            $user_id = $this->auth_id;
        }
        
        if(!$user_id OR !$user_data){
            return false;
        }
        
        if(!is_array($user_id)) {
			$this->db->where('id', $user_id);
		} else {
			 $this->db->where($user_id);
		}

		$query = $this->db->update('users', $user_data); 
        
        
        if(!$query){
            return false;
        }else{
            return true;
        }
    }
    
    // ----------------------------------------------------------------

    /**
     * Возвращает массив с некоторыми данными пользователя
     * для вставки их в tpl_data
     * 
     * @return array
    */
    function tpl_userdata($id = false, $user_data = false)
    {
        $this->load->helper('date');
        
        if (!$id) {
            $user_data = $this->auth_data;
        } else {
			if (!$user_data) {
				$user_data = $this->get_user_data($id, false, true);
			}
        }
        
        if (!$user_data) {
			return false;
		}
        
        $tpl_data['id'] 				= $user_data['id'];
        $tpl_data['user_id'] 			= $user_data['id'];
        $tpl_data['user_login'] 		= $user_data['login'];
        $tpl_data['user_name'] 			= $user_data['name'];
        $tpl_data['user_email'] 		= $user_data['email'];
        $tpl_data['balance'] 			= $user_data['balance'];
   
        $tpl_data['user_reg_date'] = unix_to_human($user_data['reg_date'], true, 'eu');
        $tpl_data['user_last_auth'] = unix_to_human($user_data['last_auth'], true, 'eu');
        
        $this->user_data = $user_data;
        
        return $tpl_data;
    }
    
    // ----------------------------------------------------------------

    /**
     * Получение привилегий на отдельные серверы
     * 
     * @return array
    */
    function get_server_privileges($server_id = false, $user_id = false, $no_insert_this = false)
    {
        
        if(!$user_id){
            $user_id = $this->auth_id;
        }
        
        if(!is_numeric($user_id)){
            return false;
        }

        if(!$server_id){
            $where = array('user_id' => $user_id);
        }else{
            $where = array('user_id' => $user_id, 'server_id' => $server_id);
        }
        
        $query = $this->db->get_where('servers_privileges', $where);
        $user_privileges = array();
        foreach ($query->result_array() as $privileges)
        {
            if(array_key_exists($privileges['privilege_name'], $this->all_privileges)){
                $user_privileges[$privileges['privilege_name']] = $privileges['privilege_value'];
            }
        }

        // Заполнение пустых привилегий
        foreach ($this->all_privileges as $key => $value)
        {
            if(!array_key_exists($key, $user_privileges)){
                $user_privileges[$key] = 0;
            }
        }
        
        /* Если не запрашиваются данные другого пользователя
         * в этом случае записываем еще в $this->servers_privileges
        */
        
        //print_r($user_privileges);
        
        if(!$no_insert_this){
            $this->servers_privileges = $user_privileges;
            $this->auth_servers_privileges = $user_privileges;
        }
        
        return $user_privileges;
    }
    
    // ----------------------------------------------------------------

    /**
     * Запись привилегий на отдельные серверы
     * 
     * @return string
    */
    function set_server_privileges($privilege_name, $rule, $server_id, $user_id = false)
    {
        if(!$user_id){
            $user_id = $this->auth_id;
        }else{
            $user_id  = (int)$user_id;
        }
        
        $where = array('user_id' => $user_id, 
                       'server_id' => $server_id,
                       'privilege_name' => $privilege_name);
        
        $query = $this->db->get_where('servers_privileges', $where);
        
        $data = array(
            'user_id' =>            $user_id,
            'server_id' =>          $server_id,
            'privilege_name' =>     $privilege_name,
            'privilege_value' =>    $rule
        );
        
        $this->db->where('user_id', $user_id);
        $this->db->where('server_id', $server_id);
        $this->db->where('privilege_name', $privilege_name);
            
        if($query->num_rows > 0){
           /* Если привилегия уже есть в базе данных, то обновляем */
           if($this->db->update('servers_privileges', $data)){
                return true;
            }else{
                return false;
            }
            
        }else{
			/* Привилегии нет в базе данных, создаем новую строку */
			if($this->db->insert('servers_privileges', $data)){
                return true;
            }else{
                return false;
            }
		}

            
    }

    // ----------------------------------------------------------------
    
    /**
     * Получение списка пользователей
     * 
     * @return array
    */
    function tpl_users_list()
    {
        $this->load->helper('date');
        
        if(empty($this->users_list)){
			$this->get_users_list();
		}

        $num = -1;
        foreach ($this->users_list as $users){
            $num++;
            
            $list[$num] = $users;
            $list[$num]['user_id'] 			= $users['id'];
            $list[$num]['user_reg_date'] 	= unix_to_human($users['reg_date'], true, 'eu');
            $list[$num]['user_last_auth'] 	= unix_to_human($users['last_auth'], true, 'eu');
            $list[$num]['user_balance'] 	= $users['balance'];
        }
        
        return $list;
    }
    
    //-----------------------------------------------------------
	/**
     * Получение списка пользователей
     * 
     * @param array - условия для выборки
     * @param int
     * 
     * @return array
     *
    */
    function get_users_list($where = false, $limit = false)
    {
		/*
		 * В массиве $where храняться данные для выборки.
		 * Например:
		 * 		$where = array('id' => 1);
		 * в этом случае будет выбран пользователь id которого = 1
		 * 
		*/
		if(is_array($where)){
			$query = $this->db->get_where('users', $where, $limit);
		}else{
			
			$query = $this->db->get('users');
		}

		if($query->num_rows > 0){
			
			$this->users_list = $query->result_array();
			
			/* Расшифровка баланса */
			foreach($this->users_list as &$user) {
				$user['balance'] = (int)$this->encrypt->decode($user['balance']);
			}
			
			return $this->users_list;
			
		}else{
			return NULL;
		}
	}
    
    // ----------------------------------------------------------------
    
    /**
     * Проверяет, существует ли пользователь с данным id, логином
     * 
     * @return bool
    */  
    function user_live($string, $type = 'id'){
		
		$type = strtolower($type);
        
        switch($type){
            case('id'):
				$this->db->where('id', $string);
				break;
            
            case('login'):
				$this->db->where('login', $string);
				break;
				
            case('email'):
               $this->db->where('email', $string);
				break;
			
			default:
				return false;
				break;
        }

        if ($this->db->count_all_results('users') > 0) {
            return true;
        } else {
            return false;
        }
        
    }
    
    // ----------------------------------------------------------------
    
    /**
     * Проверяет привилегию
     * 
     * @param int - id пользователя
     * @param int - id сервера
     * @param string - имя привилегии
     * 
    */  
    function check_privilege($user_id = false, $server_id = false, $privilege = false){
        
        if(!$user_id){
            $user_id = $this->user_data['id'];
        }else{
            $user_id  = (int)$user_id;
        }
        
        if(!$privilege){
            $query = $this->db->get_where('users', array('login' => $string));
        }else{
			
        }
    }
    
    // ----------------------------------------------------------------
    
    /**
     * Получаем hash пользователя, случайной строки
     * @return string
     * 
    */  
    function get_user_hash(){
        
        $this->load->helper('safety');
        $hash = md5(generate_code(10) . $_SERVER['REMOTE_ADDR']);
        $this->update_user(array('hash' => $hash, 'last_auth' => time()));
        
        return $hash;
    }
    
    // ----------------------------------------------------------------
    
    /**
     * Получаем код для восстановления пароля пользователя
     * @param - id пользователя
     * @return string
     * 
    */  
    function get_user_recovery_code($user_id)
    {
		$user_data = $this->get_user_data($user_id, false, true);
		return $user_data['recovery_code'];
    }
    
    // ----------------------------------------------------------------
    
    /**
     * Задает код для восстановления пароля пользователя
     * @param - id пользователя
     * @return string
     * 
    */ 
    function set_user_recovery_code($user_id)
    {
		$this->load->helper('safety');
        $code = generate_code(20);
        
        $this->update_user(array('recovery_code' => $code), $user_id);
        
        return $code;
	}
	
	// ----------------------------------------------------------------
    
    /**
     * Отправка сообщения пользователю на почту
     * @return string
    */  
	function send_mail($subject = '<empty>', $message = '', $user_id)
	{
		$this->load->library('email');
		
		$user_data = $this->get_user_data($user_id, false, true);
		
		$this->email->to($user_data['email']);
		$this->email->from($this->config->config['system_email'], 'AdminPanel');
		$this->email->subject($subject);
		$this->email->message($message);
		
		if ($this->email->send()) {
			return true;
		} else {
			//echo $this->email->print_debugger();
			return false;
		}
	}
    
    // ----------------------------------------------------------------
    
    /**
     * Отправка сообщения администратору на почту
     * @return string
    */  
    function admin_msg($subject = '<empty>', $message) {
		
		$admin_list = $this->get_users_list(array('is_admin' => '1'), 1000);
		
		if (empty($admin_list)) {
			// Админов нет
			return false;
		}
		
		foreach($admin_list as $admin_data) {
			$email_list[] = $admin_data['email'];
		}
		
		$this->load->library('email');
		
		$this->email->to($email_list);
		$this->email->from($this->config->config['system_email'], 'AdminPanel');
		$this->email->subject($subject);
		$this->email->message($message);
							
		if($this->email->send()) {
			return true;
		} else {
			//echo $this->email->print_debugger();
			return false;
		}
    }
}
