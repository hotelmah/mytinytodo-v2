<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello World</title>
    <style>
        p {
            width: 40vw;
            padding: 15px;

            display: block;

            color: blueviolet;
            font-size: larger;
            font-weight: 800;
        }

        body {
            width: 90vw;
            height: 90vh;
            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: center;

            background-color: lightpink;
        }

        button {
            margin-top: 2.5em;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        div {
            margin: 0 auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <p>
        <svg width="800" height="800" viewBox="0 0 240 240" xmlns="http://www.w3.org/2000/svg">
            <!-- Face with subtle animated blush -->
            <ellipse cx="120" cy="120" rx="90" ry="70" fill="#fff" stroke="#000" stroke-width="4"/>
            <!-- Left Ear -->
            <polygon points="35,70 50,20 80,80" fill="#fffbe7" stroke="#000" stroke-width="4"/>
            <!-- Right Ear -->
            <polygon points="205,70 190,20 160,80" fill="#fffbe7" stroke="#000" stroke-width="4"/>
            <!-- Left Eye -->
            <ellipse cx="80" cy="120" rx="10" ry="16" fill="#222"/>
            <!-- Right Eye -->
            <ellipse cx="160" cy="120" rx="10" ry="16" fill="#222"/>
            <!-- Nose -->
            <ellipse cx="120" cy="145" rx="9" ry="7" fill="#FEC366" stroke="#000" stroke-width="2"/>
            <!-- Whiskers Left -->
            <line x1="35" y1="130" x2="75" y2="130" stroke="#9e4f27" stroke-width="3"/>
            <line x1="35" y1="145" x2="75" y2="140" stroke="#9e4f27" stroke-width="3"/>
            <line x1="35" y1="115" x2="75" y2="120" stroke="#9e4f27" stroke-width="3"/>
            <!-- Whiskers Right -->
            <line x1="205" y1="130" x2="165" y2="130" stroke="#9e4f27" stroke-width="3"/>
            <line x1="205" y1="145" x2="165" y2="140" stroke="#9e4f27" stroke-width="3"/>
            <line x1="205" y1="115" x2="165" y2="120" stroke="#9e4f27" stroke-width="3"/>
            <!-- Bow (animated color pulse) -->
            <ellipse id="bow-center" cx="120" cy="60" rx="13" ry="8" fill="#ec407a" stroke="#b71c50" stroke-width="2">
                <animate attributeName="fill" values="#ec407a;#ffd600;#ec407a" dur="3s" repeatCount="indefinite"/>
            </ellipse>
            <ellipse cx="104" cy="60" rx="8" ry="13" fill="#f06292" stroke="#b71c50" stroke-width="2">
                <animate attributeName="fill" values="#f06292;#ffd600;#f06292" dur="3s" repeatCount="indefinite"/>
            </ellipse>
            <ellipse cx="136" cy="60" rx="8" ry="13" fill="#f06292" stroke="#b71c50" stroke-width="2">
                <animate attributeName="fill" values="#f06292;#ffd600;#f06292" dur="3s" repeatCount="indefinite"/>
            </ellipse>
            <circle cx="120" cy="60" r="6" fill="#fff" stroke="#b71c50" stroke-width="2"/>
            <!-- Animated Blush (left) -->
            <ellipse cx="80" cy="165" rx="13" ry="6" fill="#ffb6c1">
                <animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite"/>
            </ellipse>
            <!-- Animated Blush (right) -->
            <ellipse cx="160" cy="165" rx="13" ry="6" fill="#ffb6c1">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
            </ellipse>
            <!-- Waving Paw (animated up/down) -->
            <g>
                <ellipse id="paw" cx="200" cy="180" rx="22" ry="16" fill="#fff" stroke="#000" stroke-width="3">
                <animate attributeName="cy" values="180;165;180" dur="1.5s" repeatCount="indefinite"/>
                </ellipse>
                <circle cx="200" cy="180" r="6" fill="#f9a825">
                <animate attributeName="cy" values="180;165;180" dur="1.5s" repeatCount="indefinite"/>
                </circle>
            </g>
            <!-- Shirt (colored) -->
            <rect x="80" y="180" width="80" height="40" rx="20" fill="#90caf9" stroke="#1565c0" stroke-width="3"/>
            <!-- Heart floating up (animated) -->
            <g>
                <path id="heart" d="M120,200
                C116,190 105,192 105,200
                C105,210 120,215 120,215
                C120,215 135,210 135,200
                C135,192 124,190 120,200
                Z"
                fill="#ef5350">
                <animateTransform attributeName="transform" type="translate" from="0 0" to="0 -30" dur="2.5s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="1;0.8;0.4;0" dur="2.5s" repeatCount="indefinite"/>
                </path>
            </g>
        </svg>
    </p>
    <div>
        <p>
            ~ Hello World! ~
        </p>
        <p>
            <?= $name ?>
        </p>
        <button onclick="history.back()">Go Back</button>
    </div>
</body>
</html>