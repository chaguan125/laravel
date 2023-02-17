<?php

/**
 * A helper file for Dcat Admin, to provide autocomplete information to your IDE
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author jqh <841324345@qq.com>
 */
namespace Dcat\Admin {
    use Illuminate\Support\Collection;

    /**
     * @property Grid\Column|Collection tenant_id
     * @property Grid\Column|Collection id
     * @property Grid\Column|Collection name
     * @property Grid\Column|Collection type
     * @property Grid\Column|Collection version
     * @property Grid\Column|Collection detail
     * @property Grid\Column|Collection created_at
     * @property Grid\Column|Collection updated_at
     * @property Grid\Column|Collection is_enabled
     * @property Grid\Column|Collection parent_id
     * @property Grid\Column|Collection order
     * @property Grid\Column|Collection icon
     * @property Grid\Column|Collection uri
     * @property Grid\Column|Collection extension
     * @property Grid\Column|Collection permission_id
     * @property Grid\Column|Collection menu_id
     * @property Grid\Column|Collection slug
     * @property Grid\Column|Collection http_method
     * @property Grid\Column|Collection http_path
     * @property Grid\Column|Collection role_id
     * @property Grid\Column|Collection user_id
     * @property Grid\Column|Collection value
     * @property Grid\Column|Collection username
     * @property Grid\Column|Collection password
     * @property Grid\Column|Collection avatar
     * @property Grid\Column|Collection remember_token
     * @property Grid\Column|Collection brand_name
     * @property Grid\Column|Collection goods_cover_image_id
     * @property Grid\Column|Collection goods_name
     * @property Grid\Column|Collection goods_info
     * @property Grid\Column|Collection goods_id
     * @property Grid\Column|Collection floor_amount
     * @property Grid\Column|Collection amount
     * @property Grid\Column|Collection nofity_uri
     * @property Grid\Column|Collection duration
     * @property Grid\Column|Collection publish_start_time
     * @property Grid\Column|Collection publish_end_time
     * @property Grid\Column|Collection out_biz_no
     * @property Grid\Column|Collection voucher_quantity
     * @property Grid\Column|Collection voucher_description
     * @property Grid\Column|Collection voucher_discount_limit
     * @property Grid\Column|Collection template_id
     * @property Grid\Column|Collection user_give_max
     * @property Grid\Column|Collection pre_day_give_max
     * @property Grid\Column|Collection renew_user_id
     * @property Grid\Column|Collection uuid
     * @property Grid\Column|Collection connection
     * @property Grid\Column|Collection queue
     * @property Grid\Column|Collection payload
     * @property Grid\Column|Collection exception
     * @property Grid\Column|Collection failed_at
     * @property Grid\Column|Collection attempts
     * @property Grid\Column|Collection reserved_at
     * @property Grid\Column|Collection available_at
     * @property Grid\Column|Collection wish
     * @property Grid\Column|Collection img
     * @property Grid\Column|Collection player_id
     * @property Grid\Column|Collection number_votes
     * @property Grid\Column|Collection rank
     * @property Grid\Column|Collection state
     * @property Grid\Column|Collection examined_time
     * @property Grid\Column|Collection examined_user
     * @property Grid\Column|Collection refute_reason
     * @property Grid\Column|Collection release_time
     * @property Grid\Column|Collection deleted_at
     * @property Grid\Column|Collection serial_number
     * @property Grid\Column|Collection wx_openid
     * @property Grid\Column|Collection wx_name
     * @property Grid\Column|Collection wx_avatar
     * @property Grid\Column|Collection phone
     * @property Grid\Column|Collection province
     * @property Grid\Column|Collection city
     * @property Grid\Column|Collection area
     * @property Grid\Column|Collection address
     * @property Grid\Column|Collection level
     * @property Grid\Column|Collection quantity
     * @property Grid\Column|Collection describe
     * @property Grid\Column|Collection category
     * @property Grid\Column|Collection email
     * @property Grid\Column|Collection token
     * @property Grid\Column|Collection tokenable_type
     * @property Grid\Column|Collection tokenable_id
     * @property Grid\Column|Collection abilities
     * @property Grid\Column|Collection last_used_at
     * @property Grid\Column|Collection email_verified_at
     *
     * @method Grid\Column|Collection tenant_id(string $label = null)
     * @method Grid\Column|Collection id(string $label = null)
     * @method Grid\Column|Collection name(string $label = null)
     * @method Grid\Column|Collection type(string $label = null)
     * @method Grid\Column|Collection version(string $label = null)
     * @method Grid\Column|Collection detail(string $label = null)
     * @method Grid\Column|Collection created_at(string $label = null)
     * @method Grid\Column|Collection updated_at(string $label = null)
     * @method Grid\Column|Collection is_enabled(string $label = null)
     * @method Grid\Column|Collection parent_id(string $label = null)
     * @method Grid\Column|Collection order(string $label = null)
     * @method Grid\Column|Collection icon(string $label = null)
     * @method Grid\Column|Collection uri(string $label = null)
     * @method Grid\Column|Collection extension(string $label = null)
     * @method Grid\Column|Collection permission_id(string $label = null)
     * @method Grid\Column|Collection menu_id(string $label = null)
     * @method Grid\Column|Collection slug(string $label = null)
     * @method Grid\Column|Collection http_method(string $label = null)
     * @method Grid\Column|Collection http_path(string $label = null)
     * @method Grid\Column|Collection role_id(string $label = null)
     * @method Grid\Column|Collection user_id(string $label = null)
     * @method Grid\Column|Collection value(string $label = null)
     * @method Grid\Column|Collection username(string $label = null)
     * @method Grid\Column|Collection password(string $label = null)
     * @method Grid\Column|Collection avatar(string $label = null)
     * @method Grid\Column|Collection remember_token(string $label = null)
     * @method Grid\Column|Collection brand_name(string $label = null)
     * @method Grid\Column|Collection goods_cover_image_id(string $label = null)
     * @method Grid\Column|Collection goods_name(string $label = null)
     * @method Grid\Column|Collection goods_info(string $label = null)
     * @method Grid\Column|Collection goods_id(string $label = null)
     * @method Grid\Column|Collection floor_amount(string $label = null)
     * @method Grid\Column|Collection amount(string $label = null)
     * @method Grid\Column|Collection nofity_uri(string $label = null)
     * @method Grid\Column|Collection duration(string $label = null)
     * @method Grid\Column|Collection publish_start_time(string $label = null)
     * @method Grid\Column|Collection publish_end_time(string $label = null)
     * @method Grid\Column|Collection out_biz_no(string $label = null)
     * @method Grid\Column|Collection voucher_quantity(string $label = null)
     * @method Grid\Column|Collection voucher_description(string $label = null)
     * @method Grid\Column|Collection voucher_discount_limit(string $label = null)
     * @method Grid\Column|Collection template_id(string $label = null)
     * @method Grid\Column|Collection user_give_max(string $label = null)
     * @method Grid\Column|Collection pre_day_give_max(string $label = null)
     * @method Grid\Column|Collection renew_user_id(string $label = null)
     * @method Grid\Column|Collection uuid(string $label = null)
     * @method Grid\Column|Collection connection(string $label = null)
     * @method Grid\Column|Collection queue(string $label = null)
     * @method Grid\Column|Collection payload(string $label = null)
     * @method Grid\Column|Collection exception(string $label = null)
     * @method Grid\Column|Collection failed_at(string $label = null)
     * @method Grid\Column|Collection attempts(string $label = null)
     * @method Grid\Column|Collection reserved_at(string $label = null)
     * @method Grid\Column|Collection available_at(string $label = null)
     * @method Grid\Column|Collection wish(string $label = null)
     * @method Grid\Column|Collection img(string $label = null)
     * @method Grid\Column|Collection player_id(string $label = null)
     * @method Grid\Column|Collection number_votes(string $label = null)
     * @method Grid\Column|Collection rank(string $label = null)
     * @method Grid\Column|Collection state(string $label = null)
     * @method Grid\Column|Collection examined_time(string $label = null)
     * @method Grid\Column|Collection examined_user(string $label = null)
     * @method Grid\Column|Collection refute_reason(string $label = null)
     * @method Grid\Column|Collection release_time(string $label = null)
     * @method Grid\Column|Collection deleted_at(string $label = null)
     * @method Grid\Column|Collection serial_number(string $label = null)
     * @method Grid\Column|Collection wx_openid(string $label = null)
     * @method Grid\Column|Collection wx_name(string $label = null)
     * @method Grid\Column|Collection wx_avatar(string $label = null)
     * @method Grid\Column|Collection phone(string $label = null)
     * @method Grid\Column|Collection province(string $label = null)
     * @method Grid\Column|Collection city(string $label = null)
     * @method Grid\Column|Collection area(string $label = null)
     * @method Grid\Column|Collection address(string $label = null)
     * @method Grid\Column|Collection level(string $label = null)
     * @method Grid\Column|Collection quantity(string $label = null)
     * @method Grid\Column|Collection describe(string $label = null)
     * @method Grid\Column|Collection category(string $label = null)
     * @method Grid\Column|Collection email(string $label = null)
     * @method Grid\Column|Collection token(string $label = null)
     * @method Grid\Column|Collection tokenable_type(string $label = null)
     * @method Grid\Column|Collection tokenable_id(string $label = null)
     * @method Grid\Column|Collection abilities(string $label = null)
     * @method Grid\Column|Collection last_used_at(string $label = null)
     * @method Grid\Column|Collection email_verified_at(string $label = null)
     */
    class Grid {}

