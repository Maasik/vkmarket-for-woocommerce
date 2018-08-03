<?php

// Register the plugin page
function vkm_admin_menu() {

	add_menu_page( __( 'WooCommerce & Товары ВКонтакте', 'vkmarket-for-woocommerce' ), __( 'Товары ВК', 'vkmarket-for-woocommerce' ), 'activate_plugins', 'vkmarket', 'vkm_vk_api_settings_page', null, '90' );
}

add_action( 'admin_menu', 'vkm_admin_menu', 20 );


function vkm_settings_admin_init() {
	global $vkm_settings;

	$vkm_settings = new WP_Settings_API_Class2;

	$tabs = array(
		'vkm_options' => array(
			'id'       => 'vkm_options',
			'name'     => 'vkm_options',
			'title'    => __( 'Настройки', 'vkmarket-for-woocommerce' ),
			'desc'     => __( '', 'vkmarket-for-woocommerce' ),
			'sections' => array(

				'vkm_market_section'  => array(
					'id'    => 'vkm_market_section',
					'name'  => 'vkm_market_section',
					'title' => __( 'Настройки страницы ВК', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Настройки страницы ВКонтакте на которой будут размещены товары.', 'vkmarket-for-woocommerce' ),
				),
				'vkm_options_section' => array(
					'id'    => 'vkm_options_section',
					'name'  => 'vkm_options_section',
					'title' => __( 'Настройки синхронизации', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Настройки синхронизации товаров WooCommerce с товарами в группе ВКонтакте.', 'vkmarket-for-woocommerce' ),
				),

			)
		)
	);
	$tabs = apply_filters( 'vkm_settings_tabs', $tabs, $tabs );

	$en = vkm_get_vk_categories();

	$fields = array(
		'vkm_market_section'  => array(
			array(
				'name'  => 'page_url',
				'label' => __( 'Ссылка на страницу', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'Урл страницы, на которой вы будете размещать товары.
        <br/>Например: <code>http://vk.com/pasportvzubi</code>.
        <br/><br/>Вы можете создать <a href="http://vk.com/public.php?act=new" target="_blank">новую страницу</a> ВКонтакте или найти среди ваших уже <a href="http://vk.com/groups?tab=admin" target="_blank">созданных страниц</a>.', 'vkmarket-for-woocommerce' ),
				'type'  => 'text'
			),
			array(
				'name'     => 'page_id',
				'label'    => __( 'ID страницы ВКонтакте', 'vkmarket-for-woocommerce' ),
				'desc'     => __( 'Значение будет подставлено автоматически.
				<br><br>Если значение не появилось, нужно: навести курсор на поле с урлом группы, кликнуть левой кнопкой мыши, затем кликнуть левой кнопкой мыши в любом месте страницы - появится знак ожидания и id группы.
				<br>Если значение все еще не появилось, нужно открыть <a href="' . admin_url( '/admin.php?page=vkm-log' ) . '">Лог плагина</a>, там могут отображаться возможные ошибки.
				<br>Если из Лога неясно в чем дело, можно написать в <a href="https://vk.me/wordpressvk">службу поддержки</a>.', 'vkmarket-for-woocommerce' ),
				'type'     => 'text',
				'readonly' => true
			),
			array(
				'name'     => 'page_screen_name',
				'label'    => __( 'Короткое имя', 'vkmarket-for-woocommerce' ),
				'desc'     => __( 'Значение будет подставлено автоматически.', 'vkmarket-for-woocommerce' ),
				'type'     => 'text',
				'readonly' => true
			),
			array(
				'name'    => 'timeout',
				'label'   => __( 'Timeout', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<b>Внимание!</b> Служебные настройки. Менять только в <a href="http://ukraya.ru/easy-vkontakte-connect/documentation#3_1">указанном случае</a>.', 'vkmarket-for-woocommerce' ),
				'type'    => 'text',
				'default' => '5',
			)
		),
		'vkm_options_section' => array(
			array(
				'name'    => 'sync',
				'label'   => __( 'Синхронизация', 'vkmarket-for-woocommerce' ),
				'desc'    => __( 'Включить или отключить синхронизацию товаров на сайте с товарами в группе ВК.', 'vkmarket-for-woocommerce' ),
				'type'    => 'radio',
				'default' => '0',
				'options' => array(
					'1' => __( 'Включена', 'vkmarket-for-woocommerce' ),
					'0' => __( 'Отключена', 'vkmarket-for-woocommerce' )
				)
			),
			array(
				'name'    => 'vkm_category',
				'label'   => __( 'Рубрика', 'vkmarket-for-woocommerce' ),
				'desc'    => __( 'Рубрика в Товарах ВК в которой по умолчанию будут размещаться товары с сайта.', 'vkmarket-for-woocommerce' ),
				'type'    => 'select',
				'default' => '0',
				'options' => vkm_get_vk_categories( null, true )
			),
			array(
				'name'    => 'message',
				'label'   => __( 'Описание товара', 'vkmarket-for-woocommerce' ),
				'desc'    => __( 'Маска для описания товара в разделе Товары ВК:
        <br/><code>%content%</code> - полное описание товара,
        <br/><code>%excerpt%</code> - краткое описание товара (excerpt) или описание до тега <code>' . esc_html( '<!--more-->' ) . '</code>,
        <br/><code>%link%</code> - ссылка на товар.
        <br/>
        <br/><small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
        <br/><code>%attributes%</code> - атрибуты товара (attributes, свойства),
        <br/><code>%variations%</code> - вариации товара, например: <code>Размер: Большой, Цвет: Белый: 14 Р</code>,
        <br/><code>%addToCartLink%</code> - ссылка на товар в корзине: при клике, товар автоматически помещается в корзину, и открывается страница оформления заказа.', 'vkmarket-for-woocommerce' ),
				'type'    => 'textarea',
				'default' => "%content%"
			),
			array(
				'name'     => 'message_default',
				'desc'     => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
		<br>Если у продукта нет описания (включая свойства, вариации и все, что указано в предыдущей опции), будет добавлен данный текст. <b>Зачем?</b> ВК требует, чтобы у продукта было описание не менее 10 символов.', 'vkmarket-for-woocommerce' ),
				'type'     => 'text',
				'default'  => 'Описание отсутствует.',
				'readonly' => true
			),

		),

	);
	$fields = apply_filters( 'vkm_settings_fields', $fields, $fields );

	//set sections and fields
	$vkm_settings->set_option_name( 'vkm_options' );
	$vkm_settings->set_sections( $tabs );
	$vkm_settings->set_fields( $fields );

	//initialize them
	$vkm_settings->admin_init();
}

add_action( 'admin_init', 'vkm_settings_admin_init' );


function vkm_settings_admin_menu() {
	global $vkm_settings_page;

	$vkm_settings_page = add_submenu_page( 'vkmarket', __( 'Настройки Товаров ВК ', 'vkmarket-for-woocommerce' ), __( 'Настройки', 'vkmarket-for-woocommerce' ), 'activate_plugins', 'vkmarket-settings', 'vkm_settings_page' );

	add_action( 'admin_footer-' . $vkm_settings_page, 'vkm_settings_page_js' );
}

add_action( 'admin_menu', 'vkm_settings_admin_menu', 25 );


function vkm_settings_page_js() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			$("#vkm_options\\[page_url\\]").focusout(function () {
				var data = {
					action: 'vkm_get_group_id',
					group_url: $("#vkm_options\\[page_url\\]").val()
				};

				$.ajax({
					url: ajaxurl,
					data: data,
					type: "POST",
					dataType: 'json',
					beforeSend: function () {
						$("#vkm_options\\[page_url\\]\\[spinner\\]").css({
							'display': 'inline-block',
							'visibility': 'visible'
						});
					},
					success: function (data) {
						$("#vkm_options\\[page_url\\]\\[spinner\\]").hide();
						//if (data['gid'] < 0)
						//  data['gid'] = -1 * data['gid'];
						$("#vkm_options\\[page_id\\]").val(data['gid']);
						$("#vkm_options\\[page_screen_name\\]").val(data['screen_name']);

						//console.log(data);
					}
				});
			});

		}); // jQuery End
	</script>
	<?php
}


function vkm_settings_page() {
	global $vkm_settings;
	$options = get_option( 'vkm_vk_api_site' );

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'WooCommerce & Товары ВКонтакте', 'vkmarket-for-woocommerce' ); ?></h2>

		<?php
		if ( ! isset( $options['site_access_token'] ) || empty( $options['site_access_token'] ) ) {
			?>
			<div class="error">
				<p>
					<?php _e( 'Необходимо настроить VK API. Откройте страницу "<a href="' . admin_url( 'admin.php?page=vkmarket' ) . '">Настройки VK API</a>".', 'vkmarket-for-woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
		?>

		<div id="col-container">
			<div id="col-right" class="vkm">
				<div class="vkm-box">
					<?php vkm_admin_sticky(); ?>
				</div>
			</div>
			<div id="col-left" class="vkm">
				<?php
				settings_errors();
				$vkm_settings->show_navigation();
				$vkm_settings->show_forms();
				?>
			</div>
		</div>
	</div>
	<?php
}


function vkm_log_admin_init() {
	global $vkm_log;

	$vkm_log = new WP_Settings_API_Class2;

	$tabs = array(
		'vkm_log' => array(
			'id'            => 'vkm_log',
			'name'          => 'vkm_log',
			'title'         => __( 'Лог', 'vkmarket-for-woocommerce' ),
			'desc'          => __( '', 'vkmarket-for-woocommerce' ),
			'submit_button' => false,
			'sections'      => array(
				'vkm_log_section' => array(
					'id'    => 'vkm_log_section',
					'name'  => 'vkm_log_section',
					'title' => __( 'Лог действий плагина', 'vkmarket-for-woocommerce' ),
					'desc'  => __( '<div>' . vkm_the_log( 100 ) . '</div>', 'vkmarket-for-woocommerce' ),
				)
			)
		)
	);

	$fields = array();

	//set sections and fields
	$vkm_log->set_option_name( 'vkm_options' );
	$vkm_log->set_sections( $tabs );
	$vkm_log->set_fields( $fields );

	//initialize them
	$vkm_log->admin_init();
}

add_action( 'admin_init', 'vkm_log_admin_init' );


// Register the plugin page
function vkm_log_admin_menu() {
	global $vkm_log_settings_page;

	$vkm_log_settings_page = add_submenu_page( 'vkmarket', 'Лог', 'Лог', 'activate_plugins', 'vkmarket-log', 'vkm_log_page' );
}

add_action( 'admin_menu', 'vkm_log_admin_menu', 60 );

// Display the plugin settings options page
function vkm_log_page() {
	global $vkm_log;
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Лог плагина WooCommerce & Товары ВК', 'vkmarket-for-woocommerce' ); ?></h2>

		<div id="col-container">
			<div id="col-right" class="vkm">
				<div class="vkm-box">
					<?php vkm_admin_sticky(); ?>
				</div>
			</div>
			<div id="col-left" class="vkm">
				<?php
				settings_errors();
				$vkm_log->show_navigation();
				$vkm_log->show_forms();
				?>
			</div>
		</div>
	</div>
	<?php
}


/* Помощь по работе с плагином */

function vkm_help_admin_init() {
	global $vkm_help;

	$vkm_help = new WP_Settings_API_Class2;

	$tabs = array(
		'vkm_help' => array(
			'id'            => 'vkm_help',
			'name'          => 'vkm_help',
			'title'         => __( 'Помощь', 'vkmarket-for-woocommerce' ),
			'desc'          => __( '', 'vkmarket-for-woocommerce' ),
			'submit_button' => false,
			'sections'      => array(

				'vkm_help_section' => array(
					'id'    => 'vkm_help_section',
					'name'  => 'vkm_help_section',
					'title' => __( 'Настройки и начало работы', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Настройки и начало работы с плагином Товары ВК для WooCommerce.', 'vkmarket-for-woocommerce' ),
				)
			)
		)
	);


	$fields = array(
		'vkm_help_section' => array(
			array(
				'name'  => 'vk_group_settings',
				'label' => __( 'Настройки группы ВКонтакте', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'В группе, куда планируется экспортировать товары, нужно:
				<ol><li>Открыть меню <b>Управление сообществом</b> - <b>Разделы</b>,</li>
				<li>Поставить галочки в опциях <b>Фотографии</b> (иначе будет невозможно отправить фото товаров) и <b>Товары</b>,</li>
				<li>Нажать кнопку <b>Сохранить</b>.</li></ol>', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),
			array(
				'name'  => 'plugin_vkapi_settings',
				'label' => __( 'Настройки VK API в плагине', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'В меню плагина <b>Товары ВК</b> - <a href="' . admin_url( '/admin.php?page=vkmarket' ) . '" target="_blank">Настройки VK API</a>, следуя описанным там инструкциям, нужно:
				<ol><li>Создать приложение ВКонтакте и настроить его,</li>
				<li>Получить токен,</li>
				<li>Нажать кнопку <b>Сохранить</b>.</li></ol>', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),
			array(
				'name'  => 'plugin_settings',
				'label' => __( 'Настройки плагина', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'В меню плагина <b>Товары ВК</b> - <a href="' . admin_url( '/admin.php?page=vkmarket-settings' ) . '" target="_blank">Настройки</a>, нужно:
				<ol><li>В опции <b>Ссылка на страницу</b> ввести адрес группы ВК, после этого кликнуть левой кнопкой мыши в любом месте сайта (появится знак ожидания, затем ID и короткое имя страницы),
				<li>В опции <b>Синхронизация</b> выбрать Включено,</li>
				<li>В опции <b>Рубрика</b> задать категорию в ВК в которую будут отправляться товары с сайта,</li>
				<li>Нажать кнопку <b>Сохранить</b>.</li></ol>', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),
			array(
				'name'  => 'product_export',
				'label' => __( 'Отправить товар в группу', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'Чтобы отправить товар в группу, нужно:
				<ol><li><a href="' . admin_url( '/edit.php?post_type=product' ) . '" target="_blank">Открыть</a> любой товар в режиме редактирования,
				<li>Нажать кнопку <b>Обновить</b> (большая синяя кнопка в блоке Опубликовать). Товар будет отправлен в группу.</li>
				</ol>', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),
			array(
				'name'  => 'errors',
				'label' => __( 'Отладка', 'vkmarket-for-woocommerce' ),
				'desc'  => __( 'Если что-то идет не так, нужно:
				<ol><li>Открыть в меню плагина <b>Товары ВК</b> - <a href="' . admin_url( '/admin.php?page=vkmarket-log' ) . '" target="_blank">Лог</a>, там могут отображаться возможные ошиибки,
				<li>Обратиться в <a href="https://vk.me/wordpressvk" target="_blank">службу поддержки</a>, описав проблему и, по возможности, приведя сообщения из Лога.</li>
				</ol>', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),

			array(
				'name'  => 'documentation',
				'label' => __( 'Документация', 'vkmarket-for-woocommerce' ),
				'desc'  => __( '<a href="http://ukraya.ru/vkmarket-for-woocommerce/documentation" target="_blank">Руководство</a> по работе с плагином.', 'vkmarket-for-woocommerce' ),
				'type'  => 'html'
			),
		)
	);

	$is_pro = vkm_is_pro();

	if ( ! $is_pro ) {

		$fields['vkm_help_section'][] = array(
			'name'  => 'get_pro',
			'label' => __( 'Больше возможностей', 'vkmarket-for-woocommerce' ),
			'desc'  => __( '<b>Товары ВКонтакте PRO для WooCommerce</b> поддерживает:
			<ol><li><strong>массовые операции с товарами</strong>: экспорт, удаление из группы ВК,</li>
		 	<li>все действия с <strong>подборками товаров ВК</strong>: создание, изменение, удаление, перемещение, поддержка псевдовложенных подборок,</li>
		  	<li>и многое другое.</li></ol>
			' . get_submit_button( 'Узнать больше', 'primary', 'get-vkm-pro', false ), 'vkmarket-for-woocommerce' ),
			'type'  => 'html'
		);
	}


	$fields = apply_filters( 'vkm_help_fields', $fields, $fields );

	//$fields = array();

	//set sections and fields
	$vkm_help->set_option_name( 'vkm_options' );
	$vkm_help->set_sections( $tabs );
	$vkm_help->set_fields( $fields );

	//initialize them
	$vkm_help->admin_init();
}

add_action( 'admin_init', 'vkm_help_admin_init' );


// Register the plugin page
function vkm_help_admin_menu() {
	global $vkm_help_settings_page;

	$vkm_help_settings_page = add_submenu_page( 'vkmarket', 'Помощь', 'Помощь', 'activate_plugins', 'vkmarket-help', 'vkm_help_page' );
}

add_action( 'admin_menu', 'vkm_help_admin_menu', 70 );


// Display the plugin settings options page
function vkm_help_page() {
	global $vkm_help;
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Настройки и начало работы', 'vkmarket-for-woocommerce' ); ?></h2>

		<div id="col-container">
			<div id="col-right" class="vkm">
				<div class="vkm-box">
					<?php vkm_admin_sticky(); ?>
				</div>
			</div>
			<div id="col-left" class="vkm">
				<?php
				settings_errors();
				$vkm_help->show_navigation();
				$vkm_help->show_forms();
				?>
			</div>
		</div>
	</div>
	<?php
}

/* END */


function vkm_bulk_admin_init() {
	global $vkm_bulk;

	$vkm_bulk = new WP_Settings_API_Class2;

	$tabs = array(
		'vkm_bulk'           => array(
			'id'            => 'vkm_bulk',
			'name'          => 'vkm_bulk',
			'title'         => __( 'Экспорт & Удаление', 'vkmarket-for-woocommerce' ),
			'desc'          => __( '', 'vkmarket-for-woocommerce' ),
			'submit_button' => false,
			'sections'      => array(

				'vkm_export_section' => array(
					'id'    => 'vkm_export_section',
					'name'  => 'vkm_export_section',
					'title' => __( 'Экспорт / Удаление', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Массовый экспорт или удаление товаров из группы ВКонтакте.', 'vkmarket-for-woocommerce' ),
				),


			)
		),
		'vkm_bulk_reactions' => array(
			'id'            => 'vkm_bulk_reactions',
			'name'          => 'vkm_bulk_reactions',
			'title'         => __( 'Подборки & Товары', 'vkmarket-for-woocommerce' ),
			'desc'          => __( '', 'vkmarket-for-woocommerce' ),
			'submit_button' => false,
			'sections'      => array(

				'vkm_bulk_reactions_section' => array(
					'id'    => 'vkm_bulk_reactions_section',
					'name'  => 'vkm_bulk_reactions_section',
					'title' => __( 'Подборки & Товары', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Обновление очередности подборок в разделе Товары в группе ВК и обновление товаров в подборках ВК.', 'vkmarket-for-woocommerce' ),
				),


			)
		),
	);
	$tabs = apply_filters( 'vkm_bulk_tabs', $tabs, $tabs );

	$en = vkm_get_vk_categories();

	$fields = array(
		'vkm_export_section'         => array(
			array(
				'name'    => 'action',
				'label'   => __( 'Действие', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Действия с товарами или подборками.', 'vkmarket-for-woocommerce' ),
				'type'    => 'radio',
				'default' => 'export',
				'options' => array(
					'export'      => __( 'Экспорт товаров в ВК', 'vkmarket-for-woocommerce' ),
					'delete'      => __( 'Удаление товаров из ВК', 'vkmarket-for-woocommerce' ),
					'update'      => __( 'Обновление товаров в ВК', 'vkmarket-for-woocommerce' ),
					'term_export' => __( 'Экспорт подборок в ВК', 'vkmarket-for-woocommerce' ),
					'term_delete' => __( 'Удаление подборок из ВК', 'vkmarket-for-woocommerce' )
				)
			),
			array(
				'name' => 'vkm_updated',
				'desc' => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
			<br>Обновить записи, опубликованные ранее указанной даты (в формате <code>' . gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . '</code>).', 'vkmarket-for-woocommerce' ),
				'type' => 'text'
			),
			array(
				'name'  => 'product_cats',
				'label' => __( 'Категории товаров', 'vkmarket-for-woocommerce' ),
				'desc'  => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Выберите категорию, товары из которой нужно отправить или удалить из группы ВК. ', 'vkmarket-for-woocommerce' ),
				'type'  => 'select_product_checklist'
			),
			array(
				'name'    => 'product_cats_select_all',
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Выделить все категории или снять выделение со всех категорий.', 'vkmarket-for-woocommerce' ),
				'type'    => 'multicheck',
				'options' => array(
					'1' => 'Выделить все',
				)
			),
			array(
				'name'    => 'posts_per_page',
				'label'   => __( 'Количество', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Сколько объектов экспортировать, обновить или удалить из / в группы ВК.', 'vkmarket-for-woocommerce' ),
				'default' => '1',
				'type'    => 'text'
			),
			array(
				'name'    => 'order',
				'label'   => __( 'Порядок', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Порядок в котором будут отправлены товары (сортировка по дате создания товара на сате).', 'vkmarket-for-woocommerce' ),
				'type'    => 'radio',
				'default' => 'desc',
				'options' => array(
					'desc' => __( 'От новых к старым', 'vkmarket-for-woocommerce' ),
					'asc'  => __( 'От старых к новым', 'vkmarket-for-woocommerce' )
				)
			),
			array(
				'name'    => 'stock_status',
				'label'   => __( 'В наличии', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br>Отправлять ли в группу все товары или только те, которые есть в наличии.', 'vkmarket-for-woocommerce' ),
				'type'    => 'radio',
				'default' => 'all',
				'options' => array(
					'all'     => __( 'Все товары', 'vkmarket-for-woocommerce' ),
					'instock' => __( 'Только <em>Есть в наличии</em>', 'vkmarket-for-woocommerce' )
				)
			),
			array(
				'name' => 'export',
				'desc' => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br><br>', 'vkmarket-for-woocommerce' ) .
				          get_submit_button( __( 'Начать', 'vkmarket-for-woocommerce' ), 'primary', 'vkm_export_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          get_submit_button( __( 'Остановить', 'vkmarket-for-woocommerce' ), 'secondary', 'vkm_export_stop_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          '<span id="vkm_export_ajax[spinner]" style="float:none !important; margin: 0 5px !important;" class="spinner"></span>
				           <span id="vkm_export_msg"></span>',
				'type' => 'html'
			)

		),
		'vkm_bulk_reactions_section' => array(

			array(
				'name'    => 'action',
				'label'   => __( 'Действие', 'vkmarket-for-woocommerce' ),
				'desc'    => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
					<br>При обновлении подборок, плагин приведет взаимное расположение подборок в группе в соовтетствие со взаимным расположением соответствующих категорий на сайте.
					<br>При обновлении товаров в подборках, плагин добавит товары в ВК в соответствующие подборки.
					<br><br><strong>Внимание!</strong> При обновлении, эта страница сайта должна оставаться открытой.
					', 'vkmarket-for-woocommerce' ),
				'type'    => 'radio',
				'default' => 'reorder',
				'options' => array(
					'reorder' => __( 'Обновить подборки', 'vkmarket-for-woocommerce' ),
					'readd'   => __( 'Обновить товары в подборках', 'vkmarket-for-woocommerce' )
				)
			),
			array(
				'name' => 'reset_albums_order',
				'desc' => get_submit_button( __( 'Сбросить', 'vkmarket-for-woocommerce' ), 'secondary', 'vkm_reset_albums_order_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          '<span id="vkm_reset_albums_order_ajax[spinner]" style="float:none !important; margin: 0 5px !important;" class="spinner"></span>
				          <p class = "description"><small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
					<br>Сбросить порядок подборок в группе.</p>',
				'type' => 'html'
			),
			array(
				'name' => 'reset_errors',
				'desc' => get_submit_button( __( 'Сбросить ошибки', 'vkmarket-for-woocommerce' ), 'secondary', 'vkm_reset_errors_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          '<span id="vkm_reset_errors_ajax[spinner]" style="float:none !important; margin: 0 5px !important;" class="spinner"></span>
				          <p class = "description"><small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small>
					<br>Сбросить ошибки (будут удалены служебные сообщения об ошибках, при неудачной попытке отправить товар).</p>',
				'type' => 'html'
			),
			array(
				'name' => 'reaction',
				'desc' => __( '<small>Доступно в <a href = "javascript:void(0);" class = "get-vkm-pro">PRO версии</a>.</small><br><br>', 'vkmarket-for-woocommerce' ) .
				          get_submit_button( __( 'Начать', 'vkmarket-for-woocommerce' ), 'primary', 'vkm_reaction_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          get_submit_button( __( 'Остановить', 'vkmarket-for-woocommerce' ), 'secondary', 'vkm_reaction_stop_button', false, 'disabled' ) . '&nbsp;&nbsp;' . '&nbsp;&nbsp;' .
				          '<span id="vkm_reaction_ajax[spinner]" style="float:none !important; margin: 0 5px !important;" class="spinner"></span>
				           <span id="vkm_reaction_msg"></span>',
				'type' => 'html'
			)
		)

	);
	$fields = apply_filters( 'vkm_bulk_fields', $fields, $fields );

	//set sections and fields
	$vkm_bulk->set_option_name( 'vkm_bulk' );
	$vkm_bulk->set_sections( $tabs );
	$vkm_bulk->set_fields( $fields );

	//initialize them
	$vkm_bulk->admin_init();
}

add_action( 'admin_init', 'vkm_bulk_admin_init' );


// Register the plugin page
function vkm_bulk_admin_menu() {
	global $vkm_bulk_page;

	$vkm_bulk_page = add_submenu_page( 'vkmarket', 'Действия', 'Действия', 'activate_plugins', 'vkmarket-bulk', 'vkm_bulk_page' );
}

add_action( 'admin_menu', 'vkm_bulk_admin_menu', 50.01 );


// Display the plugin settings options page
function vkm_bulk_page() {
	global $vkm_bulk;

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Массовые действия с товарами', 'vkmarket-for-woocommerce' ); ?></h2>

		<div id="col-container">
			<div id="col-right" class="vkm">
				<div class="vkm-box">
					<?php vkm_admin_sticky(); ?>
				</div>
			</div>
			<div id="col-left" class="vkm">
				<?php
				settings_errors();
				$vkm_bulk->show_navigation();
				$vkm_bulk->show_forms();
				?>
			</div>
		</div>
	</div>
	<?php
}


function vkm_product_cat_add_form_fields() {
	$is_pro = vkm_is_pro();

	?>
	<div class="form-field">
		<label for="vkm_category"><?php _e( 'Категория в Товары ВК', 'vkmarket-for-woocommerce' ); ?></label>
		<select id="vkm_category" name="vkm_category" class="postform">
			<?php
			echo vkm_vk_categories_select_helper();
			?>
		</select>

		<p>
			<strong>Необязательно.</strong> Категория в Товары ВК, в которую будут отправляться товары из данной категории на сайте.
		</p>
	</div>

	<div class="form-field">
		<label for="vkm_album">
			<input type="checkbox" id="vkm_album" name="vkm_album" value="1" <?php if ( ! $is_pro ) { ?>disabled="disabled"<?php } ?>>
			Это <strong>подборка</strong> в Товары ВК
		</label>

		<p><?php if ( ! $is_pro ) { ?>
				<small>Доступно в <a href="javascript:void(0);" class="get-vkm-pro">PRO версии</a>.</small><br>
			<?php } ?>
			Если отмечено, в Товары ВК будет создана подборка с соответствующим "Названием" и "Миниатюрой".
			<br>Если было отмечено ранее, а теперь - нет, подборка из Товары ВК будет удалена.
		</p>
	</div>

	<div class="form-field">
		<label for="vkm_main_album">
			<input type="checkbox" id="vkm_main_album" name="vkm_main_album" value="1" <?php if ( ! $is_pro ) { ?>disabled="disabled"<?php } ?> >
			Это <strong>основная</strong> подборка в Товары ВК
		</label>

		<p><?php if ( ! $is_pro ) { ?>
				<small>Доступно в <a href="javascript:void(0);" class="get-vkm-pro">PRO версии</a>.</small><br>
			<?php } ?>
			Если отмечено, подборка станет основной и первые 3 товара из нее будут видны в блоке Товары над записями на главной странице группы.
			<br>Если было отмечено ранее, а теперь - нет, подборка сохранится но уже не будет основной.
		</p>
	</div>
	<?php
}

add_action( 'product_cat_add_form_fields', 'vkm_product_cat_add_form_fields' );


function vkm_product_cat_edit_form_fields( $term ) {
	$is_pro = vkm_is_pro();

	if ( function_exists( 'get_term_meta' ) ) {
		$vkm_category = get_term_meta( $term->term_id, 'vkm_category', true );
		$vk_item_id   = get_term_meta( $term->term_id, 'vk_item_id', true );
	} else {
		$vkm_category = get_woocommerce_term_meta( $term->term_id, 'vkm_category', true );
		$vk_item_id   = get_woocommerce_term_meta( $term->term_id, 'vk_item_id', true );
	}


	$vk_album_link = '';
	$vk_album      = false;
	$vk_main_album = 0;
	if ( ! empty( $vk_item_id ) ) {
		$vk_album      = true;
		$_vk_item_id   = explode( '_', $vk_item_id );
		$vk_album_url  = 'https://vk.com/market' . $_vk_item_id[0] . '?section=album_' . $_vk_item_id[1];
		$vk_album_link = '/ <small><a href="' . $vk_album_url . '" target="_blank">' . $vk_album_url . '</a></small>';

		if ( function_exists( 'get_term_meta' ) ) {
			$vk_main_album = get_term_meta( $term->term_id, 'vk_main_album', true );
		} else {
			$vk_main_album = get_woocommerce_term_meta( $term->term_id, 'vk_main_album', true );
		}
	}

	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label><?php _e( 'Категория в Товары ВК', 'vkmarket-for-woocommerce' ); ?></label>
		</th>
		<td>
			<select id="vkm_category" name="vkm_category" class="postform">
				<?php
				echo vkm_vk_categories_select_helper( $vkm_category );
				?>
			</select>

			<p>
				<strong>Необязательно.</strong> Категория в Товары ВК, в которую будут отправляться товары из данной категории на сайте.
			</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top"></th>
		<td>
			<label for="vkm_album">
				<input type="checkbox" id="vkm_album" name="vkm_album" value="1" <?php checked( $vk_album, true );
				if ( ! $is_pro ) { ?>disabled="disabled"<?php } ?>>
				Это <strong>подборка</strong> в Товары ВК <?php echo $vk_album_link; ?>
			</label>

			<p class="description"><?php if ( ! $is_pro ) { ?>
					<small>Доступно в <a href="javascript:void(0);" class="get-vkm-pro">PRO версии</a>.</small><br>
				<?php } ?>
				Если отмечено, в Товары ВК будет создана подборка с соответствующим "Названием" и "Миниатюрой".
				<br>Если было отмечено ранее, а теперь - нет, подборка из Товары ВК будет удалена.
			</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top"></th>
		<td>
			<label for="vkm_main_album">
				<input type="checkbox" id="vkm_main_album" name="vkm_main_album" value="1" <?php checked( $vk_main_album, 1 );
				if ( ! $is_pro ) { ?>disabled="disabled"<?php } ?> >
				Это <strong>основная</strong> подборка в Товары ВК
			</label>

			<p class="description"><?php if ( ! $is_pro ) { ?>
					<small>Доступно в <a href="javascript:void(0);" class="get-vkm-pro">PRO версии</a>.</small><br>
				<?php } ?>
				Если отмечено, подборка станет основной и первые 3 товара из нее будут видны в блоке Товары над записями на главной странице группы.
				<br>Если было отмечено ранее, а теперь - нет, подборка сохранится но уже не будет основной.
			</p>
		</td>
	</tr>

	<?php
}

add_action( 'product_cat_edit_form_fields', 'vkm_product_cat_edit_form_fields', 10 );


function vkm_created_term( $term_id, $tt_id = '', $taxonomy = '' ) {
	if ( isset( $_POST['vkm_category'] ) && 'product_cat' === $taxonomy ) {

		if ( function_exists( 'get_term_meta' ) ) {
			if ( ! update_term_meta( $term_id, 'vkm_category', esc_attr( $_POST['vkm_category'] ) ) ) {

				add_term_meta( $term_id, 'vkm_category', esc_attr( $_POST['vkm_category'] ), true );
			}
		} else {
			if ( ! update_woocommerce_term_meta( $term_id, 'vkm_category', esc_attr( $_POST['vkm_category'] ) ) ) {

				add_woocommerce_term_meta( $term_id, 'vkm_category', esc_attr( $_POST['vkm_category'] ), true );
			}
		}
	}
}

add_action( 'created_term', 'vkm_created_term', 10, 3 );
add_action( 'edit_term', 'vkm_created_term', 10, 3 );


function vkm_manage_edit_product_cat_columns( $columns ) {
	$columns['vkm_category'] = __( 'Товары ВК', 'vkmarket-for-woocommerce' );

	return $columns;
}

add_filter( 'manage_edit-product_cat_columns', 'vkm_manage_edit_product_cat_columns' );


function vkm_manage_product_cat_custom_column( $columns, $column, $id ) {
	global $vk_market_categories;

	if ( 'vkm_category' == $column ) {
		if ( function_exists( 'get_term_meta' ) ) {
			$cat_id = get_term_meta( $id, 'vkm_category', true );
		} else {
			$cat_id = get_woocommerce_term_meta( $id, 'vkm_category', true );
		}
		$name = '';

		if ( $cat_id && ! empty( $vk_market_categories[ $cat_id ] ) ) {

			$name = $vk_market_categories[ $cat_id ];
		}

		$columns .= $name;
	}

	return $columns;
}

add_filter( 'manage_product_cat_custom_column', 'vkm_manage_product_cat_custom_column', 10, 3 );


/* TODO
function vkm_post_row_actions( $actions, $post ) {

	if ( $post->post_type == 'product' ) {
		$vk_item_id = get_post_meta( $post->ID, 'vk_item_id', true );
		$out        = array();

		$actions['vkm_sync'] = '<a href = "javascript:void(0);" data-owner_id =' . $vk_item_id . '  class = "vkm_sync">TO VK Market</a>';
	}

	return $actions;
}

add_filter( 'post_row_actions', 'vkm_post_row_actions', 10, 2 );
*/

function vkm_product_posts_custom_column( $column, $post_id ) {
	global $post;

	$vk_item_id = get_post_meta( $post->ID, 'vk_item_id', true );

	switch ( $column ) {
		case "vk_market":
			$vk_item_url = '';
			if ( ! empty( $vk_item_id ) ) {
				$_vk_item_id = explode( '_', $vk_item_id );
				$vk_item_url = 'https://vk.com/market' . $_vk_item_id[0] . '?w=product' . $vk_item_id;
				printf( __( '<a href="%s" target="_blank">Есть</a>', 'vkmarket-for-woocommerce' ), $vk_item_url );
			} else {
				_e( 'Нет', 'vkmarket-for-woocommerce' );
			}

			break;
	}
}

add_action( "manage_product_posts_custom_column", 'vkm_product_posts_custom_column', 10, 2 );


function vkm_product_posts_columns( $columns ) {

	$columns['vk_market'] = __( 'Товары ВК', 'vkmarket-for-woocommerce' );

	return $columns;
}

add_filter( "manage_product_posts_columns", 'vkm_product_posts_columns' );


function vkm_vk_api_settings_admin_init() {
	global $vkm_vk_api_settings;

	$vkm_vk_api_settings = new WP_Settings_API_Class2;

	$options = get_option( 'vkm_vk_api_site' );

	$tabs = array(
		'vkm_vk_api_site' => array(
			'id'       => 'vkm_vk_api_site',
			'name'     => 'vkm_vk_api_site',
			'title'    => __( 'VK API', 'vkmarket-for-woocommerce' ),
			'desc'     => __( '', 'vkmarket-for-woocommerce' ),
			'sections' => array(
				'vkm_vk_api_site_section' => array(
					'id'    => 'vkm_vk_api_site_section',
					'name'  => 'vkm_vk_api_site_section',
					'title' => __( 'Настройки VK API', 'vkmarket-for-woocommerce' ),
					'desc'  => __( 'Создание приложения ВКонтакте и подключение его к сайту.', 'vkmarket-for-woocommerce' ),
				)
			)
		),
	);

	$url     = site_url();
	$url2    = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );
	$url_arr = explode( ".", basename( $url2 ) );
	$domain  = $url_arr[ count( $url_arr ) - 2 ] . "." . $url_arr[ count( $url_arr ) - 1 ];

	$site_app_id_desc = '<p>Чтобы получить доступ к <b>API ВКонтакте</b>, вам нужно <a href="http://vk.com/editapp?act=create" target="_blank">создать приложение</a> со следующими настройками:</p>
  <ol>
    <li><strong>Название:</strong> любое</li>
    <li><strong>Тип:</strong> Веб-сайт</li>
    <li><strong>Адрес сайта:</strong> ' . $url . '</li>
    <li><strong>Базовый домен:</strong> ' . $domain . '</li>
  </ol>
  <p>Если приложение с этими настройками у вас было создано ранее, вы можете найти его на <a href="https://vk.com/apps?act=manage" target="_blank">странице приложений</a> и, затем нажмите "Редактировать", чтобы открылись его параметры.</p>
  <p>В полях ниже вам нужно указать: <b>ID приложения</b> и его <b>Защищенный ключ</b>.</p>';

	$site_get_access_token_url = ( ! empty( $options['site_app_id'] ) ) ? vkm_vk_login_url() : 'javascript:void(0);';

	$site_access_token_desc = '<p>Чтобы получить <strong>Access Token</strong>:</p>
  <ol>
    <li>Пройдите по <a href="' . $site_get_access_token_url . '" id = "getaccesstokenurl">ссылке</a></li>
    <li>Подтвердите уровень доступа.</li>
  </ol>';


	$fields = array(
		'vkm_vk_api_site_section' => array(
			array(
				'name' => 'site_app_id_desc',
				'desc' => __( $site_app_id_desc, 'vkmarket-for-woocommerce' ),
				'type' => 'html',
			),
			array(
				'name'  => 'site_app_id',
				'label' => __( 'ID приложения', 'evc' ),
				'desc'  => __( 'ID вашего приложения VK.', 'vkmarket-for-woocommerce' ),
				'type'  => 'text'
			),
			array(
				'name'  => 'site_app_secret',
				'label' => __( 'Защищенный ключ', 'evc' ),
				'desc'  => __( 'Защищенный ключ вашего приложения VK.', 'vkmarket-for-woocommerce' ),
				'type'  => 'text'
			),
		),

	);

	if ( ! empty( $options['site_app_id'] ) && ! empty( $options['site_app_secret'] ) ) {

		array_push(
			$fields['vkm_vk_api_site_section'],
			array(
				'name' => 'site_access_token_desc',
				'desc' => __( $site_access_token_desc, 'vkmarket-for-woocommerce' ),
				'type' => 'html',
			),
			array(
				'name'     => 'site_access_token',
				'label'    => __( 'Access Token', 'evc' ),
				'desc'     => __( 'Значение будет подставлено автоматически, как только вы пройдете по указанной выше ссылке.', 'vkmarket-for-woocommerce' ),
				'type'     => 'text',
				'readonly' => true
			)
		);

	}

	//set sections and fields
	$vkm_vk_api_settings->set_option_name( 'vkm_vk_api' );
	$vkm_vk_api_settings->set_sections( $tabs );
	$vkm_vk_api_settings->set_fields( $fields );

	//initialize them
	$vkm_vk_api_settings->admin_init();
}

add_action( 'admin_init', 'vkm_vk_api_settings_admin_init' );


// Register the plugin page
function vkm_vk_api_admin_menu() {
	global $vkm_vk_api_settings_page;

	$vkm_vk_api_settings_page = add_submenu_page( 'vkmarket', 'Настройки API ВКонтакте', 'Настройки VK API', 'activate_plugins', 'vkmarket', 'vkm_vk_api_settings_page' );

	//add_action( 'admin_footer-'. $vkm_vk_api_settings_page, 'vkm_vk_api_settings_page_js' );
}

add_action( 'admin_menu', 'vkm_vk_api_admin_menu', 20 );


function vkm_vk_api_settings_page_js() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			$("#evc_vk_api_autopost\\[app_id\\]").change(function () {
				if ($(this).val().trim().length) {
					$(this).val($(this).val().trim());
					$('#getaccesstokenurl').attr({
						'href': 'http://oauth.vk.com/authorize?client_id=' + $(this).val().trim() + '&scope=wall,photos,video,market,offline&redirect_uri=http://api.vk.com/blank.html&display=page&response_type=token',
						'target': '_blank'
					});

				}
				else {
					$('#getaccesstokenurl').attr({'href': 'javscript:void(0);'});
				}

			});

		}); // jQuery End
	</script>
	<?php
}


// Display the plugin settings options page
function vkm_vk_api_settings_page() {
	global $vkm_vk_api_settings;

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Настройки API ВКонтакте', 'vkmarket-for-woocommerce' ); ?></h2>

		<div id="col-container">
			<div id="col-right" class="vkm">
				<div class="vkm-box">
					<?php vkm_admin_sticky(); ?>
				</div>
			</div>
			<div id="col-left" class="vkm">
				<?php

				settings_errors();
				$vkm_vk_api_settings->show_navigation();
				$vkm_vk_api_settings->show_forms();
				?>
			</div>
		</div>
	</div>
	<?php
}


function vkm_vk_login_url( $redirect_url = false, $echo = false ) {
	//$options = get_option('evc_options');
	$options = get_option( 'vkm_vk_api_site' );

	if ( ! $redirect_url ) {
		$redirect_url = remove_query_arg( array(
			'code',
			'redirect_uri',
			'settings-updated',
			'loggedout',
			'error',
			'access_denied',
			'error_reason',
			'error_description',
			'reauth'
		), $_SERVER['REQUEST_URI'] );
		//$redirect_url = get_bloginfo('wpurl') . $redirect_url;
		$redirect_url = site_url( $redirect_url );
	}

	$params = array(
		'client_id'     => trim( $options['site_app_id'] ),
		'redirect_uri'  => $redirect_url,
		'display'       => 'popup',
		'response_type' => 'code',
		'scope'         => 'market,photos,offline'
	);
	$query  = http_build_query( $params );

	$out = VKM_AUTHORIZATION_URL . '?' . $query;

	if ( $echo ) {
		echo $out;
	} else {
		return $out;
	}
}


function vkm_vk_autorization() {

	if ( ! empty( $_GET['page'] ) && 'vkmarket' == $_GET['page'] && false !== ( $token = vkm_get_token() ) ) {
		$options = get_option( 'vkm_vk_api_site' );

		if ( isset( $token['access_token'] ) && ! empty( $token['access_token'] ) ) {
			$options['site_access_token'] = $token['access_token'];
			update_option( 'vkm_vk_api_site', $options );
		}
		$redirect = remove_query_arg( array( 'code' ), $_SERVER['REQUEST_URI'] );
		//print__r($redirect);
		wp_redirect( site_url( $redirect ) );
		exit;
	}
}

add_action( 'admin_init', 'vkm_vk_autorization' );


function vkm_get_token() {
	$options = get_option( 'vkm_vk_api_site' );

	if ( ! empty( $_GET['page'] ) && 'vkmarket' == $_GET['page'] && isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'code' ), $_SERVER['REQUEST_URI'] );

		$params = array(
			'client_id'     => trim( $options['site_app_id'] ),
			'redirect_uri'  => site_url( $_SERVER['REQUEST_URI'] ),
			'client_secret' => $options['site_app_secret'],
			'code'          => $_GET['code']
		);
		$query  = http_build_query( $params );
		//print__r($query); //

		$data = wp_remote_get( VKM_TOKEN_URL . '?' . $query );
		//print__r($data); //
		//exit;
		if ( is_wp_error( $data ) ) {
			//print__r($data); //
			//exit;
			return false;
		}

		$resp = json_decode( $data['body'], true );
		if ( isset( $resp['error'] ) ) {
			return false;
		}

		return $resp;
	}

	return false;
}

function vkm_admin_sticky() {
	?>
	<div class="vkm-boxx">
		<p><?php _e( '<a href="http://ukraya.ru/vkmarket-for-woocommerce/documentation" target="_blank">Руководство</a> по работе с плагином и <a href="https://vk.me/wordpressvk" target="_blank">решение</a> проблем.', 'vkmarket-for-woocommerce' ); ?>
		</p>
	</div>
	<?php

	$is_pro = vkm_is_pro();

	if ( ! $is_pro ) {
		?>
		<h3>Товары ВКонтакте PRO для WooCommerce</h3>
		<p>PRO версия плагина поддерживает
			<strong>массовые операции с товарами</strong>: экспорт и удаление из группы ВК; все действия с
			<strong>подборками товаров ВК</strong>: создание, изменение, удаление, перемещение, поддержка псевдовложенных подборок и многое другое.
		</p>
		<p> <?php echo get_submit_button( 'Узнать больше', 'primary', 'get-vkm-pro', false ); ?></p>
		<?php
	}
}


function vkm_admin_footer() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			$(document).on('click', '#get-vkm-pro, .get-vkm-pro', function (e) {
				e.preventDefault();
				window.open(
					'http://ukraya.ru/vkmarket-pro-for-woocommerce',
					'_blank'
				);
			});

		}); // jQuery End
	</script>
	<?php
}

add_action( 'admin_footer', 'vkm_admin_footer' );


function vkm_edit_form_after_title( $post ) {
	$vk_item_id = get_post_meta( $post->ID, 'vk_item_id', true );

	if ( ! empty( $vk_item_id ) && $post->post_type == 'product' ) {
		$_vk_item_id = explode( '_', $vk_item_id );
		$vk_item_url = 'https://vk.com/market' . $_vk_item_id[0] . '?w=product' . $vk_item_id;
		?>
		<div id="vkm-product-link">
			<?php _e( '<strong>Товары ВК:</strong> ', 'vkmarket-for-woocommerce' );
			printf( __( '<a href="%s" target="_blank">%s</a>', 'vkmarket-for-woocommerce' ), $vk_item_url, $vk_item_url ); ?>
		</div>
		<?php
	}

}

add_action( 'edit_form_after_title', 'vkm_edit_form_after_title' );


// DELETE PRODUCT FROM VK GROUP

function vkm_delete_product_check_box() {
	global $post;

	if ( $post->post_type != 'product' ) {
		return;
	}

	$vk_item_id = get_post_meta( $post->ID, 'vk_item_id', true );
	if ( empty( $vk_item_id ) ) {
		return;
	}

	?>
	<div class="misc-pub-section">
		<p>
			<input type="checkbox" name="vkm_delete_product"/> <?php _e( '<span style="color: #a00;">Удалить</span> товар из ВК', 'vkmarket-for-woocommerce' ); ?>
		</p>

		<?php
		$vk_captcha = get_transient( 'vk_captcha' );

		if ( ! empty( $vk_captcha['vkm_vkapi_market_add'] ) &&
		     'post' == $vk_captcha['vkm_vkapi_market_add']['item_type'] &&
		     $post->ID == $vk_captcha['vkm_vkapi_market_add']['item_id']
		) {
			?>
			<p><span style="color: #FF0000; border-bottom: 1px solid #FF0000;">Не опубликовано!</span>
				<br/><img src="<?php echo $vk_captcha['vkm_vkapi_market_add']['captcha_img']; ?>" style="margin:10px 0 3px;"/>
				<br/><input type="hidden" name="captcha_sid" value="<?php echo $vk_captcha['vkm_vkapi_market_add']['captcha_sid']; ?>"><input type="text" value="" autocomplete="off" size="16" class="form-input-tip" name="captcha_key">
				<br/>Введите текст с картинки, чтобы опубликовать товар ВКонтакте.</p>
			<?php
		}
		?>
	</div>
	<?php
}

add_action( 'post_submitbox_misc_actions', 'vkm_delete_product_check_box' );