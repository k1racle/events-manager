<?php
/**
 * Plugin Name: Events Manager
 * Description: Кастомный тип записей "События" с датой, городом, местом, описанием, типом мероприятия, изображением, шорткодом, AJAX-подгрузкой, фильтрацией по дате (неделя/месяц) и выбором карт (Яндекс/Google). С настройками и кастомизацией цветов.
 * Version: 2.1
 * Author: Александр Мальцев
 * Text Domain: events-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function em_register_post_type() {
    $labels = array(
        'name'               => __( 'События', 'events-manager' ),
        'singular_name'      => __( 'Событие', 'events-manager' ),
        'menu_name'          => __( 'События', 'events-manager' ),
        'add_new'            => __( 'Добавить событие', 'events-manager' ),
        'add_new_item'       => __( 'Добавить новое событие', 'events-manager' ),
        'edit_item'          => __( 'Редактировать событие', 'events-manager' ),
        'new_item'           => __( 'Новое событие', 'events-manager' ),
        'view_item'          => __( 'Просмотр события', 'events-manager' ),
        'search_items'       => __( 'Поиск событий', 'events-manager' ),
        'not_found'          => __( 'Событий не найдено', 'events-manager' ),
        'not_found_in_trash' => __( 'В корзине событий не найдено', 'events-manager' ),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array( 'slug' => 'event' ),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => array( 'title', 'thumbnail' ),
        'show_in_rest'        => false,
    );

    register_post_type( 'event', $args );
}
add_action( 'init', 'em_register_post_type' );

function em_register_taxonomy() {
    $labels = array(
        'name'              => __( 'Типы мероприятий', 'events-manager' ),
        'singular_name'     => __( 'Тип мероприятия', 'events-manager' ),
        'search_items'      => __( 'Искать типы', 'events-manager' ),
        'all_items'         => __( 'Все типы', 'events-manager' ),
        'parent_item'       => null,
        'parent_item_colon' => null,
        'edit_item'         => __( 'Редактировать тип', 'events-manager' ),
        'update_item'       => __( 'Обновить тип', 'events-manager' ),
        'add_new_item'      => __( 'Добавить новый тип', 'events-manager' ),
        'new_item_name'     => __( 'Название типа', 'events-manager' ),
        'menu_name'         => __( 'Типы событий', 'events-manager' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'event-type' ),
        'show_in_rest'      => false,
    );

    register_taxonomy( 'event_type', 'event', $args );
}
add_action( 'init', 'em_register_taxonomy' );

function em_add_thumbnail_support() {
    if ( ! current_theme_supports( 'post-thumbnails' ) ) {
        add_theme_support( 'post-thumbnails', array( 'event' ) );
    } else {
        add_filter( 'theme_post_thumbnails_supports', function( $supports ) {
            if ( is_array( $supports ) ) {
                $supports[] = 'event';
            }
            return $supports;
        } );
    }
}
add_action( 'after_setup_theme', 'em_add_thumbnail_support' );

function em_add_meta_box() {
    add_meta_box(
        'event_details',
        __( 'Детали события', 'events-manager' ),
        'em_render_meta_box',
        'event',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'em_add_meta_box' );

function em_render_meta_box( $post ) {
    wp_nonce_field( 'em_save_event_data', 'em_event_nonce' );

    $event_date  = get_post_meta( $post->ID, 'event_date', true );
    $event_place = get_post_meta( $post->ID, 'event_place', true );
    $event_city  = get_post_meta( $post->ID, 'event_city', true );
    $event_description = get_post_meta( $post->ID, 'event_description', true );

    $terms = wp_get_post_terms( $post->ID, 'event_type' );
    $current_type = ! empty( $terms ) ? $terms[0]->term_id : '';

    $all_types = get_terms( array(
        'taxonomy'   => 'event_type',
        'hide_empty' => false,
    ) );
    ?>
    <p>
        <label for="event_date"><?php _e( 'Дата события (ГГГГ-ММ-ДД):', 'events-manager' ); ?></label><br>
        <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr( $event_date ); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="event_city"><?php _e( 'Город:', 'events-manager' ); ?></label><br>
        <input type="text" id="event_city" name="event_city" value="<?php echo esc_attr( $event_city ); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="event_place"><?php _e( 'Место проведения (адрес):', 'events-manager' ); ?></label><br>
        <input type="text" id="event_place" name="event_place" value="<?php echo esc_attr( $event_place ); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="event_description"><?php _e( 'Описание:', 'events-manager' ); ?></label><br>
        <textarea id="event_description" name="event_description" rows="4" style="width: 100%;"><?php echo esc_textarea( $event_description ); ?></textarea>
    </p>
    <p>
        <label for="event_type"><?php _e( 'Тип мероприятия:', 'events-manager' ); ?></label><br>
        <select name="event_type" id="event_type" style="width: 100%;">
            <option value="">— <?php _e( 'Выберите тип', 'events-manager' ); ?> —</option>
            <?php foreach ( $all_types as $type ) : ?>
                <option value="<?php echo $type->term_id; ?>" <?php selected( $current_type, $type->term_id ); ?>>
                    <?php echo esc_html( $type->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function em_save_event_data( $post_id, $post ) {
    if ( ! isset( $_POST['em_event_nonce'] ) || ! wp_verify_nonce( $_POST['em_event_nonce'], 'em_save_event_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['event_date'] ) ) {
        update_post_meta( $post_id, 'event_date', sanitize_text_field( $_POST['event_date'] ) );
    }
    if ( isset( $_POST['event_city'] ) ) {
        update_post_meta( $post_id, 'event_city', sanitize_text_field( $_POST['event_city'] ) );
    }
    if ( isset( $_POST['event_place'] ) ) {
        update_post_meta( $post_id, 'event_place', sanitize_text_field( $_POST['event_place'] ) );
    }
    if ( isset( $_POST['event_description'] ) ) {
        update_post_meta( $post_id, 'event_description', wp_kses_post( $_POST['event_description'] ) );
    }

    if ( isset( $_POST['event_type'] ) ) {
        $term_id = intval( $_POST['event_type'] );
        if ( $term_id > 0 ) {
            wp_set_object_terms( $post_id, $term_id, 'event_type' );
        } else {
            wp_delete_object_term_relationships( $post_id, 'event_type' );
        }
    }
}
add_action( 'save_post', 'em_save_event_data', 10, 2 );

function em_add_admin_menu() {
    add_options_page(
        __( 'Events Manager Settings', 'events-manager' ),
        __( 'Events Manager', 'events-manager' ),
        'manage_options',
        'events-manager',
        'em_render_settings_page'
    );
}
add_action( 'admin_menu', 'em_add_admin_menu' );

function em_register_settings() {
    register_setting( 'em_settings_group', 'em_map_provider', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'yandex',
    ) );
    register_setting( 'em_settings_group', 'em_primary_color', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default'           => '#0066cc',
    ) );
}
add_action( 'admin_init', 'em_register_settings' );

function em_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Events Manager Settings', 'events-manager' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'em_settings_group' ); ?>
            <?php do_settings_sections( 'em_settings_group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Провайдер карт по умолчанию', 'events-manager' ); ?></th>
                    <td>
                        <select name="em_map_provider">
                            <option value="yandex" <?php selected( get_option( 'em_map_provider', 'yandex' ), 'yandex' ); ?>><?php _e( 'Яндекс.Карты', 'events-manager' ); ?></option>
                            <option value="google" <?php selected( get_option( 'em_map_provider', 'yandex' ), 'google' ); ?>><?php _e( 'Google Карты', 'events-manager' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Выберите карты, которые будут использоваться, если не указано иное в шорткоде.', 'events-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Основной цвет', 'events-manager' ); ?></th>
                    <td>
                        <input type="color" name="em_primary_color" value="<?php echo esc_attr( get_option( 'em_primary_color', '#0066cc' ) ); ?>" />
                        <p class="description"><?php _e( 'Цвет кнопок и акцентных элементов.', 'events-manager' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>
        <h2><?php _e( 'Информация о шорткодах', 'events-manager' ); ?></h2>
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <p><strong>[events_list]</strong> – выводит список событий.</p>
            <p><strong>Атрибуты:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><code>count</code> – количество событий на страницу (по умолчанию 3).</li>
                <li><code>show_map</code> – показывать карту в модальном окне (1 или 0, по умолчанию 1).</li>
                <li><code>show_image</code> – показывать изображение (1 или 0, по умолчанию 1).</li>
                <li><code>show_filters</code> – показывать фильтры (1 или 0, по умолчанию 1).</li>
                <li><code>map_provider</code> – провайдер карт: <code>yandex</code> или <code>google</code> (по умолчанию берется из настроек).</li>
            </ul>
            <p><strong>Пример:</strong> <code>[events_list count="5" map_provider="google"]</code></p>
        </div>
    </div>
    <?php
}

function em_events_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'count'        => 3,
        'show_map'     => 1,
        'show_image'   => 1,
        'show_filters' => 1,
        'map_provider' => get_option( 'em_map_provider', 'yandex' ),
    ), $atts );

    ob_start();

    if ( $atts['show_filters'] ) {
        $cities = em_get_all_cities();
        ?>
        <div class="em-filters">
            <select name="em_date_filter" id="em_date_filter">
                <option value="future"><?php _e( 'Будущие события', 'events-manager' ); ?></option>
                <option value="past"><?php _e( 'Прошедшие события', 'events-manager' ); ?></option>
                <option value="this_week"><?php _e( 'На этой неделе', 'events-manager' ); ?></option>
                <option value="next_week"><?php _e( 'На следующей неделе', 'events-manager' ); ?></option>
                <option value="this_month"><?php _e( 'В этом месяце', 'events-manager' ); ?></option>
                <option value="next_month"><?php _e( 'В следующем месяце', 'events-manager' ); ?></option>
                <option value="all"><?php _e( 'Все события', 'events-manager' ); ?></option>
            </select>

            <select name="em_city_filter" id="em_city_filter">
                <option value=""><?php _e( 'Все города', 'events-manager' ); ?></option>
                <?php foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city ); ?>"><?php echo esc_html( $city ); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="em_type_filter" id="em_type_filter">
                <option value=""><?php _e( 'Все типы', 'events-manager' ); ?></option>
                <?php
                $types = get_terms( array(
                    'taxonomy'   => 'event_type',
                    'hide_empty' => false,
                ) );
                foreach ( $types as $type ) {
                    echo '<option value="' . $type->term_id . '">' . esc_html( $type->name ) . '</option>';
                }
                ?>
            </select>

            <button id="em_apply_filters" class="em-apply-filters"><?php _e( 'Применить фильтры', 'events-manager' ); ?></button>
        </div>
        <?php
    }

    echo '<div class="em-events-grid" 
        data-count="' . esc_attr( $atts['count'] ) . '" 
        data-showmap="' . esc_attr( $atts['show_map'] ) . '" 
        data-showimage="' . esc_attr( $atts['show_image'] ) . '"
        data-mapprovider="' . esc_attr( $atts['map_provider'] ) . '">';
    echo '<div class="em-loading">' . __( 'Загрузка событий...', 'events-manager' ) . '</div>';
    echo '</div>';

    return ob_get_clean();
}
add_shortcode( 'events_list', 'em_events_list_shortcode' );

function em_get_all_cities() {
    global $wpdb;
    $cities = $wpdb->get_col( "
        SELECT DISTINCT meta_value 
        FROM $wpdb->postmeta 
        WHERE meta_key = 'event_city' 
        AND meta_value != '' 
        ORDER BY meta_value ASC
    " );
    return $cities;
}

function em_load_events_ajax() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'em_load_more_nonce' ) ) {
        wp_die( 'Ошибка безопасности' );
    }

    $page         = intval( $_POST['page'] );
    $count        = intval( $_POST['count'] );
    $show_map     = intval( $_POST['showmap'] );
    $show_image   = intval( $_POST['showimage'] );
    $map_provider = sanitize_text_field( $_POST['mapprovider'] );
    $date_filter  = sanitize_text_field( $_POST['date_filter'] );
    $city_filter  = sanitize_text_field( $_POST['city_filter'] );
    $type_filter  = intval( $_POST['type_filter'] );

    $today = current_time( 'Y-m-d' );

    $args = array(
        'post_type'      => 'event',
        'posts_per_page' => $count,
        'paged'          => $page,
        'meta_key'       => 'event_date',
        'meta_type'      => 'DATE',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    );

    $meta_query = array();

    $start_date = '';
    $end_date = '';
    if ( in_array( $date_filter, array( 'this_week', 'next_week', 'this_month', 'next_month' ) ) ) {
        $now = current_time( 'timestamp' );
        switch ( $date_filter ) {
            case 'this_week':
                $start_date = date( 'Y-m-d', strtotime( 'monday this week', $now ) );
                $end_date   = date( 'Y-m-d', strtotime( 'sunday this week', $now ) );
                break;
            case 'next_week':
                $start_date = date( 'Y-m-d', strtotime( 'monday next week', $now ) );
                $end_date   = date( 'Y-m-d', strtotime( 'sunday next week', $now ) );
                break;
            case 'this_month':
                $start_date = date( 'Y-m-d', strtotime( 'first day of this month', $now ) );
                $end_date   = date( 'Y-m-d', strtotime( 'last day of this month', $now ) );
                break;
            case 'next_month':
                $start_date = date( 'Y-m-d', strtotime( 'first day of next month', $now ) );
                $end_date   = date( 'Y-m-d', strtotime( 'last day of next month', $now ) );
                break;
        }
    }

    if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
        $meta_query[] = array(
            'key'     => 'event_date',
            'value'   => array( $start_date, $end_date ),
            'compare' => 'BETWEEN',
            'type'    => 'DATE',
        );
    } elseif ( $date_filter === 'future' ) {
        $meta_query[] = array(
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        );
    } elseif ( $date_filter === 'past' ) {
        $meta_query[] = array(
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '<',
            'type'    => 'DATE',
        );
    }

    if ( ! empty( $city_filter ) ) {
        $meta_query[] = array(
            'key'     => 'event_city',
            'value'   => $city_filter,
            'compare' => '=',
        );
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    if ( $type_filter > 0 ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'event_type',
                'field'    => 'term_id',
                'terms'    => $type_filter,
            ),
        );
    }

    $query = new WP_Query( $args );

    ob_start();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $event_date  = get_post_meta( get_the_ID(), 'event_date', true );
            $event_place = get_post_meta( get_the_ID(), 'event_place', true );
            $event_city  = get_post_meta( get_the_ID(), 'event_city', true );
            $event_description = get_post_meta( get_the_ID(), 'event_description', true );
            $formatted_date = date_i18n( 'd.m.Y', strtotime( $event_date ) );
            $image = ( $show_image && has_post_thumbnail() ) ? get_the_post_thumbnail( get_the_ID(), 'medium' ) : '';

            $type_terms = wp_get_post_terms( get_the_ID(), 'event_type' );
            $type_name = ! empty( $type_terms ) ? $type_terms[0]->name : '';
            ?>
            <div class="em-event-card" data-event-id="<?php echo get_the_ID(); ?>">
                <?php if ( $image ) : ?>
                    <div class="em-event-image">
                        <?php echo $image; ?>
                    </div>
                <?php endif; ?>
                <div class="em-event-content">
                    <h3><?php the_title(); ?></h3>
                    <?php if ( $type_name ) : ?>
                        <p class="em-event-type"><strong><?php _e( 'Тип:', 'events-manager' ); ?></strong> <?php echo esc_html( $type_name ); ?></p>
                    <?php endif; ?>
                    <p class="em-event-date"><strong><?php _e( 'Дата:', 'events-manager' ); ?></strong> <?php echo $formatted_date; ?></p>
                    <p class="em-event-city"><strong><?php _e( 'Город:', 'events-manager' ); ?></strong> <?php echo esc_html( $event_city ); ?></p>
                    <p class="em-event-place"><strong><?php _e( 'Место:', 'events-manager' ); ?></strong> <?php echo esc_html( $event_place ); ?></p>
                </div>
                <div class="em-event-description-hidden" id="em-desc-<?php echo get_the_ID(); ?>" style="display:none;">
                    <h2><?php the_title(); ?></h2>
                    <?php if ( $type_name ) : ?>
                        <p><strong><?php _e( 'Тип:', 'events-manager' ); ?></strong> <?php echo esc_html( $type_name ); ?></p>
                    <?php endif; ?>
                    <p><strong><?php _e( 'Дата:', 'events-manager' ); ?></strong> <?php echo $formatted_date; ?></p>
                    <p><strong><?php _e( 'Город:', 'events-manager' ); ?></strong> <?php echo esc_html( $event_city ); ?></p>
                    <p><strong><?php _e( 'Место:', 'events-manager' ); ?></strong> <?php echo esc_html( $event_place ); ?></p>
                    <hr>
                    <?php echo wp_kses_post( $event_description ); ?>
                    <?php if ( $show_map && ! empty( $event_place ) ) : ?>
                        <div class="em-event-map">
                            <?php
                            if ( $map_provider === 'google' ) {
                                $map_src = 'https://maps.google.com/maps?q=' . urlencode( $event_place ) . '&output=embed';
                            } else {
                                $map_src = 'https://yandex.ru/map-widget/v1/?text=' . urlencode( $event_place );
                            }
                            ?>
                            <iframe width="100%" height="200" frameborder="0" style="border:0" src="<?php echo esc_url( $map_src ); ?>" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        $has_more = ( $query->max_num_pages > $page );
    } else {
        $has_more = false;
        echo '<p class="em-no-events">' . __( 'Событий не найдено.', 'events-manager' ) . '</p>';
    }
    wp_reset_postdata();

    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'      => $html,
        'has_more'  => $has_more,
        'next_page' => $page + 1,
        'max_pages' => $query->max_num_pages,
    ) );
}
add_action( 'wp_ajax_load_more_events', 'em_load_events_ajax' );
add_action( 'wp_ajax_nopriv_load_more_events', 'em_load_events_ajax' );

function em_enqueue_scripts() {
    wp_enqueue_style( 'events-manager', plugin_dir_url( __FILE__ ) . 'assets/events-manager.css', array(), '2.1' );

    $primary_color = get_option( 'em_primary_color', '#0066cc' );
    $primary_dark  = em_adjust_brightness( $primary_color, -20 );

    $custom_css = "
        .em-filters button {
            background: linear-gradient(135deg, {$primary_color}, {$primary_dark});
            box-shadow: 0 6px 14px " . em_hex_to_rgba( $primary_color, 0.3 ) . ";
        }
        .em-filters button:hover {
            background: linear-gradient(135deg, {$primary_dark}, " . em_adjust_brightness( $primary_color, -30 ) . ");
            box-shadow: 0 10px 20px " . em_hex_to_rgba( $primary_color, 0.4 ) . ";
        }
        .em-load-more {
            background: linear-gradient(135deg, {$primary_color}, {$primary_dark});
            box-shadow: 0 8px 18px " . em_hex_to_rgba( $primary_color, 0.25 ) . ";
        }
        .em-load-more:hover {
            background: linear-gradient(135deg, {$primary_dark}, " . em_adjust_brightness( $primary_color, -30 ) . ");
            box-shadow: 0 12px 24px " . em_hex_to_rgba( $primary_color, 0.35 ) . ";
        }
        .em-filters select:hover {
            border-color: {$primary_color};
        }
        .em-event-card {
            cursor: pointer;
        }
    ";
    wp_add_inline_style( 'events-manager', $custom_css );

    wp_enqueue_script( 'events-manager', plugin_dir_url( __FILE__ ) . 'assets/events-manager.js', array(), '2.1', true );

    wp_localize_script( 'events-manager', 'em_ajax', array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'em_load_more_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'em_enqueue_scripts' );

function em_adjust_brightness( $hex, $steps ) {
    $hex = str_replace( '#', '', $hex );
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );

    $r = max( 0, min( 255, $r + $steps ) );
    $g = max( 0, min( 255, $g + $steps ) );
    $b = max( 0, min( 255, $b + $steps ) );

    return '#' . sprintf( "%02x%02x%02x", $r, $g, $b );
}

function em_hex_to_rgba( $hex, $alpha = 1 ) {
    $hex = str_replace( '#', '', $hex );
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    return "rgba($r, $g, $b, $alpha)";
}