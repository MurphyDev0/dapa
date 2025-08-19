// Mobile menu toggle
document.querySelector('.mobile-menu-button').addEventListener('click', function() {
    document.querySelector('.mobile-menu').classList.toggle('hidden');
});

// Fortune wheel functionality
function wheelOfFortune(selector) {
    const node = document.querySelector(selector);
    if (!node) return;
    
    const spin = document.getElementById('spinButton');
    const wheel = node.querySelector('ul');
    const segments = wheel.querySelectorAll('li');
    let animation;
    let previousEndDegree = 0;
    
    // Explicit mapping of visual segments to values
    const segmentValueMap = [
        { name: "Ajándék termék", index: 0 },
        { name: "Pörgess újra", index: 1 },
        { name: "Ingyenes szállítás", index: 2 },
        { name: "15% kedvezmény", index: 3 },
        { name: "10% kedvezmény", index: 4 },
        { name: "30% kedvezmény", index: 5 }
    ];
    
    // Function to send coupon to server
    function saveCouponToServer(coupon, segmentName) {
        fetch('save_coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `coupon=${encodeURIComponent(coupon)}&type=${encodeURIComponent(segmentName)}`
        })
        .then(response => {
            // First check if response is OK before parsing JSON
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Success:', data);
            console.log("Elküldve");
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    }
    
    // Function to create and start countdown timer
    function startCountdown() {
        // Create countdown container if it doesn't exist
        let countdownContainer = document.getElementById('countdownContainer');
        if (!countdownContainer) {
            countdownContainer = document.createElement('div');
            countdownContainer.id = 'countdownContainer';
            countdownContainer.style.cssText = 'margin-bottom: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; text-align: center; font-weight: bold; color: red;';
            countdownContainer.classList.add('dark:bg-gray-800');
            
            // Check if couponCodeElement exists before inserting
            const couponCodeElement = document.getElementById('couponCodeText');
            if (couponCodeElement) {
                couponCodeElement.parentNode.insertBefore(countdownContainer, couponCodeElement);
            } else {
                // If couponCodeText doesn't exist, append to another container
                const couponContainer = document.getElementById('couponContainer') || document.querySelector('.ui-wheel-of-fortune');
                if (couponContainer) {
                    couponContainer.appendChild(countdownContainer);
                }
            }
        }
        
        // Set countdown end time (24 hours from now)
        const endTime = new Date();
        endTime.setHours(endTime.getHours() + 24);
        
        // Save end time to localStorage to persist across page refreshes
        localStorage.setItem('wheelCountdownEnd', endTime.getTime());
        
        // Update countdown every second
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        function updateCountdown() {
            const now = new Date().getTime();
            const storedEndTime = parseInt(localStorage.getItem('wheelCountdownEnd'));
            const timeLeft = storedEndTime - now;
            
            if (timeLeft <= 0) {
                // Countdown finished
                clearInterval(countdownInterval);
                countdownContainer.textContent = 'Újra pörgethető!';
                const spinButton = document.getElementById('spinButton');
                if (spinButton) {
                    spinButton.disabled = false;
                }
                localStorage.removeItem('wheelCountdownEnd'); // Clear stored time
            } else {
                // Calculate hours, minutes, seconds
                const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                // Display countdown
                countdownContainer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }
        
        // Initial call to update countdown immediately
        updateCountdown();
    }
    
   // Check if there's an active countdown on page load
    function checkExistingCountdown() {
        // Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
        const spinButton = document.getElementById('spinButton');
        // Ha a gomb le van tiltva (nem a visszaszámlálás miatt, hanem mert nincs bejelentkezve),
        // akkor ne is próbáljon visszaszámlálót indítani
        if (spinButton && spinButton.hasAttribute('disabled') && !spinButton.getAttribute('data-logged-in')) {
            return;
        }
        
        const storedEndTime = localStorage.getItem('wheelCountdownEnd');
        if (storedEndTime) {
            const now = new Date().getTime();
            if (now < parseInt(storedEndTime)) {
                // There's an active countdown
                if (spinButton) {
                    spinButton.disabled = true;
                }
                startCountdown();
            } else {
                // Countdown has expired
                localStorage.removeItem('wheelCountdownEnd');
            }
        }
    }
    
    // Call this on initialization
    checkExistingCountdown();
    
    if (spin) {
        spin.addEventListener('click', () => {
            if (spin.disabled) return;
            
            if (animation) {
                animation.cancel();
            }
            
            // Generate random angle for the wheel
            const randomAdditionalDegrees = Math.random() * 360 + 1800;
            const newEndDegree = previousEndDegree + randomAdditionalDegrees;
            
            animation = wheel.animate([
                { transform: `rotate(${previousEndDegree}deg)` },
                { transform: `rotate(${newEndDegree}deg)` }
            ], {
                duration: 4000,
                direction: 'normal',
                easing: 'cubic-bezier(0.440, -0.205, 0.000, 1.130)',
                fill: 'forwards',
                iterations: 1
            });
            
            previousEndDegree = newEndDegree;
            
            animation.onfinish = () => {
                // Calculate final position
                const finalRotation = newEndDegree % 360;
                
                // Determine which segment would normally be at the pointer
                const segmentSize = 360 / segments.length;
                
                // This formula calculates which segment is at the pointer
                // Adjust the offset (0 here) based on the alignment of your wheel
                let visualSegmentIndex = Math.floor(((360 - finalRotation + 0) % 360) / segmentSize);
                
                // Ensure index is within bounds
                visualSegmentIndex = (visualSegmentIndex + segments.length) % segments.length;
                
                // Map visual index to specified index
                // We need to know the order of segments on the wheel to map correctly
                // This assumes segments order matches the order in segmentValueMap
                // You may need to adjust this logic based on actual wheel layout
                const mappedIndex = segmentValueMap[visualSegmentIndex].index;
                
                // Get the name of the segment for user-friendly output
                const segmentName = segmentValueMap[visualSegmentIndex].name;
                
                console.log("Vizuális szegmens index:", visualSegmentIndex);
                console.log("Leképezett index:", mappedIndex);
                console.log("Szegmens neve:", segmentName);
                
                // Additional debugging info
                console.log("Végső forgatási szög:", finalRotation);
                console.log("Szegmens mérete fokokban:", segmentSize);

                if(mappedIndex != 1){
                    const couponArray = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

                    let coupon = "";
                    for(let i = 0; i < 10; i++){
                        let random = Math.floor(Math.random()*couponArray.length)    
                        let randomUpper = Math.floor(Math.random()*2)
                        if(random <= 25){
                            if(randomUpper == 0){
                                coupon+=couponArray[random];
                            } else{
                                coupon+=couponArray[random].toUpperCase();
                            }
                        } else{
                            coupon+=couponArray[random];
                        }
                    }

                    const couponCodeElement = document.getElementById('couponCode');
                    if (couponCodeElement) {
                        couponCodeElement.innerHTML = coupon;
                    }
                    
                    if (spin) {
                        spin.disabled = true;
                    }
                    
                    // Save coupon to server
                    saveCouponToServer(coupon, segmentName);
                    
                    // Start the countdown
                    startCountdown();
                }
            };
        });
    }
}

// Show modal when page loads
window.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('fortuneModal');
    if (!modal) return; // Exit if modal doesn't exist
    
    modal.style.display = 'block';
    
    // Initialize wheel of fortune
    wheelOfFortune('.ui-wheel-of-fortune');
    
    // Close modal functionality
    const closeButton = document.getElementById('closeModal');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }
    
    // Also close when clicking outside the modal content
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Notification megjelenítése
    function showNotification(message, isSuccess) {
        var notification = document.getElementById('notification');
        notification.classList.remove('hidden', 'bg-green-100', 'bg-red-100', 'text-green-800', 'text-red-800');
        notification.classList.add(isSuccess ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
        notification.innerHTML = '<p>' + message + '</p>';
        notification.style.display = 'block';
        
        // 5 másodperc után elrejtjük
        setTimeout(function() {
            notification.classList.add('hidden');
        }, 5000);
    }
    
    // A form küldés kezelő funkció létrehozása
    function handleFormSubmission(formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Űrlap adatok összegyűjtése
            var formData = new FormData(form);
            
            // Fetch API használata AJAX helyett
            fetch('profile_update.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('Response:', data); // Debug információ
                showNotification(data.message, data.success);
            })
            .catch(function(error) {
                console.error('Fetch error:', error);
                showNotification('Hiba történt a kérés feldolgozása során.', false);
            });
        });
    }
    
    // Az űrlapok feldolgozása
    handleFormSubmission('personalForm');
    handleFormSubmission('addressForm');
    handleFormSubmission('passwordForm');
});

document.addEventListener('DOMContentLoaded', function() {
    // Kupon kód másolása a vágólapra funkció
    var kuponKodok = document.querySelectorAll('.bg-gray-100.px-3.py-1');
    kuponKodok.forEach(function(kodElem) {
        kodElem.addEventListener('click', function() {
            var tempInput = document.createElement('input');
            tempInput.value = this.textContent;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Értesítés megjelenítése
            var notification = document.getElementById('notification');
            notification.classList.remove('hidden', 'bg-green-100', 'bg-red-100');
            notification.classList.add('bg-green-100', 'text-green-800');
            notification.innerHTML = '<p>Kuponkód másolva a vágólapra!</p>';
            
            // 3 másodperc után elrejtjük
            setTimeout(function() {
                notification.classList.add('hidden');
            }, 3000);
        });
        
        // Kurzor mutatása, hogy jelezze hogy kattintható
        kodElem.style.cursor = 'pointer';
        kodElem.title = 'Kattints a másoláshoz';
    });
});
