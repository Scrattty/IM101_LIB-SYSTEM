document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM Content Loaded - Auth.js initialized');
  
  // Tab switching
  const authTabs = document.querySelectorAll('.auth-tab');
  const authForms = document.querySelectorAll('.auth-form');
  
  authTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      // Remove active class from all tabs and forms
      authTabs.forEach(t => t.classList.remove('active'));
      authForms.forEach(f => f.classList.remove('active'));
      
      // Add active class to clicked tab
      tab.classList.add('active');
      
      // Show corresponding form
      const formId = `${tab.dataset.tab}-form`;
      document.getElementById(formId).classList.add('active');
    });
  });
  
  // Show/hide student fields based on role selection
  const registerRole = document.getElementById('register-role');
  const studentFields = document.querySelectorAll('.student-fields');
  
  if (registerRole) {
    registerRole.addEventListener('change', function() {
      if (this.value === 'student') {
        studentFields.forEach(field => field.classList.remove('hidden'));
      } else {
        studentFields.forEach(field => field.classList.add('hidden'));
      }
    });
  }
  
  // Login form validation - only for ID field
  const loginIdInput = document.getElementById('login-id');
  if (loginIdInput) {
    // Only allow numbers and hyphens in login ID
    loginIdInput.addEventListener('keydown', function(e) {
      // Allow: backspace, delete, tab, escape, enter
      if ([8, 9, 13, 46].includes(e.keyCode) || 
          // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
          (e.keyCode >= 35 && e.keyCode <= 39) ||
          (e.ctrlKey && [65, 67, 86, 88].includes(e.keyCode))) {
        return;
      }
      
      // Allow only numbers and hyphen
      if (!/^[0-9\-]$/.test(e.key)) {
        e.preventDefault();
        showValidationMessage(this, 'Only numbers and hyphens (-) are allowed');
      }
    });

    // Prevent paste of invalid characters
    loginIdInput.addEventListener('paste', function(e) {
      const pastedText = (e.clipboardData || window.clipboardData).getData('text');
      if (/[^0-9\-]/.test(pastedText)) {
        e.preventDefault();
        showValidationMessage(this, 'Pasted text contains invalid characters. Only numbers and hyphens are allowed.');
        const cleanText = pastedText.replace(/[^0-9\-]/g, '');
        document.execCommand('insertText', false, cleanText);
      }
    });
  }

  // Login form submit
  const loginBtn = document.getElementById('login-btn');
  if (loginBtn) {
    loginBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      console.log('=== Login Attempt Started ===');
      
      // Get form values
      const roleSelect = document.getElementById('login-role');
      const idInput = document.getElementById('login-id');
      const passwordInput = document.getElementById('login-password');
      
      // Log form elements
      console.log('Form Elements:', {
        roleSelect: roleSelect ? {
          id: roleSelect.id,
          value: roleSelect.value,
          selectedIndex: roleSelect.selectedIndex
        } : 'not found',
        idInput: idInput ? {
          id: idInput.id,
          value: idInput.value
        } : 'not found',
        passwordInput: passwordInput ? {
          id: passwordInput.id,
          value: passwordInput.value ? '***' : 'empty'
        } : 'not found'
      });
      
      const role = roleSelect ? roleSelect.value : null;
      const id = idInput ? idInput.value : null;
      const password = passwordInput ? passwordInput.value : null;
      
      // Validate inputs
      if (!role) {
        console.log('Validation failed: Role is empty');
        showValidationMessage(roleSelect, 'Please select your role');
        return;
      }
      
      if (!id) {
        console.log('Validation failed: ID is empty');
        showValidationMessage(idInput, 'Please enter your ID number');
        return;
      }
      
      if (!password) {
        console.log('Validation failed: Password is empty');
        showValidationMessage(passwordInput, 'Please enter your password');
        return;
      }

      const loginData = {
        role: role,
        student_employee_id: id,
        password: password
      };

      console.log('Login Data (password masked):', {
        ...loginData,
        password: '***'
      });

      try {
        console.log('Sending request to:', 'api/auth/login.php');
        const requestOptions = {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(loginData)
        };
        
        console.log('Request Options:', {
          method: requestOptions.method,
          headers: requestOptions.headers,
          body: JSON.stringify({
            ...loginData,
            password: '***'
          })
        });

        const response = await fetch('api/auth/login.php', requestOptions);
        console.log('Response Status:', response.status);
        console.log('Response Headers:', Object.fromEntries(response.headers.entries()));
        
        const responseText = await response.text();
        console.log('Raw Response:', responseText);
        
        let data;
        try {
          data = JSON.parse(responseText);
          console.log('Parsed Response:', data);
        } catch (e) {
          console.error('Error parsing response:', e);
          console.error('Raw response that caused error:', responseText);
          showValidationMessage(passwordInput, 'Invalid server response');
          return;
        }
        
        if (data.status === 'success') {
          console.log('Login successful, redirecting...');
          localStorage.setItem('currentUser', JSON.stringify(data.user));
          
          if (data.user.role === 'admin') {
            window.location.href = '/LIB/admin/dashboard.html';
          } else {
            // Both faculty and students go to student dashboard
            window.location.href = '/LIB/student/dashboard.html';
          }
        } else {
          console.log('Login failed:', data.message);
          if (data.message.includes('role')) {
            showValidationMessage(roleSelect, data.message);
          } else if (data.message.includes('password')) {
            showValidationMessage(passwordInput, data.message);
          } else {
            showValidationMessage(idInput, data.message);
          }
        }
      } catch (error) {
        console.error('Login error:', error);
        showValidationMessage(passwordInput, 'An error occurred during login');
      }
    });
  }
  
  // Validation functions
  function validateName(name) {
    console.log('Validating name:', name);
    const isValid = /^[A-Za-z\s]+$/.test(name);
    console.log('Name validation result:', isValid);
    return isValid;
  }

  function validateStudentId(id) {
    console.log('Validating student ID:', id);
    const isValid = /^[0-9\-]+$/.test(id);
    console.log('Student ID validation result:', isValid);
    return isValid;
  }

  function validatePassword(password) {
    console.log('Validating password');
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    const isValid = password.length >= minLength && 
           hasUpperCase && 
           hasLowerCase && 
           hasNumbers && 
           hasSpecialChar;
    
    console.log('Password validation details:', {
      length: password.length >= minLength,
      hasUpperCase,
      hasLowerCase,
      hasNumbers,
      hasSpecialChar,
      isValid
    });
    
    return isValid;
  }

  // Function to show validation message
  function showValidationMessage(inputElement, message) {
    // Remove any existing message
    const existingMessage = inputElement.parentElement.querySelector('.validation-message');
    if (existingMessage) {
      existingMessage.remove();
    }

    // Add error class to input
    inputElement.classList.add('error');

    // Create and show new message
    const messageElement = document.createElement('div');
    messageElement.className = 'validation-message';
    messageElement.textContent = message;
    
    // Add the message after the input
    inputElement.parentElement.appendChild(messageElement);
    
    // Remove message and error class after 3 seconds
    setTimeout(() => {
      messageElement.remove();
      inputElement.classList.remove('error');
    }, 3000);
  }

  // Function to calculate password strength
  function calculatePasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Character type checks
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    // Additional complexity
    if (password.length >= 16) strength += 1;
    if (/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9])/.test(password)) strength += 1;
    
    return Math.min(strength, 4); // Cap at 4 for very strong
  }

  // Prevent invalid characters from being typed
  const nameInputs = ['register-lastname', 'register-firstname', 'register-middlename'];
  nameInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      // Prevent typing numbers and special characters
      input.addEventListener('keydown', function(e) {
        // Allow: backspace, delete, tab, escape, enter, space
        if ([8, 9, 13, 32, 46].includes(e.keyCode) || 
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode >= 35 && e.keyCode <= 39) ||
            (e.ctrlKey && [65, 67, 86, 88].includes(e.keyCode))) {
          return;
        }
        
        // Allow only letters
        if (!/^[a-zA-Z\s]$/.test(e.key)) {
          e.preventDefault();
          showValidationMessage(this, 'Only letters are allowed, no numbers or special characters');
        }
      });

      // Prevent paste of invalid characters
      input.addEventListener('paste', function(e) {
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        if (/[^a-zA-Z\s]/.test(pastedText)) {
          e.preventDefault();
          showValidationMessage(this, 'Pasted text contains invalid characters. Only letters are allowed.');
          const cleanText = pastedText.replace(/[^a-zA-Z\s]/g, '');
          document.execCommand('insertText', false, cleanText);
        }
      });
    }
  });

  // Student ID field - only allow numbers and hyphen
  const studentIdInput = document.getElementById('register-id');
  if (studentIdInput) {
    studentIdInput.addEventListener('keydown', function(e) {
      // Allow: backspace, delete, tab, escape, enter
      if ([8, 9, 13, 46].includes(e.keyCode) || 
          // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
          (e.keyCode >= 35 && e.keyCode <= 39) ||
          (e.ctrlKey && [65, 67, 86, 88].includes(e.keyCode))) {
        return;
      }
      
      // Allow only numbers and hyphen
      if (!/^[0-9\-]$/.test(e.key)) {
        e.preventDefault();
        showValidationMessage(this, 'Only numbers and hyphens (-) are allowed');
      }
    });

    // Prevent paste of invalid characters
    studentIdInput.addEventListener('paste', function(e) {
      const pastedText = (e.clipboardData || window.clipboardData).getData('text');
      if (/[^0-9\-]/.test(pastedText)) {
        e.preventDefault();
        showValidationMessage(this, 'Pasted text contains invalid characters. Only numbers and hyphens are allowed.');
        const cleanText = pastedText.replace(/[^0-9\-]/g, '');
        document.execCommand('insertText', false, cleanText);
      }
    });
  }

  // Password field - show requirements and strength in real-time
  const passwordInput = document.getElementById('register-password');
  if (passwordInput) {
    const requirements = {
      length: false,
      uppercase: false,
      lowercase: false,
      number: false,
      special: false
    };

    // Create requirements message element
    const requirementsMessage = document.createElement('div');
    requirementsMessage.className = 'password-requirements';
    passwordInput.parentElement.appendChild(requirementsMessage);

    // Create password strength indicator
    const strengthContainer = document.createElement('div');
    strengthContainer.className = 'password-strength';
    const strengthBar = document.createElement('div');
    strengthBar.className = 'password-strength-bar';
    strengthContainer.appendChild(strengthBar);
    passwordInput.parentElement.insertBefore(strengthContainer, requirementsMessage);

    passwordInput.addEventListener('input', function() {
      const password = this.value;
      
      // Update requirements status
      requirements.length = password.length >= 8;
      requirements.uppercase = /[A-Z]/.test(password);
      requirements.lowercase = /[a-z]/.test(password);
      requirements.number = /\d/.test(password);
      requirements.special = /[!@#$%^&*(),.?":{}|<>]/.test(password);

      // Calculate and update password strength
      const strength = calculatePasswordStrength(password);
      strengthBar.className = 'password-strength-bar';
      if (strength > 0) {
        const strengthClasses = ['weak', 'medium', 'strong', 'very-strong'];
        strengthBar.classList.add(strengthClasses[strength - 1]);
      }

      // Update requirements message
      const requirementsList = [
        { met: requirements.length, text: 'At least 8 characters' },
        { met: requirements.uppercase, text: 'At least 1 uppercase letter' },
        { met: requirements.lowercase, text: 'At least 1 lowercase letter' },
        { met: requirements.number, text: 'At least 1 number' },
        { met: requirements.special, text: 'At least 1 special character' }
      ];

      requirementsMessage.innerHTML = requirementsList
        .map(req => `<div class="${req.met ? 'requirement-met' : 'requirement-unmet'}">${req.text}</div>`)
        .join('');

      // Update custom validity
      const missingRequirements = requirementsList
        .filter(req => !req.met)
        .map(req => req.text);

      if (missingRequirements.length > 0) {
        this.setCustomValidity('Password must contain: ' + missingRequirements.join(', '));
        this.classList.add('error');
      } else {
        this.setCustomValidity('');
        this.classList.remove('error');
      }
    });
  }

  // Add hover effect to form controls
  const formControls = document.querySelectorAll('.form-control');
  formControls.forEach(control => {
    control.addEventListener('mouseenter', function() {
      if (!this.classList.contains('error')) {
        this.style.borderColor = '#cbd5e1';
        this.style.backgroundColor = '#f8fafc';
      }
    });

    control.addEventListener('mouseleave', function() {
      if (!this.classList.contains('error')) {
        this.style.borderColor = '#e2e8f0';
        this.style.backgroundColor = '#fff';
      }
    });
  });

  // Registration form submit
  const registerBtn = document.getElementById('register-btn');
  console.log('Setting up register button handler:', registerBtn);
  if (registerBtn) {
    registerBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      console.log('Register button clicked');
      
      const role = document.getElementById('register-role').value;
      const lastName = document.getElementById('register-lastname').value;
      const firstName = document.getElementById('register-firstname').value;
      const middleName = document.getElementById('register-middlename').value;
      const email = document.getElementById('register-email').value;
      const id = document.getElementById('register-id').value;
      const password = document.getElementById('register-password').value;
      const confirmPassword = document.getElementById('register-confirm').value;
      
      console.log('Form values:', {
        role,
        lastName,
        firstName,
        middleName,
        email,
        id,
        password: '***' // Don't log actual password
      });
      
      // Validate inputs
      if (!role || !lastName || !firstName || !email || !id || !password || !confirmPassword) {
        console.log('Validation failed: Missing required fields');
        showValidationMessage(document.getElementById('register-btn'), 'Please fill in all required fields');
        return;
      }

      if (password !== confirmPassword) {
        console.log('Validation failed: Passwords do not match');
        showValidationMessage(document.getElementById('register-confirm'), 'Passwords do not match');
        return;
      }

      // Additional validation for student role
      let course = '', year = '', section = '';
      if (role === 'student') {
        course = document.getElementById('register-course').value;
        year = document.getElementById('register-year').value;
        section = document.getElementById('register-section').value;
        
        if (!course || !year || !section) {
          console.log('Validation failed: Missing student fields');
          showValidationMessage(document.getElementById('register-btn'), 'Please fill in all student-specific fields');
          return;
        }
      }

      // Email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        console.log('Validation failed: Invalid email format');
        showValidationMessage(document.getElementById('register-email'), 'Please enter a valid email address');
        return;
      }

      const requestData = {
        role: role,
        student_employee_id: id,
        last_name: lastName,
        first_name: firstName,
        middle_name: middleName,
        email: email,
        password: password,
        course: course,
        year_level: year,
        section: section
      };

      console.log('Sending registration request to server...');
      try {
        const response = await fetch('api/auth/register.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(requestData)
        });

        console.log('Server response status:', response.status);
        const data = await response.json();
        console.log('Server response data:', data);
        
        if (data.status === 'success') {
          console.log('Registration successful');
          alert('Registration successful! Please login with your credentials.');
          // Switch to login tab
          document.querySelector('.auth-tab[data-tab="login"]').click();
        } else {
          console.log('Registration failed:', data.message);
          showValidationMessage(document.getElementById('register-btn'), data.message);
        }
      } catch (error) {
        console.error('Registration error:', error);
        showValidationMessage(document.getElementById('register-btn'), 'An error occurred during registration');
      }
    });
  }
});