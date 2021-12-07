<?php
namespace PayPal\Model\Entity;

use Cake\ORM\Entity;

/**
 * Paypal Entity
 *
 * @property string $id
 * @property int $fk_id
 * @property string $fk_model
 * @property string $data
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class Paypal extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'id' => true,
        'fk_id' => true,
        'fk_model' => true,
        'data' => true,
        'created' => true,
        'modified' => true,
    ];
}
