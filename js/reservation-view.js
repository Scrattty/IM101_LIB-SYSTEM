document.addEventListener('DOMContentLoaded', function() {
  // Get current user from localStorage
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  if (!currentUser) {
    // Redirect to login if no user is logged in
    window.location.href = '../index.html';
    return;
  }

  // Update user name in the header
  const userNameElements = document.querySelectorAll('#user-name');
  userNameElements.forEach(element => {
    element.textContent = currentUser.name || 'User';
  });
  
  // Update profile image if exists
  const userProfileImg = document.getElementById('user-profile-img');
  if (currentUser.profileImage && userProfileImg) {
    userProfileImg.src = currentUser.profileImage;
  }

  // Load user's reservations
  loadUserReservations();

  // Set up status filters
  setupStatusFilters();

  // Set up event listeners for modals
  setupModals();

  // Handle logout
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      localStorage.removeItem('currentUser');
      window.location.href = '../index.html';
    });
  }
});

// Function to load user's reservations
function loadUserReservations() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  if (!currentUser) return;

  fetch(`../api/rooms/user_reservations.php?user_id=${currentUser.user_id}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        displayReservations(data.reservations);
      } else {
        console.error('Failed to load reservations:', data.message);
        showEmptyState('Failed to load reservations. Please try again later.');
      }
    })
    .catch(error => {
      console.error('Error fetching reservations:', error);
      showEmptyState('Could not connect to the server. Please try again later.');
    });
}

// Function to display reservations
function displayReservations(reservations) {
  const reservationsList = document.getElementById('reservations-list');
  
  if (!reservationsList) return;
  
  // Clear existing content
  reservationsList.innerHTML = '';
  
  if (reservations.length === 0) {
    showEmptyState('You have no room reservations yet.');
    return;
  }
  
  // Sort reservations by date (newest first)
  reservations.sort((a, b) => {
    const dateA = new Date(a.reservation_date + ' ' + a.start_time);
    const dateB = new Date(b.reservation_date + ' ' + b.start_time);
    return dateB - dateA;
  });
  
  // Create table rows for each reservation
  reservations.forEach(reservation => {
    const row = document.createElement('tr');
    row.dataset.id = reservation.reservation_id;
    row.dataset.status = reservation.status;
    
    // Format date
    const date = new Date(reservation.reservation_date);
    const formattedDate = date.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
    
    // Format time
    const startTime = formatTime(reservation.start_time);
    const endTime = formatTime(reservation.end_time);
    
    // Format purpose (limit length)
    let purpose = reservation.purpose;
    if (purpose.length > 40) {
      purpose = purpose.substring(0, 37) + '...';
    }
    
    // Status badge class
    let statusClass = 'badge-warning';
    if (reservation.status === 'approved') {
      statusClass = 'badge-success';
    } else if (reservation.status === 'rejected') {
      statusClass = 'badge-danger';
    }
    
    // Create row content
    row.innerHTML = `
      <td>${reservation.room_name}</td>
      <td>${formattedDate}</td>
      <td>${startTime} - ${endTime}</td>
      <td>${purpose}</td>
      <td><span class="badge ${statusClass}">${capitalizeFirstLetter(reservation.status)}</span></td>
      <td>
        <button class="btn btn-sm btn-primary view-details" data-id="${reservation.reservation_id}">
          <i class="fas fa-eye"></i> View
        </button>
        ${reservation.status === 'pending' ? `
        <button class="btn btn-sm btn-danger cancel-reservation" data-id="${reservation.reservation_id}">
          <i class="fas fa-times"></i> Cancel
        </button>
        ` : ''}
      </td>
    `;
    
    reservationsList.appendChild(row);
  });
  
  // Add event listeners to detail buttons
  document.querySelectorAll('.view-details').forEach(button => {
    button.addEventListener('click', function() {
      const reservationId = this.dataset.id;
      showReservationDetails(reservationId, reservations);
    });
  });
  
  // Add event listeners to cancel buttons
  document.querySelectorAll('.cancel-reservation').forEach(button => {
    button.addEventListener('click', function() {
      const reservationId = this.dataset.id;
      cancelReservation(reservationId);
    });
  });
}

// Function to show reservation details in modal
function showReservationDetails(reservationId, reservations) {
  const reservation = reservations.find(r => r.reservation_id === reservationId);
  if (!reservation) return;
  
  // Update modal content
  document.getElementById('detail-room-name').textContent = reservation.room_name;
  
  // Format date
  const date = new Date(reservation.reservation_date);
  const formattedDate = date.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
  document.getElementById('detail-date').textContent = formattedDate;
  
  // Format time
  const timeText = `${formatTime(reservation.start_time)} - ${formatTime(reservation.end_time)}`;
  document.getElementById('detail-time').textContent = timeText;
  
  // Set purpose
  document.getElementById('detail-purpose').textContent = reservation.purpose;
  
  // Set status badge
  const statusBadge = document.getElementById('reservation-status-badge');
  statusBadge.textContent = capitalizeFirstLetter(reservation.status);
  statusBadge.className = 'badge';
  
  if (reservation.status === 'approved') {
    statusBadge.classList.add('badge-success');
  } else if (reservation.status === 'rejected') {
    statusBadge.classList.add('badge-danger');
  } else {
    statusBadge.classList.add('badge-warning');
  }
  
  // Fetch and display group members
  fetchGroupMembers(reservationId);
  
  // Set up cancel button visibility
  const cancelBtn = document.getElementById('cancel-reservation');
  if (cancelBtn) {
    if (reservation.status === 'pending') {
      cancelBtn.style.display = 'inline-block';
      cancelBtn.dataset.id = reservationId;
    } else {
      cancelBtn.style.display = 'none';
    }
  }
  
  // Show modal
  document.getElementById('reservation-details-modal').classList.add('active');
}

// Function to fetch group members for a reservation
function fetchGroupMembers(reservationId) {
  const membersList = document.getElementById('detail-members-list');
  if (!membersList) return;
  
  // Show loading state
  membersList.innerHTML = '<div class="loading-spinner"></div>';
  
  // Fetch members from API
  fetch(`../api/rooms/get_members.php?reservation_id=${reservationId}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        if (data.members.length === 0) {
          membersList.innerHTML = '<p class="text-muted">No group members added</p>';
          return;
        }
        
        // Clear previous content
        membersList.innerHTML = '';
        
        // Add member chips
        data.members.forEach(member => {
          const memberChip = document.createElement('div');
          memberChip.className = 'member-chip';
          memberChip.innerHTML = `
            <span class="member-name">${member.member_name}</span>
            <span class="member-id">${member.student_id}</span>
          `;
          membersList.appendChild(memberChip);
        });
      } else {
        membersList.innerHTML = '<p class="text-muted">Failed to load group members</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching group members:', error);
      membersList.innerHTML = '<p class="text-muted">Could not load group members</p>';
    });
}

