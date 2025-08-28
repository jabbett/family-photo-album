# Project Summary

This project is a mobile-first web application for a family of five living abroad to easily share photos with friends and family back home. The platform features a simple, Instagram-like public photo stream that requires no login for viewers, while family members authenticate to upload photos from their iPhones. The admin (project owner) manages family access by entering the valid email addresses that can log in. The goal is to create a frictionless photo sharing experience that encourages frequent uploads while maintaining full content ownership and control, avoiding the complexity and platform dependencies of existing social media or cloud photo services.

# Objectives / Goals

**Primary Success Metric:** Each family member uploads photos several times per week (target: 3+ uploads per person per week)

**Secondary Goals:** 
- Fast, performant platform that encourages regular use
- Easy photo sharing for familiy and visitors via clean, shareable URLs

# Stakeholders

> Who are our key customer contacts or other critical external people we expect to work with during this project?

| Name | Role | Notes |
|------|------|-------|
| You | Admin/Project Owner | Will manage family access and overall platform |
| Spouse | Co-uploading parent | Age 44, tech-comfortable |
| Child 1 | Uploading family member | Age 18, tech-comfortable |
| Child 2 | Uploading family member | Age 15, tech-comfortable |
| Child 3 | Uploading family member | Age 13, tech-comfortable |

# Assumptions & Questions

> Anything driving or constraining the design that we don't know for certain is true. Ideally, these assumptions would be investigated up front through research or validated later by user testing.

**Assumptions:**
- Family members will consistently use the platform if UX friction is minimal
- Photo timestamps can be extracted from iPhone uploads reliably

# Constraints

> Anything out of our control, or out of scope for this project, that will limit the solutions we consider. These may include technical limitations, legal regulations, deadlines, users' abilities, or customers' expectations.

**Technical Requirements:**
- **PHP/Laravel running on Dreamhost:** The app will be hosted on Dreamhost shared hosting, so it needs to be built with PHP. We will use the Laravel framework to jumpstart our development. The app must run locally during development on a Mac.
- **Primary Upload Device:** iPhone using Mobile Safari (web app, not native app)
- **Public Interface:** Mobile-first responsive design for any browser (mobile/desktop)
- **Backend funtionality over front-end:** Ideally keep the HTML/CSS/JS simple, and leave complex logic for the backend. Avoid front-end JavaScript frameworks if you can.
- **Simple pages over dialogs:** Show key interfaces as simple pages, rather than using modal dialogs
- **Architecture:** Use simple HTML/CSS/HTTP solutions over custom Javascript code when possible.
- **URL Structure:** Clean, shareable URLs for individual photos (e.g., `/photo/124` or `/photo/pastries-at-michaela`)

**Authentication & Access:**
- **Admin Control:** Admin inputs family email addresses to allow self-signup only for those people. Admin can customize site settings (site title, site subtitle, primary theme color)
- **Public Access:** No login required for viewing photo stream

**Upload Requirements:**
- **Photo Upload:** Simple upload interface optimized for Mobile Safari
- **Simple Cropping:** Let the user determine which part of the photo should be used for a square thumbnail
- **Simple Caption:** Optional caption field for each photo
- **Automatic Metadata:** Capture uploader identity and original photo timestamp from EXIF datga
- **Stream Organization:** Simple chronological photo stream - grid display for the public main page
- **Image size optimization:** Store full image but grid should use smaller square thumbnails 

**Content & Security:**
- **Content Ownership:** Must maintain full control over content (no third-party platforms like Google Photos, Instagram, or Facebook)
- **Security Approach:** Obvious security best practices while prioritizing usability
- **Public Interface Style:** Instagram-like visual presentation for photo grid and individual photos.

# Audiences

> Who will benefit from this design? What are their behaviors, motivations, and challenges? What user research, customer feedback, or sales prospects are we drawing from?

**Primary Audience - Family Uploaders (5 people):**
- Tech-comfortable but value smooth, frictionless UX
- Ages 13-44, all iPhone users
- Living abroad, want to easily share experiences with friends/family back home
- Motivated by staying connected but discouraged by friction in current solutions

**Secondary Audience - Friends & Family Viewers (dozens):**
- Mixed device usage (mobile and desktop browsers)
- Prefer casual, no-commitment viewing (no login required)
- Want to easily share individual photos they find interesting
- Occasional viewers rather than daily users

# Problem Scenarios

> For each audience, how do they currently experience the problems we hope to solve?

**Family Uploaders:**
- Current photo sharing through iCloud/Google Photos/Instagram creates unnecessary complexity and platform dependencies
- Each platform has different sharing mechanisms and privacy settings
- Want to document year abroad but existing solutions feel cumbersome for frequent updates
- Barriers to uploading (login friction, complex interfaces) discourage regular sharing

**Friends & Family Viewers:**
- Currently need to navigate multiple platforms or wait for email attachments to see family updates
- Want easy way to view latest photos without creating accounts or remembering login credentials
- Desire simple way to share specific photos they enjoy with others
