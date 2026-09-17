<?php
/**
 * SkillSwap Campus - Real-Time Messages
 */
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content flex-grow-1 bg-light">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-4" style="height: calc(100vh - 70px);">
      <div class="row h-100 bg-white rounded shadow-sm overflow-hidden border">
        
        <!-- Left: Contacts List -->
        <div class="col-md-4 col-lg-3 p-0 border-end d-flex flex-column h-100 bg-white">
          <div class="p-3 border-bottom bg-light">
            <h5 class="mb-0 fw-bold">Messages</h5>
          </div>
          <div id="contactsList" class="flex-grow-1 overflow-auto">
            <div class="text-center p-4 text-muted">
              <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              <p class="mt-2 small">Loading contacts...</p>
            </div>
          </div>
        </div>
        
        <!-- Right: Chat Window -->
        <div class="col-md-8 col-lg-9 p-0 d-flex flex-column h-100 bg-light" id="chatContainer">
          
          <!-- Empty State -->
          <div id="chatEmptyState" class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <i class="bi bi-chat-dots" style="font-size: 4rem; color: #cbd5e1;"></i>
            <h4 class="mt-3 text-secondary">Select a conversation</h4>
            <p class="small">Choose a contact from the left menu to start messaging.</p>
          </div>
          
          <!-- Active Chat Window (Hidden by default) -->
          <div id="activeChatWindow" class="d-flex flex-column h-100 d-none">
            
            <!-- Chat Header -->
            <div class="p-3 bg-white border-bottom d-flex align-items-center shadow-sm z-1">
              <img src="" id="chatHeaderImg" class="rounded-circle me-3 border" style="width: 45px; height: 45px; object-fit: cover;">
              <div>
                <h6 class="mb-0 fw-bold text-dark" id="chatHeaderName">Partner Name</h6>
                <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Active</small>
              </div>
            </div>
            
            <!-- Chat Messages Area -->
            <div id="chatMessagesBox" class="flex-grow-1 p-3 overflow-auto" style="background-color: #f8fafc; scroll-behavior: smooth;">
              <!-- Messages will be injected here via JS -->
            </div>
            
            <!-- Chat Input Area -->
            <div class="p-3 bg-white border-top">
              <form id="sendMessageForm" class="d-flex gap-2">
                <input type="hidden" id="activePartnerId" value="">
                <input type="text" id="messageInput" class="form-control rounded-pill bg-light" placeholder="Type a message..." required autocomplete="off">
                <button type="submit" class="btn btn-primary rounded-circle" style="width: 45px; height: 45px; flex-shrink: 0;">
                  <i class="bi bi-send-fill"></i>
                </button>
              </form>
            </div>
            
          </div>
          
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Chat specific styles */
.contact-item {
    cursor: pointer;
    transition: background-color 0.2s;
}
.contact-item:hover, .contact-item.active {
    background-color: #f1f5f9;
}
.chat-bubble {
    max-width: 75%;
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 2px;
    font-size: 0.95rem;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.chat-bubble-me {
    background-color: #4f46e5;
    color: white;
    border-bottom-right-radius: 4px;
}
.chat-bubble-partner {
    background-color: white;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}
.chat-time {
    font-size: 0.65rem;
    opacity: 0.8;
    margin-top: 4px;
    text-align: right;
}
.chat-bubble-me .chat-time { color: #e0e7ff; }
.chat-bubble-partner .chat-time { color: #94a3b8; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentPartnerId = null;
    let pollInterval = null;
    
    // Elements
    const contactsList = document.getElementById('contactsList');
    const emptyState = document.getElementById('chatEmptyState');
    const activeWindow = document.getElementById('activeChatWindow');
    const messagesBox = document.getElementById('chatMessagesBox');
    const sendForm = document.getElementById('sendMessageForm');
    const messageInput = document.getElementById('messageInput');
    const headerName = document.getElementById('chatHeaderName');
    const headerImg = document.getElementById('chatHeaderImg');
    const partnerInput = document.getElementById('activePartnerId');
    
    // Load Contacts
    function loadContacts() {
        fetch('../api/messages.php?action=fetch_contacts')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderContacts(data.contacts);
                }
            });
    }
    
    function renderContacts(contacts) {
        if (contacts.length === 0) {
            contactsList.innerHTML = '<div class="p-4 text-center text-muted small">No conversations yet.<br>Accept a learning request to start chatting!</div>';
            return;
        }
        
        let html = '';
        contacts.forEach(c => {
            const isActive = c.user_id == currentPartnerId ? 'active' : '';
            const unreadBadge = c.unread_count > 0 ? `<span class="badge bg-danger rounded-pill">${c.unread_count}</span>` : '';
            const msgPreview = c.is_last_mine ? `You: ${c.last_message}` : c.last_message;
            const imgPath = c.profile_image ? `../uploads/profiles/${c.profile_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(c.name)}&background=4f46e5&color=fff`;
            
            html += `
            <div class="contact-item p-3 border-bottom d-flex align-items-center ${isActive}" data-id="${c.user_id}" data-name="${c.name}" data-img="${imgPath}">
                <div class="position-relative">
                    <img src="${imgPath}" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.name)}&background=4f46e5&color=fff';">
                </div>
                <div class="ms-3 flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fw-semibold text-truncate text-dark">${c.name}</h6>
                        <small class="text-muted" style="font-size: 0.7rem;">${c.last_message_time}</small>
                    </div>
                    <p class="mb-0 small text-muted text-truncate" style="font-size: 0.8rem;">${msgPreview}</p>
                </div>
                <div class="ms-2">
                    ${unreadBadge}
                </div>
            </div>`;
        });
        
        contactsList.innerHTML = html;
        
        // Add click events to contacts
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.contact-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const img = this.getAttribute('data-img');
                
                openChat(id, name, img);
            });
        });
    }
    
    function openChat(partnerId, name, img) {
        currentPartnerId = partnerId;
        partnerInput.value = partnerId;
        headerName.textContent = name;
        headerImg.src = img;
        
        emptyState.classList.add('d-none');
        activeWindow.classList.remove('d-none');
        
        loadMessages(partnerId, true);
        
        // Start polling for this chat every 3 seconds
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            loadMessages(currentPartnerId, false);
            loadContacts(); // update left bar silently
        }, 3000);
    }
    
    function loadMessages(partnerId, scrollToBottom) {
        fetch(`../api/messages.php?action=fetch_messages&partner_id=${partnerId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages, scrollToBottom);
                }
            });
    }
    
    function renderMessages(messages, scrollToBottom) {
        if (messages.length === 0) {
            messagesBox.innerHTML = '<div class="text-center text-muted p-4 small">No messages yet. Say hi!</div>';
            return;
        }
        
        let html = '';
        let lastDate = '';
        
        messages.forEach(msg => {
            const dateObj = new Date(msg.created_at);
            const dateStr = dateObj.toLocaleDateString();
            
            // Add date separator if new day
            if (dateStr !== lastDate) {
                html += `<div class="d-flex justify-content-center my-3"><span class="badge bg-secondary opacity-50 px-3 py-1 rounded-pill fw-normal">${dateStr}</span></div>`;
                lastDate = dateStr;
            }
            
            if (msg.is_mine) {
                html += `
                <div class="d-flex justify-content-end mb-2">
                    <div class="chat-bubble chat-bubble-me">
                        <div>${escapeHtml(msg.message)}</div>
                        <div class="chat-time">${msg.time_formatted}</div>
                    </div>
                </div>`;
            } else {
                html += `
                <div class="d-flex justify-content-start mb-2">
                    <div class="chat-bubble chat-bubble-partner">
                        <div>${escapeHtml(msg.message)}</div>
                        <div class="chat-time">${msg.time_formatted}</div>
                    </div>
                </div>`;
            }
        });
        
        // Only update HTML if changed to prevent scrolling jumps, unless forced
        if (messagesBox.innerHTML !== html || scrollToBottom) {
            const isScrolledToBottom = messagesBox.scrollHeight - messagesBox.clientHeight <= messagesBox.scrollTop + 50;
            
            messagesBox.innerHTML = html;
            
            // Auto scroll down if user was already at bottom, or if forced (initial load)
            if (scrollToBottom || isScrolledToBottom) {
                setTimeout(() => {
                    messagesBox.scrollTop = messagesBox.scrollHeight;
                }, 50);
            }
        }
    }
    
    // Send Message
    sendForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = messageInput.value.trim();
        if (!text || !currentPartnerId) return;
        
        messageInput.value = ''; // clear instantly for good UX
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('receiver_id', currentPartnerId);
        formData.append('message', text);
        
        fetch('../api/messages.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadMessages(currentPartnerId, true);
                loadContacts();
            }
        });
    });
    
    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Initial Load
    loadContacts();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