    class MiniGrid extends Grid {}

    /**
     * @property Show\Field|Collection tenant_id
     * @property Show\Field|Collection id
     * @property Show\Field|Collection name
     * @property Show\Field|Collection type
     * @property Show\Field|Collection version
     * @property Show\Field|Collection detail
     * @property Show\Field|Collection created_at
     * @property Show\Field|Collection updated_at
     * @property Show\Field|Collection is_enabled
     * @property Show\Field|Collection parent_id
     * @property Show\Field|Collection order
     * @property Show\Field|Collection icon
     * @property Show\Field|Collection uri
     * @property Show\Field|Collection extension
     * @property Show\Field|Collection permission_id
     * @property Show\Field|Collection menu_id
     * @property Show\Field|Collection slug
     * @property Show\Field|Collection http_method
     * @property Show\Field|Collection http_path
     * @property Show\Field|Collection role_id
     * @property Show\Field|Collection user_id
     * @property Show\Field|Collection value
     * @property Show\Field|Collection username
     * @property Show\Field|Collection password
     * @property Show\Field|Collection avatar
     * @property Show\Field|Collection remember_token
     * @property Show\Field|Collection brand_name
     * @property Show\Field|Collection goods_cover_image_id
     * @property Show\Field|Collection goods_name
     * @property Show\Field|Collection goods_info
     * @property Show\Field|Collection goods_id
     * @property Show\Field|Collection floor_amount
     * @property Show\Field|Collection amount
     * @property Show\Field|Collection nofity_uri
     * @property Show\Field|Collection duration
     * @property Show\Field|Collection publish_start_time
     * @property Show\Field|Collection publish_end_time
     * @property Show\Field|Collection out_biz_no
     * @property Show\Field|Collection voucher_quantity
     * @property Show\Field|Collection voucher_description
     * @property Show\Field|Collection voucher_discount_limit
     * @property Show\Field|Collection template_id
     * @property Show\Field|Collection user_give_max
     * @property Show\Field|Collection pre_day_give_max
     * @property Show\Field|Collection renew_user_id
     * @property Show\Field|Collection uuid
     * @property Show\Field|Collection connection
     * @property Show\Field|Collection queue
     * @property Show\Field|Collection payload
     * @property Show\Field|Collection exception
     * @property Show\Field|Collection failed_at
     * @property Show\Field|Collection attempts
     * @property Show\Field|Collection reserved_at
     * @property Show\Field|Collection available_at
     * @property Show\Field|Collection wish
     * @property Show\Field|Collection img
     * @property Show\Field|Collection player_id
     * @property Show\Field|Collection number_votes
     * @property Show\Field|Collection rank
     * @property Show\Field|Collection state
     * @property Show\Field|Collection examined_time
     * @property Show\Field|Collection examined_user
     * @property Show\Field|Collection refute_reason
     * @property Show\Field|Collection release_time
     * @property Show\Field|Collection deleted_at
     * @property Show\Field|Collection serial_number
     * @property Show\Field|Collection wx_openid
     * @property Show\Field|Collection wx_name
     * @property Show\Field|Collection wx_avatar
     * @property Show\Field|Collection phone
     * @property Show\Field|Collection province
     * @property Show\Field|Collection city
     * @property Show\Field|Collection area
     * @property Show\Field|Collection address
     * @property Show\Field|Collection level
     * @property Show\Field|Collection quantity
     * @property Show\Field|Collection describe
     * @property Show\Field|Collection category
     * @property Show\Field|Collection email
     * @property Show\Field|Collection token
     * @property Show\Field|Collection tokenable_type
     * @property Show\Field|Collection tokenable_id
     * @property Show\Field|Collection abilities
     * @property Show\Field|Collection last_used_at
     * @property Show\Field|Collection email_verified_at
     *
     * @method Show\Field|Collection tenant_id(string $label = null)
     * @method Show\Field|Collection id(string $label = null)
     * @method Show\Field|Collection name(string $label = null)
     * @method Show\Field|Collection type(string $label = null)
     * @method Show\Field|Collection version(string $label = null)
     * @method Show\Field|Collection detail(string $label = null)
     * @method Show\Field|Collection created_at(string $label = null)
     * @method Show\Field|Collection updated_at(string $label = null)
     * @method Show\Field|Collection is_enabled(string $label = null)
     * @method Show\Field|Collection parent_id(string $label = null)
     * @method Show\Field|Collection order(string $label = null)
     * @method Show\Field|Collection icon(string $label = null)
     * @method Show\Field|Collection uri(string $label = null)
     * @method Show\Field|Collection extension(string $label = null)
     * @method Show\Field|Collection permission_id(string $label = null)
     * @method Show\Field|Collection menu_id(string $label = null)
     * @method Show\Field|Collection slug(string $label = null)
     * @method Show\Field|Collection http_method(string $label = null)
     * @method Show\Field|Collection http_path(string $label = null)
     * @method Show\Field|Collection role_id(string $label = null)
     * @method Show\Field|Collection user_id(string $label = null)
     * @method Show\Field|Collection value(string $label = null)
     * @method Show\Field|Collection username(string $label = null)
     * @method Show\Field|Collection password(string $label = null)
     * @method Show\Field|Collection avatar(string $label = null)
     * @method Show\Field|Collection remember_token(string $label = null)
     * @method Show\Field|Collection brand_name(string $label = null)
     * @method Show\Field|Collection goods_cover_image_id(string $label = null)
     * @method Show\Field|Collection goods_name(string $label = null)
     * @method Show\Field|Collection goods_info(string $label = null)
     * @method Show\Field|Collection goods_id(string $label = null)
     * @method Show\Field|Collection floor_amount(string $label = null)
     * @method Show\Field|Collection amount(string $label = null)
     * @method Show\Field|Collection nofity_uri(string $label = null)
     * @method Show\Field|Collection duration(string $label = null)
     * @method Show\Field|Collection publish_start_time(string $label = null)
     * @method Show\Field|Collection publish_end_time(string $label = null)
     * @method Show\Field|Collection out_biz_no(string $label = null)
     * @method Show\Field|Collection voucher_quantity(string $label = null)
     * @method Show\Field|Collection voucher_description(string $label = null)
     * @method Show\Field|Collection voucher_discount_limit(string $label = null)
     * @method Show\Field|Collection template_id(string $label = null)
     * @method Show\Field|Collection user_give_max(string $label = null)
     * @method Show\Field|Collection pre_day_give_max(string $label = null)
     * @method Show\Field|Collection renew_user_id(string $label = null)
     * @method Show\Field|Collection uuid(string $label = null)
     * @method Show\Field|Collection connection(string $label = null)
     * @method Show\Field|Collection queue(string $label = null)
     * @method Show\Field|Collection payload(string $label = null)
     * @method Show\Field|Collection exception(string $label = null)
     * @method Show\Field|Collection failed_at(string $label = null)
     * @method Show\Field|Collection attempts(string $label = null)
     * @method Show\Field|Collection reserved_at(string $label = null)
     * @method Show\Field|Collection available_at(string $label = null)
     * @method Show\Field|Collection wish(string $label = null)
     * @method Show\Field|Collection img(string $label = null)
     * @method Show\Field|Collection player_id(string $label = null)
     * @method Show\Field|Collection number_votes(string $label = null)
     * @method Show\Field|Collection rank(string $label = null)
     * @method Show\Field|Collection state(string $label = null)
     * @method Show\Field|Collection examined_time(string $label = null)
     * @method Show\Field|Collection examined_user(string $label = null)
     * @method Show\Field|Collection refute_reason(string $label = null)
     * @method Show\Field|Collection release_time(string $label = null)
     * @method Show\Field|Collection deleted_at(string $label = null)
     * @method Show\Field|Collection serial_number(string $label = null)
     * @method Show\Field|Collection wx_openid(string $label = null)
     * @method Show\Field|Collection wx_name(string $label = null)
     * @method Show\Field|Collection wx_avatar(string $label = null)
     * @method Show\Field|Collection phone(string $label = null)
     * @method Show\Field|Collection province(string $label = null)
     * @method Show\Field|Collection city(string $label = null)
     * @method Show\Field|Collection area(string $label = null)
     * @method Show\Field|Collection address(string $label = null)
     * @method Show\Field|Collection level(string $label = null)
     * @method Show\Field|Collection quantity(string $label = null)
     * @method Show\Field|Collection describe(string $label = null)
     * @method Show\Field|Collection category(string $label = null)
     * @method Show\Field|Collection email(string $label = null)
     * @method Show\Field|Collection token(string $label = null)
     * @method Show\Field|Collection tokenable_type(string $label = null)
     * @method Show\Field|Collection tokenable_id(string $label = null)
     * @method Show\Field|Collection abilities(string $label = null)
     * @method Show\Field|Collection last_used_at(string $label = null)
     * @method Show\Field|Collection email_verified_at(string $label = null)
     */
    class Show {}

    /**
     
     */
    class Form {}

}

namespace Dcat\Admin\Grid {
    /**
     
     */
    class Column {}

    /**
     
     */
    class Filter {}
}

namespace Dcat\Admin\Show {
    /**
     
     */
    class Field {}
}
