    <style>
        .pin-card {
            max-width: 500px;
            margin: 50px auto;
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .pin-input-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        .pin-input-box {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            border: 2px solid #ced4da;
            border-radius: 8px;
        }

        .pin-input-box:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: none;
        }

        .pin-input-box.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            position: relative;
        }

        .step.active {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed {
            background-color: #198754;
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #e9ecef;
            right: -100%;
            top: 50%;
            transform: translateY(-50%);
        }

        .step:last-child::after {
            display: none;
        }

        .step.completed::after {
            background-color: #198754;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

    </style>

    <div class="container-fluid">
        <div class="card pin-card">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-4">Change PIN</h4>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" data-step="1">1</div>
                    <div class="step" data-step="2">2</div>
                    <div class="step" data-step="3">3</div>
                </div>

                <!-- Step 1: Current PIN -->
                <div id="step1" class="step-content">
                    <h5 class="text-center mb-3">Enter Current PIN</h5>
                    <div class="pin-input-group">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    </div>
                </div>

                <!-- Step 2: New PIN -->
                <div id="step2" class="step-content d-none">
                    <h5 class="text-center mb-3">Enter New PIN</h5>
                    <div class="pin-input-group">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    </div>
                </div>

                <!-- Step 3: Confirm New PIN -->
                <div id="step3" class="step-content d-none">
                    <h5 class="text-center mb-3">Confirm New PIN</h5>
                    <div class="pin-input-group">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                    </div>
                </div>

                <div id="errorMessage" class="alert alert-danger mt-3 d-none"></div>

                <div class="d-grid gap-2 mt-4">
                    <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script>
        // Mock current PIN for demonstration
        const CURRENT_PIN = '1234';

        let currentStep = 1;
        let newPin = '';

        const nextBtn = document.getElementById('nextBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const errorMessage = document.getElementById('errorMessage');

        // Initialize all PIN input groups
        document.querySelectorAll('.pin-input-group').forEach(group => {
            const inputs = group.querySelectorAll('.pin-input-box');

            inputs.forEach((input, index) => {
                // Handle input
                input.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');

                    if (e.target.value !== '') {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && e.target.value === '') {
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    }
                });
            });
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('d-none');
        }

        function hideError() {
            errorMessage.classList.add('d-none');
        }

        function getStepPIN(stepNumber) {
            const inputs = document.querySelector(`#step${stepNumber} .pin-input-group`)
                .querySelectorAll('.pin-input-box');
            return Array.from(inputs).map(input => input.value).join('');
        }

        function clearStepInputs(stepNumber) {
            const inputs = document.querySelector(`#step${stepNumber} .pin-input-group`)
                .querySelectorAll('.pin-input-box');
            inputs.forEach(input => input.value = '');
            inputs[0].focus();
        }

        function updateStepIndicator() {
            document.querySelectorAll('.step').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    step.classList.add('active');
                } else if (index + 1 < currentStep) {
                    step.classList.add('completed');
                }
            });
        }

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.add('d-none');
            });
            document.getElementById(`step${step}`).classList.remove('d-none');

            // Focus first input of new step
            const firstInput = document.querySelector(`#step${step} .pin-input-box`);
            if (firstInput) firstInput.focus();

            // Update button text
            nextBtn.textContent = step === 3 ? 'Change PIN' : 'Next';
        }

        async function handleNextStep() {
            hideError();
            const currentPIN = getStepPIN(currentStep);

            // Validate complete PIN entry
            if (currentPIN.length !== 4) {
                showError('Please enter all 4 digits');
                return;
            }

            switch (currentStep) {
                case 1:
                    // Verify current PIN
                    if (currentPIN !== CURRENT_PIN) {
                        showError('Current PIN is incorrect');
                        clearStepInputs(1);
                        return;
                    }
                    break;

                case 2:
                    // Store new PIN for confirmation
                    newPin = currentPIN;
                    break;

                case 3:
                    // Confirm new PIN matches
                    if (currentPIN !== newPin) {
                        showError('PINs do not match');
                        clearStepInputs(3);
                        return;
                    }

                    // Submit PIN change
                    try {
                        loadingOverlay.style.display = 'flex';

                        // Simulate API call
                        await new Promise(resolve => setTimeout(resolve, 1500));

                        // Redirect on success
                        window.location.href = '/security-settings.html?pinChanged=true';
                    } catch (error) {
                        loadingOverlay.style.display = 'none';
                        showError('An error occurred. Please try again.');
                    }
                    return;
            }

            // Move to next step
            currentStep++;
            updateStepIndicator();
            showStep(currentStep);
        }

        nextBtn.addEventListener('click', handleNextStep);

        // Initialize first step
        updateStepIndicator();
        showStep(1);

    </script>
