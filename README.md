# Attendance System Web Application

This is a modern web-based attendance and login system converted from Flutter to HTML, CSS, and JavaScript.

## Project Structure

```
Attendance-System-Web/
├── index.html          # Login page
├── home.html           # Home/Dashboard page
├── styles.css          # All styles for the application
├── script.js           # Login page functionality
├── home.js             # Home page functionality
├── assets/
│   └── images/
│       └── login_animation.svg  # Login page illustration
└── README.md           # This file
```

## Features

- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Modern UI**: Clean and professional interface with cyan/teal color scheme
- **Authentication**: Login system with form validation
- **Local Storage**: User session management using browser localStorage
- **Password Toggle**: Show/hide password functionality
- **Logout Confirmation**: Modal dialog for logout confirmation

## Setup Instructions

### 1. Configure API Endpoint

Update the API base URL in `script.js`:

```javascript
const API_BASE_URL = 'http://your-server.com/api'; // Update with your actual server URL
```

### 2. Backend API Requirements

Your backend API should provide the following endpoint:

#### Login Endpoint: `POST /login.php`

**Request:**
```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "token": "your-auth-token",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### 3. Running the Application

#### Option 1: Using XAMPP (Recommended)

1. Your files are already in the XAMPP htdocs directory
2. Start Apache server from XAMPP Control Panel
3. Open your browser and navigate to:
   - Login: `http://localhost/Attendance-System-Web/index.html`
   - Or: `http://localhost/Attendance-System-Web/`

#### Option 2: Using Live Server (VS Code)

1. Install the "Live Server" extension in VS Code
2. Right-click on `index.html` and select "Open with Live Server"

#### Option 3: Using Python HTTP Server

```bash
cd c:\xampp\htdocs\Attendance-System-Web
python -m http.server 8000
```

Then navigate to `http://localhost:8000/index.html`

## Pages Overview

### Login Page (`index.html`)

- Clean, centered login form
- Admin ID and password fields
- Form validation
- Password visibility toggle
- Loading state during login
- Error message display
- Responsive design with illustration

### Home Page (`home.html`)

- User profile display
- Welcome message with user avatar
- User information card
- Logout functionality with confirmation modal
- Protected route (redirects to login if not authenticated)

## Color Scheme

- **Primary**: `#00ACC1` (Cyan)
- **Secondary**: `#7DE3D9` (Teal/Mint)
- **Background**: `#E8F4F8` (Light Blue)
- **Text**: `#000000de` (Dark Gray)

## Browser Compatibility

- Chrome/Edge (Latest)
- Firefox (Latest)
- Safari (Latest)
- Mobile browsers

## Security Notes

1. **HTTPS**: Use HTTPS in production for secure data transmission
2. **Token Storage**: Currently using localStorage for simplicity. Consider more secure options for production
3. **API Keys**: Never hardcode sensitive information in the frontend
4. **CORS**: Ensure your backend API allows requests from your domain

## Customization

### Changing Colors

Edit the color values in `styles.css`:

```css
/* Primary color */
background-color: #00ACC1;

/* Button color */
background-color: #7DE3D9;

/* Background color */
background-color: #E8F4F8;
```

### Adding New Pages

1. Create new HTML file
2. Link `styles.css` and create corresponding JS file
3. Add authentication check if needed:

```javascript
function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = 'index.html';
    }
}
```

## Troubleshooting

### Issue: Login button not working

- Check browser console for errors
- Verify API_BASE_URL is correct
- Ensure backend server is running
- Check CORS settings on backend

### Issue: Images not loading

- Verify the SVG file exists in `assets/images/`
- Check file path in HTML is correct
- Clear browser cache

### Issue: Redirecting to login after successful login

- Check localStorage in browser DevTools
- Verify token and user data are being saved
- Check for JavaScript errors in console

## Development

To modify the application:

1. **HTML**: Edit `index.html` and `home.html` for structure
2. **Styles**: Edit `styles.css` for appearance
3. **Logic**: Edit `script.js` and `home.js` for functionality

## Migration from Flutter

This application was converted from a Flutter project and maintains the same:
- Visual design and layout
- Color scheme
- User flow and navigation
- Authentication logic

## License

This project is open source and available for modification.

## Support

For issues or questions, please refer to the original Flutter application documentation or contact your development team.
