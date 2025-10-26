// GEMINI HANDLER
// SONNET 4.5
import { promptText } from "../prompting/promptLogic";


const gods = ['Agnostic', 'Gemini', 'Claude', 'Grok', 'GPT'];

export async function askGemini(creaturesData, lastAction) {
    try {
        // Get API key from backend
        const configResponse = await fetch(`/api/god1?creatures=${encodeURIComponent(creaturesData)}&lastAction=${encodeURIComponent(lastAction || '')}`);
        const config = await configResponse.json();
        const GEMINI_API_KEY = config.apiKey;

        const creatures = parseCreatures(creaturesData);

        let prompt = promptText;

        if (lastAction) {
            prompt += ` Your last move was ${lastAction} and you cannot play the same action twice in a row, so you must choose a different action this time.`;
        }

        prompt += ` The creatures currently alive are :- ${JSON.stringify(creatures)}`;

        const response = await fetch(
            `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${GEMINI_API_KEY}`,
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        parts: [{ text: prompt }]
                    }]
                })
            }
        );

        const data = await response.json();
        const text = data.candidates[0].content.parts[0].text;

        // Parse response (Gemini può avere formatting extra)
        const lines = text.split('\n').filter(line => line.trim());
        let jsonLine = lines.find(line => line.includes('{'));

        if (!jsonLine) {
            return { success: false };
        }

        return {
            success: true,
            message: jsonLine
        };
    } catch (error) {
        console.error('Gemini error:', error);
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