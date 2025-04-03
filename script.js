 // Get modal elements and buttons
 const signupBtn = document.getElementById('signupBtn');
 const loginBtn = document.getElementById('loginBtn');
 const signupModal = document.getElementById('signupModal');
 const loginModal = document.getElementById('loginModal');
 const closeSignup = document.getElementById('closeSignup');
 const closeLogin = document.getElementById('closeLogin');

 // Open modals
 signupBtn.addEventListener('click', () => {
   signupModal.classList.remove('modal-hidden');
 });
 loginBtn.addEventListener('click', () => {
   loginModal.classList.remove('modal-hidden');
 });

 // Close modals on close button click
 closeSignup.addEventListener('click', () => {
   signupModal.classList.add('modal-hidden');
 });
 closeLogin.addEventListener('click', () => {
   loginModal.classList.add('modal-hidden');
 });

 // Optional: close modals when clicking outside modal content
 window.addEventListener('click', (e) => {
   if (e.target === signupModal) {
	 signupModal.classList.add('modal-hidden');
   }
   if (e.target === loginModal) {
	 loginModal.classList.add('modal-hidden');
   }
 });