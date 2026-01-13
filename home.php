<?php
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row hero-section">
        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center text-center text-md-start">
            <h1>Welcome to RentEsy</h1>
            <p>Your one-stop solution for managing rental properties. Effortlessly connect with tenants, manage payments, and handle maintenance requests.</p>
            <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
        </div>
        <div class="col-md-6">
            <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2" class="img-fluid" alt="Modern living room">
        </div>
    </div>

    <div class="row features-section">
        <div class="col-12 text-center mb-5">
            <h2>Features</h2>
        </div>
        <div class="col-md-4 text-center">
            <div class="feature-icon mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5H.5a.5.5 0 0 1-.5-.5v-15a.5.5 0 0 1 .5-.5H1v1.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V.5h1v1.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V.5h1.263zM2 1.5V3h2V1.5H2zm4 0V3h2V1.5H6zm4 0V3h2V1.5h-2zM2 4.5V6h2V4.5H2zm4 0V6h2V4.5H6zm4 0V6h2V4.5h-2zM2 7.5V9h2V7.5H2zm4 0V9h2V7.5H6zm4 0V9h2V7.5h-2zM2 10.5V12h2v-1.5H2zm4 0V12h2v-1.5H6zm4 0V12h2v-1.5h-2z"/>
                </svg>
            </div>
            <h3>Property Management</h3>
            <p>Manage all your properties from a single dashboard. Keep track of tenants, rent, and more.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="feature-icon mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/>
                    <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z"/>
                </svg>
            </div>
            <h3>Online Payments</h3>
            <p>Accept rent payments online. Secure, fast, and convenient for both you and your tenants.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="feature-icon mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-tools" viewBox="0 0 16 16">
                    <path d="M1 0a1 1 0 0 0-1 1v5.586a1 1 0 0 0 .293.707l8 8a1 1 0 0 0 1.414 0l5.586-5.586a1 1 0 0 0 0-1.414l-8-8A1 1 0 0 0 6.586 0H1zm5.586 1L12 6.414 6.414 12 1 6.586V1h5.586zM15.854 8.854a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708l5-5a.5.5 0 0 1 .708 0z"/>
                    <path d="M8.293 1.293a1 1 0 0 1 1.414 0l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414-1.414L11.586 8 8.293 4.707a1 1 0 0 1 0-1.414z"/>
                    <path d="M4 11.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                </svg>
            </div>
            <h3>Maintenance Requests</h3>
            <p>Tenants can submit maintenance requests with photos. Track requests from start to finish.</p>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
