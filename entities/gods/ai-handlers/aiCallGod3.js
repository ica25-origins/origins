// RANDOM
// SONNET 4.5

export async function askRandom(creaturesData, lastAction) {
    const actions = ['Revelation', 'Bless', 'Curse'];
    const x = Math.floor(Math.random() * 81) - 40;
    const y = Math.floor(Math.random() * 81) - 40;
    const action = actions[Math.floor(Math.random() * 3)];

    return {
        success: true,
        message: JSON.stringify({ action, x, y })
    };
}