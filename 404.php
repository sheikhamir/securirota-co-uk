<?php
// Set the HTTP status code to 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - App Moved</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Grotesk:wght@300;400;500;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: #fff;
            text-align: center;
            padding: 0;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 60px 40px;
            border-radius: 30px;
            box-shadow: 
                0 8px 32px rgba(31, 38, 135, 0.37),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            position: relative;
            z-index: 2;
            animation: containerFloat 6s ease-in-out infinite;
            transform-style: preserve-3d;
        }
        
        @keyframes containerFloat {
            0%, 100% { transform: translateY(0px) rotateX(0deg); }
            50% { transform: translateY(-20px) rotateX(2deg); }
        }
        
        .glitch-wrapper {
            position: relative;
            display: inline-block;
        }
        
        h1 {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 5em;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            animation: pulse 2s infinite, neonGlow 3s ease-in-out infinite alternate;
            position: relative;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes neonGlow {
            from {
                text-shadow: 
                    0 0 5px #fff,
                    0 0 10px #fff,
                    0 0 15px #f093fb,
                    0 0 20px #f093fb,
                    0 0 35px #f093fb,
                    0 0 40px #f093fb;
            }
            to {
                text-shadow: 
                    0 0 2px #fff,
                    0 0 5px #667eea,
                    0 0 8px #667eea,
                    0 0 12px #667eea,
                    0 0 18px #667eea,
                    0 0 25px #667eea;
            }
        }
        
        .glitch {
            position: relative;
        }
        
        .glitch::before,
        .glitch::after {
            content: "App has been moved";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 1em;
        }
        
        .glitch::before {
            animation: glitch1 2s infinite;
            color: #f093fb;
            z-index: -1;
        }
        
        .glitch::after {
            animation: glitch2 2s infinite;
            color: #667eea;
            z-index: -2;
        }
        
        @keyframes glitch1 {
            0%, 14%, 15%, 49%, 50%, 99%, 100% {
                transform: translate3d(0, 0, 0);
            }
            1%, 13% {
                transform: translate3d(-2px, 0, 0);
            }
            16%, 48% {
                transform: translate3d(2px, 0, 0);
            }
        }
        
        @keyframes glitch2 {
            0%, 20%, 21%, 62%, 63%, 99%, 100% {
                transform: translate3d(0, 0, 0);
            }
            2%, 19% {
                transform: translate3d(2px, 0, 0);
            }
            22%, 61% {
                transform: translate3d(-2px, 0, 0);
            }
        }
        
        h2 {
            font-weight: 600;
            font-size: 2em;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.9);
            animation: fadeInUp 1s ease-out 0.5s both;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        p {
            font-size: 1.2em;
            line-height: 1.8;
            margin-bottom: 25px;
            color: rgba(255, 255, 255, 0.85);
            animation: fadeInUp 1s ease-out 0.7s both;
            font-weight: 300;
        }
        
        .email-container {
            animation: fadeInUp 1s ease-out 1s both;
            margin: 40px 0;
        }
        
        .email {
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.3em;
            padding: 15px 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            display: inline-block;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .email::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .email:hover::before {
            left: 100%;
        }
        
        .email:hover {
            transform: translateY(-5px) scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 
                0 10px 30px rgba(255, 255, 255, 0.2),
                0 0 20px rgba(255, 255, 255, 0.1);
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
        }
        
        .icon-container {
            margin-bottom: 30px;
            animation: fadeInDown 1s ease-out 0.3s both;
        }
        
        .truck-icon {
            font-size: 6em;
            animation: truckMove 4s ease-in-out infinite;
            display: inline-block;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
        }
        
        @keyframes truckMove {
            0%, 100% { 
                transform: translateX(-20px) rotate(-2deg); 
            }
            50% { 
                transform: translateX(20px) rotate(2deg); 
            }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: particleFloat 8s infinite linear;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 40px 20px;
            }
            
            h1 {
                font-size: 3.5em;
            }
            
            h2 {
                font-size: 1.5em;
            }
            
            p {
                font-size: 1em;
            }
            
            .truck-icon {
                font-size: 4em;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background elements -->
    <div class="stars" id="stars"></div>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="icon-container">
            <div class="truck-icon">🚚</div>
        </div>
        
        <div class="glitch-wrapper">
            <h1 class="glitch">App has been moved</h1>
        </div>
        
        <p>The application that was previously hosted on this URL has been moved to a new location.</p>
        
        <p>If you're looking for the SecuriRota application or need assistance accessing your account, please contact us at:</p>
        
        <div class="email-container">
            <a href="mailto:info@securirota.com" class="email">info@securirota.com</a>
        </div>
        
        <p>We apologize for any inconvenience and will be happy to help you find what you're looking for.</p>
    </div>

    <script>
        // Create animated stars
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const numberOfStars = 100;
            
            for (let i = 0; i < numberOfStars; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.width = Math.random() * 3 + 1 + 'px';
                star.style.height = star.style.width;
                star.style.animationDelay = Math.random() * 3 + 's';
                star.style.animationDuration = (Math.random() * 3 + 2) + 's';
                starsContainer.appendChild(star);
            }
        }
        
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            
            setInterval(() => {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDuration = (Math.random() * 5 + 5) + 's';
                
                particlesContainer.appendChild(particle);
                
                // Remove particle after animation
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.parentNode.removeChild(particle);
                    }
                }, 10000);
            }, 500);
        }
        
        // Mouse movement parallax effect
        document.addEventListener('mousemove', (e) => {
            const container = document.querySelector('.container');
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;
            
            container.style.transform = `translateY(-20px) rotateX(2deg) rotateY(${x * 0.1}deg) rotateX(${y * 0.1}deg)`;
        });
        
        // Initialize animations
        document.addEventListener('DOMContentLoaded', () => {
            createStars();
            createParticles();
        });
        
        // Add click effect to container
        document.querySelector('.container').addEventListener('click', function() {
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = 'containerFloat 6s ease-in-out infinite';
            }, 100);
        });
    </script>
</body>
</html>