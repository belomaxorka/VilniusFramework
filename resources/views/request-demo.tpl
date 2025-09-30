<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Collector Demo</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-top: 0;
            font-size: 2em;
        }
        h2 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-weight: bold;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .link-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .link-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .link-buttons a:hover {
            background: #218838;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #667eea;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .emoji {
            font-size: 1.5em;
            margin-right: 10px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .grid-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .grid-item strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><span class="emoji">🌐</span>Request Collector Demo</h1>
            
            <div class="info">
                <strong>ℹ️ Информация:</strong> Эта страница демонстрирует работу Request Collector 
                в Debug Toolbar. Попробуйте разные типы запросов и посмотрите, как они отображаются 
                в debug панели внизу страницы.
            </div>

            {% if request_info %}
            <div class="grid">
                <div class="grid-item">
                    <strong>Method:</strong> {{ request_info.method }}
                </div>
                <div class="grid-item">
                    <strong>URI:</strong> {{ request_info.uri }}
                </div>
                <div class="grid-item">
                    <strong>IP:</strong> {{ request_info.ip }}
                </div>
                <div class="grid-item">
                    <strong>Time:</strong> {{ request_info.time }}
                </div>
            </div>
            {% endif %}
        </div>

        <!-- GET Request Demo -->
        <div class="card">
            <h2>📥 GET Requests</h2>
            <p>Нажмите на ссылки ниже, чтобы отправить GET запросы с различными параметрами:</p>
            
            <div class="link-buttons">
                <a href="?page=1&limit=10">Page 1, Limit 10</a>
                <a href="?search=test&filter=active&sort=name">Search with Filters</a>
                <a href="?id=123&action=view&debug=true">ID 123 Debug</a>
                <a href="?category=php&tags=framework,debug,tools">With Tags</a>
            </div>
        </div>

        <!-- POST Request Demo -->
        <div class="card">
            <h2>📤 POST Request Form</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" placeholder="Enter your message here..."></textarea>
                </div>

                <div class="form-group">
                    <label for="file">Upload File:</label>
                    <input type="file" id="file" name="uploaded_file">
                </div>

                <div class="form-group">
                    <label for="files">Upload Multiple Files:</label>
                    <input type="file" id="files" name="uploaded_files[]" multiple>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="subscribe" value="1"> Subscribe to newsletter
                    </label>
                    <label>
                        <input type="checkbox" name="agree" value="1"> I agree to terms
                    </label>
                </div>

                <button type="submit">Submit POST Request</button>
            </form>

            {% if post_data %}
            <div class="info" style="margin-top: 20px;">
                <strong>✅ POST данные получены!</strong> Проверьте Request Collector в Debug Toolbar.
            </div>
            {% endif %}
        </div>

        <!-- Cookies Demo -->
        <div class="card">
            <h2>🍪 Cookies</h2>
            <p>Установите cookie и посмотрите его в Request Collector:</p>
            <form method="GET" action="">
                <input type="hidden" name="set_cookie" value="1">
                <button type="submit">Set Demo Cookie</button>
            </form>
            
            {% if cookies_set %}
            <div class="info" style="margin-top: 20px;">
                <strong>✅ Cookie установлен!</strong> Перезагрузите страницу, чтобы увидеть его в Request Collector.
            </div>
            {% endif %}
        </div>

        <!-- Headers Demo -->
        <div class="card">
            <h2>📋 Custom Headers</h2>
            <p>Request Collector автоматически собирает все HTTP заголовки запроса. 
            Попробуйте отправить запрос с кастомными заголовками через cURL:</p>
            
            <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 6px; overflow-x: auto;">
<code>curl -H "X-Custom-Header: test" \
     -H "X-API-Key: your-api-key" \
     http://localhost:8000/</code></pre>
        </div>

        <!-- Tips -->
        <div class="card">
            <h2>💡 Советы</h2>
            <ul>
                <li>Откройте <strong>Debug Toolbar</strong> внизу страницы</li>
                <li>Перейдите на вкладку <strong>🌐 Request</strong></li>
                <li>Исследуйте все секции: GET, POST, Headers, Cookies, Server Variables</li>
                <li>Попробуйте разные типы запросов и параметров</li>
                <li>Обратите внимание на цветовую кодировку HTTP методов</li>
            </ul>
        </div>
    </div>

    <script>
        // Set cookie via JavaScript for demo
        if (!document.cookie.includes('demo_cookie')) {
            document.cookie = 'demo_cookie=javascript_value; path=/';
        }
    </script>
</body>
</html>

