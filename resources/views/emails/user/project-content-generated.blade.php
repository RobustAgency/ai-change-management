<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Project Content is Ready!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background-color: #ffffff;
            color: #333;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .header .icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .main-message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.7;
        }
        
        .project-name {
            color: #667eea;
            font-weight: 600;
        }
        
        .content-list {
            background-color: #f7fafc;
            border-radius: 6px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
        }
        
        .content-list h3 {
            margin: 0 0 15px 0;
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
        }
        
        .content-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
            color: #4a5568;
        }
        
        .content-item:last-child {
            margin-bottom: 0;
        }
        
        .content-item .icon {
            width: 20px;
            height: 20px;
            background-color: #48bb78;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 30px 0;
            transition: transform 0.2s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .footer-message {
            font-size: 15px;
            color: #718096;
            margin: 30px 0 20px 0;
            line-height: 1.6;
        }
        
        .footer {
            background-color: #edf2f7;
            padding: 30px;
            text-align: center;
            color: #718096;
            font-size: 14px;
        }
        
        .footer .logo {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .content-list {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
             <img src="{{ url('logo.png') }}" alt="Innovative Dialogues® Logo" style="max-width:220px;height:auto;">
        </div>
        
        <div class="content">
            <h1 style="text-align: center; font-size: 28px; font-weight: 600; color: #2d3748; margin: 0 0 30px 0;">Content Ready!</h1>
            
            <div class="greeting">
                Hello {{ $user->name }}!
            </div>
            
            <div class="main-message">
                Great news! The AI-generated content for your project 
                <span class="project-name">"{{ $project->name }}"</span> 
                has been successfully generated and is ready for your review.
            </div>
            
            <div class="main-message">
                <a href="{{ env('FRONTEND_URL') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">Login to your account</a> to access your project content.
            </div>
            
            <div class="content-list">
                <h3>Your project now includes:</h3>
                <div class="content-item">
                    <span class="icon">✓</span>
                    <span>Professional presentation slides</span>
                </div>
                <div class="content-item">
                    <span class="icon">✓</span>
                    <span>Customizable email templates</span>
                </div>
                <div class="content-item">
                    <span class="icon">✓</span>
                    <span>Comprehensive FAQ section</span>
                </div>
                <div class="content-item">
                    <span class="icon">✓</span>
                    <span>Engaging video script</span>
                </div>
            </div>
            
            <div class="footer-message">
                Each asset has been crafted specifically based on your project requirements and input. You may now, review, customize, and implement the generated content to ensure it aligns with your specific change management or communications objectives.
            </div>
        </div>
        
        <div class="footer">
            <div class="logo">Innovative Dialogs®</div>
            <p>Transforming organizations through intelligent change management</p>
            <p>©  {{ date('Y') }} Life Vision, LLC – Innovative Dialogs®.  All rights reserved.</p>
        </div>
    </div>
</body>
</html>