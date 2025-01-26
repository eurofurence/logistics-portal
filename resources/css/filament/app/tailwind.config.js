import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Clusters/TypesAndUnits/**/*.php',
        './resources/views/filament/clusters/types-and-units/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/kenepa/banner/resources/**/*.php',
    ],
}
