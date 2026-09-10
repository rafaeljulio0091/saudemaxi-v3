export function brandTokens(color) {
    const brand = /^#[0-9a-f]{6}$/i.test(color || '') ? color : '#5E5212';
    const rgb = brand
        .slice(1)
        .match(/.{2}/g)
        .map((value) => parseInt(value, 16));
    const dark = rgb.map((value, index) =>
        Math.round(value * 0.82 + [4, 18, 26][index] * 0.18),
    );
    const linear = dark.map((value) => {
        const normalized = value / 255;
        return normalized <= 0.04045
            ? normalized / 12.92
            : ((normalized + 0.055) / 1.055) ** 2.4;
    });
    const luminance =
        linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
    return {
        '--marca': brand,
        '--marca-on': luminance > 0.179 ? '#000000' : '#ffffff',
    };
}
