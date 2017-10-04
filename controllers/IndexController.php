<?php

namespace Controllers;

use Silex\Application;
use Silex\Route;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;

/*Основной и единственный контроллер микросервиса*/
class IndexController implements ControllerProviderInterface {
	
	public function connect(Application $app) {
		
		/*Получаем коллекцию контроллеров приложения*/
		$cfactory = new ControllerCollection( new Route() );
		
		/*Определяем роут для индексной страницы, функцию 
		обработки GET-запроса по нему*/
		$cfactory->get('/', function() use ($app) {
			//Возвращаем результат рендеринга шаблонизатором Twig
			//шаблона главной страницы
			return $app['twig']->render('index.twig.html', array());
			
		});
		
		/*Определяем роут для страницы вывода статистики, 
		функцию обработки GET-запроса по нему*/
		$cfactory->get('/stat', function() use ($app) {
		
			$sql = 'SELECT * FROM GROUPED_PAYLOADS';
			//Выбираем из группирующего представления набор данных
			//со значениями счетчиков для комбинаций TYPE+PAYLOAD,
			//при этом записей с одной кукой может быть несколько
			$payloads = $app['db']->fetchAll($sql);
			
			/*Промежуточный массив статистики с ключами-куками и 
			значениями из массивов с комбинированными ключами 
			TYPE+PAYLOAD (состояния просмотра) по ТЗ. Элементов с одним ключом-кукой
			несколько не может быть, объединяем по ключу-куке в одну запись*/
			$modifiedPayloads = array();
			foreach ($payloads as $payload) {
				$cookieVal = $payload['COOKIE'];
				/*Если в промежуточном массиве с ключами-куками нет 
				еще элемента-массива ассоциированного с очередной кукой*/
				if (!isset($modifiedPayloads[$cookieVal])) {
					/*Создаем элемент-массив относящийся к ключу-куке и 
					заполненный нулевыми значениями с комбинированными ключами-куками
					элементов*/
					$modifiedPayloads[$cookieVal] = array( 'loaded' => 0, 'progress25' => 0, 'progress50' => 0, 'progress75' => 0, 'finished' => 0 );
				}
				$modifiedPayloads[$cookieVal][$payload['TYPE'].$payload['PAYLOAD']] = $payload['PCOUNT'];
			}
			
			/*Обычный массив с цифровыми порядковыми ключами*/
			$payloadGroupMassive = array();
			foreach ($modifiedPayloads as $mpKey => $modifiedPayload) {
				/*Складываем в массив элементы-массивы, представляющие объединение 
				массива с кукой-хэшем и относящися к этой куке счетчик с комбинированными
				именами ключей (состояний просмотра) TYPE+PAYLOAD согласно ТЗ*/
				$payloadGroupMassive[] = array_merge( array('COOKIE' => $mpKey), $modifiedPayload);
			}
			
			//Возвращаем результат рендеринга шаблонизатором Twig
			//шаблона главной страницы, передаем массив специально
			//сгруппированных данных - каждый элемент это массив,
			//содержащий куку и ее счетчики по всем вариантам состояний
			//просмотра TYPE+PAYLOAD
			return $app['twig']->render('view.twig.html', array('payloads' => $payloadGroupMassive));
			
		});
		
		/*Определяем роут для обработчика добавления статистики, 
		функцию обработки POST-запроса по нему*/
		$app->post('/stat', function (Request $request) use ($app) {
			
			ErrorHandler::register();
			//регистрируем обработчик ошибки для данного роута
			$app->error( function ( \Exception $e, $code ) use ($app) {

				//формируем JSON, говорящий об ошибке в обработчике
				$error = array( 'success' => false );
				//возврат ошибки клиентской части в JSON
				return $app->json( $error, 200 );
				
			});
			
			//получаем значение куки как md5-хэш от заголовочного User-Agent
			$cookie = isset($_SERVER['HTTP_USER_AGENT']) ? md5($_SERVER['HTTP_USER_AGENT']) : '';
			//получаем переданные TYPE и PAYLOAD поля
			$type = $request->get('type');
			$payload = $request->get('payload');
			//Получаем текущее Unix-time в числовом виде
			$timestamp = time();
			
			//по умолчанию не добавлено ни одной записи статистики
			$res = 0;
			
			//Вызываем инструкцию добавления записи статистики 
			//с указанием переданных и сформированных полей
			//в ответ получаем количество добавленных записей
			$res = $app['db']->insert('VIEW', array(
					'COOKIE' => $cookie,
					'TYPE' => $type,
					'PAYLOAD' => $payload,
					'TIMESTAMP' => $timestamp,
				)
			);
			
			//Если запись добавлена
			if ($res && $res == 1) {
				//Возвращаем JSON с признаком успешности, переданными в обработчик данными и полученной кукой
				return json_encode(array( 'success' => true, 'type' => $type, 'payload' => $payload, 'cookie' => $cookie ));
			} else {
				//Возвращаем JSON только с признаком неудачной обработки
				return json_encode(array( 'success' => false ));
			}
			
		});
		
		//Возвращаем фабрику для монтирования контроллера 
		//в index.php
		return $cfactory;
		
	}
	
}