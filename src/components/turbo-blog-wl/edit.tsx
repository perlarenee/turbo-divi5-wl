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
 * 
 * @param currentPage Current page number
 * @param totalPages Total number of pages
 * @returns Array of page numbers and ellipsis strings
 */
const getPaginationRange = (currentPage: number, totalPages: number): (number | string)[] => {
  const delta = 2; // Number of pages to show around current page
  const range: number[] = [];
  const rangeWithDots: (number | string)[] = [];
  let l: number | undefined;

  // Always include first page
  range.push(1);

  // Add pages around current page
  for (let i = currentPage - delta; i <= currentPage + delta; i++) {
    if (i > 1 && i < totalPages) {
      range.push(i);
    }
  }

  // Always include last page
  if (totalPages > 1) {
    range.push(totalPages);
  }

  // Add dots where there are gaps
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
  // Priority 1: Manual excerpt
  if (post?.excerpt?.rendered && post.excerpt.rendered.trim()) {
    const tmp = document.createElement('div');
    tmp.innerHTML = post.excerpt.rendered;
    const text = tmp.textContent || tmp.innerText || '';
    if (text.trim()) {
      return truncateText(text, length);
    }
  }
  
  // Priority 2: Extract from content
  if (post?.content?.rendered) {
    const tmp = document.createElement('div');
    tmp.innerHTML = post.content.rendered;
    let text = tmp.textContent || tmp.innerText || '';
    
    // Remove excessive whitespace
    text = text.replace(/\s+/g, ' ').trim();
    
    if (text) {
      return truncateText(text, length);
    }
  }
  
  return '';
};

// Helper to truncate text at word boundary
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

// helper function to fetch terms
const useTerms = (postType: string, filterType: string, showFilter: boolean) => {
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

    const taxonomy = filterType === 'categories' ? 'categories' : 'tags';
    
    fetchTerms({
      restRoute: `/wp/v2/${taxonomy}?per_page=100&hide_empty=true`,
      method: 'GET',
      signal: fetchAbortRef.current.signal,
    }).catch((error) => {
      console.error(error);
    });

    return () => {
      if (fetchAbortRef.current) {
        fetchAbortRef.current.abort();
      }
    };
  }, [filterType, showFilter, postType]);

  return { terms: termsResponse, termsLoading };
};


