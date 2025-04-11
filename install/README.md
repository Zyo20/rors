# Install Directory

This directory contains scripts for setting up and configuring the Restaurant Ordering and Reservation System (RORS).

## Sample Data

To populate the database with sample users and data for testing, run the following script:

```
http://localhost/rors/install/sample_data.php
```

This will create:

1. Four test user accounts with different roles:
   - Admin account
   - Kitchen staff account
   - Manager account
   - Customer account

2. Sample menu categories 

## Sample User Credentials

| Role     | Email             | Password     |
|----------|-------------------|--------------|
| admin    | admin@rors.com    | admin123     |
| kitchen  | kitchen@rors.com  | kitchen123   |
| manager  | manager@rors.com  | manager123   |
| customer | customer@rors.com | customer123  |

You can use these credentials to log in and test different parts of the system.

## Notes

- You should run this script only in development/testing environments
- The script will not duplicate entries if run multiple times
- For security, these sample users should be removed before deploying to production 