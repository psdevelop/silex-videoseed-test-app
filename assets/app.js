//Переменная содержащая значение 
//имитируемого прогресса просмотра
var progress = 0;

/**
  * Производит шаг имитируемого прогресса 
  * просмотра видеоролика и отсылает
  * данные о просмотре сервису сбора статистики
  * @returns {*}
 */	
function incrementLoadProgress() {
		
	//Значения полей статистики по умолчанию,
	//соответствующие началу просмотра
	var typeValue = 'loaded',
		payloadValue = '';
		
	//Формирование значений полей TYPE и PAYLOAD, 
	//передаваемых через AJAX-POST запрос для
	//формирования записи статистики просмотров в БД
	switch (progress) {	
		case 0:
		    //Оставляем значения для начала просмотра
			break;
		//Три варианта значений полей неполного просмотра
		case 1:
		case 2:
		case 3:
			typeValue = 'progress';
			payloadValue = progress*25;
			break;
		//Конец просмотра
		case 4:
			typeValue = 'finished';
			break;
		default:
			typeValue = 'undefined';
	}
		
	//Формируем и отправляем AJAX запрос
	//на соответствующий роут /stat
	$.ajax({
		url: "http://127.0.0.1:1234/stat",
		//Передаем в формате POST 
		type: "POST",
		//Указываем сформированные значения статистики
		data: {
			type: typeValue,
			payload: payloadValue
		},
		//Указываем, что сервер вернет 
		//данные в формате JSON
		dataType: 'json'
	})
	.done( function ( data ) {
		
		//Анализирует ответ в JSON формате, если он имеет признак 
		//успеха обработки запроса, содержит хэш header User-Agent
		//и не установлен соотв. кук
		if (data && data.success && data.cookie && !$.cookie('vseed_token')) {
			//устанавливаем кук
			$.cookie('vseed_token', data.cookie);
		}
		
		//Если в ответе возвращен признак
		//неудачной обработки запроса добавления
		//записи статистики
		if (data && data.success === false) {
			var msg = 'Unsuccess stat item insert!!!';
			
			$('.VideoSeed_TestApp__StatusRegion').empty().append(msg);
			console.log( msg );
		}	

	})
	.fail( function () {
		//Оповещаем в консоли ошибку транспортировки 
		//запроса через AJAX
		var errMsg = 'AJAX request error!';
		$('.VideoSeed_TestApp__StatusRegion').empty().append(errMsg);
		console.log( errMsg );
	});
		
	//Инкрементируем признак прогресса шкалы просмотра
	progress++;
}

//После полной загрузки документа	
$( document ).ready( function () {
	//Заполняем контейнер отображения процента просмотра 
	//начальным значением
	$('.VideoSeed_TestApp__ProgressRegion').empty().append('0%'); //html работает дольше)
			
	incrementLoadProgress();
	// начать повторы с интервалом 5 сек
	var timerId = setInterval( function() {
			
		//Если просмотр окончен
		if (progress >= 5) {
			//Останавливаем прогресс просмотра
			clearInterval(timerId);
		} else {
			//Заполняем контейнер отображения процента просмотра 
			//текущим значением на основании progress
			$('.VideoSeed_TestApp__ProgressRegion').empty().append( (progress * 25) + '%' );
			//Производим шаг прогресса просмотра и отправку статистики
			incrementLoadProgress();
		}
				
	}, 5000);
	
});