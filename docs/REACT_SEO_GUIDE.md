# React SPA SEO Optimization Guide

## Overview

This guide explains how to add SEO optimization to your React Single-Page Application (SPA) using React Helmet Async and the custom `useSEO` hook.

---

## Installation

### 1. Install Dependencies

The required package has been added to `package.json`:

```bash
npm install
```

This installs:
- `react-helmet-async` - For managing meta tags in React

### 2. Verify Installation

Check that `react-helmet-async` is in your `package.json`:

```json
{
  "dependencies": {
    "react-helmet-async": "^2.0.4"
  }
}
```

---

## Setup

### 1. Main App Entry Point

The `main.jsx` file has been updated to wrap your app with `HelmetProvider`:

```jsx
import { HelmetProvider } from 'react-helmet-async';

function App() {
  return (
    <HelmetProvider>
      <BrowserRouter basename="/university">
        <AuthProvider>
          <AppLayout />
        </AuthProvider>
      </BrowserRouter>
    </HelmetProvider>
  );
}
```

**This is essential** - it enables `useSEO` hook functionality throughout your app.

### 2. Blade Template

The `university.blade.php` has been updated with:

- Comprehensive meta tags
- Open Graph tags
- Trending fonts (Inter, Poppins, JetBrains Mono)
- Favicon support
- JSON-LD structured data placeholder
- ID attributes for dynamic updates: `id="page-title"`

---

## Using the useSEO Hook

### Basic Usage

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function StudentsPage() {
  // Set SEO tags for this page
  useSEO({
    title: 'Students',
    description: 'View and manage all students in the university system.',
    keywords: 'students, university, management, enrollment'
  });

  return (
    <div>
      <h1>Students</h1>
      {/* Your page content */}
    </div>
  );
}

export default StudentsPage;
```

### Hook Parameters

```jsx
useSEO({
  // Page title (will be appended with app name)
  title: 'Students',
  
  // Meta description (50-160 characters recommended)
  description: 'View and manage all students...',
  
  // Comma-separated keywords
  keywords: 'students, university, management',
  
  // Open Graph image URL (optional)
  ogImage: null,
  
  // Canonical URL (optional - auto-detected)
  canonical: null,
  
  // Schema.org type (optional)
  type: 'WebPage'
});
```

---

## SEO Best Practices

### 1. Page Titles

✅ **Good Examples:**
- `Students` → `Students - University Management System`
- `Create New Student` → `Create New Student - University Management System`
- `Edit Student` → `Edit Student - University Management System`
- `Master Report` → `Master Report - University Management System`

❌ **Avoid:**
- Just "Page" or generic titles
- Missing entity names
- Too long (over 60 chars is truncated in search results)

### 2. Meta Descriptions

✅ **Good Examples:**
- "View and manage all students in the university system with filtering and bulk actions."
- "Create a new student record with enrollment details and contact information."
- "Edit student information including enrollment status and contact details."

✅ **Guidelines:**
- 50-160 characters
- Includes action verbs (manage, view, create, edit)
- Specifies what users will find
- Natural, readable language

❌ **Avoid:**
- Keyword stuffing
- Duplicate descriptions across pages
- Vague or generic descriptions (e.g., "Student page")

### 3. Keywords

✅ **Good Examples:**
```jsx
keywords: 'students, university, management, enrollment, academic records'
keywords: 'courses, curriculum, course catalog, scheduling'
keywords: 'instructors, faculty, professors, teaching staff'
```

✅ **Guidelines:**
- 2-4 primary keywords
- Separated by commas
- Include entity type (students, courses, etc.)
- Include action words (manage, view, create)

❌ **Avoid:**
- Keyword stuffing (10+ keywords)
- Irrelevant keywords
- Keywords that don't match page content

---

## Implementation Examples

### Students Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function StudentsPage() {
  useSEO({
    title: 'Students',
    description: 'Manage all students in the university system with enrollment tracking and academic records.',
    keywords: 'students, university, management, enrollment, academic records'
  });

  return (
    <div>
      <h1>Students Management</h1>
      {/* List students here */}
    </div>
  );
}

export default StudentsPage;
```

### Create Student Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function StudentsCreatePage() {
  useSEO({
    title: 'Create New Student',
    description: 'Register a new student in the university system with enrollment details and contact information.',
    keywords: 'create student, enrollment, new student, registration'
  });

  return (
    <div>
      <h1>Create New Student</h1>
      {/* Form here */}
    </div>
  );
}

export default StudentsCreatePage;
```

### Edit Student Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function StudentsEditPage() {
  useSEO({
    title: 'Edit Student',
    description: 'Modify student information including contact details, enrollment status, and academic records.',
    keywords: 'edit student, update enrollment, student records'
  });

  return (
    <div>
      <h1>Edit Student</h1>
      {/* Form here */}
    </div>
  );
}

export default StudentsEditPage;
```

### Courses Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function CoursesPage() {
  useSEO({
    title: 'Courses',
    description: 'Manage all university courses including schedules, instructors, enrolled students, and curriculum details.',
    keywords: 'courses, university, management, curriculum, course catalog, scheduling'
  });

  return (
    <div>
      <h1>Courses Management</h1>
      {/* Courses list here */}
    </div>
  );
}

export default CoursesPage;
```

### Instructors Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function InstructorsPage() {
  useSEO({
    title: 'Instructors',
    description: 'View and manage faculty members, their qualifications, teaching assignments, and contact information.',
    keywords: 'instructors, faculty, management, professors, teaching staff'
  });

  return (
    <div>
      <h1>Instructors Management</h1>
      {/* Instructors list here */}
    </div>
  );
}

export default InstructorsPage;
```