// Function to cancel a reservation
function cancelReservation(reservationId) {
  if (!confirm('Are you sure you want to cancel this reservation?')) {
    return;
  }
  
  fetch('../api/rooms/cancel_reservation.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      reservation_id: reservationId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      alert('Reservation cancelled successfully.');
      
      // Close modal if open
      document.getElementById('reservation-details-modal').classList.remove('active');
      
      // Reload reservations
      loadUserReservations();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error cancelling reservation:', error);
    alert('Failed to cancel reservation. Please try again later.');
  });
}

// Function to set up status filters
function setupStatusFilters() {
  const filterButtons = document.querySelectorAll('.status-filter');
  
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Remove active class from all buttons
      filterButtons.forEach(btn => btn.classList.remove('active'));
      
      // Add active class to clicked button
      this.classList.add('active');
      
      // Get status to filter by
      const status = this.dataset.status;
      
      // Apply filter
      filterReservationsByStatus(status);
    });
  });
}

// Function to filter reservations by status
function filterReservationsByStatus(status) {
  const rows = document.querySelectorAll('#reservations-list tr:not(.empty-state)');
  
  rows.forEach(row => {
    if (status === 'all' || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  // Check if any rows are visible
  const visibleRows = document.querySelectorAll('#reservations-list tr:not(.empty-state):not([style*="display: none"])');
  
  if (visibleRows.length === 0) {
    // Show empty state for the filtered status
    let message = 'No reservations found.';
    if (status !== 'all') {
      message = `No ${status} reservations found.`;
    }
    showEmptyState(message);
  } else {
    // Hide empty state if it exists
    const emptyState = document.querySelector('#reservations-list tr.empty-state');
    if (emptyState) {
      emptyState.remove();
    }
  }
}

// Function to show empty state
function showEmptyState(message) {
  const reservationsList = document.getElementById('reservations-list');
  
  // Clear existing content
  reservationsList.innerHTML = `
    <tr class="empty-state">
      <td colspan="6">
        <div class="empty-state-container">
          <i class="fas fa-calendar-xmark"></i>
          <p>${message}</p>
          <a href="room-reservation.html" class="btn btn-primary btn-sm">Make a Reservation</a>
        </div>
      </td>
    </tr>
  `;
}

// Function to set up modal event listeners
function setupModals() {
  // Close modal buttons
  document.querySelectorAll('.close-modal').forEach(button => {
    button.addEventListener('click', function() {
      // Find closest modal and close it
      const modal = this.closest('.modal');
      if (modal) {
        modal.classList.remove('active');
      }
    });
  });
  
  // Cancel reservation button in modal
  const cancelBtn = document.getElementById('cancel-reservation');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      const reservationId = this.dataset.id;
      cancelReservation(reservationId);
    });
  }
}

// Helper function to format time
function formatTime(timeString) {
  if (!timeString) return '';
  
  const [hours, minutes] = timeString.split(':');
  let formattedHours = parseInt(hours);
  const ampm = formattedHours >= 12 ? 'PM' : 'AM';
  
  formattedHours = formattedHours % 12;
  formattedHours = formattedHours ? formattedHours : 12; // 0 should be 12
  
  return `${formattedHours}:${minutes} ${ampm}`;
}

// Helper function to capitalize first letter
function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
} 