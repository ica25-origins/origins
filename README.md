# Origins

**Origins** is an interactive simulation and generative artwork developed for the ICA 25 course on art and computation.  
It explores how systems of belief and influence can emerge from simple rules, and how large language models (LLMs) can act as autonomous creative agents within a digital ecology.

The project is built using JavaScript and Three.js for the simulation and rendering layers, with PHP used only for connecting to external LLM APIs.  
It is both a creative coding exercise and an inquiry into the deification of software.

---

## Overview

In **Origins**, autonomous digital creatures inhabit a bounded two-dimensional world.  
Each creature has basic needs such as energy and survival, and can act independently based on those needs.  
At regular intervals, four different LLMs (Gemini, Claude, Grok, and GPT) act as gods that issue simple JSON commands to modify the world state.

These gods can perform one of three actions:

- **Revelation**: Emit a light beam that converts nearby creatures into followers.  
- **Bless**: Create beneficial items that restore creature energy.  
- **Curse**: Create harmful regions that drain energy and can cause death.

The human player also participates by placing food or hazards in the world.  
Player input is intentionally limited to encourage observation and reflection rather than control.

The piece functions as both a game and an art installation, asking how computation, belief, and agency overlap when autonomous systems interact.

---

## Technical Structure

### Languages and Frameworks

- **JavaScript (ES6)** for core simulation logic
- **Three.js** for 3D rendering and camera interaction
- **Cannon-ES** for simple physics (movement, collisions)
- **PHP** for API interaction with external LLMs
- **HTML/CSS** for layout and interface display

### System Components

frontend/
├── index.php # Main simulation file (could also be named index.html)
├── js/
│ ├── creatures.js # Creature class and behavior system
│ ├── world.js # World initialization and update loop
│ ├── llmManager.js # Handles LLM API calls and responses
│ └── ui.js # Displays statistics and player interface
backend/
├── ajaxgod1.php # Gemini endpoint
├── ajaxgod2.php # Claude endpoint
├── ajaxgod3.php # Grok endpoint
└── ajaxgod4.php # GPT endpoint
assets/
├── textures/
└── models/


The PHP files are minimal wrappers that send the current world state to each LLM and receive their JSON-formatted actions.  
All game logic and visualization occur in the JavaScript environment.

---

## Game Mechanics

Each creature in the simulation has three key variables:

- **Energy**: Represents vitality. It drains over time and is replenished by eating.  
- **Faith**: Indicates allegiance to one of the gods or "Agnostic" if unaligned.  
- **Devotion**: A floating-point value between 0 and 1 representing how strongly a creature believes.

### Core Actions

| Action | Description | Energy Change |
|---------|--------------|----------------|
| Move | Basic exploration or evasion | -1 |
| Eat | Consume food to regain energy | +25 |
| Pray | Increases devotion, can trigger divine response | -5 |

### Energy Model
E(t + Δt) = E(t) - 0.10Δt - 0.12vΔt + f_eat + f_pray


- Baseline energy drain: 0.10 per second  
- Movement cost: 0.12 × velocity per second  
- Eat adds 25 energy units  
- Pray subtracts 5 energy units  
- Death occurs when energy ≤ 0

### Faith Conversion Model

When a creature stands within a revelation beam from a god, it may convert to that god’s faith:

λ = β * I * (1 - r/R) * (1 - d)
P(convert) = 1 - exp(-λ * Δt)


- β = 1.2 per second  
- I = beam intensity (0 to 1)  
- r = distance from beam center  
- R = beam radius (6 units)  
- d = creature’s current devotion  
Conversion probability increases for closer, less devoted creatures.

---

## LLM Integration

Each god uses a PHP backend script to interact with a different large language model API.  
The script sends the current world state as JSON and receives a move in the following format:

```json
{"action":"Bless","x":-5,"y":12}
```

Actions are executed in the simulation as follows:
| Action     | Description               |
| ---------- | ------------------------- |
| Revelation | Converts nearby creatures |
| Bless      | Creates food items        |
| Curse      | Creates damaging hazards  |

Each god acts once every 20 seconds.
Only one LLM acts at a time, creating a rhythm of divine intervention across the simulation.

### Player Interaction

| Control    | Function                                                |
| ---------- | ------------------------------------------------------- |
| Left Click | Place food or hazard depending on mode                  |
| Spacebar   | Toggle between Good (food) and Bad (hazard)             |
| Cooldown   | 2 seconds between placements, maximum of 5 active items |

The player is designed as a minor agent of chaos.
The intention is for the player to observe rather than dominate.

### Running the project (To be confirmed)
1. Clone the repository
```
git clone https://github.com/username/origins.git
cd origins
```
2. Host the project locally using PHP (required for LLM API calls):
```
php -S localhost:8000
```
3. Open a browser and navigate to:
```
http://localhost:8000/index.php
```
4. The simulation begins automatically.
The PHP backend will contact the external APIs if valid API keys are provided in each ajaxgodX.php file.

### Directory Summary

| Directory   | Description                                   |
| ----------- | --------------------------------------------- |
| `frontend/` | Simulation logic, visuals, and user interface |
| `backend/`  | PHP scripts for LLM API interaction           |
| `assets/`   | Textures, models, and visual assets           |
| `docs/`     | Design documentation and system diagrams      |

### Artistic Context

Origins was developed as a collaborative final project for ICA 25, a creative coding course hosted in the Mathcastles Discord.
The course focuses on computation as a medium for art.
The project draws inspiration from Mathcastles: Terraforms, algorithmic world-building, and the idea of procedural cosmology.

Conceptually, the work examines how faith systems and hierarchies can emerge in code-based ecosystems.
Each LLM acts as a deity, each creature as a believer, and the player as a limited creator.
The piece asks how belief, authority, and survival intertwine in a world governed by algorithms.

### License

This project is released under a Creative Commons Attribution-NonCommercial 4.0 International License (CC BY-NC 4.0).
You may study, remix, and adapt the code for non-commercial purposes with attribution to the original authors.

### Contributors

PlayPeng – Team Lead, LLM Integration

Cyclez – Systems Design and Game Mechanics

Patti – Art and Visual Design

JJoy – Art and Visual Design, LLM Integration

RaulOnAStool – Systems Design and Game Mechanics

### Acknowledgments

Developed for ICA 25 with guidance from 0x113d (Mathcastles).
Thanks to the Mathcastles community for inspiring the exploration of computation as art.