### Master Report Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function ReportPage() {
  useSEO({
    title: 'Master Report',
    description: 'Comprehensive reporting dashboard with enrollment analytics, student demographics, and system-wide insights.',
    keywords: 'reporting, analytics, enrollment, statistics, university reports, data analysis'
  });

  return (
    <div>
      <h1>Master Report</h1>
      {/* Report content here */}
    </div>
  );
}

export default ReportPage;
```

### Login Page

```jsx
import { useSEO } from '../hooks/useSEO.jsx';

function LoginPage() {
  useSEO({
    title: 'Login',
    description: 'Sign in to your University Management System account to access student records, courses, and reporting tools.',
    keywords: 'login, sign in, authentication, university system, access'
  });

  return (
    <div>
      <h1>Sign In</h1>
      {/* Login form */}
    </div>
  );
}

export default LoginPage;
```

---

## What Gets Generated

When you call `useSEO()`, it automatically generates:

### 1. Page Title
```html
<title>Students - University Management System</title>
```

### 2. Meta Tags
```html
<meta name="description" content="Manage all students...">
<meta name="keywords" content="students, university, management...">
<meta name="theme-color" content="#321fdb">
```

### 3. Open Graph Tags (Social Sharing)
```html
<meta property="og:type" content="website">
<meta property="og:title" content="Students - University Management System">
<meta property="og:description" content="Manage all students...">
<meta property="og:url" content="https://yourdomain.com/university/students">
<meta property="og:image" content="https://yourdomain.com/images/og-default.png">
```

### 4. Twitter Card Tags
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Students - University Management System">
<meta name="twitter:description" content="Manage all students...">
```

### 5. Canonical URL
```html
<link rel="canonical" href="https://yourdomain.com/university/students">
```

### 6. JSON-LD Structured Data
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Students - University Management System",
  "description": "Manage all students...",
  "url": "https://yourdomain.com/university/students"
}
</script>
```

---

## Trending Fonts in React

The updated styles include trending modern fonts:

### Available Fonts

1. **Inter** - Primary font for body text
   ```css
   font-family: 'Inter', sans-serif;
   ```

2. **Poppins** - Display/heading font
   ```css
   font-family: 'Poppins', sans-serif;
   ```

3. **JetBrains Mono** - Code/monospace font
   ```css
   font-family: 'JetBrains Mono', monospace;
   ```

### Using Fonts in Components

```jsx
// Using inline styles
<h1 style={{ fontFamily: 'Poppins' }}>Page Title</h1>

// Using CSS classes (if defined in app.css)
<h1 className="font-display">Page Title</h1>

// CoreUI components inherit fonts automatically
<CButton>Click Me</CButton> {/* Uses Inter by default */}
```

---

## Testing SEO

### 1. Check in Browser DevTools

```
1. Open DevTools (F12)
2. Go to Console
3. Run: document.title
4. Check <head> section for meta tags
```

### 2. Verify Meta Tags

Visit your page and check the HTML source:
```html
View Page Source (Ctrl+U) → Search for meta tags
```

### 3. Social Media Preview

- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)

Test URLs in format: `https://yourdomain.com/university/students`

### 4. Google Search Console

- [Google Search Console](https://search.google.com/search-console)
- Submit your sitemap.xml
- Check for indexing issues

### 5. Structured Data Testing

- [Schema.org Validator](https://validator.schema.org/)
- Paste your page URL
- Verify JSON-LD markup

---

## File Locations

- **useSEO Hook**: `resources/js/university/hooks/useSEO.jsx`
- **Main App**: `resources/js/university/main.jsx` (already updated with HelmetProvider)
- **Blade Template**: `resources/views/university.blade.php`
- **Examples**: `resources/js/university/REACT_SEO_EXAMPLES.jsx`
- **Styles**: `resources/css/app.css` (includes font definitions)

---

## Troubleshooting

### Meta tags not updating?

1. Ensure HelmetProvider wraps your entire app
2. Clear browser cache (Ctrl+Shift+R)
3. Check that useSEO is called in render

### Page title not changing?

1. Verify the ID attribute: `id="page-title"` in Blade
2. Update useSEO() title value
3. Test in different page

### Fonts not loading?

1. Check Network tab in DevTools
2. Verify Google Fonts CDN is accessible
3. Check CSS @import statements

### OG images not showing in social preview?

1. Create `public/images/og-default.png` (1200x630px recommended)
2. Update OG image URL in useSEO if custom
3. Test with Facebook/Twitter validators

---

## Next Steps

1. ✅ Install `react-helmet-async` (already done)
2. ✅ Wrap app with HelmetProvider (already done)
3. ✅ Update Blade template (already done)
4. ⏳ **Add useSEO to each page component**
5. ⏳ Create OG images for better social sharing
6. ⏳ Test with search console
7. ⏳ Monitor ranking improvements

---

## Resources

- [React Helmet Async Docs](https://github.com/staylor/react-helmet-async)
- [Google SEO Guide](https://developers.google.com/search/docs)
- [Schema.org Documentation](https://schema.org/docs/schemas.html)
- [Web.dev SEO Guide](https://web.dev/lighthouse-seo/)
- [Markdown Format Guide](https://www.markdownguide.org/)

---

## Summary

✅ React Helmet Async installed and configured
✅ HelmetProvider wraps entire app
✅ Blade template updated with SEO tags
✅ useSEO hook ready for use
✅ Trending fonts configured
✅ Examples provided

**Next**: Add `useSEO({...})` calls to your page components following the examples above.

---

Generated: April 10, 2026
