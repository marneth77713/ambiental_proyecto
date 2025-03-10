from flask import Flask, send_from_directory, redirect, request
import os
import subprocess
import logging

# Configurar logging
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger(__name__)

app = Flask(__name__)

@app.route('/', defaults={'path': ''})
@app.route('/<path:path>')
def serve_php(path):
    """Serve PHP files through a PHP built-in server"""
    try:
        # If no specific path, default to index.php
        if not path:
            path = 'index.php'
        
        # Handle static files (CSS, JS, images)
        if path.startswith(('css/', 'js/', 'img/', 'assets/')):
            directory = os.path.dirname(path)
            filename = os.path.basename(path)
            return send_from_directory(directory, filename)
            
        # Only proceed if file exists
        if not os.path.exists(path) and not path.endswith('.php'):
            path = path + '.php'
            if not os.path.exists(path):
                logger.error(f"File not found: {path}")
                return f"File not found: {path}", 404
        
        # Create environment with all request headers and server variables
        env = dict(os.environ)
        env['REQUEST_URI'] = request.full_path
        env['QUERY_STRING'] = request.query_string.decode('utf-8')
        env['REQUEST_METHOD'] = request.method

        # Add headers to environment
        for header, value in request.headers:
            header_name = 'HTTP_' + header.upper().replace('-', '_')
            env[header_name] = value
                
        # Execute PHP script
        logger.info(f"Executing PHP script: {path}")
        php_process = subprocess.run(
            ['php', path],
            capture_output=True,
            text=True,
            env=env
        )
        
        # Log errors if any
        if php_process.stderr:
            logger.error(f"PHP Error: {php_process.stderr}")
        
        # Return the output
        return php_process.stdout
    except Exception as e:
        logger.exception(f"Error executing PHP: {str(e)}")
        return f"<h1>Error executing PHP</h1><pre>{str(e)}</pre>", 500

@app.route('/database/<path:filename>')
def protected_files(filename):
    """Block direct access to database files"""
    return "Access denied", 403

if __name__ == "__main__":
    # Make sure database directory exists
    if not os.path.exists('database'):
        os.makedirs('database')
        
    # Make sure img/plantillas directory exists for template previews
    if not os.path.exists('img/plantillas'):
        os.makedirs('img/plantillas')
        
    app.run(host="0.0.0.0", port=5000, debug=True)