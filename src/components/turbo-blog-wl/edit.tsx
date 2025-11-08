// External Dependencies.
import React, { ReactElement, useEffect, useRef, useState } from 'react';

// Divi Dependencies.
import {
  ModuleContainer,
  ElementComponents,
} from '@divi/module';
import { useFetch } from '@divi/rest';

// Local Dependencies.
import { TurboBlogWlEditProps } from './types';
import { ModuleStyles } from './styles';
import { map } from 'lodash';
import { __ } from '@wordpress/i18n';
import { ModuleScriptData } from './module-script-data';
import { moduleClassnames } from './module-classnames';

/**
 * Generate pagination range with ellipsis
 */
const getPaginationRange = (currentPage: number, totalPages: number): (number | string)[] => {
  const delta = 2;
  const range: number[] = [];
  const rangeWithDots: (number | string)[] = [];
  let l: number | undefined;

  range.push(1);

  for (let i = currentPage - delta; i <= currentPage + delta; i++) {
    if (i > 1 && i < totalPages) {
      range.push(i);
    }
  }

  if (totalPages > 1) {
    range.push(totalPages);
  }

  for (const i of range) {
    if (l !== undefined) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
};

// Helper function to extract text from content and truncate
const getCustomExcerpt = (post: any, length: number): string => {
  if (post?.excerpt?.rendered && post.excerpt.rendered.trim()) {
    const tmp = document.createElement('div');
    tmp.innerHTML = post.excerpt.rendered;
    const text = tmp.textContent || tmp.innerText || '';
    if (text.trim()) {
      return truncateText(text, length);
    }
  }
  
  if (post?.content?.rendered) {
    const tmp = document.createElement('div');
    tmp.innerHTML = post.content.rendered;
    let text = tmp.textContent || tmp.innerText || '';
    text = text.replace(/\s+/g, ' ').trim();
    
    if (text) {
      return truncateText(text, length);
    }
  }
  
  return '';
};

const truncateText = (text: string, length: number): string => {
  if (text.length <= length) {
    return text;
  }
  
  const truncated = text.substring(0, length);
  const lastSpace = truncated.lastIndexOf(' ');
  
  if (lastSpace > 0) {
    return truncated.substring(0, lastSpace) + '...';
  }
  
  return truncated + '...';
};

// Utility function to clean and validate comma-separated IDs
const cleanAndValidateIds = (str: string | undefined | null): string => {
  if (!str || typeof str !== 'string') return '';
  
  try {
    // Remove trailing/leading commas and whitespace
    let cleaned = str.trim().replace(/^,+|,+$/g, '');
    
    if (!cleaned) return '';
    
    // Split by comma, trim each part, filter out empty strings and non-numeric values
    const ids = cleaned
      .split(',')
      .map(id => id.trim())
      .filter(id => id && /^\d+$/.test(id) && parseInt(id) > 0);
    
    // Remove duplicates using Set
    const uniqueIds = Array.from(new Set(ids));
    
    // Return comma-separated string of valid IDs
    return uniqueIds.length > 0 ? uniqueIds.join(',') : '';
  } catch (error) {
    console.error('Error cleaning IDs:', error);
    return '';
  }
};

// Helper function to fetch terms - extracts categories from field-restricted posts
const useTerms = (
  postType: string, 
  filterType: string, 
  showFilter: boolean, 
  categories: string, 
  tags: string
) => {
  const {
    fetch: fetchTerms,
    response: termsResponse,
    isLoading: termsLoading,
  } = useFetch<any[]>([]);

  const fetchAbortRef = useRef<AbortController>();

  useEffect(() => {
    if (!showFilter) return;

    if (fetchAbortRef.current) {
      fetchAbortRef.current.abort();
    }

    fetchAbortRef.current = new AbortController();
    
    const cleanCategories = cleanAndValidateIds(categories);
    const cleanTags = cleanAndValidateIds(tags);
    
    // If categories are specified, just return those as terms
    // Don't fetch posts - we know which categories to show
    if (cleanCategories) {
      const categoryIds = cleanCategories.split(',').map(id => parseInt(id.trim()));
      
      // Fetch just those category terms
      const taxonomy = 'categories';
      const termQuery = `include=${cleanCategories}&per_page=100`;
      
      //console.log('Fetching specified categories:', `/wp/v2/${taxonomy}?${termQuery}`);
      
      fetchTerms({
        restRoute: `/wp/v2/${taxonomy}?${termQuery}`,
        method: 'GET',
        signal: fetchAbortRef.current.signal,
      }).catch((error) => {
        if (error.name !== 'AbortError') {
          console.error('Terms fetch error:', error);
        }
      });
    } else {
      // No categories specified - extract from posts matching tag filter
      let postQuery = `per_page=100&_embed`;
      
      // Apply tag filter if specified
      if (cleanTags) {
        postQuery += `&tags=${cleanTags}`;
      }
      
      const endpoint = postType === 'page' ? 'pages' : 'posts';
      
      //console.log('Fetching posts for category extraction:', `/wp/v2/${endpoint}?${postQuery}`);
      
      fetchTerms({
        restRoute: `/wp/v2/${endpoint}?${postQuery}`,
        method: 'GET',
        signal: fetchAbortRef.current.signal,
      }).catch((error) => {
        if (error.name !== 'AbortError') {
          console.error('Terms fetch error:', error);
        }
      });
    }

    return () => {
      if (fetchAbortRef.current) {
        fetchAbortRef.current.abort();
      }
    };
  }, [filterType, showFilter, postType, categories, tags]);

  // Process the response based on what we fetched
  const extractedTerms = React.useMemo(() => {
    if (!Array.isArray(termsResponse) || termsResponse.length === 0) return [];
    
    // Check if response is categories (has taxonomy property)
    if (termsResponse[0]?.taxonomy === 'category') {
      // Direct category response - create a copy before sorting
      return [...termsResponse].sort((a, b) => a.name.localeCompare(b.name));
    }
    
    // Response is posts - extract categories
    const categoryMap = new Map();
    
    termsResponse.forEach(post => {
      const postCategories = post?._embedded?.['wp:term']?.[0] || [];
      postCategories.forEach((cat: any) => {
        if (!categoryMap.has(cat.id)) {
          categoryMap.set(cat.id, cat);
        }
      });
    });
    
    return Array.from(categoryMap.values()).sort((a, b) => a.name.localeCompare(b.name));
  }, [termsResponse]);

  return { terms: extractedTerms, termsLoading };
};

const TurboBlogWlEdit = (props: TurboBlogWlEditProps): ReactElement => {
  const {
    attrs,
    id,
    name,
    elements,
  } = props;

  const {
    fetch,
    response,
    isLoading,
  } = useFetch<any[]>([]);

  const attrsAny = attrs as any;

  const PostTitleHeading = attrs?.postTitle?.decoration?.font?.font?.desktop?.value?.headingLevel;
  const postsNumber = parseInt(attrs?.postItems?.innerContent?.desktop?.value?.postsNumber) || 6;
  const postType = ((attrsAny)?.postType?.innerContent?.desktop?.value?.postType as string) || 'post';
  
  // Safe extraction functions for categories and tags with trailing comma handling
  const getCategoriesValue = (): string => {
    try {
      const value = attrs?.categories?.innerContent?.desktop?.value;
      
      if (!value) return '';
      
      if (typeof value === 'string') {
        // Clean trailing commas immediately
        const strValue = value as string;
        return strValue.trim().replace(/^,+|,+$/g, '');
      }
      
      if (typeof value === 'object' && value !== null) {
        if ('categories' in value && typeof value.categories === 'string') {
          return value.categories.trim().replace(/^,+|,+$/g, '');
        }
      }
      
      return '';
    } catch (error) {
      console.error('Error extracting categories:', error);
      return '';
    }
  };

  const getTagsValue = (): string => {
    try {
      const value = attrs?.tags?.innerContent?.desktop?.value;
      
      if (!value) return '';
      
      if (typeof value === 'string') {
        // Clean trailing commas immediately
        const strValue = value as string;
        return strValue.trim().replace(/^,+|,+$/g, '');
      }
      
      if (typeof value === 'object' && value !== null) {
        if ('tags' in value && typeof value.tags === 'string') {
          return value.tags.trim().replace(/^,+|,+$/g, '');
        }
      }
      
      return '';
    } catch (error) {
      console.error('Error extracting tags:', error);
      return '';
    }
  };

  const categories = getCategoriesValue();
  const tags = getTagsValue();
  const showFeaturedImage = (attrsAny?.showFeaturedImage?.innerContent?.desktop?.value as string) === 'on';
  const showAuthor = (attrsAny?.showAuthor?.innerContent?.desktop?.value as string) === 'on';
  const showDate = (attrsAny?.showDate?.innerContent?.desktop?.value as string) === 'on';
  const showCategories = (attrsAny?.showCategories?.innerContent?.desktop?.value as string) === 'on';
  const showTags = (attrsAny?.showTags?.innerContent?.desktop?.value as string) === 'on';
  const metaPosition = ((attrsAny)?.metaPosition?.innerContent?.desktop?.value as string) || 'off';
  const layoutType = ((attrsAny)?.layoutType?.innerContent?.desktop?.value as string) || 'off';
  const imagePosition = (attrsAny?.imagePosition?.innerContent?.desktop?.value as string) || 'above';
  const alternateImagePosition = (attrsAny?.alternateImagePosition?.innerContent?.desktop?.value as string) === 'on';
  const firstPostFullWidth = (attrsAny?.firstPostFullWidth?.innerContent?.desktop?.value as string) === 'on';
  const firstPostShowImage = ((attrsAny?.firstPostShowImage?.innerContent?.desktop?.value as string) || 'on') === 'on';
  const postOffset = parseInt(attrsAny?.postOffset?.innerContent?.desktop?.value?.postOffset) || 0;
  const sortOrder = ((attrsAny)?.sortOrder?.innerContent?.desktop?.value?.sortOrder as string) || 'desc';
  const showPagination = ((attrsAny?.showPagination?.innerContent?.desktop?.value as string) || 'on') === 'on';
  const showFilter = ((attrsAny?.showFilter?.innerContent?.desktop?.value as string) || 'off') === 'on';
  const filterType = (attrsAny?.filterType?.innerContent?.desktop?.value as string) || 'categories';
  const filterPosition = (attrsAny?.filterPosition?.innerContent?.desktop?.value as string) || 'left';
  const excerptLength = parseInt(attrsAny?.excerptLength?.innerContent?.desktop?.value?.excerptLength) || 270;
  const showReadMore = ((attrsAny?.showReadMore?.innerContent?.desktop?.value as string) || 'on') === 'on';
  const readMoreStyle = (attrsAny?.readMoreStyle?.innerContent?.desktop?.value as string) || 'arrow';
  const readMoreText = (attrsAny?.readMoreText?.innerContent?.desktop?.value as string) || 'Read More';

  const [currentPage, setCurrentPage] = useState(1);
  const [selectedFilter, setSelectedFilter] = useState<string>('all');
  const [fetchError, setFetchError] = useState<string | null>(null);
  const [metaFilter, setMetaFilter] = useState<{ type: 'category' | 'tag' | null; id: string | null; name: string | null }>({ type: null, id: null, name: null });  
  const { terms, termsLoading } = useTerms(postType, filterType, showFilter, categories, tags);

  const fetchAbortRef = useRef<AbortController>();

  useEffect(() => {
    // Reset error state
    setFetchError(null);
    
    if(fetchAbortRef.current) {
      fetchAbortRef.current.abort();
    }

    fetchAbortRef.current = new AbortController();

    // Validate postsNumber to prevent crashes
    const validPostsNumber = postsNumber > 0 ? postsNumber : 6;
    const fetchLimit = showPagination ? validPostsNumber * 5 : validPostsNumber;

    let queryParams = `context=view&per_page=${fetchLimit}&_embed&order=${sortOrder}`;
    
    if (postOffset > 0) {
      queryParams += `&offset=${postOffset}`;
    }
    
    const cleanCategories = cleanAndValidateIds(categories);
    const cleanTags = cleanAndValidateIds(tags);
    const hasCategoryFilter = cleanCategories.length > 0;
    const hasTagFilter = cleanTags.length > 0;
    
    // Handle filtering logic
    if (metaFilter.type && metaFilter.id) {
      // Meta filter is active (clicked from post meta)
      if (metaFilter.type === 'category') {
        queryParams += `&categories=${metaFilter.id}`;
      } else if (metaFilter.type === 'tag') {
        queryParams += `&tags=${metaFilter.id}`;
      }
      // Still apply the original restrictions as additional filters
      if (metaFilter.type === 'tag' && hasCategoryFilter) {
        queryParams += `&categories=${cleanCategories}`;
      } else if (metaFilter.type === 'category' && hasTagFilter) {
        queryParams += `&tags=${cleanTags}`;
      }
    } else if (showFilter && selectedFilter !== 'all') {
      // A specific filter is selected from filter nav
      queryParams += `&categories=${selectedFilter}`;
      // Still apply tag restrictions in background
      if (hasTagFilter) {
        queryParams += `&tags=${cleanTags}`;
      }
    } else {
      // "View All" is selected or filter is disabled
      // Apply field restrictions if any are specified
      if (hasCategoryFilter) {
        queryParams += `&categories=${cleanCategories}`;
      }
      if (hasTagFilter) {
        queryParams += `&tags=${cleanTags}`;
      }
    }

    const endpoint = postType === 'page' ? 'pages' : 'posts';

    fetch({
      restRoute: `/wp/v2/${endpoint}?${queryParams}`,
      method: 'GET',
      signal: fetchAbortRef.current.signal,
    }).then(() => {
      setFetchError(null);
    }).catch((error) => {
      if (error.name !== 'AbortError') {
        console.error('Posts fetch error:', error);
        setFetchError('Failed to fetch posts. Please check your tag and category IDs.');
      }
    });

    return () => {
      if(fetchAbortRef.current) {
        fetchAbortRef.current.abort();
      }
    };
  }, [postsNumber, postType, categories, tags, postOffset, sortOrder, showPagination, showFilter, selectedFilter, filterType, metaFilter]);

  useEffect(() => {
    setSelectedFilter('all');
    setCurrentPage(1);
  }, [filterType, showFilter]);

  const totalPosts = response?.length || 0;
  const validPostsNumber = postsNumber > 0 ? postsNumber : 6;
  const totalPages = Math.ceil(totalPosts / validPostsNumber);
  const startIndex = (currentPage - 1) * validPostsNumber;
  const endIndex = startIndex + validPostsNumber;
  const currentPosts = response ? response.slice(startIndex, endIndex) : [];

  const handlePrevPage = () => {
    if (currentPage > 1) {
      setCurrentPage(currentPage - 1);
    }
  };

  const handleNextPage = () => {
    if (currentPage < totalPages) {
      setCurrentPage(currentPage + 1);
    }
  };

  const handlePageClick = (page: number) => {
    setCurrentPage(page);
  };

  const handleCategoryClick = (categoryId: number, categoryName: string) => {
    console.log('Clicked category:', categoryId, categoryName);
    setMetaFilter({ type: 'category', id: String(categoryId), name: categoryName });
    setCurrentPage(1);
  };

  const handleTagClick = (tagId: number, tagName: string) => {
    console.log('Clicked tag:', tagId, tagName);
    setMetaFilter({ type: 'tag', id: String(tagId), name: tagName });
    setCurrentPage(1);
  };

  const handleClearMetaFilter = () => {
    setMetaFilter({ type: null, id: null, name: null });
    setCurrentPage(1);
  };

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
      scriptDataComponent={ModuleScriptData}
    >
      {elements.styleComponents({
        attrName: 'module',
      })}
      {
        fetchError && (
          <div className="turbo_blog_wl__error" style={{ 
            padding: '20px', 
            background: '#fee', 
            border: '1px solid #fcc', 
            borderRadius: '4px',
            margin: '10px 0',
            color: '#c00'
          }}>
            {fetchError}
          </div>
        )
      }
      <ElementComponents
        attrs={attrs?.module?.decoration ?? {}}
        id={id}
      />
      <div className="turbo_blog_wl__inner">
        {elements.render({
          attrName: 'title',
        })}

        {metaFilter.type && metaFilter.id && (
          <div style={{
            padding: '10px 15px',
            background: '#f0f0f0',
            border: '1px solid #ddd',
            borderRadius: '4px',
            margin: '10px 0',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center'
          }}>
            <span>
              {__('Filtering by', 'd5-extension-example-modules')} {metaFilter.type === 'category' ? __('category', 'd5-extension-example-modules') : __('tag', 'd5-extension-example-modules')}: <strong>{metaFilter.name}</strong>
            </span>
            <button
              onClick={handleClearMetaFilter}
              type="button"
              style={{
                background: '#333',
                color: '#fff',
                border: 'none',
                padding: '5px 10px',
                borderRadius: '3px',
                cursor: 'pointer'
              }}
            >
              {__('Clear Filter', 'd5-extension-example-modules')}
            </button>
          </div>
        )}

        <div className={`turbo_blog_wl__content-wrapper ${showFilter ? `turbo_blog_wl__content-wrapper--with-filter turbo_blog_wl__content-wrapper--filter-${filterPosition}` : ''}`}>

          {showFilter && (
            <nav
              className={`turbo_blog_wl__filter turbo_blog_wl__filter--${filterPosition}`}
              role="navigation"
              aria-label={
                filterType === 'categories'
                  ? __('Filter posts by category', 'd5-extension-example-modules')
                  : __('Filter posts by tag', 'd5-extension-example-modules')
              }
            >
              <h2 className="screen-reader-text">
                {__('Filter Posts', 'd5-extension-example-modules')}
              </h2>
              <div className="turbo_blog_wl__filter-inner">
                <button
                  className={`turbo_blog_wl__filter-item ${selectedFilter === 'all' ? 'turbo_blog_wl__filter-item--active' : ''}`}
                  onClick={() => {
                    console.log('Clicked View All');
                    setSelectedFilter('all');
                    setCurrentPage(1);
                    handleClearMetaFilter(); // Also clear meta filter
                  }}
                  type="button"
                  aria-current={selectedFilter === 'all' ? 'true' : undefined}
                >
                  {__('View All', 'd5-extension-example-modules')}
                </button>

                {!termsLoading && terms && Array.isArray(terms) && terms.map((term: any) => (
                  <button
                    key={term.id}
                    className={`turbo_blog_wl__filter-item ${selectedFilter === String(term.id) ? 'turbo_blog_wl__filter-item--active' : ''}`}
                    onClick={() => {
                      console.log('Clicked filter:', term.id, term.name);
                      setSelectedFilter(String(term.id));
                      setCurrentPage(1);
                      handleClearMetaFilter(); // Clear meta filter when using nav filter
                    }}
                    type="button"
                    aria-current={selectedFilter === String(term.id) ? 'true' : undefined}
                  >
                    {term.name}
                  </button>
                ))}
              </div>
            </nav>
          )}

          <section className="turbo_blog_wl__posts-wrapper" aria-label={__('Blog Posts', 'd5-extension-example-modules')}>
            {isLoading && (
              <div>{__('Loading...', 'd5-extension-example-modules')}</div>
            )}
            
            {!isLoading && !fetchError && (!response || response.length < 1) && (
              <div style={{ padding: '20px', textAlign: 'center' }}>
                {__('No posts found.', 'd5-extension-example-modules')}
                {metaFilter.type && metaFilter.id && (
                  <div style={{ marginTop: '10px' }}>
                    <button
                      onClick={handleClearMetaFilter}
                      type="button"
                      style={{
                        background: '#0073aa',
                        color: '#fff',
                        border: 'none',
                        padding: '8px 16px',
                        borderRadius: '3px',
                        cursor: 'pointer',
                        fontSize: '14px'
                      }}
                    >
                      {__('View All Posts', 'd5-extension-example-modules')}
                    </button>
                  </div>
                )}
              </div>
            )}
            
            {!isLoading && !fetchError && response && response.length > 0 && (
              <>
                <div className={`turbo_blog_wl__post-items ${layoutType === 'on' ? 'turbo_blog_wl__post-items--grid' : 'turbo_blog_wl__post-items--fullwidth'}`}>
                  {
                    map(currentPosts, (post, index) => {
                      // Add safety checks for embedded data
                      if (!post || !post._embedded) {
                        console.warn('Invalid post data:', post);
                        return null;
                      }

                      const featuredImage = post?._embedded?.['wp:featuredmedia']?.[0]?.source_url;
                      const featuredImageAlt = post?._embedded?.['wp:featuredmedia']?.[0]?.alt_text;
                      const author = post?._embedded?.author?.[0]?.name;
                      const postDate = post?.date ? new Date(post.date).toLocaleDateString() : '';
                      
                      // Safely access terms with fallbacks
                      const allPostCategories = Array.isArray(post?._embedded?.['wp:term']?.[0]) 
                        ? post._embedded['wp:term'][0] 
                        : [];
                      const allPostTags = Array.isArray(post?._embedded?.['wp:term']?.[1]) 
                        ? post._embedded['wp:term'][1] 
                        : [];
                      
                      // Filter categories to only show ones in the specified field (if any)
                      const specifiedCategoryIds = categories && typeof categories === 'string' 
                        ? categories.split(',').map((id: string) => parseInt(id.trim())).filter(id => !isNaN(id) && id > 0)
                        : [];
                      
                      const postCategories = specifiedCategoryIds.length > 0 
                        ? allPostCategories.filter((cat: any) => cat && specifiedCategoryIds.includes(cat.id))
                        : allPostCategories;
                        
                      // Tags are always shown in full (not filtered) since they work in background
                      const postTags = allPostTags.filter((tag: any) => tag && tag.id);

                      const isFirstPost = index === 0;
                      const shouldBeFullWidth = isFirstPost && firstPostFullWidth && layoutType === 'on';
                      const shouldShowImage = isFirstPost 
                        ? (shouldBeFullWidth ? firstPostShowImage : showFeaturedImage)
                        : showFeaturedImage;
                      
                      let currentImagePosition = imagePosition;
                      if (alternateImagePosition && (imagePosition === 'left' || imagePosition === 'right')) {
                        if (imagePosition === 'left') {
                          currentImagePosition = index % 2 === 0 ? 'left' : 'right';
                        } else {
                          currentImagePosition = index % 2 === 0 ? 'right' : 'left';
                        }
                      }

                      return (
                        <div 
                          className={`turbo_blog_wl__post-item ${shouldBeFullWidth ? 'turbo_blog_wl__post-item--full-width' : ''}`} 
                          key={post.id}
                        >
                          <div className={`turbo_blog_wl__post-inner turbo_blog_wl__post-inner--${currentImagePosition}`}>
                            
                            {(currentImagePosition === 'above' || currentImagePosition === 'left') && shouldShowImage && featuredImage && (
                              <div className="turbo_blog_wl__post-featured-image">
                                <img src={featuredImage} alt={featuredImageAlt || post?.title?.rendered} />
                              </div>
                            )}

                            <div className="turbo_blog_wl__post-content-wrapper">
                              
                              <PostTitleHeading className={`turbo_blog_wl__post-item-title ${showReadMore && readMoreStyle === 'arrow' ? 'turbo_blog_wl__post-item-title--with-arrow' : ''}`}>
                                <a href={post?.link} onClick={(e) => e.preventDefault()}>
                                  {post?.title?.rendered}
                                  {showReadMore && readMoreStyle === 'arrow' && (
                                    <span className={`turbo_blog_wl__read-more-arrow turbo_blog_wl__read-more-arrow--${currentImagePosition}`}>
                                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                      </svg>
                                    </span>
                                  )}
                                </a>
                              </PostTitleHeading>
                              
                              {metaPosition === 'off' && (
                                <div className="turbo_blog_wl__post-meta turbo_blog_wl__post-meta--above">
                                  {showAuthor && author && (
                                    <span className="turbo_blog_wl__post-author">
                                      {__('By', 'd5-extension-example-modules')} {author}
                                    </span>
                                  )}
                                  
                                  {showDate && postDate && (
                                    <span className="turbo_blog_wl__post-date">
                                      {postDate}
                                    </span>
                                  )}
                                  
                                  {showCategories && postCategories.length > 0 && (
                                    <span className="turbo_blog_wl__post-categories">
                                      {__('Categories:', 'd5-extension-example-modules')} {
                                        postCategories.map((cat: any, catIndex: number) => (
                                          <span key={cat.id}>
                                            <button
                                              onClick={() => handleCategoryClick(cat.id, cat.name)}
                                              type="button"
                                              style={{
                                                background: 'none',
                                                border: 'none',
                                                color: 'inherit',
                                                textDecoration: 'underline',
                                                cursor: 'pointer',
                                                padding: 0,
                                                font: 'inherit'
                                              }}
                                            >
                                              {cat.name}
                                            </button>
                                            {catIndex < postCategories.length - 1 ? ', ' : ''}
                                          </span>
                                        ))
                                      }
                                    </span>
                                  )}
                                  
                                  {showTags && postTags.length > 0 && (
                                    <span className="turbo_blog_wl__post-tags">
                                      {__('Tags:', 'd5-extension-example-modules')} {
                                        postTags.map((tag: any, tagIndex: number) => (
                                          <span key={tag.id}>
                                            <button
                                              onClick={() => handleTagClick(tag.id, tag.name)}
                                              type="button"
                                              style={{
                                                background: 'none',
                                                border: 'none',
                                                color: 'inherit',
                                                textDecoration: 'underline',
                                                cursor: 'pointer',
                                                padding: 0,
                                                font: 'inherit'
                                              }}
                                            >
                                              {tag.name}
                                            </button>
                                            {tagIndex < postTags.length - 1 ? ', ' : ''}
                                          </span>
                                        ))
                                      }
                                    </span>
                                  )}
                                </div>
                              )}
                              
                              <div className="turbo_blog_wl__post-item-content">
                                {getCustomExcerpt(post, excerptLength)}
                              </div>

                              {showReadMore && readMoreStyle === 'link' && (
                                <a 
                                  href={post?.link} 
                                  className="turbo_blog_wl__read-more-link"
                                  onClick={(e) => e.preventDefault()}
                                >
                                  {readMoreText}
                                </a>
                              )}
                              
                              {metaPosition === 'on' && (
                                <div className="turbo_blog_wl__post-meta turbo_blog_wl__post-meta--below">
                                  {((showCategories && postCategories.length > 0) || (showTags && postTags.length > 0)) && (
                                    <div className="turbo_blog_wl__post-taxonomy">
                                      {showCategories && postCategories.length > 0 && (
                                        <>
                                          {postCategories.map((cat: any, catIndex: number) => (
                                            <span key={cat.id}>
                                              <button
                                                onClick={() => handleCategoryClick(cat.id, cat.name)}
                                                type="button"
                                                style={{
                                                  background: 'none',
                                                  border: 'none',
                                                  color: 'inherit',
                                                  textDecoration: 'underline',
                                                  cursor: 'pointer',
                                                  padding: 0,
                                                  font: 'inherit'
                                                }}
                                              >
                                                {cat.name}
                                              </button>
                                              {catIndex < postCategories.length - 1 ? ', ' : ''}
                                            </span>
                                          ))}
                                        </>
                                      )}
                                      
                                      {showCategories && postCategories.length > 0 && showTags && postTags.length > 0 && (
                                        <span> | </span>
                                      )}
                                      
                                      {showTags && postTags.length > 0 && (
                                        <>
                                          {postTags.map((tag: any, tagIndex: number) => (
                                            <span key={tag.id}>
                                              <button
                                                onClick={() => handleTagClick(tag.id, tag.name)}
                                                type="button"
                                                style={{
                                                  background: 'none',
                                                  border: 'none',
                                                  color: 'inherit',
                                                  textDecoration: 'underline',
                                                  cursor: 'pointer',
                                                  padding: 0,
                                                  font: 'inherit'
                                                }}
                                              >
                                                {tag.name}
                                              </button>
                                              {tagIndex < postTags.length - 1 ? ', ' : ''}
                                            </span>
                                          ))}
                                        </>
                                      )}
                                    </div>
                                  )}
                                  
                                  {(showAuthor || showDate) && (
                                    <div className="turbo_blog_wl__post-author-info">
                                      {showAuthor && author && (
                                        <>
                                          {post?._embedded?.author?.[0]?.avatar_urls?.['96'] && (
                                            <img 
                                              src={post._embedded.author[0].avatar_urls['96']} 
                                              alt={author}
                                              className="turbo_blog_wl__post-author-avatar"
                                            />
                                          )}
                                          <div className="turbo_blog_wl__post-author-details">
                                            <div className="turbo_blog_wl__post-author-name">
                                              {author}
                                            </div>
                                            {showDate && postDate && (
                                              <div className="turbo_blog_wl__post-date">
                                                {new Date(post.date).toLocaleDateString('en-US', { 
                                                  year: 'numeric', 
                                                  month: 'long', 
                                                  day: 'numeric' 
                                                })}
                                              </div>
                                            )}
                                          </div>
                                        </>
                                      )}
                                      {!showAuthor && showDate && postDate && (
                                        <div className="turbo_blog_wl__post-date">
                                          {new Date(post.date).toLocaleDateString('en-US', { 
                                            year: 'numeric', 
                                            month: 'long', 
                                            day: 'numeric' 
                                          })}
                                        </div>
                                      )}
                                    </div>
                                  )}
                                </div>
                              )}
                              
                            </div>

                            {(currentImagePosition === 'below' || currentImagePosition === 'right') && shouldShowImage && featuredImage && (
                              <div className="turbo_blog_wl__post-featured-image">
                                <img src={featuredImage} alt={featuredImageAlt || post?.title?.rendered} />
                              </div>
                            )}
                            
                          </div>
                        </div>
                      );
                    }).filter(Boolean)
                  }
                </div>

                {showPagination && totalPages > 1 && (
                  <nav
                    className="turbo_blog_wl__pagination"
                    role="navigation"
                    aria-label={__('Pagination', 'd5-extension-example-modules')}
                  >
                    <button
                      className={`turbo_blog_wl__pagination-prev ${currentPage === 1 ? 'turbo_blog_wl__pagination-disabled' : ''}`}
                      onClick={handlePrevPage}
                      disabled={currentPage === 1}
                      type="button"
                    >
                      ← PREVIOUS
                    </button>

                    <div className="turbo_blog_wl__pagination-numbers">
                      {getPaginationRange(currentPage, totalPages).map((page, index) => {
                        if (page === '...') {
                          return (
                            <span
                              key={`ellipsis-${index}`}
                              className="turbo_blog_wl__pagination-ellipsis"
                              aria-hidden="true"
                            >
                              ...
                            </span>
                          );
                        }

                        return (
                          <button
                            key={page}
                            className={`turbo_blog_wl__pagination-number ${page === currentPage ? 'turbo_blog_wl__pagination-current' : ''}`}
                            onClick={() => handlePageClick(page as number)}
                            type="button"
                            aria-current={page === currentPage ? 'page' : undefined}
                          >
                            {page}
                          </button>
                        );
                      })}
                    </div>

                    <button
                      className={`turbo_blog_wl__pagination-next ${currentPage === totalPages ? 'turbo_blog_wl__pagination-disabled' : ''}`}
                      onClick={handleNextPage}
                      disabled={currentPage === totalPages}
                      type="button"
                    >
                      NEXT →
                    </button>
                  </nav>
                )}
              </>
            )}
          </section>
        </div>
      </div>
    </ModuleContainer>
  );
}

export {
  TurboBlogWlEdit, 
};