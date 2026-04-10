import { Helmet } from 'react-helmet-async';
import { useEffect } from 'react';

/**
 * useSEO Hook
 * 
 * Manages page title and meta tags for SEO in React SPA
 * 
 * @param {Object} options - SEO configuration
 * @param {string} options.title - Page title
 * @param {string} options.description - Meta description
 * @param {string} options.keywords - Meta keywords (comma-separated)
 * @param {string} options.ogImage - Open Graph image URL
 * @param {string} options.canonical - Canonical URL
 * @param {string} options.type - Schema type (e.g., 'WebPage', 'Article')
 * 
 * @example
 * useSEO({
 *   title: 'Students - University Management System',
 *   description: 'View and manage all students in the system',
 *   keywords: 'students, university, management'
 * });
 */
export function useSEO({
  title = 'University Management System',
  description = 'Manage students, courses, instructors, classrooms, and departments with advanced reporting features.',
  keywords = 'University, Management, Students, Courses, Instructors',
  ogImage = null,
  canonical = null,
  type = 'WebPage',
} = {}) {
  const appName = 'University Management System';
  const finalTitle = title.includes(appName) ? title : `${title} - ${appName}`;
  const fullUrl = canonical || window.location.href;
  const ogImageUrl = ogImage || `${window.location.origin}/images/og-default.png`;

  // Update page title in document head
  useEffect(() => {
    document.title = finalTitle;
    
    // Update title element ID if it exists
    const titleElement = document.getElementById('page-title');
    if (titleElement) {
      titleElement.textContent = finalTitle;
    }
  }, [finalTitle]);

  return (
    <Helmet>
      {/* Basic Meta Tags */}
      <title>{finalTitle}</title>
      <meta name="description" content={description} />
      <meta name="keywords" content={keywords} />
      <meta name="theme-color" content="#321fdb" />
      
      {/* Open Graph Tags */}
      <meta property="og:type" content="website" />
      <meta property="og:title" content={finalTitle} />
      <meta property="og:description" content={description} />
      <meta property="og:image" content={ogImageUrl} />
      <meta property="og:url" content={fullUrl} />
      <meta property="og:site_name" content={appName} />
      
      {/* Twitter Card Tags */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={finalTitle} />
      <meta name="twitter:description" content={description} />
      <meta name="twitter:image" content={ogImageUrl} />
      
      {/* Canonical */}
      <link rel="canonical" href={fullUrl} />
      
      {/* Schema.org JSON-LD */}
      <script type="application/ld+json">
        {JSON.stringify({
          '@context': 'https://schema.org',
          '@type': type,
          name: finalTitle,
          description: description,
          url: fullUrl,
          image: ogImageUrl,
        })}
      </script>
    </Helmet>
  );
}

/**
 * PageSEO Component
 * 
 * Wrapper component for easy SEO management in React Pages
 * 
 * @example
 * <PageSEO
 *   title="Students"
 *   description="Manage all students"
 *   keywords="students, management"
 * >
 *   {children}
 * </PageSEO>
 */
export function PageSEO({
  title = 'University Management System',
  description = 'Manage students, courses, instructors, classrooms, and departments.',
  keywords = 'University, Management',
  children,
}) {
  useSEO({ title, description, keywords });
  return children;
}

export default useSEO;
