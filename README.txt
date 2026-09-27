================================================================
BADSHAH PROPERTY - IMPORTANT SETUP INSTRUCTIONS
================================================================

❌ ERROR: "Failed to send message"
================================================================

This error occurs when opening the HTML file directly from file explorer.

WRONG WAY (This causes the error):
❌ Double-clicking index.html
❌ Opening from: C:\wamp64\www\Badshah Property\index.html
❌ Browser shows: file:///C:/wamp64/www/Badshah%20Property/index.html

================================================================
✅ CORRECT WAY TO OPEN YOUR WEBSITE:
================================================================

1. START WAMP SERVER
   - Click WAMP icon in system tray
   - Make sure it's GREEN (running)
   - If orange/yellow, click "Start All Services"

2. OPEN IN BROWSER USING LOCALHOST:
   http://localhost/Badshah Property/index.html
   
   OR
   
   http://127.0.0.1/Badshah Property/index.html

3. Now the contact form will work and send SMS!

================================================================
STEP-BY-STEP TO FIX YOUR ERROR:
================================================================

1. ✅ Make sure WAMP is running (green icon)

2. ✅ Open browser (Chrome, Firefox, Edge)

3. ✅ Type in address bar:
   http://localhost/Badshah Property/index.html

4. ✅ Test the contact form

5. ✅ You should receive SMS on 9893372352!

================================================================
QUICK TEST TO VERIFY SMS WORKING:
================================================================

Open this URL in your browser:
http://localhost/Badshah Property/test-sms.php

Click the "Send Test SMS" button
Check your phone for test message!

================================================================
WHY THIS HAPPENS:
================================================================

- HTML opened directly (file://) cannot connect to PHP files
- PHP needs to run through a web server (WAMP/Apache)
- Using localhost allows browser to connect to PHP backend
- This is normal for any website with backend functionality

================================================================
VERIFYING WAMP IS RUNNING:
================================================================

1. Look for WAMP icon in system tray (bottom right)
2. Should be GREEN
3. If not green:
   - Left click WAMP icon
   - Click "Start All Services"
   - Wait for it to turn green

================================================================
ACCESSING YOUR WEBSITE:
================================================================

CORRECT URLs:
✅ http://localhost/Badshah Property/index.html
✅ http://127.0.0.1/Badshah Property/index.html

WRONG URLs (Don't work with PHP):
❌ file:///C:/wamp64/www/Badshah Property/index.html
❌ C:\wamp64\www\Badshah Property\index.html

================================================================
TESTING CHECKLIST:
================================================================

□ WAMP icon is GREEN
□ Open browser
□ Go to: http://localhost/Badshah Property/test-sms.php
□ Click "Send Test SMS"
□ Check phone for SMS
□ If working, go to: http://localhost/Badshah Property/index.html
□ Test contact form
□ Receive SMS on 9893372352!

================================================================
TROUBLESHOOTING:
================================================================

Problem: WAMP won't start / stays orange
Solution: 
- Check if port 80 is free
- Stop Skype or other programs using port 80
- Right-click WAMP → Tools → Check Port 80

Problem: localhost not working
Solution:
- Verify WAMP is green
- Try: http://127.0.0.1/Badshah Property/index.html
- Check Windows Firewall settings

Problem: SMS still not sending
Solution:
- Test using test-sms.php first
- Check browser console (F12) for errors
- Verify Vonage credentials in send-sms.php
- Check inquiries.json file is being created

================================================================
QUICK FIX RIGHT NOW:
================================================================

1. Close your current browser tab
2. Make sure WAMP is running (green icon)
3. Open new browser tab
4. Type: http://localhost/Badshah Property/index.html
5. Try contact form again
6. Should work! ✅

================================================================