/**
 * Turbo Blog WL edit component of visual builder.
 *
 * @since ??
 *
 * @param {TurboBlogWlEditProps} props React component props.
 *
 * @returns {ReactElement}
 */
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
  const postsNumber = parseInt(attrs?.postItems?.innerContent?.desktop?.value?.postsNumber);
  const postType = ((attrsAny)?.postType?.innerContent?.desktop?.value?.postType as string) || 'post';
  const categories = attrs?.categories?.innerContent?.desktop?.value?.categories || '';
  const tags = attrs?.tags?.innerContent?.desktop?.value?.tags || '';
  
  // settings
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
const [selectedFilter, setSelectedFilter] = useState<string>('all');
const { terms, termsLoading } = useTerms(postType, filterType, showFilter);
const excerptLength = parseInt(attrsAny?.excerptLength?.innerContent?.desktop?.value?.excerptLength) || 270;
const showReadMore = ((attrsAny?.showReadMore?.innerContent?.desktop?.value as string) || 'on') === 'on';
const readMoreStyle = (attrsAny?.readMoreStyle?.innerContent?.desktop?.value as string) || 'arrow';
const readMoreText = (attrsAny?.readMoreText?.innerContent?.desktop?.value as string) || 'Read More';

  // Pagination state for VB preview
  const [currentPage, setCurrentPage] = useState(1);

  const fetchAbortRef = useRef<AbortController>();

  /**
   * Fetches new Posts based on filter parameters.
   * Note: We fetch MORE posts than needed to simulate pagination in VB
   */
  useEffect(() => {
    if(fetchAbortRef.current) {
      fetchAbortRef.current.abort();
    }

    fetchAbortRef.current = new AbortController();

    const fetchLimit = showPagination ? postsNumber * 5 : postsNumber;

    let queryParams = `context=view&per_page=${fetchLimit}&_embed&order=${sortOrder}`;
    
    if (postOffset > 0) {
      queryParams += `&offset=${postOffset}`;
    }
    
    // Handle filter selection
    if (showFilter && selectedFilter !== 'all') {
      const filterParam = filterType === 'categories' ? 'categories' : 'tags';
      queryParams += `&${filterParam}=${selectedFilter}`;
    } else {
      // Use manual filters only if no active filter selection
      if (categories) {
        queryParams += `&categories=${categories}`;
      }
      if (tags) {
        queryParams += `&tags=${tags}`;
      }
    }

    const endpoint = postType === 'page' ? 'pages' : 'posts';

    fetch({
      restRoute: `/wp/v2/${endpoint}?${queryParams}`,
      method:    'GET',
      signal:    fetchAbortRef.current.signal,
    }).catch((error) => {
      console.error(error);
    });

    return () => {
      if(fetchAbortRef.current) {
        fetchAbortRef.current.abort();
      }
    };
  }, [postsNumber, postType, categories, tags, postOffset, sortOrder, showPagination, showFilter, selectedFilter, filterType]);

  // Reset selected filter when settings change
  useEffect(() => {
    setSelectedFilter('all');
    setCurrentPage(1);
  }, [filterType, showFilter]);

  // Calculate pagination
  const totalPosts = response.length;
  const totalPages = Math.ceil(totalPosts / postsNumber);
  const startIndex = (currentPage - 1) * postsNumber;
  const endIndex = startIndex + postsNumber;
  const currentPosts = response.slice(startIndex, endIndex);

  // Pagination handlers
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
        ! isLoading && (
          <>
            <ElementComponents
              attrs={attrs?.module?.decoration ?? {}}
              id={id}
            />
            <div className="turbo_blog_wl__inner">
              {elements.render({
                attrName: 'title',
              })}
              

            <div className={`turbo_blog_wl__content-wrapper ${showFilter ? `turbo_blog_wl__content-wrapper--with-filter turbo_blog_wl__content-wrapper--filter-${filterPosition}` : ''}`}>


            {/* Filter Sidebar */}
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
                        setSelectedFilter('all');
                        setCurrentPage(1);
                      }}
                      type="button"
                      aria-current={selectedFilter === 'all' ? 'true' : undefined}
                    >
                      {__('View All', 'd5-extension-example-modules')}
                    </button>

                    {!termsLoading && terms.map((term: any) => (
                      <button
                        key={term.id}
                        className={`turbo_blog_wl__filter-item ${selectedFilter === String(term.id) ? 'turbo_blog_wl__filter-item--active' : ''}`}
                        onClick={() => {
                          setSelectedFilter(String(term.id));
                          setCurrentPage(1);
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

              {/* Posts Content */}
              <section className="turbo_blog_wl__posts-wrapper" aria-label={__('Blog Posts', 'd5-extension-example-modules')}>
              <div className={`turbo_blog_wl__post-items ${layoutType === 'on' ? 'turbo_blog_wl__post-items--grid' : 'turbo_blog_wl__post-items--fullwidth'}`}>
                
                {
                  map(currentPosts, (post, index) => {
                    // Get featured image
                    const featuredImage = post?._embedded?.['wp:featuredmedia']?.[0]?.source_url;
                    const featuredImageAlt = post?._embedded?.['wp:featuredmedia']?.[0]?.alt_text;
                    
                    // Get author
                    const author = post?._embedded?.author?.[0]?.name;
                    
                    // Get date
                    const postDate = post?.date ? new Date(post.date).toLocaleDateString() : '';
                    
                    // Get categories
                    const postCategories = post?._embedded?.['wp:term']?.[0] || [];
                    
                    // Get tags
                    const postTags = post?._embedded?.['wp:term']?.[1] || [];

                    // Determine if this is the first post and should be full width
                    const isFirstPost = index === 0;
                    const shouldBeFullWidth = isFirstPost && firstPostFullWidth && layoutType === 'on';
                    const shouldShowImage = isFirstPost 
  ? (shouldBeFullWidth ? firstPostShowImage : showFeaturedImage)
  : showFeaturedImage;
                    
                    // Determine image position for this post
                    let currentImagePosition = imagePosition;
                    if (alternateImagePosition && (imagePosition === 'left' || imagePosition === 'right')) {
                      // Alternate between left and right
                      if (imagePosition === 'left') {
                        // User chose left: alternate left → right → left → right
                        currentImagePosition = index % 2 === 0 ? 'left' : 'right';
                      } else {
                        // User chose right: alternate right → left → right → left
                        currentImagePosition = index % 2 === 0 ? 'right' : 'left';
                      }
                    }

                    
                    return (
                      <div 
                        className={`turbo_blog_wl__post-item ${shouldBeFullWidth ? 'turbo_blog_wl__post-item--full-width' : ''}`} 
                        key={post.id}
                      >
                        <div className={`turbo_blog_wl__post-inner turbo_blog_wl__post-inner--${currentImagePosition}`}>
                          
                          {/* Featured Image - Top or Left */}
                          {(currentImagePosition === 'above' || currentImagePosition === 'left') && shouldShowImage && featuredImage && (
                            <div className="turbo_blog_wl__post-featured-image">
                              <img src={featuredImage} alt={featuredImageAlt || post?.title?.rendered} />
                            </div>
                          )}

                          {/* Content Wrapper */}
                          <div className="turbo_blog_wl__post-content-wrapper">
                            
                            {/* Post Title */}
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
                            
                            {/* Meta Above Content */}
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
                                          {cat.name}
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
                                          {tag.name}
                                          {tagIndex < postTags.length - 1 ? ', ' : ''}
                                        </span>
                                      ))
                                    }
                                  </span>
                                )}
                              </div>
                            )}
                            
                          {/* Post Excerpt/Content */}
                          <div className="turbo_blog_wl__post-item-content">
                            {getCustomExcerpt(post, excerptLength)}
                          </div>


                          {/* Read More Link */}
                          {showReadMore && readMoreStyle === 'link' && (
                            <a 
                              href={post?.link} 
                              className="turbo_blog_wl__read-more-link"
                              onClick={(e) => e.preventDefault()}
                            >
                              {readMoreText}
                            </a>
                          )}
                            
                            {/* Meta Below Content */}
                            {metaPosition === 'on' && (
                              <div className="turbo_blog_wl__post-meta turbo_blog_wl__post-meta--below">
                                {/* Categories and Tags row */}
                                {((showCategories && postCategories.length > 0) || (showTags && postTags.length > 0)) && (
                                  <div className="turbo_blog_wl__post-taxonomy">
                                    {showCategories && postCategories.length > 0 && (
                                      <>
                                        {postCategories.map((cat: any, catIndex: number) => (
                                          <span key={cat.id}>
                                            {cat.name}
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
                                            {tag.name}
                                            {tagIndex < postTags.length - 1 ? ', ' : ''}
                                          </span>
                                        ))}
                                      </>
                                    )}
                                  </div>
                                )}
                                
                                {/* Author info row */}
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
                          {/* End Content Wrapper */}

                          {/* Featured Image - Bottom or Right */}
                          {(currentImagePosition === 'below' || currentImagePosition === 'right') && shouldShowImage && featuredImage && (
                            <div className="turbo_blog_wl__post-featured-image">
                              <img src={featuredImage} alt={featuredImageAlt || post?.title?.rendered} />
                            </div>
                          )}
                          
                        </div>
                      </div>
                    );
                  })
                }
              </div>{/*end turbo_blog_wl__post-items*/}

              {/* Pagination with Ellipsis */}
              {showPagination && totalPages > 1 && (
                <nav
                  className="turbo_blog_wl__pagination"
                  role="navigation"
                  aria-label={__('Pagination', 'd5-extension-example-modules')}
                >
                  {/* Previous Button */}
                  <button
                    className={`turbo_blog_wl__pagination-prev ${currentPage === 1 ? 'turbo_blog_wl__pagination-disabled' : ''}`}
                    onClick={handlePrevPage}
                    disabled={currentPage === 1}
                    type="button"
                  >
                    ← PREVIOUS
                  </button>

                  {/* Page Numbers with Ellipsis */}
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

                  {/* Next Button */}
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
              </section>{/* end posts wrapper */}
              </div>{/*end content wrapper */}
            </div>{/*end turbo_blog_wl__inner*/}
          </>
        )
      }
      {
        ! isLoading && response.length < 1 && (
          <div>{__('No post found.', 'd5-extension-example-modules')}</div>
        )
      }
      {
        isLoading && (
          <div>{__('Loading...', 'd5-extension-example-modules')}</div>
        )
      }
    </ModuleContainer>
  );
}

export {
  TurboBlogWlEdit,
};