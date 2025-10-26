// CLAUDE HANDLER
// SONNET 4.5

import { promptText } from "../prompting/promptLogic";

const gods = ['Agnostic', 'Gemini', 'Claude', 'Grok', 'GPT'];

export async function askClaude(creaturesData, lastAction) {
    try {
        // Get API key from backend
        const configResponse = await fetch(`/api/god2?creatures=${encodeURIComponent(creaturesData)}&lastAction=${encodeURIComponent(lastAction || '')}`);
        const config = await configResponse.json();
        const ANTHROPIC_API_KEY = config.apiKey;

        const creatures = parseCreatures(creaturesData);

        let prompt = promptText;

        if (lastAction) {
            prompt += ` Your last move was ${lastAction} and you cannot play the same action twice in a row, so you must choose a different action this time.`;
        }

        prompt += ` The creatures currently alive are :- ${JSON.stringify(creatures)}`;

        const response = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'anthropic-version': '2023-06-01',
                'x-api-key': ANTHROPIC_API_KEY
            },
            body: JSON.stringify({
                model: 'claude-3-opus-20240229',
                max_tokens: 1024,
                messages: [{
                    role: 'user',
                    content: prompt
                }]
            })
        });

        const data = await response.json();
        const text = data.content[0].text.replace(/\\n/g, '');

        return {
            success: true,
            message: text
        };
    } catch (error) {
        console.error('Claude error:', error);
        return { success: false };
    }
}

function parseCreatures(dataString) {
    if (!dataString) return [];
    return dataString.split('^').map(creature => {
        const [x, y, health, faith] = creature.split('~');
        return {
            x: parseFloat(x),
            y: parseFloat(y),
            health: parseFloat(health),
            faith: gods[parseInt(faith)]
        };
    });
}