# FIRMA - Event Management System (Gestion Événement)
## Comprehensive Technical Report

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Database Architecture](#database-architecture)
3. [Entity Model](#entity-model)
4. [CRUD Functionalities](#crud-functionalities)
5. [Advanced Features](#advanced-features)
6. [User Side (Front-End)](#user-side-front-end)
7. [Admin Side (Back-Office)](#admin-side-back-office)
8. [API & Services Layer](#api--services-layer)
9. [Security & Session Management](#security--session-management)
10. [Email & Notification System](#email--notification-system)
11. [Analytics & Reporting](#analytics--reporting)
12. [Technical Stack](#technical-stack)

---

## 1. System Overview

The **Event Management System (Gestion Événement)** is a comprehensive module within the FIRMA platform designed to manage agricultural events, conferences, workshops, exhibitions, and training sessions. The system provides a complete event lifecycle management solution from creation to participation tracking and analytics.

### Key Capabilities:
- **Event Creation & Management**: Full CRUD operations for events
- **Participant Registration**: User registration with companion management
- **Ticket Generation**: Automated PDF ticket generation with QR codes
- **Email Notifications**: Automated confirmation emails
- **Analytics Dashboard**: Real-time statistics and insights
- **Multi-role Support**: Separate interfaces for administrators and visitors

---

## 2. Database Architecture

### Core Tables

#### 2.1 `evenements` Table
Stores all event information with the following structure:

```sql
CREATE TABLE `evenements` (
  `id_evenement` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `type_evenement` enum('exposition','atelier','conference','salon','formation','autre') DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `horaire_debut` time DEFAULT NULL,
  `horaire_fin` time DEFAULT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `capacite_max` int(11) DEFAULT NULL,
  `places_disponibles` int(11) DEFAULT NULL,
  `organisateur` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_tel` varchar(20) DEFAULT NULL,
  `statut` enum('actif','annule','termine','complet') DEFAULT 'actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Fields:**
- **Event Types**: exposition, atelier, conference, salon, formation, autre
- **Status Types**: actif, annule, termine, complet
- **Capacity Management**: `capacite_max` and `places_disponibles` for availability tracking
- **Timestamps**: Automatic creation and modification tracking

#### 2.2 `participations` Table
Manages user registrations for events:

```sql
CREATE TABLE `participations` (
  `id_participation` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_evenement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `statut` enum('en_attente','confirme','annule') DEFAULT 'confirme',
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_annulation` datetime DEFAULT NULL,
  `nombre_accompagnants` int(11) DEFAULT 0,
  `commentaire` text DEFAULT NULL,
  `code_participation` varchar(20) DEFAULT NULL,
  FOREIGN KEY (`id_evenement`) REFERENCES `evenements`(`id_evenement`),
  FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Features:**
- **Status Workflow**: en_attente → confirme (or annule)
- **Companion Support**: `nombre_accompagnants` field
- **Unique Code**: `code_participation` for ticket identification
- **Audit Trail**: Registration and cancellation timestamps


#### 2.3 `accompagnants` Table
Stores companion information for each participation:

```sql
CREATE TABLE `accompagnants` (
  `id_accompagnant` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_participation` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  FOREIGN KEY (`id_participation`) REFERENCES `participations`(`id_participation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Tracks individual companions with their full names for each registration.

---

## 3. Entity Model

### 3.1 Evenement Entity


**Attributes**:
- `idEvenement`: Integer (Primary Key)
- `titre`: String (Event title)
- `description`: String (Detailed description)
- `imageUrl`: String (Path to event image)
- `typeEvenement`: Type enum (Event category)
- `dateDebut`: LocalDate (Start date)
- `dateFin`: LocalDate (End date)
- `horaireDebut`: LocalTime (Start time)
- `horaireFin`: LocalTime (End time)
- `lieu`: String (Venue name)
- `adresse`: String (Full address)
- `capaciteMax`: Integer (Maximum capacity)
- `placesDisponibles`: Integer (Available seats)
- `organisateur`: String (Organizer name)
- `contactEmail`: String (Contact email)
- `contactTel`: String (Contact phone)
- `statut`: Statutevent enum (Event status)
- `dateCreation`: LocalDateTime (Creation timestamp)
- `dateModification`: LocalDateTime (Last modification timestamp)

### 3.2 Participation Entity


**Attributes**:
- `idParticipation`: Integer (Primary Key)
- `idEvenement`: Integer (Foreign Key to Event)
- `idUtilisateur`: Integer (Foreign Key to User)
- `statut`: Statut enum (Registration status)
- `dateInscription`: LocalDateTime (Registration timestamp)
- `dateAnnulation`: LocalDateTime (Cancellation timestamp)
- `nombreAccompagnants`: Integer (Number of companions)
- `commentaire`: String (User comment/note)
- `codeParticipation`: String (Unique participation code)


### 3.3 Accompagnant Entity


**Attributes**:
- `idAccompagnant`: Integer (Primary Key)
- `idParticipation`: Integer (Foreign Key to Participation)
- `nom`: String (Last name)
- `prenom`: String (First name)

### 3.4 Utilisateur Entity


**Attributes**:
- `id`: Integer (Primary Key)
- `typeUser`: Role enum (client, admin)
- `nom`: String (Last name)
- `prenom`: String (First name)
- `email`: String (Email address)
- `motDePasse`: String (Encrypted password)
- `telephone`: String (Phone number)
- `adresse`: String (Address)
- `ville`: String (City)
- `dateCreation`: LocalDateTime (Account creation date)

### 3.5 Enumerations

#### Type (Event Types)
```java
public enum Type {
    EXPOSITION,    // Exhibition
    ATELIER,       // Workshop
    CONFERENCE,    // Conference
    SALON,         // Trade show
    FORMATION,     // Training
    AUTRE          // Other
}
```

#### Statutevent (Event Status)
```java
public enum Statutevent {
    ACTIF,      // Active
    ANNULE,     // Cancelled
    TERMINE,    // Completed
    COMPLET     // Full capacity
}
```

#### Statut (Participation Status)
```java
public enum Statut {
    EN_ATTENTE,  // Pending confirmation
    CONFIRME,    // Confirmed
    ANNULE       // Cancelled
}
```

#### Role (User Roles)
```java
public enum Role {
    CLIENT,  // Regular user/visitor
    ADMIN    // Administrator
}
```

---

## 4. CRUD Functionalities

### 4.1 Event CRUD Operations

#### CREATE - Add New Event


**Process**:
1. Admin fills form with event details (title, description, dates, capacity, etc.)
2. System validates all required fields:
   - Title (3-100 characters, required)
   - Event type (required)
   - Start/end dates (end >= start, start >= today)
   - Time slots (HH:mm format, end > start if same day)
   - Capacity (> 0, <= 100,000)
   - Organizer name (required)
3. Optional image upload or AI-generated image
4. Event saved with status "actif"
5. Available places initialized to capacity
6. Success notification displayed
7. Dashboard refreshed

**Validation Rules**:
- Title: Minimum 3 characters, maximum 100 characters
- Dates: End date must be >= start date, start date cannot be in the past
- Time: Valid HH:mm format (e.g., 09:30)
- Capacity: Integer between 1 and 100,000
- Organizer: Required field

#### READ - View Events


**Features**:
- **List All Events**: Displays all events in card format
- **Search**: Filter by title, organizer, or location
- **Sort Options**:
  - Date (most recent / oldest)
  - Title (A-Z / Z-A)
  - Available places
  - Status
- **Detailed View**: Click on event card to see full details in popup

#### UPDATE - Modify Event


**Process**:
1. Admin searches for event by title or clicks edit button
2. Form pre-populated with existing data
3. Admin modifies desired fields
4. Same validation rules as CREATE apply
5. Event updated in database
6. Modification timestamp automatically updated
7. List refreshed with updated information

**Editable Fields**: All event fields except creation date and ID


#### DELETE - Remove Event


**Process**:
1. Admin clicks delete button on event card
2. Confirmation dialog appears
3. If confirmed, event permanently deleted from database
4. Associated participations and companions also removed (CASCADE)
5. Success notification displayed
6. List refreshed

**Warning**: This is a hard delete operation. Consider implementing soft delete for production.

#### CANCEL - Change Event Status


**Process**:
1. Admin clicks cancel button
2. Confirmation dialog appears
3. Event status changed to "annule"
4. Event remains in database but marked as cancelled
5. Participants can still view their registrations

### 4.2 Participation CRUD Operations

#### CREATE - Register for Event


**Process**:
1. User clicks "Participer" button on event card
2. Registration form appears with:
   - Pre-filled user information (from session)
   - Number of companions selector (0 to available places - 1)
   - Dynamic companion name fields
   - Optional comment field
3. System validates:
   - Required fields (first name, last name, email)
   - Name format (letters only, min 2 characters)
   - Email format (standard email regex)
   - Phone format (8-15 digits, optional + prefix)
   - Total capacity (user + companions <= available places)
   - Duplicate registration check
4. Participation created with status "EN_ATTENTE"
5. Companions saved to database
6. Unique participation code generated (format: PART-XXXXX)
7. Confirmation email sent (if configured)
8. Available places decremented

**Validation Rules**:
- First/Last Name: Min 2 characters, letters only (including accented characters)
- Email: Valid email format (name@domain.com)
- Phone: 8-15 digits, optional + prefix
- Companions: Each must have first and last name
- Capacity: Total attendees cannot exceed available places


#### READ - View Participations

**User Side**:
- **My Participations**: View all personal registrations
- **Event-Specific**: View participation details for specific event
- **Companion List**: See all registered companions with names

**Admin Side**:
- **Participant List**: View all participants for an event
- **Detailed Grid**: Expandable rows showing companion details
- **Statistics**: Total registrations, companions, and total attendees
- **Export Options**: PDF export capability

#### UPDATE - Modify Participation


**Process**:
1. User accesses "My Participations"
2. Clicks modify button
3. Form pre-populated with existing data
4. Can modify:
   - Number of companions
   - Companion names
   - Comment
5. Same validation rules apply
6. Changes saved to database
7. Confirmation displayed

#### DELETE - Cancel Participation


**Process**:
1. User clicks "Cancel Participation" button
2. Confirmation dialog appears
3. Participation status changed to "ANNULE" or deleted
4. Available places incremented
5. Cancellation timestamp recorded
6. Confirmation displayed

---

## 5. Advanced Features

### 5.1 AI-Powered Image Generation
**Service**: `HuggingFaceImageService`
**Integration**: HuggingFace Stable Diffusion API

**Functionality**:
- Generates event images based on title, description, and type
- Uses free HuggingFace API (no cost)
- Asynchronous processing (non-blocking UI)
- Automatic prompt engineering based on event details
- Images saved locally in `image/` directory
- Fallback to manual upload if generation fails

**Process**:
1. Admin clicks "Generate AI Image" button
2. System constructs prompt from event data
3. API call to HuggingFace Stable Diffusion
4. Image downloaded and saved locally
5. Image path updated in form
6. Status indicator shows progress/success/failure


### 5.2 Email Notification System
**Service**: `EmailService`
**Protocol**: SMTP (JavaMail API)

**Features**:
- **Confirmation Emails**: Sent upon registration
- **Email Verification**: Link to confirm participation
- **PDF Ticket Attachment**: Included after confirmation
- **HTML Templates**: Professional email design
- **Async Processing**: Non-blocking email sending

**Email Content**:
- Event details (title, date, location)
- Participation code
- Confirmation link
- Organizer contact information
- PDF ticket (after confirmation)

**Configuration**:
- SMTP server settings
- Sender email credentials
- Template customization
- Fallback to direct confirmation if not configured

### 5.3 PDF Ticket Generation
**Controller**: `AffichageTicketsEtExportPDF`
**Library**: iText PDF

**Features**:
- **QR Code**: Contains participation code for scanning
- **Event Information**: Title, date, time, location
- **Participant Details**: Name, email, companions
- **Unique Code**: Participation identifier
- **Professional Design**: Branded layout with colors
- **Multiple Tickets**: One per participant + companions
- **Export Options**: Download or email

**Ticket Contents**:
- FIRMA branding
- Event title and type
- Date and time
- Venue and address
- Participant name
- Participation code
- QR code for verification
- Number of companions
- Organizer contact

### 5.4 Capacity Management
**Automatic Tracking**:
- Available places decremented on registration
- Incremented on cancellation
- Real-time updates across all views
- Automatic status change to "COMPLET" when full
- Prevents over-booking

**Business Rules**:
- User + companions cannot exceed available places
- One registration per user per event
- Companions counted in total capacity
- Admin can override capacity (manual adjustment)


### 5.5 Search and Filtering
**Location**: `FrontController` and `EvenementController`

**Search Capabilities**:
- **Text Search**: Title, organizer, location
- **Case-Insensitive**: Flexible matching
- **Real-Time**: Updates as you type
- **Multi-Field**: Searches across multiple attributes

**Sort Options**:
- Date (ascending/descending)
- Title (A-Z / Z-A)
- Available places (most to least)
- Event type
- Status

**Filter Criteria**:
- Event type (exposition, atelier, conference, etc.)
- Status (active, cancelled, completed, full)
- Date range
- Location

### 5.6 Companion Management
**Features**:
- **Dynamic Form**: Fields appear based on companion count
- **Individual Details**: First and last name for each companion
- **Validation**: Required fields for all companions
- **Modification**: Can update companion information
- **Display**: Expandable rows in participant list
- **Ticket Generation**: Separate tickets for companions

**User Experience**:
1. User selects number of companions via spinner
2. Form dynamically generates name fields
3. Each companion requires first and last name
4. Validation ensures all fields completed
5. Companions saved with participation
6. Displayed in participant details
7. Included in ticket generation

---

## 6. User Side (Front-End)

### 6.1 Main Interface
**Controller**: `FrontController`
**View**: `front.fxml`

**Components**:
- **Navigation Bar**: Access to all modules (Events, Marketplace, Forum, etc.)
- **Search Bar**: Quick event search
- **Sort Dropdown**: Multiple sorting options
- **Event Grid**: Card-based event display
- **My Participations Button**: Access personal registrations

### 6.2 Event Card Display
**Builder**: `ConstructionCartesVisiteur`

**Card Information**:
- Event image (or default placeholder)
- Event title
- Event type badge
- Date and time
- Location
- Available places indicator
- Status badge
- Action buttons (Participate, View Details)

**Visual Design**:
- Color-coded by event type
- Status indicators (green=active, red=cancelled, etc.)
- Hover effects
- Responsive layout
- Professional styling


### 6.3 Event Details Popup
**Features**:
- Full event description
- Complete schedule (dates and times)
- Venue information with address
- Organizer details
- Capacity information
- Current registration count
- Participate button
- Close button

**Layout**:
- Header with event image
- Tabbed or scrollable content
- Clear call-to-action buttons
- Professional design matching brand

### 6.4 Registration Form
**Controller**: `GestionParticipationsVisiteur.afficherFormulaireParticipation()`

**Form Fields**:
1. **Personal Information** (pre-filled from session):
   - First Name *
   - Last Name *
   - Email *
   - Phone (optional)

2. **Companion Selection**:
   - Number spinner (0 to available - 1)
   - Dynamic name fields for each companion

3. **Additional Information**:
   - Comment/Note (optional)

4. **Action Buttons**:
   - Confirm (validates and submits)
   - Cancel (closes form)

**Validation Feedback**:
- Real-time error messages
- Field-level validation
- Clear error descriptions
- Success confirmation

### 6.5 My Participations
**Controller**: `GestionParticipationsVisiteur.afficherListeMesParticipations()`

**Features**:
- List of all user registrations
- Event details for each participation
- Status indicators
- Action buttons:
  - View Ticket
  - Modify Registration
  - Cancel Participation
- Companion list display
- Registration date and time

**Filters**:
- Upcoming events
- Past events
- Cancelled registrations
- All participations

### 6.6 Ticket Display
**Controller**: `AffichageTicketsEtExportPDF.afficherTicket()`

**Features**:
- Digital ticket preview
- QR code display
- Event and participant information
- Download PDF button
- Email ticket button
- Print option
- Multiple tickets (for companions)

---

## 7. Admin Side (Back-Office)

### 7.1 Dashboard Overview
**Controller**: `EvenementController`
**View**: `Dashboard.fxml`

**Main Sections**:
1. **Analytics Dashboard** (Tab 1)
2. **Event List** (Tab 2)
3. **Create Event** (Tab 3)
4. **Modify Event** (Tab 4)
5. **Participants** (Popup)

### 7.2 Analytics Dashboard
**Controller**: `DashboardAnalytique`

**KPI Cards**:
- 📅 Total Events
- ✅ Active Events
- 👥 Total Participants
- 🎫 Confirmed Participations
- ⏳ Pending Confirmations
- 📊 Average Fill Rate (%)
- 🗓 Events This Week

**Charts and Graphs**:

1. **Pie Chart - Event Distribution by Type**:
   - Shows breakdown of events by category
   - Interactive tooltips with percentages
   - Color-coded segments

2. **Pie Chart - Event Status Distribution**:
   - Active, Cancelled, Completed, Full
   - Visual status overview

3. **Bar Chart - Top 5 Popular Events**:
   - Ranked by participation count
   - Shows event titles and participant numbers
   - Color-coded bars

4. **Bar Chart - Available Places**:
   - Shows capacity for active events
   - Helps identify events needing promotion

5. **Line Chart - Events Per Month**:
   - Trend analysis over time
   - Identifies peak periods

6. **Line Chart - Registrations Per Month**:
   - Participation trends
   - Growth tracking

7. **Pie Chart - Participation Status**:
   - Confirmed, Pending, Cancelled
   - Registration workflow insights

**Upcoming Events Section**:
- Events in next 7 days
- Quick overview with dates
- Status indicators
- Location information

**Statistics Summary**:
- Events this week
- Events this month
- Total companions
- Average fill rate

**Refresh Button**: Updates all statistics in real-time


### 7.3 Event List Management
**Controller**: `EvenementController.chargerListe()`

**Features**:
- **Search Bar**: Filter events by title
- **Sort Dropdown**: Multiple sorting criteria
- **Event Cards**: Visual representation with:
  - Event image
  - Title and description
  - Date, time, location
  - Capacity information
  - Status badge
  - Action buttons

**Action Buttons on Each Card**:
- ✏️ **Edit**: Opens modification form
- 👥 **Participants**: Shows participant list
- ❌ **Cancel**: Changes status to cancelled
- 🗑️ **Delete**: Permanently removes event

**Card Design**:
- Professional layout
- Color-coded status
- Hover effects
- Responsive grid

### 7.4 Create Event Form
**Controller**: `FormulaireCreationModificationEvenement.creerEvenement()`

**Form Sections**:

1. **Basic Information**:
   - Title * (3-100 characters)
   - Description (text area)
   - Organizer * (required)
   - Event Type * (dropdown)

2. **Schedule**:
   - Start Date * (date picker)
   - End Date * (date picker)
   - Start Time * (HH:mm format)
   - End Time * (HH:mm format)

3. **Location**:
   - Venue Name
   - Full Address

4. **Capacity**:
   - Maximum Capacity * (1-100,000)
   - Available Places (auto-set to capacity)

5. **Image**:
   - Upload Image button (file chooser)
   - Generate AI Image button (HuggingFace)
   - Image preview/filename display
   - AI generation status indicator

**Validation**:
- Real-time field validation
- Error messages displayed inline
- Required field indicators
- Format validation (dates, times, numbers)

**Buttons**:
- Create Event (validates and saves)
- Clear Form (resets all fields)


### 7.5 Modify Event Form
**Controller**: `FormulaireCreationModificationEvenement.modifierEvenement()`

**Features**:
- Search field to find event by title
- Form pre-populated with existing data
- Same fields as Create form
- Same validation rules
- Update button (saves changes)
- Cancel button (discards changes)

**Workflow**:
1. Admin searches for event or clicks edit from list
2. Form loads with current data
3. Admin modifies desired fields
4. Validation occurs on submit
5. Changes saved to database
6. Success notification
7. Returns to event list

### 7.6 Participant Management
**Controller**: `AffichageListeParticipants.afficherParticipantsGrid()`

**Popup Features**:
- **Header**: Event title and date
- **Statistics Bar**:
  - Total registrations
  - Total companions
  - Total attendees (registrations + companions)

**Participant Table**:
- **Columns**:
  - # (row number)
  - Full Name
  - Email
  - Status (confirmed/pending/cancelled)
  - Companions count
  - Comment

**Expandable Rows**:
- Click to expand
- Shows companion details:
  - Companion first and last names
  - Numbered list
- Visual indicator (▸/▾)

**Design**:
- Alternating row colors
- Status badges (color-coded)
- Professional table layout
- Scrollable content
- Close button

**Export Options** (future enhancement):
- Export to Excel
- Export to PDF
- Print participant list

---

## 8. API & Services Layer

### 8.1 EvenementService
**Location**: `src/main/java/Firma/services/GestionEvenement/EvenementService.java`

**Methods**:
- `getData()`: Retrieve all events
- `getById(int id)`: Get specific event
- `addEntity(Evenement e)`: Create new event
- `updateEntity(int id, Evenement e)`: Update event
- `deleteEntity(Evenement e)`: Delete event
- `updateStatut(int id, String statut)`: Change event status
- `getEventsByType(Type type)`: Filter by type
- `getActiveEvents()`: Get only active events
- `getUpcomingEvents()`: Get future events


### 8.2 ParticipationService
**Location**: `src/main/java/Firma/services/GestionEvenement/ParticipationService.java`

**Methods**:
- `getData()`: Get all participations
- `getById(int id)`: Get specific participation
- `addEntity(Participation p)`: Create participation
- `addEntityWithAccompagnants(Participation p, List<Accompagnant> acc)`: Create with companions
- `updateEntity(int id, Participation p)`: Update participation
- `deleteEntity(Participation p)`: Delete participation
- `updateStatut(int id, Statut statut)`: Change participation status
- `getParticipationsByEvent(int eventId)`: Get all for event
- `getParticipationsByUser(int userId)`: Get all for user
- `getParticipationByUserAndEvent(int userId, int eventId)`: Check specific registration
- `isUserAlreadyParticipating(int userId, int eventId)`: Duplicate check
- `countParticipationsByEvent(int eventId)`: Count registrations
- `getParticipantsDetailsByEvent(int eventId)`: Get full details with user info

### 8.3 AccompagnantService
**Location**: `src/main/java/Firma/services/GestionEvenement/AccompagnantService.java`

**Methods**:
- `getData()`: Get all companions
- `getById(int id)`: Get specific companion
- `addEntity(Accompagnant a)`: Create companion
- `updateEntity(int id, Accompagnant a)`: Update companion
- `deleteEntity(Accompagnant a)`: Delete companion
- `getByParticipation(int participationId)`: Get companions for participation
- `deleteByParticipation(int participationId)`: Remove all companions for participation

### 8.4 StatistiquesService
**Location**: `src/main/java/Firma/services/GestionEvenement/StatistiquesService.java`

**Methods**:
- `countEvenements()`: Total event count
- `countEvenementsActifs()`: Active events count
- `countParticipationsConfirmees()`: Confirmed participations
- `countParticipationsEnAttente()`: Pending participations
- `countTotalParticipants()`: Total unique participants
- `countAccompagnants()`: Total companions
- `tauxRemplissageMoyen()`: Average fill rate percentage
- `evenementsCetteSemaine()`: Events in current week
- `evenementsCeMois()`: Events in current month
- `repartitionParType()`: Event distribution by type
- `repartitionParStatut()`: Event distribution by status
- `repartitionParticipationsParStatut()`: Participation distribution by status
- `topEvenements()`: Top 5 events by participation
- `evenementsPlacesDisponibles()`: Available places per event
- `evenementsParMois()`: Event count per month
- `participationsParMois()`: Participation count per month


### 8.5 UtilisateurService
**Location**: `src/main/java/Firma/services/GestionEvenement/UtilisateurService.java`

**Methods**:
- `getData()`: Get all users
- `getById(int id)`: Get specific user
- `addEntity(Utilisateur u)`: Create user
- `updateEntity(int id, Utilisateur u)`: Update user
- `deleteEntity(Utilisateur u)`: Delete user
- `authenticate(String email, String password)`: Login validation
- `getUserByEmail(String email)`: Find user by email

---

## 9. Security & Session Management

### 9.1 SessionManager
**Location**: `src/main/java/Firma/tools/GestionEvenement/SessionManager.java`

**Pattern**: Singleton

**Features**:
- Stores current logged-in user
- Thread-safe implementation
- Global access point
- Session persistence during app lifecycle

**Methods**:
- `getInstance()`: Get singleton instance
- `setUtilisateur(Utilisateur u)`: Set current user
- `getUtilisateur()`: Get current user
- `isLoggedIn()`: Check if user is logged in
- `logout()`: Clear session

**Usage**:
```java
// Set user on login
SessionManager.getInstance().setUtilisateur(user);

// Get current user
Utilisateur currentUser = SessionManager.getInstance().getUtilisateur();

// Check if logged in
if (SessionManager.getInstance().isLoggedIn()) {
    // User-specific logic
}

// Logout
SessionManager.getInstance().setUtilisateur(null);
```

### 9.2 Authentication Flow
1. User enters email and password
2. `UtilisateurService.authenticate()` validates credentials
3. If valid, user object stored in SessionManager
4. User redirected to appropriate dashboard (admin/client)
5. All subsequent operations use session user
6. Logout clears session and redirects to login

### 9.3 Authorization
**Role-Based Access**:
- **Admin**: Full access to dashboard, CRUD operations, analytics
- **Client**: Access to event browsing, registration, personal participations

**Route Protection**:
- Admin routes check for admin role
- User-specific data filtered by session user ID
- Unauthorized access redirects to login


---

## 10. Email & Notification System

### 10.1 EmailService
**Location**: `src/main/java/Firma/tools/GestionEvenement/EmailService.java`

**Pattern**: Singleton

**Configuration**:
- SMTP server settings
- Sender email credentials
- Port configuration
- SSL/TLS support

**Methods**:
- `getInstance()`: Get singleton instance
- `isConfigured()`: Check if email service is set up
- `envoyerEmailConfirmation(String email, String prenom, Evenement e, String code)`: Send confirmation email
- `envoyerTicketPDF(String email, String prenom, Evenement e, byte[] pdfData)`: Send ticket as attachment

**Email Templates**:
- HTML-formatted emails
- Professional design matching brand
- Responsive layout
- Event details included
- Call-to-action buttons

### 10.2 Confirmation Email Flow
1. User completes registration form
2. Participation created with status "EN_ATTENTE"
3. Confirmation email sent with verification link
4. User clicks link in email
5. Participation status changed to "CONFIRME"
6. PDF ticket generated and sent
7. Available places decremented

**Email Content**:
- Welcome message
- Event details (title, date, location)
- Participation code
- Confirmation link
- Organizer contact information
- FIRMA branding

### 10.3 Ticket Email
**Sent After Confirmation**:
- PDF ticket attached
- Event reminder
- QR code for check-in
- Companion information
- Venue directions (optional)

### 10.4 Fallback Mechanism
If email service not configured:
- Participation directly confirmed
- Ticket displayed in browser
- Download option provided
- No email sent
- User notified of direct confirmation

---

## 11. Analytics & Reporting

### 11.1 Dashboard Analytics
**Real-Time Metrics**:
- Event counts by status
- Participation statistics
- Capacity utilization
- Trend analysis
- Popular events ranking


### 11.2 Key Performance Indicators (KPIs)

**Event Metrics**:
- Total events created
- Active events count
- Cancelled events
- Completed events
- Events at full capacity
- Events this week/month

**Participation Metrics**:
- Total registrations
- Confirmed participations
- Pending confirmations
- Cancelled registrations
- Total unique participants
- Total companions
- Average companions per registration

**Capacity Metrics**:
- Average fill rate (%)
- Total capacity across all events
- Total available places
- Events with low attendance
- Events near capacity

**Trend Metrics**:
- Events per month (historical)
- Participations per month (historical)
- Growth rate
- Peak periods identification

### 11.3 Visual Analytics

**Chart Types**:
1. **Pie Charts**: Distribution analysis (type, status)
2. **Bar Charts**: Comparisons (top events, capacity)
3. **Line Charts**: Trends over time (monthly data)
4. **Progress Bars**: Fill rate visualization

**Interactive Features**:
- Tooltips on hover
- Click to drill down (future enhancement)
- Export chart data (future enhancement)
- Refresh button for real-time updates

### 11.4 Reporting Capabilities

**Current Reports**:
- Participant list per event
- Event summary statistics
- Upcoming events list
- Top events ranking

**Export Formats**:
- PDF (participant lists, tickets)
- Excel (future enhancement)
- CSV (future enhancement)

**Report Scheduling** (future enhancement):
- Daily summary emails
- Weekly analytics reports
- Monthly performance reports

---

## 12. Technical Stack

### 12.1 Backend Technologies
- **Language**: Java 11+
- **Framework**: JavaFX (Desktop Application)
- **Database**: MySQL/MariaDB
- **ORM**: JDBC (Direct SQL queries)
- **Build Tool**: Maven


### 12.2 Frontend Technologies
- **UI Framework**: JavaFX
- **FXML**: Scene Builder for UI design
- **CSS**: Custom styling
- **Charts**: JavaFX Charts API
- **Layout**: VBox, HBox, GridPane, FlowPane

### 12.3 Libraries & Dependencies

**Core Libraries**:
- **JavaFX**: UI components and controls
- **JDBC**: Database connectivity
- **JavaMail**: Email functionality
- **iText PDF**: PDF generation
- **ZXing**: QR code generation
- **HTTP Client**: API calls (HuggingFace)

**Database**:
- **MySQL Connector/J**: JDBC driver
- **Connection Pooling**: (recommended for production)

**Utilities**:
- **Java Time API**: Date and time handling
- **Java Collections**: Data structures
- **Java Streams**: Functional programming

### 12.4 Architecture Pattern

**MVC (Model-View-Controller)**:
- **Model**: Entity classes (Evenement, Participation, etc.)
- **View**: FXML files and JavaFX components
- **Controller**: Controller classes handling business logic

**Service Layer**:
- Separates business logic from controllers
- Handles database operations
- Provides reusable methods

**Helper Classes**:
- Delegate specific responsibilities
- Improve code organization
- Enhance maintainability

**Design Patterns Used**:
- **Singleton**: SessionManager, EmailService, StatisticsService
- **Builder**: Card construction classes
- **Delegation**: Helper classes for controllers
- **Observer**: JavaFX property listeners

### 12.5 Database Connection
**Configuration**:
- Host: localhost (or configured server)
- Port: 3306 (default MySQL)
- Database: firma
- Charset: utf8mb4
- Collation: utf8mb4_general_ci

**Connection Management**:
- Connection established per operation
- Proper resource cleanup (try-with-resources)
- Exception handling
- SQL injection prevention (PreparedStatements)


### 12.6 File Structure

```
src/main/java/Firma/
├── controllers/
│   └── GestionEvenement/
│       ├── AccueilController.java              # Home dashboard
│       ├── EvenementController.java            # Admin event management
│       ├── FrontController.java                # User event browsing
│       ├── LoginController.java                # Authentication
│       ├── FormulaireCreationModificationEvenement.java  # Event CRUD forms
│       ├── ConstructionCartesEvenement.java    # Admin event cards
│       ├── ConstructionCartesVisiteur.java     # User event cards
│       ├── AffichageListeParticipants.java     # Participant list
│       ├── GestionParticipationsVisiteur.java  # User participation management
│       ├── AffichageTicketsEtExportPDF.java    # Ticket generation
│       ├── DashboardAnalytique.java            # Analytics dashboard
│       └── OutilsInterfaceGraphique.java       # UI utilities
│
├── entities/
│   └── GestionEvenement/
│       ├── Evenement.java                      # Event entity
│       ├── Participation.java                  # Participation entity
│       ├── Accompagnant.java                   # Companion entity
│       ├── Utilisateur.java                    # User entity
│       ├── Type.java                           # Event type enum
│       ├── Statutevent.java                    # Event status enum
│       ├── Statut.java                         # Participation status enum
│       └── Role.java                           # User role enum
│
├── services/
│   └── GestionEvenement/
│       ├── EvenementService.java               # Event business logic
│       ├── ParticipationService.java           # Participation business logic
│       ├── AccompagnantService.java            # Companion business logic
│       ├── UtilisateurService.java             # User business logic
│       └── StatistiquesService.java            # Analytics business logic
│
├── tools/
│   └── GestionEvenement/
│       ├── SessionManager.java                 # Session management
│       ├── EmailService.java                   # Email functionality
│       └── HuggingFaceImageService.java        # AI image generation
│
└── database/
    └── firma.sql                               # Database schema

src/main/resources/
├── GestionEvenement/
│   ├── Dashboard.fxml                          # Admin dashboard view
│   ├── front.fxml                              # User event browsing view
│   ├── Accueil.fxml                            # Home page view
│   └── LoginApplication.fxml                   # Login view
│
└── image/                                      # Event images directory
```

---

## 13. Workflow Diagrams

### 13.1 Event Creation Workflow (Admin)
```
Admin Login
    ↓
Navigate to Dashboard
    ↓
Click "Create Event" Tab
    ↓
Fill Event Form
    ├── Basic Info (title, description, organizer, type)
    ├── Schedule (dates, times)
    ├── Location (venue, address)
    ├── Capacity (max capacity)
    └── Image (upload or AI generate)
    ↓
Validate Form
    ├── Success → Save to Database
    │              ↓
    │          Set status = "actif"
    │              ↓
    │          Set available_places = capacity
    │              ↓
    │          Show success message
    │              ↓
    │          Refresh event list
    │
    └── Failure → Show error messages
                   ↓
               Stay on form
```

### 13.2 User Registration Workflow
```
User Browse Events
    ↓
Select Event
    ↓
Click "Participer" Button
    ↓
Check if already registered
    ├── Yes → Show "Already registered" message
    │
    └── No → Show Registration Form
              ↓
          Fill Form
              ├── Personal info (pre-filled from session)
              ├── Select number of companions
              ├── Fill companion names
              └── Add optional comment
              ↓
          Validate Form
              ├── Success → Create Participation (status = EN_ATTENTE)
              │              ↓
              │          Save Companions
              │              ↓
              │          Generate Participation Code
              │              ↓
              │          Check Email Service
              │              ├── Configured → Send Confirmation Email
              │              │                ↓
              │              │            Show "Check email" message
              │              │
              │              └── Not Configured → Confirm Directly
              │                                    ↓
              │                                Set status = CONFIRME
              │                                    ↓
              │                                Generate & Show Ticket
              │
              └── Failure → Show validation errors
                             ↓
                         Stay on form
```


### 13.3 Email Confirmation Workflow
```
User Registers for Event
    ↓
Participation Created (status = EN_ATTENTE)
    ↓
Email Service Sends Confirmation Email
    ├── Subject: "Confirmation de participation - [Event Title]"
    ├── Body: Event details + Confirmation link
    └── Link: Contains participation ID and verification token
    ↓
User Receives Email
    ↓
User Clicks Confirmation Link
    ↓
System Validates Link
    ├── Valid → Update Participation (status = CONFIRME)
    │            ↓
    │        Decrement Available Places
    │            ↓
    │        Generate PDF Ticket
    │            ↓
    │        Send Ticket Email
    │            ↓
    │        Show "Confirmed" page
    │
    └── Invalid → Show "Invalid link" error
```

### 13.4 Ticket Generation Workflow
```
Participation Confirmed
    ↓
System Generates PDF Ticket
    ├── Create PDF Document
    ├── Add FIRMA Branding
    ├── Add Event Information
    │   ├── Title
    │   ├── Date & Time
    │   ├── Location & Address
    │   └── Organizer Contact
    ├── Add Participant Information
    │   ├── Name
    │   ├── Email
    │   ├── Participation Code
    │   └── Number of Companions
    ├── Generate QR Code (contains participation code)
    └── Add QR Code to PDF
    ↓
For Each Companion
    ├── Create Separate Ticket
    ├── Add Companion Name
    └── Same Event Info & QR Code
    ↓
Combine All Tickets
    ↓
Save PDF File
    ↓
Return PDF
    ├── Email as Attachment
    ├── Display in Browser
    └── Offer Download
```

---

## 14. Data Validation Rules

### 14.1 Event Validation

| Field | Rule | Error Message |
|-------|------|---------------|
| Title | Required, 3-100 chars | "Le titre doit contenir au moins 3 caracteres" / "Le titre ne doit pas depasser 100 caracteres" |
| Type | Required | "Veuillez selectionner un type d'evenement" |
| Start Date | Required, >= today | "La date de debut ne peut pas etre dans le passe" |
| End Date | Required, >= start date | "La date de fin doit etre egale ou posterieure a la date de debut" |
| Start Time | Required, HH:mm format | "Format attendu : HH:mm (ex: 09:30)" |
| End Time | Required, HH:mm format, > start time (if same day) | "L'horaire de fin doit etre apres l'horaire de debut" |
| Capacity | Required, integer, 1-100,000 | "Le nombre de places doit etre superieur a 0" / "Le nombre de places ne peut pas depasser 100 000" |
| Organizer | Required | "Veuillez saisir le nom de l'organisateur" |


### 14.2 Participation Validation

| Field | Rule | Error Message |
|-------|------|---------------|
| First Name | Required, min 2 chars, letters only | "Le prenom doit contenir au moins 2 caracteres" / "Le prenom ne doit contenir que des lettres" |
| Last Name | Required, min 2 chars, letters only | "Le nom doit contenir au moins 2 caracteres" / "Le nom ne doit contenir que des lettres" |
| Email | Required, valid email format | "Format d'email invalide (ex: nom@domaine.com)" |
| Phone | Optional, 8-15 digits, + optional | "Numero de telephone invalide (8 a 15 chiffres, + optionnel)" |
| Companions | Total (user + companions) <= available places | "Pas assez de places (max : [available])" |
| Companion Names | Required for each companion | "Veuillez remplir le nom et prenom de tous les accompagnants" |
| Duplicate Check | One registration per user per event | "Vous etes deja inscrit a cet evenement" |

### 14.3 Regex Patterns

**Name Validation**:
```regex
^[A-Za-z\u00C0-\u017F\s'-]+$
```
- Allows letters (A-Z, a-z)
- Allows accented characters (À-ſ)
- Allows spaces, hyphens, apostrophes

**Email Validation**:
```regex
^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$
```
- Standard email format
- Allows alphanumeric, +, _, ., -
- Requires @ and domain with TLD

**Phone Validation**:
```regex
^[+]?[0-9]{8,15}$
```
- Optional + prefix
- 8-15 digits
- No spaces or special characters

---

## 15. User Interface Design

### 15.1 Color Scheme
- **Primary Green**: #49ad32 (FIRMA brand color)
- **Light Green**: #e8f8e0 (backgrounds, highlights)
- **Dark Green**: #2d8a1a (confirmed status)
- **Orange**: #f39c12 (warnings, pending)
- **Red**: #e74c3c (errors, cancelled)
- **Blue**: #3498db (information)
- **Purple**: #9b59b6 (special indicators)
- **Background**: #fefbde (light cream)
- **White**: #ffffff (cards, forms)
- **Gray**: #888888 (secondary text)

### 15.2 Typography
- **Headers**: System font, Bold, 18-28px
- **Body Text**: System font, Regular, 13-14px
- **Labels**: System font, Bold, 12-13px
- **Small Text**: System font, Regular, 11-12px


### 15.3 Component Styling

**Buttons**:
- Border radius: 20px (rounded)
- Padding: 8-16px
- Font weight: Bold
- Cursor: Hand pointer
- Hover effects

**Cards**:
- Background: White
- Border radius: 12-14px
- Border: 1px solid #eeeeee
- Shadow: dropshadow(gaussian, rgba(0,0,0,0.06), 8, 0, 0, 2)
- Padding: 16-20px

**Forms**:
- Input fields: Border radius 8px, border 1.5px
- Labels: Bold, 13-14px
- Error messages: Red (#e74c3c), 12px
- Required indicators: Asterisk (*)

**Status Badges**:
- Border radius: 10px
- Padding: 2-8px
- Font size: 11px
- Font weight: Bold
- Color-coded by status

### 15.4 Responsive Layout
- **FlowPane**: Auto-wrapping event cards
- **ScrollPane**: Scrollable content areas
- **VBox/HBox**: Flexible spacing
- **GridPane**: Structured forms
- **Priority.ALWAYS**: Expandable regions

---

## 16. Security Considerations

### 16.1 Current Security Measures
- **Session Management**: User authentication via SessionManager
- **Role-Based Access**: Admin vs. Client permissions
- **SQL Injection Prevention**: PreparedStatements (recommended)
- **Input Validation**: Client-side and server-side validation
- **Password Storage**: Should be encrypted (bcrypt recommended)

### 16.2 Recommended Enhancements
1. **Password Encryption**: Implement bcrypt or similar
2. **HTTPS**: Secure communication (if web-based)
3. **CSRF Protection**: Token-based validation
4. **Rate Limiting**: Prevent abuse
5. **Audit Logging**: Track all CRUD operations
6. **Email Verification**: Verify email addresses
7. **Two-Factor Authentication**: Additional security layer
8. **Session Timeout**: Auto-logout after inactivity
9. **SQL Injection**: Use PreparedStatements consistently
10. **XSS Prevention**: Sanitize user inputs

---

## 17. Performance Optimization

### 17.1 Current Optimizations
- **Lazy Loading**: Load data on demand
- **Caching**: SessionManager caches user data
- **Efficient Queries**: Targeted SQL queries
- **Async Operations**: Email sending, AI image generation


### 17.2 Recommended Enhancements
1. **Connection Pooling**: Reuse database connections
2. **Pagination**: Load events in batches
3. **Image Optimization**: Compress and resize images
4. **Caching Strategy**: Cache frequently accessed data
5. **Indexing**: Database indexes on foreign keys
6. **Query Optimization**: Analyze and optimize slow queries
7. **Batch Operations**: Bulk inserts/updates
8. **Lazy Image Loading**: Load images on scroll
9. **Background Tasks**: Queue for heavy operations
10. **Memory Management**: Proper resource cleanup

---

## 18. Testing Strategy

### 18.1 Recommended Test Types

**Unit Tests**:
- Service layer methods
- Validation logic
- Utility functions
- Entity getters/setters

**Integration Tests**:
- Database operations
- Email service
- PDF generation
- API calls (HuggingFace)

**UI Tests**:
- Form validation
- Navigation flows
- Button actions
- Data display

**End-to-End Tests**:
- Complete user registration flow
- Event creation to participation
- Email confirmation to ticket generation
- Admin dashboard operations

### 18.2 Test Coverage Goals
- Service Layer: 80%+
- Controllers: 70%+
- Utilities: 90%+
- Overall: 75%+

---

## 19. Future Enhancements

### 19.1 Planned Features
1. **Calendar View**: Visual event calendar
2. **Event Categories**: Additional filtering
3. **Waitlist**: Queue for full events
4. **Recurring Events**: Repeat event creation
5. **Event Templates**: Reusable event configurations
6. **Social Sharing**: Share events on social media
7. **Reviews & Ratings**: Post-event feedback
8. **Notifications**: Push notifications for updates
9. **Mobile App**: iOS/Android applications
10. **Payment Integration**: Paid events support

### 19.2 Analytics Enhancements
1. **Advanced Reporting**: Custom report builder
2. **Predictive Analytics**: Attendance predictions
3. **Heatmaps**: Popular event times/locations
4. **Cohort Analysis**: User behavior patterns
5. **Export Options**: Excel, CSV, JSON
6. **Scheduled Reports**: Automated email reports
7. **Dashboard Customization**: User-defined widgets


### 19.3 User Experience Improvements
1. **Dark Mode**: Theme toggle
2. **Accessibility**: WCAG compliance
3. **Multi-Language**: i18n support
4. **Keyboard Shortcuts**: Power user features
5. **Drag & Drop**: Image upload
6. **Auto-Save**: Draft event saving
7. **Undo/Redo**: Action history
8. **Tooltips**: Contextual help
9. **Onboarding**: First-time user guide
10. **Search Suggestions**: Auto-complete

### 19.4 Integration Opportunities
1. **Google Calendar**: Sync events
2. **Outlook Integration**: Calendar export
3. **Zoom/Teams**: Virtual event links
4. **Payment Gateways**: Stripe, PayPal
5. **SMS Notifications**: Twilio integration
6. **CRM Systems**: Salesforce, HubSpot
7. **Marketing Tools**: Mailchimp, SendGrid
8. **Analytics Platforms**: Google Analytics
9. **Social Media**: Facebook Events, LinkedIn
10. **Mapping Services**: Google Maps integration

---

## 20. Deployment & Maintenance

### 20.1 Deployment Requirements
- **Java Runtime**: JRE 11 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: Minimum 512MB RAM
- **Storage**: 100MB+ for application and images
- **Network**: Internet connection for email and AI features

### 20.2 Installation Steps
1. Install Java JRE 11+
2. Install MySQL/MariaDB
3. Create database: `CREATE DATABASE firma;`
4. Import schema: `mysql -u root -p firma < firma.sql`
5. Configure database connection in application
6. Configure email service (optional)
7. Run application JAR file

### 20.3 Maintenance Tasks
- **Daily**: Database backups
- **Weekly**: Log file cleanup
- **Monthly**: Performance monitoring
- **Quarterly**: Security updates
- **Annually**: Major version upgrades

### 20.4 Monitoring
- **Application Logs**: Error tracking
- **Database Performance**: Query analysis
- **Email Delivery**: Success/failure rates
- **User Activity**: Usage statistics
- **System Resources**: CPU, memory, disk

---

## 21. Troubleshooting Guide

### 21.1 Common Issues

**Issue**: Cannot connect to database
- **Solution**: Check database credentials, ensure MySQL is running, verify network connectivity

**Issue**: Email not sending
- **Solution**: Verify SMTP configuration, check firewall settings, test email credentials

**Issue**: AI image generation fails
- **Solution**: Check internet connection, verify HuggingFace API availability, use manual upload

**Issue**: PDF generation error
- **Solution**: Ensure iText library is included, check file permissions, verify QR code generation

**Issue**: Session lost after navigation
- **Solution**: Verify SessionManager implementation, check for proper user storage


### 21.2 Error Messages

| Error Code | Message | Solution |
|------------|---------|----------|
| DB_001 | "Erreur de connexion à la base de données" | Check database connection settings |
| VAL_001 | "Champs obligatoires manquants" | Fill all required fields |
| VAL_002 | "Format invalide" | Check input format (email, phone, date) |
| CAP_001 | "Pas assez de places disponibles" | Reduce number of companions or choose different event |
| DUP_001 | "Vous êtes déjà inscrit" | User already registered for this event |
| EMAIL_001 | "Erreur d'envoi d'email" | Check email service configuration |
| PDF_001 | "Erreur de génération de ticket" | Check PDF library and file permissions |
| AI_001 | "Erreur de génération d'image IA" | Check internet connection and API availability |

---

## 22. API Documentation (Internal Services)

### 22.1 EvenementService API

#### getData()
```java
List<Evenement> getData() throws SQLException
```
**Returns**: List of all events
**Throws**: SQLException if database error

#### getById(int id)
```java
Evenement getById(int id) throws SQLException
```
**Parameters**: Event ID
**Returns**: Event object or null
**Throws**: SQLException if database error

#### addEntity(Evenement e)
```java
void addEntity(Evenement e) throws SQLException
```
**Parameters**: Event object to create
**Returns**: void
**Throws**: SQLException if database error

#### updateEntity(int id, Evenement e)
```java
void updateEntity(int id, Evenement e) throws SQLException
```
**Parameters**: Event ID, Updated event object
**Returns**: void
**Throws**: SQLException if database error

#### deleteEntity(Evenement e)
```java
void deleteEntity(Evenement e) throws SQLException
```
**Parameters**: Event object to delete
**Returns**: void
**Throws**: SQLException if database error

#### updateStatut(int id, String statut)
```java
void updateStatut(int id, String statut) throws SQLException
```
**Parameters**: Event ID, New status
**Returns**: void
**Throws**: SQLException if database error

### 22.2 ParticipationService API

#### addEntityWithAccompagnants(Participation p, List<Accompagnant> acc)
```java
void addEntityWithAccompagnants(Participation p, List<Accompagnant> acc) throws SQLException
```
**Parameters**: Participation object, List of companions
**Returns**: void
**Throws**: SQLException if database error
**Description**: Creates participation and associated companions in a transaction


#### getParticipationByUserAndEvent(int userId, int eventId)
```java
Participation getParticipationByUserAndEvent(int userId, int eventId) throws SQLException
```
**Parameters**: User ID, Event ID
**Returns**: Participation object or null
**Throws**: SQLException if database error
**Description**: Finds specific user's participation for an event

#### isUserAlreadyParticipating(int userId, int eventId)
```java
boolean isUserAlreadyParticipating(int userId, int eventId) throws SQLException
```
**Parameters**: User ID, Event ID
**Returns**: true if user already registered, false otherwise
**Throws**: SQLException if database error
**Description**: Checks for duplicate registrations

#### countParticipationsByEvent(int eventId)
```java
int countParticipationsByEvent(int eventId) throws SQLException
```
**Parameters**: Event ID
**Returns**: Number of participations
**Throws**: SQLException if database error

#### getParticipantsDetailsByEvent(int eventId)
```java
List<Map<String, Object>> getParticipantsDetailsByEvent(int eventId) throws SQLException
```
**Parameters**: Event ID
**Returns**: List of maps containing participant details and companions
**Throws**: SQLException if database error
**Description**: Returns complete participant information including user details and companions

---

## 23. Database Schema Details

### 23.1 Table Relationships

```
utilisateur (1) ──────< (N) participations (N) >────── (1) evenements
                              │
                              │ (1)
                              │
                              ▼
                            (N) accompagnants
```

**Relationships**:
- One user can have many participations (1:N)
- One event can have many participations (1:N)
- One participation can have many companions (1:N)

### 23.2 Indexes (Recommended)

```sql
-- Primary Keys (auto-indexed)
ALTER TABLE evenements ADD PRIMARY KEY (id_evenement);
ALTER TABLE participations ADD PRIMARY KEY (id_participation);
ALTER TABLE accompagnants ADD PRIMARY KEY (id_accompagnant);
ALTER TABLE utilisateur ADD PRIMARY KEY (id);

-- Foreign Keys
ALTER TABLE participations ADD INDEX idx_evenement (id_evenement);
ALTER TABLE participations ADD INDEX idx_utilisateur (id_utilisateur);
ALTER TABLE accompagnants ADD INDEX idx_participation (id_participation);

-- Search Optimization
ALTER TABLE evenements ADD INDEX idx_titre (titre);
ALTER TABLE evenements ADD INDEX idx_date_debut (date_debut);
ALTER TABLE evenements ADD INDEX idx_statut (statut);
ALTER TABLE evenements ADD INDEX idx_type (type_evenement);

-- Unique Constraints
ALTER TABLE participations ADD UNIQUE KEY unique_user_event (id_utilisateur, id_evenement);
ALTER TABLE utilisateur ADD UNIQUE KEY unique_email (email);
```


### 23.3 Sample Data Queries

**Get all active events with available places**:
```sql
SELECT * FROM evenements 
WHERE statut = 'actif' 
  AND places_disponibles > 0 
  AND date_debut >= CURDATE()
ORDER BY date_debut ASC;
```

**Get participant count per event**:
```sql
SELECT 
    e.id_evenement,
    e.titre,
    COUNT(p.id_participation) as total_participants,
    SUM(p.nombre_accompagnants) as total_accompagnants,
    COUNT(p.id_participation) + SUM(p.nombre_accompagnants) as total_personnes
FROM evenements e
LEFT JOIN participations p ON e.id_evenement = p.id_evenement
WHERE p.statut = 'confirme'
GROUP BY e.id_evenement, e.titre;
```

**Get user's upcoming events**:
```sql
SELECT e.*, p.statut as participation_statut, p.code_participation
FROM evenements e
INNER JOIN participations p ON e.id_evenement = p.id_evenement
WHERE p.id_utilisateur = ? 
  AND e.date_debut >= CURDATE()
  AND p.statut != 'annule'
ORDER BY e.date_debut ASC;
```

**Get events by type with statistics**:
```sql
SELECT 
    type_evenement,
    COUNT(*) as total_events,
    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as active_events,
    AVG((capacite_max - places_disponibles) / capacite_max * 100) as avg_fill_rate
FROM evenements
GROUP BY type_evenement;
```

---

## 24. Conclusion

### 24.1 System Summary
The FIRMA Event Management System is a comprehensive, feature-rich solution for managing agricultural events. It provides:

- **Complete CRUD Operations**: Full event and participation management
- **User-Friendly Interfaces**: Separate admin and user experiences
- **Advanced Features**: AI image generation, email notifications, PDF tickets
- **Real-Time Analytics**: Comprehensive dashboard with charts and KPIs
- **Scalable Architecture**: Well-organized MVC pattern with service layer
- **Professional Design**: Modern UI with consistent branding

### 24.2 Key Strengths
1. **Comprehensive Functionality**: Covers entire event lifecycle
2. **User Experience**: Intuitive interfaces for both admins and users
3. **Automation**: Email confirmations, ticket generation, capacity management
4. **Analytics**: Rich insights and reporting capabilities
5. **Extensibility**: Modular design allows easy feature additions
6. **Validation**: Robust input validation and error handling
7. **Modern Features**: AI integration, QR codes, PDF generation


### 24.3 Areas for Improvement
1. **Security**: Implement password encryption and enhanced authentication
2. **Performance**: Add connection pooling and caching strategies
3. **Testing**: Comprehensive test coverage
4. **Documentation**: API documentation and user manuals
5. **Accessibility**: WCAG compliance for UI
6. **Mobile**: Responsive design or native mobile apps
7. **Internationalization**: Multi-language support
8. **Soft Delete**: Implement soft delete for data recovery

### 24.4 Business Value
The system provides significant value to FIRMA by:

- **Streamlining Operations**: Automated event management reduces manual work
- **Improving User Experience**: Easy registration and ticket access
- **Data-Driven Decisions**: Analytics inform event planning
- **Professional Image**: Branded tickets and communications
- **Scalability**: Handles growing event portfolio
- **Cost Efficiency**: Free AI image generation, automated processes
- **Engagement**: Email notifications keep users informed
- **Insights**: Understand participant behavior and preferences

### 24.5 Technical Excellence
The codebase demonstrates:

- **Clean Architecture**: Separation of concerns with MVC pattern
- **Code Organization**: Logical package structure
- **Reusability**: Service layer and helper classes
- **Maintainability**: Clear naming conventions and structure
- **Best Practices**: Proper exception handling, resource management
- **Modern Java**: Use of Java 11+ features (LocalDate, Streams, etc.)
- **Design Patterns**: Singleton, Builder, Delegation patterns

---

## 25. Appendix

### 25.1 Glossary

- **Accompagnant**: Companion/guest accompanying a participant
- **Capacité**: Event capacity (maximum attendees)
- **Événement**: Event
- **Participation**: Registration/participation in an event
- **Places disponibles**: Available seats/places
- **Statut**: Status (of event or participation)
- **Utilisateur**: User
- **CRUD**: Create, Read, Update, Delete operations
- **KPI**: Key Performance Indicator
- **MVC**: Model-View-Controller architecture pattern
- **SMTP**: Simple Mail Transfer Protocol (for email)
- **QR Code**: Quick Response code (2D barcode)
- **PDF**: Portable Document Format

### 25.2 Acronyms

- **API**: Application Programming Interface
- **CSS**: Cascading Style Sheets
- **FXML**: FX Markup Language (JavaFX)
- **JDBC**: Java Database Connectivity
- **JRE**: Java Runtime Environment
- **SQL**: Structured Query Language
- **UI**: User Interface
- **UX**: User Experience
- **WCAG**: Web Content Accessibility Guidelines


### 25.3 References

**Technologies**:
- JavaFX Documentation: https://openjfx.io/
- MySQL Documentation: https://dev.mysql.com/doc/
- iText PDF: https://itextpdf.com/
- ZXing (QR Codes): https://github.com/zxing/zxing
- HuggingFace API: https://huggingface.co/docs/api-inference

**Design Patterns**:
- MVC Pattern: https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller
- Singleton Pattern: https://refactoring.guru/design-patterns/singleton
- Builder Pattern: https://refactoring.guru/design-patterns/builder

**Best Practices**:
- Java Coding Conventions: https://www.oracle.com/java/technologies/javase/codeconventions-contents.html
- SQL Best Practices: https://www.sqlstyle.guide/
- JavaFX Best Practices: https://openjfx.io/openjfx-docs/

### 25.4 Contact & Support

For questions or support regarding the Event Management System:

- **Technical Documentation**: This report
- **Source Code**: `src/main/java/Firma/`
- **Database Schema**: `src/main/java/Firma/database/firma.sql`
- **Issue Tracking**: (Configure your issue tracker)
- **Development Team**: FIRMA Development Team

---

## Document Information

**Report Title**: FIRMA - Event Management System (Gestion Événement) - Comprehensive Technical Report

**Version**: 1.0

**Date**: March 30, 2026

**Author**: AI Technical Analyst

**Purpose**: Complete documentation of the Event Management module functionality, architecture, and implementation details

**Audience**: Developers, System Administrators, Project Managers, Stakeholders

**Status**: Final

---

**End of Report**

---

*This report provides a comprehensive overview of the FIRMA Event Management System. For specific implementation details, refer to the source code and database schema. For questions or clarifications, contact the development team.*
