<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $label
 * @property string|null $name
 * @property string|null $street
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property int $default
 * @property int $locked
 * @property string|null $comment
 * @property User|null $added_by
 * @property User|null $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|Addressbook newModelQuery()
 * @method static Builder<static>|Addressbook newQuery()
 * @method static Builder<static>|Addressbook onlyTrashed()
 * @method static Builder<static>|Addressbook query()
 * @method static Builder<static>|Addressbook whereAddedBy($value)
 * @method static Builder<static>|Addressbook whereCity($value)
 * @method static Builder<static>|Addressbook whereComment($value)
 * @method static Builder<static>|Addressbook whereCountry($value)
 * @method static Builder<static>|Addressbook whereCreatedAt($value)
 * @method static Builder<static>|Addressbook whereDefault($value)
 * @method static Builder<static>|Addressbook whereDeletedAt($value)
 * @method static Builder<static>|Addressbook whereEditedBy($value)
 * @method static Builder<static>|Addressbook whereEmail($value)
 * @method static Builder<static>|Addressbook whereId($value)
 * @method static Builder<static>|Addressbook whereLabel($value)
 * @method static Builder<static>|Addressbook whereLocked($value)
 * @method static Builder<static>|Addressbook whereName($value)
 * @method static Builder<static>|Addressbook wherePhone($value)
 * @method static Builder<static>|Addressbook whereStreet($value)
 * @method static Builder<static>|Addressbook whereUpdatedAt($value)
 * @method static Builder<static>|Addressbook whereZip($value)
 * @method static Builder<static>|Addressbook withTrashed()
 * @method static Builder<static>|Addressbook withoutTrashed()
 * @mixin \Eloquent
 * @property-read \App\Models\User|null $addedBy
 * @property-read \App\Models\User|null $editedBy
 */
	class Addressbook extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int|null $sub_unit
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @property-read SubUnit|null $subUnit
 * @method static Builder<static>|BaseUnit newModelQuery()
 * @method static Builder<static>|BaseUnit newQuery()
 * @method static Builder<static>|BaseUnit onlyTrashed()
 * @method static Builder<static>|BaseUnit query()
 * @method static Builder<static>|BaseUnit whereAddedBy($value)
 * @method static Builder<static>|BaseUnit whereCreatedAt($value)
 * @method static Builder<static>|BaseUnit whereDeletedAt($value)
 * @method static Builder<static>|BaseUnit whereEditedBy($value)
 * @method static Builder<static>|BaseUnit whereId($value)
 * @method static Builder<static>|BaseUnit whereName($value)
 * @method static Builder<static>|BaseUnit whereSubUnit($value)
 * @method static Builder<static>|BaseUnit whereUpdatedAt($value)
 * @method static Builder<static>|BaseUnit withTrashed()
 * @method static Builder<static>|BaseUnit withoutTrashed()
 * @mixin \Eloquent
 */
	class BaseUnit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property float $value
 * @property string $status
 * @property string|null $comment
 * @property string $currency
 * @property float|null $advance_payment_value
 * @property string|null $advance_payment_receiver
 * @property int $department_id
 * @property int $order_event_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static Builder<static>|Bill newModelQuery()
 * @method static Builder<static>|Bill newQuery()
 * @method static Builder<static>|Bill onlyTrashed()
 * @method static Builder<static>|Bill query()
 * @method static Builder<static>|Bill whereAddedBy($value)
 * @method static Builder<static>|Bill whereAdvancePaymentReceiver($value)
 * @method static Builder<static>|Bill whereAdvancePaymentValue($value)
 * @method static Builder<static>|Bill whereComment($value)
 * @method static Builder<static>|Bill whereCreatedAt($value)
 * @method static Builder<static>|Bill whereCurrency($value)
 * @method static Builder<static>|Bill whereDeletedAt($value)
 * @method static Builder<static>|Bill whereDepartmentId($value)
 * @method static Builder<static>|Bill whereDescription($value)
 * @method static Builder<static>|Bill whereEditedBy($value)
 * @method static Builder<static>|Bill whereId($value)
 * @method static Builder<static>|Bill whereOrderEventId($value)
 * @method static Builder<static>|Bill whereStatus($value)
 * @method static Builder<static>|Bill whereTitle($value)
 * @method static Builder<static>|Bill whereUpdatedAt($value)
 * @method static Builder<static>|Bill whereValue($value)
 * @method static Builder<static>|Bill withTrashed()
 * @method static Builder<static>|Bill withoutTrashed()
 * @property string|null $repayment_method
 * @method static Builder<static>|Bill whereRepaymentMethod($value)
 * @property-read Department|null $connected_department
 * @property-read OrderEvent|null $connected_event
 * @mixin \Eloquent
 * @property numeric $exchange_rate
 * @property int $reimbursement_to_invoice_issuer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereReimbursementToInvoiceIssuer($value)
 */
	class Bill extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $synopsis
 * @property string|null $arguments
 * @property string|null $options
 * @property string|null $error
 * @property string|null $group
 * @method static Builder<static>|Command newModelQuery()
 * @method static Builder<static>|Command newQuery()
 * @method static Builder<static>|Command query()
 * @method static Builder<static>|Command whereArguments($value)
 * @method static Builder<static>|Command whereDescription($value)
 * @method static Builder<static>|Command whereError($value)
 * @method static Builder<static>|Command whereGroup($value)
 * @method static Builder<static>|Command whereId($value)
 * @method static Builder<static>|Command whereName($value)
 * @method static Builder<static>|Command whereOptions($value)
 * @method static Builder<static>|Command whereSynopsis($value)
 * @mixin \Eloquent
 */
	class Command extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @method static Builder<static>|ContainerType newModelQuery()
 * @method static Builder<static>|ContainerType newQuery()
 * @method static Builder<static>|ContainerType onlyTrashed()
 * @method static Builder<static>|ContainerType query()
 * @method static Builder<static>|ContainerType whereAddedBy($value)
 * @method static Builder<static>|ContainerType whereCreatedAt($value)
 * @method static Builder<static>|ContainerType whereDeletedAt($value)
 * @method static Builder<static>|ContainerType whereEditedBy($value)
 * @method static Builder<static>|ContainerType whereId($value)
 * @method static Builder<static>|ContainerType whereName($value)
 * @method static Builder<static>|ContainerType whereUpdatedAt($value)
 * @method static Builder<static>|ContainerType withTrashed()
 * @method static Builder<static>|ContainerType withoutTrashed()
 * @mixin \Eloquent
 */
	class ContainerType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $icon
 * @property string|null $idp_group_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DepartmentMember> $members
 * @property-read int|null $members_count
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @method static DepartmentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Department newModelQuery()
 * @method static Builder<static>|Department newQuery()
 * @method static Builder<static>|Department onlyTrashed()
 * @method static Builder<static>|Department query()
 * @method static Builder<static>|Department whereAddedBy($value)
 * @method static Builder<static>|Department whereCreatedAt($value)
 * @method static Builder<static>|Department whereDeletedAt($value)
 * @method static Builder<static>|Department whereEditedBy($value)
 * @method static Builder<static>|Department whereIcon($value)
 * @method static Builder<static>|Department whereId($value)
 * @method static Builder<static>|Department whereIdpGroupId($value)
 * @method static Builder<static>|Department whereName($value)
 * @method static Builder<static>|Department whereUpdatedAt($value)
 * @method static Builder<static>|Department withTrashed()
 * @method static Builder<static>|Department withoutTrashed()
 * @property-read Collection<int, InventorySubCategory> $inventory_sub_categories
 * @property-read int|null $inventory_sub_categories_count
 * @property-read Collection<int, ItemsOperationSite> $items_operation_sites
 * @property-read int|null $items_operation_sites_count
 * @property-read Collection<int, Storage> $storages
 * @property-read int|null $storages_count
 * @mixin \Eloquent
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $department_id
 * @property int $user_id
 * @property int|null $role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department $department
 * @property-read Role|null $role
 * @property-read User $user
 * @method static Builder<static>|DepartmentMember newModelQuery()
 * @method static Builder<static>|DepartmentMember newQuery()
 * @method static Builder<static>|DepartmentMember query()
 * @method static Builder<static>|DepartmentMember whereCreatedAt($value)
 * @method static Builder<static>|DepartmentMember whereDepartmentId($value)
 * @method static Builder<static>|DepartmentMember whereId($value)
 * @method static Builder<static>|DepartmentMember whereRoleId($value)
 * @method static Builder<static>|DepartmentMember whereUpdatedAt($value)
 * @method static Builder<static>|DepartmentMember whereUserId($value)
 * @mixin \Eloquent
 */
	class DepartmentMember extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property int $local_role
 * @property string $idp_group
 * @property int $active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role|null $role
 * @method static Builder<static>|IdpRankSync newModelQuery()
 * @method static Builder<static>|IdpRankSync newQuery()
 * @method static Builder<static>|IdpRankSync onlyTrashed()
 * @method static Builder<static>|IdpRankSync query()
 * @method static Builder<static>|IdpRankSync whereActive($value)
 * @method static Builder<static>|IdpRankSync whereCreatedAt($value)
 * @method static Builder<static>|IdpRankSync whereDeletedAt($value)
 * @method static Builder<static>|IdpRankSync whereId($value)
 * @method static Builder<static>|IdpRankSync whereIdpGroup($value)
 * @method static Builder<static>|IdpRankSync whereLocalRole($value)
 * @method static Builder<static>|IdpRankSync whereName($value)
 * @method static Builder<static>|IdpRankSync whereUpdatedAt($value)
 * @method static Builder<static>|IdpRankSync withTrashed()
 * @method static Builder<static>|IdpRankSync withoutTrashed()
 * @mixin \Eloquent
 */
	class IdpRankSync extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department|null $connected_department
 * @method static Builder<static>|InventorySubCategory newModelQuery()
 * @method static Builder<static>|InventorySubCategory newQuery()
 * @method static Builder<static>|InventorySubCategory query()
 * @method static Builder<static>|InventorySubCategory whereAddedBy($value)
 * @method static Builder<static>|InventorySubCategory whereCreatedAt($value)
 * @method static Builder<static>|InventorySubCategory whereDepartment($value)
 * @method static Builder<static>|InventorySubCategory whereEditedBy($value)
 * @method static Builder<static>|InventorySubCategory whereId($value)
 * @method static Builder<static>|InventorySubCategory whereName($value)
 * @method static Builder<static>|InventorySubCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class InventorySubCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $shortname
 * @property string|null $serialnumber
 * @property int|null $weight
 * @property int|null $stackable
 * @property int|null $unit
 * @property string|null $due_date
 * @property string|null $sorted_out
 * @property string|null $description
 * @property string|null $comment
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property int|null $price
 * @property int $locked
 * @property int|null $specific_editor
 * @property string|null $buy_date
 * @property int|null $qr_code
 * @property int|null $storage_container_id
 * @property int $dangerous_good
 * @property int $big_size
 * @property int $needs_truck
 * @property string|null $url
 * @property int|null $storage
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department_
 * @property-read User|null $editedBy
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static Builder<static>|Item newModelQuery()
 * @method static Builder<static>|Item newQuery()
 * @method static Builder<static>|Item onlyTrashed()
 * @method static Builder<static>|Item query()
 * @method static Builder<static>|Item whereAddedBy($value)
 * @method static Builder<static>|Item whereBigSize($value)
 * @method static Builder<static>|Item whereBuyDate($value)
 * @method static Builder<static>|Item whereComment($value)
 * @method static Builder<static>|Item whereCreatedAt($value)
 * @method static Builder<static>|Item whereDangerousGood($value)
 * @method static Builder<static>|Item whereDeletedAt($value)
 * @method static Builder<static>|Item whereDepartment($value)
 * @method static Builder<static>|Item whereDescription($value)
 * @method static Builder<static>|Item whereDueDate($value)
 * @method static Builder<static>|Item whereEditedBy($value)
 * @method static Builder<static>|Item whereId($value)
 * @method static Builder<static>|Item whereLocked($value)
 * @method static Builder<static>|Item whereName($value)
 * @method static Builder<static>|Item whereNeedsTruck($value)
 * @method static Builder<static>|Item wherePrice($value)
 * @method static Builder<static>|Item whereQrCode($value)
 * @method static Builder<static>|Item whereSerialnumber($value)
 * @method static Builder<static>|Item whereShortname($value)
 * @method static Builder<static>|Item whereSortedOut($value)
 * @method static Builder<static>|Item whereSpecificEditor($value)
 * @method static Builder<static>|Item whereStackable($value)
 * @method static Builder<static>|Item whereStorage($value)
 * @method static Builder<static>|Item whereStorageContainerId($value)
 * @method static Builder<static>|Item whereUnit($value)
 * @method static Builder<static>|Item whereUpdatedAt($value)
 * @method static Builder<static>|Item whereUrl($value)
 * @method static Builder<static>|Item whereWeightG($value)
 * @method static Builder<static>|Item withTrashed()
 * @method static Builder<static>|Item withoutTrashed()
 * @property string|null $owner
 * @property int $borrowed_item
 * @property int $rented_item
 * @property int $will_be_brought_to_next_event
 * @property int|null $operation_site
 * @property array<array-key, mixed>|null $custom_fields
 * @property int|null $sub_category
 * @property string|null $manufacturer_barcode
 * @property-read Department|null $connected_department
 * @property-read ItemsOperationSite|null $connected_operation_site
 * @property-read Storage|null $connected_storage
 * @property-read InventorySubCategory|null $connected_sub_category
 * @method static Builder<static>|Item whereBorrowedItem($value)
 * @method static Builder<static>|Item whereCustomFields($value)
 * @method static Builder<static>|Item whereManufacturerBarcode($value)
 * @method static Builder<static>|Item whereOperationSite($value)
 * @method static Builder<static>|Item whereOwner($value)
 * @method static Builder<static>|Item whereRentedItem($value)
 * @method static Builder<static>|Item whereSubCategory($value)
 * @method static Builder<static>|Item whereWeight($value)
 * @method static Builder<static>|Item whereWillBeBroughtToNextEvent($value)
 * @mixin \Eloquent
 */
	class Item extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department|null $connected_department
 * @method static Builder<static>|ItemsOperationSite newModelQuery()
 * @method static Builder<static>|ItemsOperationSite newQuery()
 * @method static Builder<static>|ItemsOperationSite query()
 * @method static Builder<static>|ItemsOperationSite whereAddedBy($value)
 * @method static Builder<static>|ItemsOperationSite whereCreatedAt($value)
 * @method static Builder<static>|ItemsOperationSite whereDepartment($value)
 * @method static Builder<static>|ItemsOperationSite whereEditedBy($value)
 * @method static Builder<static>|ItemsOperationSite whereId($value)
 * @method static Builder<static>|ItemsOperationSite whereName($value)
 * @method static Builder<static>|ItemsOperationSite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class ItemsOperationSite extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string $cron_expression
 * @property string|null $timezone
 * @property string|null $ping_url
 * @property Carbon|null $last_started_at
 * @property Carbon|null $last_finished_at
 * @property Carbon|null $last_failed_at
 * @property Carbon|null $last_skipped_at
 * @property Carbon|null $registered_on_oh_dear_at
 * @property Carbon|null $last_pinged_at
 * @property int $grace_time_in_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, MonitoredScheduledTaskLogItem> $logItems
 * @property-read int|null $log_items_count
 * @method static Builder<static>|MonitoredScheduledTask newModelQuery()
 * @method static Builder<static>|MonitoredScheduledTask newQuery()
 * @method static Builder<static>|MonitoredScheduledTask query()
 * @method static Builder<static>|MonitoredScheduledTask whereCreatedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereCronExpression($value)
 * @method static Builder<static>|MonitoredScheduledTask whereGraceTimeInMinutes($value)
 * @method static Builder<static>|MonitoredScheduledTask whereId($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastFailedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastFinishedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastPingedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastSkippedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereLastStartedAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereName($value)
 * @method static Builder<static>|MonitoredScheduledTask wherePingUrl($value)
 * @method static Builder<static>|MonitoredScheduledTask whereRegisteredOnOhDearAt($value)
 * @method static Builder<static>|MonitoredScheduledTask whereTimezone($value)
 * @method static Builder<static>|MonitoredScheduledTask whereType($value)
 * @method static Builder<static>|MonitoredScheduledTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class MonitoredScheduledTask extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $delivery_provider
 * @property string|null $delivery_by
 * @property string|null $delivery_destination
 * @property string|null $tracking_number
 * @property string|null $delivery_date
 * @property float $delivery_costs
 * @property int $instant_delivery
 * @property int $department_id
 * @property int $added_by
 * @property int $edited_by
 * @property int $amount
 * @property float $price_net
 * @property float $price_gross
 * @property float $tax_rate
 * @property string|null $payment_method
 * @property string $currency
 * @property string|null $url
 * @property string|null $contact
 * @property string|null $tags
 * @property int $dangerous_good
 * @property int $big_size
 * @property int $needs_truck
 * @property string|null $ordered_at
 * @property int $booked_to_inventory
 * @property int|null $inv_id
 * @property int $order_event_id
 * @property string|null $comment
 * @property string $status
 * @property string|null $picture
 * @property int|null $order_article_id
 * @property string|null $article_number
 * @property string|null $user_note
 * @property int|null $order_request_id
 * @property int $special_delivery
 * @property string|null $special_flag_text
 * @property float $returning_deposit
 * @property float|null $discount_net
 * @property string|null $order_number
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read OrderArticle|null $directoryArticle
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read OrderRequest|null $orderRequest
 * @method static Builder<static>|Order newModelQuery()
 * @method static Builder<static>|Order newQuery()
 * @method static Builder<static>|Order onlyTrashed()
 * @method static Builder<static>|Order query()
 * @method static Builder<static>|Order whereAddedBy($value)
 * @method static Builder<static>|Order whereAmount($value)
 * @method static Builder<static>|Order whereArticleNumber($value)
 * @method static Builder<static>|Order whereBigSize($value)
 * @method static Builder<static>|Order whereBookedToInventory($value)
 * @method static Builder<static>|Order whereComment($value)
 * @method static Builder<static>|Order whereContact($value)
 * @method static Builder<static>|Order whereCreatedAt($value)
 * @method static Builder<static>|Order whereCurrency($value)
 * @method static Builder<static>|Order whereDangerousGood($value)
 * @method static Builder<static>|Order whereDeletedAt($value)
 * @method static Builder<static>|Order whereDeliveryBy($value)
 * @method static Builder<static>|Order whereDeliveryCosts($value)
 * @method static Builder<static>|Order whereDeliveryDate($value)
 * @method static Builder<static>|Order whereDeliveryDestination($value)
 * @method static Builder<static>|Order whereDeliveryProvider($value)
 * @method static Builder<static>|Order whereDepartmentId($value)
 * @method static Builder<static>|Order whereDescription($value)
 * @method static Builder<static>|Order whereDiscountNet($value)
 * @method static Builder<static>|Order whereEditedBy($value)
 * @method static Builder<static>|Order whereId($value)
 * @method static Builder<static>|Order whereInstantDelivery($value)
 * @method static Builder<static>|Order whereInvId($value)
 * @method static Builder<static>|Order whereName($value)
 * @method static Builder<static>|Order whereNeedsTruck($value)
 * @method static Builder<static>|Order whereOrderArticleId($value)
 * @method static Builder<static>|Order whereOrderEventId($value)
 * @method static Builder<static>|Order whereOrderNumber($value)
 * @method static Builder<static>|Order whereOrderRequestId($value)
 * @method static Builder<static>|Order whereOrderedAt($value)
 * @method static Builder<static>|Order wherePaymentMethod($value)
 * @method static Builder<static>|Order wherePicture($value)
 * @method static Builder<static>|Order wherePriceGross($value)
 * @method static Builder<static>|Order wherePriceNet($value)
 * @method static Builder<static>|Order whereReturningDeposit($value)
 * @method static Builder<static>|Order whereSpecialDelivery($value)
 * @method static Builder<static>|Order whereSpecialFlagText($value)
 * @method static Builder<static>|Order whereStatus($value)
 * @method static Builder<static>|Order whereTags($value)
 * @method static Builder<static>|Order whereTaxRate($value)
 * @method static Builder<static>|Order whereTrackingNumber($value)
 * @method static Builder<static>|Order whereUpdatedAt($value)
 * @method static Builder<static>|Order whereUrl($value)
 * @method static Builder<static>|Order whereUserNote($value)
 * @method static Builder<static>|Order withTrashed()
 * @method static Builder<static>|Order withoutTrashed()
 * @property string|null $approved_at
 * @property int|null $approved_by
 * @property-read User|null $approvedBy
 * @method static Builder<static>|Order whereApprovedAt($value)
 * @method static Builder<static>|Order whereApprovedBy($value)
 * @mixin \Eloquent
 */
	class Order extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $picture
 * @property User|null $added_by
 * @property User|null $edited_by
 * @property int|null $category
 * @property float $price_net
 * @property float $price_gross
 * @property string $currency
 * @property string|null $url
 * @property string|null $comment
 * @property string|null $article_number
 * @property float $tax_rate
 * @property float $returning_deposit
 * @property int $locked
 * @property string|null $locked_reason
 * @property int $quantity_available
 * @property array<array-key, mixed>|null $article_variants
 * @property float $packaging_size_per_article
 * @property int|null $packaging_size_per_article_unit
 * @property int $packaging_article_quantity
 * @property string|null $deadline
 * @property bool $auto_calculate
 * @property string|null $important_note
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OrderCategory|null $categorie
 * @method static Builder<static>|OrderArticle newModelQuery()
 * @method static Builder<static>|OrderArticle newQuery()
 * @method static Builder<static>|OrderArticle onlyTrashed()
 * @method static Builder<static>|OrderArticle query()
 * @method static Builder<static>|OrderArticle whereAddedBy($value)
 * @method static Builder<static>|OrderArticle whereArticleNumber($value)
 * @method static Builder<static>|OrderArticle whereArticleVariants($value)
 * @method static Builder<static>|OrderArticle whereAutoCalculate($value)
 * @method static Builder<static>|OrderArticle whereCategory($value)
 * @method static Builder<static>|OrderArticle whereComment($value)
 * @method static Builder<static>|OrderArticle whereCreatedAt($value)
 * @method static Builder<static>|OrderArticle whereCurrency($value)
 * @method static Builder<static>|OrderArticle whereDeadline($value)
 * @method static Builder<static>|OrderArticle whereDeletedAt($value)
 * @method static Builder<static>|OrderArticle whereDescription($value)
 * @method static Builder<static>|OrderArticle whereEditedBy($value)
 * @method static Builder<static>|OrderArticle whereId($value)
 * @method static Builder<static>|OrderArticle whereImportantNote($value)
 * @method static Builder<static>|OrderArticle whereLocked($value)
 * @method static Builder<static>|OrderArticle whereLockedReason($value)
 * @method static Builder<static>|OrderArticle whereName($value)
 * @method static Builder<static>|OrderArticle wherePackagingArticleQuantity($value)
 * @method static Builder<static>|OrderArticle wherePackagingSizePerArticle($value)
 * @method static Builder<static>|OrderArticle wherePackagingSizePerArticleUnit($value)
 * @method static Builder<static>|OrderArticle wherePicture($value)
 * @method static Builder<static>|OrderArticle wherePriceGross($value)
 * @method static Builder<static>|OrderArticle wherePriceNet($value)
 * @method static Builder<static>|OrderArticle whereQuantityAvailable($value)
 * @method static Builder<static>|OrderArticle whereReturningDeposit($value)
 * @method static Builder<static>|OrderArticle whereTaxRate($value)
 * @method static Builder<static>|OrderArticle whereUpdatedAt($value)
 * @method static Builder<static>|OrderArticle whereUrl($value)
 * @method static Builder<static>|OrderArticle withTrashed()
 * @method static Builder<static>|OrderArticle withoutTrashed()
 * @property-read \App\Models\User|null $addedBy
 * @property-read \App\Models\User|null $editedBy
 * @mixin \Eloquent
 */
	class OrderArticle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|OrderCategory newModelQuery()
 * @method static Builder<static>|OrderCategory newQuery()
 * @method static Builder<static>|OrderCategory onlyTrashed()
 * @method static Builder<static>|OrderCategory query()
 * @method static Builder<static>|OrderCategory whereAddedBy($value)
 * @method static Builder<static>|OrderCategory whereCreatedAt($value)
 * @method static Builder<static>|OrderCategory whereDeletedAt($value)
 * @method static Builder<static>|OrderCategory whereDescription($value)
 * @method static Builder<static>|OrderCategory whereEditedBy($value)
 * @method static Builder<static>|OrderCategory whereId($value)
 * @method static Builder<static>|OrderCategory whereName($value)
 * @method static Builder<static>|OrderCategory whereUpdatedAt($value)
 * @method static Builder<static>|OrderCategory withTrashed()
 * @method static Builder<static>|OrderCategory withoutTrashed()
 * @mixin \Eloquent
 */
	class OrderCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $locked
 * @property Carbon|null $order_deadline
 * @property int $is_active
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @method static OrderEventFactory factory($count = null, $state = [])
 * @method static Builder<static>|OrderEvent newModelQuery()
 * @method static Builder<static>|OrderEvent newQuery()
 * @method static Builder<static>|OrderEvent onlyTrashed()
 * @method static Builder<static>|OrderEvent query()
 * @method static Builder<static>|OrderEvent whereAddedBy($value)
 * @method static Builder<static>|OrderEvent whereCreatedAt($value)
 * @method static Builder<static>|OrderEvent whereDeletedAt($value)
 * @method static Builder<static>|OrderEvent whereEditedBy($value)
 * @method static Builder<static>|OrderEvent whereId($value)
 * @method static Builder<static>|OrderEvent whereIsActive($value)
 * @method static Builder<static>|OrderEvent whereLocked($value)
 * @method static Builder<static>|OrderEvent whereName($value)
 * @method static Builder<static>|OrderEvent whereOrderDeadline($value)
 * @method static Builder<static>|OrderEvent whereUpdatedAt($value)
 * @method static Builder<static>|OrderEvent withTrashed()
 * @method static Builder<static>|OrderEvent withoutTrashed()
 * @mixin \Eloquent
 */
	class OrderEvent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $message
 * @property string|null $comment
 * @property string|null $url
 * @property int $status
 * @property int $status_notifications
 * @property Carbon|null $deleted_at
 * @property int $quantity
 * @property int $order_event_id
 * @property int $department_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @method static OrderRequestFactory factory($count = null, $state = [])
 * @method static Builder<static>|OrderRequest newModelQuery()
 * @method static Builder<static>|OrderRequest newQuery()
 * @method static Builder<static>|OrderRequest onlyTrashed()
 * @method static Builder<static>|OrderRequest query()
 * @method static Builder<static>|OrderRequest whereAddedBy($value)
 * @method static Builder<static>|OrderRequest whereComment($value)
 * @method static Builder<static>|OrderRequest whereCreatedAt($value)
 * @method static Builder<static>|OrderRequest whereDeletedAt($value)
 * @method static Builder<static>|OrderRequest whereDepartmentId($value)
 * @method static Builder<static>|OrderRequest whereEditedBy($value)
 * @method static Builder<static>|OrderRequest whereId($value)
 * @method static Builder<static>|OrderRequest whereMessage($value)
 * @method static Builder<static>|OrderRequest whereOrderEventId($value)
 * @method static Builder<static>|OrderRequest whereQuantity($value)
 * @method static Builder<static>|OrderRequest whereStatus($value)
 * @method static Builder<static>|OrderRequest whereStatusNotifications($value)
 * @method static Builder<static>|OrderRequest whereTitle($value)
 * @method static Builder<static>|OrderRequest whereUpdatedAt($value)
 * @method static Builder<static>|OrderRequest whereUrl($value)
 * @method static Builder<static>|OrderRequest withTrashed()
 * @method static Builder<static>|OrderRequest withoutTrashed()
 * @mixin \Eloquent
 */
	class OrderRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SpatiePermission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static PermissionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Permission newModelQuery()
 * @method static Builder<static>|Permission newQuery()
 * @method static Builder<static>|Permission permission($permissions, $without = false)
 * @method static Builder<static>|Permission query()
 * @method static Builder<static>|Permission role($roles, $guard = null, $without = false)
 * @method static Builder<static>|Permission whereCreatedAt($value)
 * @method static Builder<static>|Permission whereGuardName($value)
 * @method static Builder<static>|Permission whereId($value)
 * @method static Builder<static>|Permission whereName($value)
 * @method static Builder<static>|Permission whereUpdatedAt($value)
 * @method static Builder<static>|Permission withoutPermission($permissions)
 * @method static Builder<static>|Permission withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static RoleFactory factory($count = null, $state = [])
 * @method static Builder<static>|Role newModelQuery()
 * @method static Builder<static>|Role newQuery()
 * @method static Builder<static>|Role permission($permissions, $without = false)
 * @method static Builder<static>|Role query()
 * @method static Builder<static>|Role whereCreatedAt($value)
 * @method static Builder<static>|Role whereGuardName($value)
 * @method static Builder<static>|Role whereId($value)
 * @method static Builder<static>|Role whereName($value)
 * @method static Builder<static>|Role whereUpdatedAt($value)
 * @method static Builder<static>|Role withoutPermission($permissions)
 * @mixin \Eloquent
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read MonitoredScheduledTask|null $monitoredScheduledTask
 * @method static Builder<static>|ScheduledTaskLogItem newModelQuery()
 * @method static Builder<static>|ScheduledTaskLogItem newQuery()
 * @method static Builder<static>|ScheduledTaskLogItem query()
 * @mixin \Eloquent
 */
	class ScheduledTaskLogItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read Model|\Eloquent $model
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $icon
 * @property string $title
 * @property array<array-key, mixed>|null $description
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory whereUserId($value)
 */
	class StatusHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $contact_details
 * @property string $country
 * @property string $street
 * @property string $city
 * @property string $post_code
 * @property string|null $comment
 * @property string|null $documents
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|Storage newModelQuery()
 * @method static Builder<static>|Storage newQuery()
 * @method static Builder<static>|Storage onlyTrashed()
 * @method static Builder<static>|Storage query()
 * @method static Builder<static>|Storage whereAddedBy($value)
 * @method static Builder<static>|Storage whereCity($value)
 * @method static Builder<static>|Storage whereComment($value)
 * @method static Builder<static>|Storage whereContactDetails($value)
 * @method static Builder<static>|Storage whereCountry($value)
 * @method static Builder<static>|Storage whereCreatedAt($value)
 * @method static Builder<static>|Storage whereDeletedAt($value)
 * @method static Builder<static>|Storage whereDocuments($value)
 * @method static Builder<static>|Storage whereEditedBy($value)
 * @method static Builder<static>|Storage whereId($value)
 * @method static Builder<static>|Storage whereName($value)
 * @method static Builder<static>|Storage wherePostCode($value)
 * @method static Builder<static>|Storage whereStreet($value)
 * @method static Builder<static>|Storage whereUpdatedAt($value)
 * @method static Builder<static>|Storage withTrashed()
 * @method static Builder<static>|Storage withoutTrashed()
 * @property int $type
 * @property Department|null $managing_department
 * @property-read Collection<int, StorageDepartmentAccess> $departments
 * @property-read int|null $departments_count
 * @method static Builder<static>|Storage whereManagingDepartment($value)
 * @method static Builder<static>|Storage whereType($value)
 * @mixin \Eloquent
 */
	class Storage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $storage
 * @property string|null $comment
 * @property int $added_by
 * @property int $edited_by
 * @property int|null $qr_code
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageArea newModelQuery()
 * @method static Builder<static>|StorageArea newQuery()
 * @method static Builder<static>|StorageArea onlyTrashed()
 * @method static Builder<static>|StorageArea query()
 * @method static Builder<static>|StorageArea whereAddedBy($value)
 * @method static Builder<static>|StorageArea whereComment($value)
 * @method static Builder<static>|StorageArea whereCreatedAt($value)
 * @method static Builder<static>|StorageArea whereDeletedAt($value)
 * @method static Builder<static>|StorageArea whereEditedBy($value)
 * @method static Builder<static>|StorageArea whereId($value)
 * @method static Builder<static>|StorageArea whereName($value)
 * @method static Builder<static>|StorageArea whereQrCode($value)
 * @method static Builder<static>|StorageArea whereStorage($value)
 * @method static Builder<static>|StorageArea whereUpdatedAt($value)
 * @method static Builder<static>|StorageArea withTrashed()
 * @method static Builder<static>|StorageArea withoutTrashed()
 * @mixin \Eloquent
 */
	class StorageArea extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int|null $storage_area
 * @property int $type
 * @property int|null $qr_code
 * @property int $home_storage
 * @property int|null $parent_container
 * @property string|null $comment
 * @property int $added_by
 * @property Carbon|null $deleted_at
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageContainer newModelQuery()
 * @method static Builder<static>|StorageContainer newQuery()
 * @method static Builder<static>|StorageContainer onlyTrashed()
 * @method static Builder<static>|StorageContainer query()
 * @method static Builder<static>|StorageContainer whereAddedBy($value)
 * @method static Builder<static>|StorageContainer whereComment($value)
 * @method static Builder<static>|StorageContainer whereCreatedAt($value)
 * @method static Builder<static>|StorageContainer whereDeletedAt($value)
 * @method static Builder<static>|StorageContainer whereEditedBy($value)
 * @method static Builder<static>|StorageContainer whereHomeStorage($value)
 * @method static Builder<static>|StorageContainer whereId($value)
 * @method static Builder<static>|StorageContainer whereName($value)
 * @method static Builder<static>|StorageContainer whereParentContainer($value)
 * @method static Builder<static>|StorageContainer whereQrCode($value)
 * @method static Builder<static>|StorageContainer whereStorageArea($value)
 * @method static Builder<static>|StorageContainer whereType($value)
 * @method static Builder<static>|StorageContainer whereUpdatedAt($value)
 * @method static Builder<static>|StorageContainer withTrashed()
 * @method static Builder<static>|StorageContainer withoutTrashed()
 * @mixin \Eloquent
 */
	class StorageContainer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $department
 * @property int $storage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageDepartmentAccess newModelQuery()
 * @method static Builder<static>|StorageDepartmentAccess newQuery()
 * @method static Builder<static>|StorageDepartmentAccess query()
 * @method static Builder<static>|StorageDepartmentAccess whereCreatedAt($value)
 * @method static Builder<static>|StorageDepartmentAccess whereDepartment($value)
 * @method static Builder<static>|StorageDepartmentAccess whereId($value)
 * @method static Builder<static>|StorageDepartmentAccess whereStorage($value)
 * @method static Builder<static>|StorageDepartmentAccess whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class StorageDepartmentAccess extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $value
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $added_by
 * @property User|null $edited_by
 * @method static Builder<static>|SubUnit newModelQuery()
 * @method static Builder<static>|SubUnit newQuery()
 * @method static Builder<static>|SubUnit onlyTrashed()
 * @method static Builder<static>|SubUnit query()
 * @method static Builder<static>|SubUnit whereAddedBy($value)
 * @method static Builder<static>|SubUnit whereCreatedAt($value)
 * @method static Builder<static>|SubUnit whereDeletedAt($value)
 * @method static Builder<static>|SubUnit whereEditedBy($value)
 * @method static Builder<static>|SubUnit whereId($value)
 * @method static Builder<static>|SubUnit whereName($value)
 * @method static Builder<static>|SubUnit whereUpdatedAt($value)
 * @method static Builder<static>|SubUnit whereValue($value)
 * @method static Builder<static>|SubUnit withTrashed()
 * @method static Builder<static>|SubUnit withoutTrashed()
 * @mixin \Eloquent
 */
	class SubUnit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $data1
 * @property string|null $data2
 * @property string|null $data3
 * @property string|null $data4
 * @property string|null $data5
 * @property string|null $data6
 * @property string|null $data7
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static Builder<static>|TestModel newModelQuery()
 * @method static Builder<static>|TestModel newQuery()
 * @method static Builder<static>|TestModel query()
 * @method static Builder<static>|TestModel whereCreatedAt($value)
 * @method static Builder<static>|TestModel whereData1($value)
 * @method static Builder<static>|TestModel whereData2($value)
 * @method static Builder<static>|TestModel whereData3($value)
 * @method static Builder<static>|TestModel whereData4($value)
 * @method static Builder<static>|TestModel whereData5($value)
 * @method static Builder<static>|TestModel whereData6($value)
 * @method static Builder<static>|TestModel whereData7($value)
 * @method static Builder<static>|TestModel whereId($value)
 * @method static Builder<static>|TestModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class TestModel extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $ex_id
 * @property array<array-key, mixed>|null $ex_groups
 * @property string|null $avatar
 * @property int $locked
 * @property string|null $comment
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $last_login
 * @property bool $separated_rights
 * @property bool $separated_departments
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, DepartmentMember> $departmentMemberships
 * @property-read int|null $department_memberships_count
 * @property-read Collection<int, Department> $departments
 * @property-read int|null $departments_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onlyTrashed()
 * @method static Builder<static>|User permission($permissions, $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static Builder<static>|User whereAvatar($value)
 * @method static Builder<static>|User whereComment($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereExGroups($value)
 * @method static Builder<static>|User whereExId($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereLastLogin($value)
 * @method static Builder<static>|User whereLocked($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereSeparatedDepartments($value)
 * @method static Builder<static>|User whereSeparatedRights($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User withTrashed()
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, $guard = null)
 * @method static Builder<static>|User withoutTrashed()
 * @property-read string $notification_email_or_fallback
 * @mixin \Eloquent
 * @property string|null $notification_email
 * @property string|null $discord_webhook
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDiscordWebhook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNotificationEmail($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasAvatar {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read User|null $users
 * @method static Builder<static>|Whitelist newModelQuery()
 * @method static Builder<static>|Whitelist newQuery()
 * @method static Builder<static>|Whitelist query()
 * @method static Builder<static>|Whitelist whereCreatedAt($value)
 * @method static Builder<static>|Whitelist whereEmail($value)
 * @method static Builder<static>|Whitelist whereId($value)
 * @method static Builder<static>|Whitelist whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class Whitelist extends \Eloquent {}
}

