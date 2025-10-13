<?php
class Reward extends ObjectModel
{
    public $id_reward;
    public $name;
    public $points_required;
    public $reward_type;
    public $reward_value;
    public $min_level;
    public $active;

    public static $definition = [
        'table' => 'loyalty_rewards',
        'primary' => 'id_reward',
        'fields' => [
            'name'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'points_required'=> ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'reward_type'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'reward_value'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'min_level'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active'         => ['type' => self::TYPE_BOOL],
        ],
    ];

    public static function getById($id_reward)
    {
        $row = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'loyalty_rewards` WHERE `id_reward`='.(int)$id_reward);
        if ($row) {
            $reward = new self();
            foreach ($row as $key => $value) {
                $reward->{$key} = $value;
            }
            return $reward;
        }
        return null;
    }
}
