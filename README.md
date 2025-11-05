# Turbo Blog WL – Divi 5 Blog Module

A smart, flexible blog module and extension built for **Divi 5**, designed to showcase the power and flexibility of Divi's new React-based architecture.

**Designed by** [David Irias](https://vizualized.co) • **Developed by** [Ren Koren](https://weblocomotive.com)

## Overview

Turbo Blog WL is a comprehensive blog module for Divi 5 that gives you complete control over how WordPress posts are displayed. From layout customization to advanced filtering and pagination, everything is configurable directly in the Visual Builder—no coding required.

This project was dreamed up by David Irias as a better, smarter format for blogs. Clunky blog layouts have always bothered him, and his beautiful, clean architecture inspired long hours of development on my part. A matching Divi layout for individual post views is coming soon.

Enjoy! And send good thoughts (and job offers) to our mutual friend, David.

## Features

### Content Control
- **Flexible post selection** – Display posts, pages, or custom post types
- **Smart filtering** – Filter by categories, tags, or both
- **Post offset** – Skip posts and start from any position in your query
- **Sorting options** – Ascending or descending order
- **Custom excerpts** – Control length and source (manual, Gutenberg blocks, or Divi modules)
- **Meta display** – Show/hide author, date, categories, and tags
- **Read more options** – Classic links or arrow indicators

### Layout & Design
- **Grid or list view** – Choose your preferred layout style
- **Responsive columns** – Set different column counts per device
- **Custom spacing** – Control row and column gaps independently
- **Featured post** – Highlight a top post with optional image
- **Flexible image placement** – Above, below, left, or right of content
- **Alternating layouts** – Create dynamic, staggered layouts automatically
- **Meta positioning** – Place metadata above or below content

### Advanced Filtering
- **Sidebar filter component** – Add an optional category/tag filter
- **Smart positioning** – Left or right on desktop, auto-moves to top on mobile
- **Dynamic filtering** – Users can browse all posts or filter by specific taxonomy terms

### Pagination
- **Smart pagination controls** – Navigate through posts with ease
- **Ellipsis display** – Clean, space-efficient pagination (`1 ... 5 6 7 ... 20`)
- **Previous/Next buttons** – Intuitive navigation
- **Fully accessible** – Keyboard and screen reader friendly

### Accessibility
- Semantic HTML structure with proper ARIA roles and labels
- Full keyboard navigation support
- Screen reader optimized
- WCAG compliant design patterns

## Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/turbo-blog-wl.git

# Navigate to the project directory
cd turbo-blog-wl

# Install dependencies
npm install

# Build for production
npm run build
```

## Technical Stack

- **React** + **TypeScript** – Modern, type-safe component architecture
- **Divi 5 Module API** (`@divi/module`) – Native Divi 5 integration
- **WordPress REST API** – Dynamic content fetching
- **Lodash** – Utility functions
- **@wordpress/i18n** – Translation ready

## Requirements

- WordPress 6.0 or higher
- Divi 5 or higher
- PHP 7.4 or higher
- Node.js 16+ (for development)

## Usage

After installation, the Turbo Blog WL module will be available in the Divi Visual Builder under the "Blog" category. Simply drag it onto your page and configure your preferred settings.

## Roadmap

Future enhancements planned:

- Integration with Divi 5 global presets and theme builder
- Lazy loading and infinite scroll options
- Animation and transition controls
- Enhanced responsive controls for meta and images
- Advanced query builders
- Custom post template support

## Team

| Role | Name | Website |
|------|------|---------|
| Design & Concept | David Irias | [vizualized.co](https://vizualized.co) |
| Development & Engineering | Ren Koren | [weblocomotive.com](https://weblocomotive.com) |

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact:
- Ren Koren: [weblocomotive.com](https://weblocomotive.com)

---

Built with collaboration, curiosity, and caffeine.
