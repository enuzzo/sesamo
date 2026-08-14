<?php

namespace NetmilkStudio\Sesamo;

defined('ABSPATH') || exit;

final class Presets
{
    /**
     * Return every supported historical preset.
     *
     * Sequences use KeyboardEvent.key values. Letter-only phrases are typed
     * without spaces so they remain practical outside input fields.
     */
    public static function all(): array
    {
        return [
            'konami' => [
                'label'       => __( 'Konami Code', 'sesamo' ),
                'origin'      => __( 'Gradius / Contra', 'sesamo' ),
                'display'     => '↑ ↑ ↓ ↓ ← → ← → B A',
                'description' => __( 'The most recognisable cheat-code sequence in video game history.', 'sesamo' ),
                'sequence'    => ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'],
            ],
            'iddqd' => [
                'label'       => 'IDDQD',
                'origin'      => 'DOOM',
                'display'     => 'I D D Q D',
                'description' => __( 'The original DOOM god-mode code.', 'sesamo' ),
                'sequence'    => ['i', 'd', 'd', 'q', 'd'],
            ],
            'idkfa' => [
                'label'       => 'IDKFA',
                'origin'      => 'DOOM',
                'display'     => 'I D K F A',
                'description' => __( 'The classic DOOM keys, firearms and ammunition code.', 'sesamo' ),
                'sequence'    => ['i', 'd', 'k', 'f', 'a'],
            ],
            'xyzzy' => [
                'label'       => 'XYZZY',
                'origin'      => __( 'Colossal Cave Adventure', 'sesamo' ),
                'display'     => 'X Y Z Z Y',
                'description' => __( 'The magic word from one of the earliest adventure games.', 'sesamo' ),
                'sequence'    => ['x', 'y', 'z', 'z', 'y'],
            ],
            'justin_bailey' => [
                'label'       => 'JUSTIN BAILEY',
                'origin'      => 'Metroid',
                'display'     => 'J U S T I N B A I L E Y',
                'description' => __( 'The famous Metroid password, entered without a space.', 'sesamo' ),
                'sequence'    => ['j', 'u', 's', 't', 'i', 'n', 'b', 'a', 'i', 'l', 'e', 'y'],
            ],
            'rosebud' => [
                'label'       => 'ROSEBUD',
                'origin'      => 'The Sims',
                'display'     => 'R O S E B U D',
                'description' => __( 'A memorable money cheat from The Sims.', 'sesamo' ),
                'sequence'    => ['r', 'o', 's', 'e', 'b', 'u', 'd'],
            ],
            'motherlode' => [
                'label'       => 'MOTHERLODE',
                'origin'      => 'The Sims 2',
                'display'     => 'M O T H E R L O D E',
                'description' => __( 'The long-running Simoleon shortcut.', 'sesamo' ),
                'sequence'    => ['m', 'o', 't', 'h', 'e', 'r', 'l', 'o', 'd', 'e'],
            ],
            'power_overwhelming' => [
                'label'       => 'POWER OVERWHELMING',
                'origin'      => 'StarCraft',
                'display'     => 'P O W E R O V E R W H E L M I N G',
                'description' => __( 'StarCraft invincibility, entered without a space.', 'sesamo' ),
                'sequence'    => ['p', 'o', 'w', 'e', 'r', 'o', 'v', 'e', 'r', 'w', 'h', 'e', 'l', 'm', 'i', 'n', 'g'],
            ],
            'there_is_no_cow_level' => [
                'label'       => 'THERE IS NO COW LEVEL',
                'origin'      => 'StarCraft',
                'display'     => 'T H E R E I S N O C O W L E V E L',
                'description' => __( 'A legendary Blizzard phrase, entered without spaces.', 'sesamo' ),
                'sequence'    => ['t', 'h', 'e', 'r', 'e', 'i', 's', 'n', 'o', 'c', 'o', 'w', 'l', 'e', 'v', 'e', 'l'],
            ],
            'hesoyam' => [
                'label'       => 'HESOYAM',
                'origin'      => 'Grand Theft Auto: San Andreas',
                'display'     => 'H E S O Y A M',
                'description' => __( 'Health, armour and cash from the San Andreas era.', 'sesamo' ),
                'sequence'    => ['h', 'e', 's', 'o', 'y', 'a', 'm'],
            ],
        ];
    }

    public static function default_ids(): array
    {
        return ['konami', 'iddqd'];
    }

}
