export default async function handler(req, res) {
    const { creatures, lastAction } = req.query;

    res.json({
        creatures,
        lastAction
    });
}