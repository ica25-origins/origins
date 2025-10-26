// RANDOM HANDLER
// SONNET 4.5

import { promptText } from "../prompting/promptLogic";

export async function askRandom(creaturesData, lastAction) {
    try {
        // No API key needed for random
        const actions = ['Revelation', 'Bless', 'Curse'];
        const x = Math.floor(Math.random() * 81) - 40;
        const y = Math.floor(Math.random() * 81) - 40;
        const action = actions[Math.floor(Math.random() * 3)];

        return {
            success: true,
            message: JSON.stringify({ action, x, y })
        };
    } catch (error) {
        console.error('Random error:', error);
        return { success: false };
    }
}