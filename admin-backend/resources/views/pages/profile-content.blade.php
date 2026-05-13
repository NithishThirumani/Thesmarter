<style>
       
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }
        
        .profile-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
    </style>
    <div class="container-fluid">
        <div class="text-center mb-4">
            <div class="avatar" id="userAvatar"></div>
            <h2 class="mb-0" id="userName"></h2>
        </div>
        
        <div class="profile-info">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-label">Date of Birth</div>
                    <div id="userDob"></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Sex</div>
                    <div id="userSex"></div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-label">Marital Status</div>
                    <div id="userMarital"></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Phone</div>
                    <div id="userPhone"></div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-label">Email</div>
                    <div id="userEmail"></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Company</div>
                    <div id="userCompany"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sample user data - replace with actual data from your backend
       
    </script>