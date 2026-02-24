# mod_longpage Architecture (Brief)

**Core:**
- Standard Moodle activity module (lib.php, locallib.php, mod_form.php, view.php)
- Database tables for pages, annotations, reading progress

**Frontend:**
- Vue 3 SPA for interactive page display, annotation, and teacher dashboard
- Chart.js for analytics

**APIs:**
- PHP external services for AJAX, analytics, annotation, progress, questions

**Events:**
- Custom event classes for tracking user actions (view, click, scroll, etc.)

**i18n:**
- Uses Moodle PHP lang files (lang/en/longpage.php, lang/de/longpage.php)

**Admin:**
- Settings, permissions, and AJAX model check for AI integration
    end

    %% Local Services
    subgraph "Local Services"
        subgraph "Constants"
            ANNO_TYPE[annotation_type.php]
            SELECTOR[selector.php]
        end

        subgraph "Post Recommendation"
            POST_CALC[post_recommendation_calculation_task.php]
            SIM_CALC[similarity_calculator.php]
            POST_SIM[post_similarity_calculator.php]
        end

        subgraph "Thread Subscriptions"
            THREAD_MANAGE[manage_thread_subscriptions_task.php]
            POST_ACTION[post_action.php]
        end
    end

    %% Privacy & Compliance
    subgraph "Privacy & Compliance"
        PRIVACY[privacy/provider.php<br/>GDPR Compliance]
        SEARCH[search/activity.php<br/>Search Provider]
    end

    %% Analytics
    subgraph "Analytics"
        ACTIVITY_BASE[activity_base.php]
        COGNITIVE[cognitive_depth.php]
        SOCIAL[social_breadth.php]
    end

    %% Backup/Restore
    subgraph "Backup/Restore"
        BACKUP_TASK[backup_longpage_activity_task]
        BACKUP_STEPS[backup_longpage_stepslib]
        RESTORE_TASK[restore_longpage_activity_task]
        RESTORE_STEPS[restore_longpage_stepslib]
    end

    %% Database Schema
    subgraph "Database Schema"
        DB_ACCESS[access.php<br/>Capabilities]
        DB_INSTALL[install.php & install.xml<br/>Installation]
        DB_UPGRADE[upgrade.php<br/>Updates]
        DB_SERVICES[services.php<br/>Web Services]
        DB_MESSAGES[messages.php<br/>Messaging]
        DB_LOG[log.php<br/>Logging]
    end

    %% Frontend
    subgraph "Frontend Layer"
        AMD[AMD Modules<br/>amd/src/app-lazy.js]
        VUE[Vue.js Components<br/>vue/src/]
        CSS[styles.css]
    end

    %% File Management
    subgraph "File Management"
        PIX[pix/<br/>Images & Icons]
        LANG[lang/<br/>Language Strings]
        BACKUP_FILES[backup/<br/>Backup Definitions]
    end

    %% Relationships
    MC --> LIB
    LIB --> LOCALLIB
    LIB --> DB
    VIEW --> PAGE_API
    VIEW --> AMD
    FORM --> LIB

    BASE_EXT --> PAGE_API
    BASE_EXT --> POST_API
    BASE_EXT --> HIGHLIGHT_API
    BASE_EXT --> QUESTION_API
    BASE_EXT --> READING_API
    BASE_EXT --> USER_API
    BASE_EXT --> THREAD_API
    BASE_EXT --> ANNOTATION_API
    BASE_EXT --> UTILITY_API
    BASE_EXT --> INTERACTION_API
    BASE_EXT --> QUESTIONS_BANK_API

    POST_API --> POST_CALC
    POST_API --> SIM_CALC
    POST_API --> THREAD_MANAGE
    THREAD_API --> POST_ACTION

    PAGE_API --> EVENT_BASE
    VIEW --> MODULE_VIEWED
    VIEW --> MODULE_CLICKED
    POST_API --> MODULE_QUESTION

    ANNO_TYPE --> ANNOTATION_API
    SELECTOR --> HIGHLIGHT_API

    LIB --> PRIVACY
    LIB --> SEARCH

    DB_ACCESS --> MC
    DB_SERVICES --> BASE_EXT

    AMD --> VUE
    VIEW --> CSS

    BACKUP_TASK --> BACKUP_STEPS
    RESTORE_TASK --> RESTORE_STEPS

    ACTIVITY_BASE --> COGNITIVE
    ACTIVITY_BASE --> SOCIAL

    %% External Dependencies
    LIB --> FS
    LIB --> CACHE
    PAGE_API --> DB
    POST_API --> DB
```

## Component Overview

### Core Integration

- **Moodle Core**: Central integration with Moodle's database, file system, and cache systems
- **Module Core**: Main PHP files providing core functionality (lib.php, view.php, mod_form.php, etc.)

### API Layer

The module provides a comprehensive external API with 12 specialized services:

- **Page Management**: Core page operations and content delivery
- **Discussion System**: Post creation, management, and interactions
- **Annotation System**: Text highlighting and annotation features
- **Quiz Integration**: Embedded question functionality
- **Progress Tracking**: Reading progress and analytics
- **User Management**: User-specific operations and preferences
- **Thread Management**: Discussion thread operations
- **Utility Services**: Helper functions and utilities

### Advanced Features

- **Event System**: Activity tracking and learning analytics
- **Post Recommendation**: ML-based similarity calculations for content recommendations
- **Thread Subscriptions**: Automated notification system for discussions

### Compliance & Standards

- **GDPR Compliance**: Privacy API implementation for data protection
- **Moodle Search**: Integration with Moodle's global search functionality
- **Learning Analytics**: Cognitive depth and social breadth indicators

### Data Management

- **Backup/Restore**: Complete backup and restoration capabilities
- **Database Schema**: Modular database structure with migration support
- **Capability Management**: Fine-grained permission system

### Frontend Architecture

- **AMD/JavaScript Modules**: Interactive features and client-side functionality
- **Vue.js Components**: Modern reactive UI components
- **Responsive CSS**: Mobile-first design approach

### Supporting Systems

- **Internationalization**: Multi-language support infrastructure
- **Asset Management**: Images, icons, and media files
- **Modular Constants**: Centralized configuration and constants
