/* =====================================================
   Klean Online Learning Platform — app.js
   Author: Klean Dev Team
   Version: 2.0 (Fully Responsive Modular Split)
   ===================================================== */

'use strict';

// =====================================================
// DUMMY COURSE DATABASE
// =====================================================
const DEFAULT_COURSES = [
  {
    id: 1,
    title: "Complete Web Development Bootcamp",
    subtitle: "Master HTML, CSS, JavaScript, Node, React and build beautifully functional products from scratch.",
    instructor: "Dr. Angela Yu",
    category: "Development",
    level: "Beginner",
    price: 7499,
    oldPrice: 10999,
    rating: 4.7,
    reviews: 12480,
    enrolled: 48200,
    isBestseller: true,
    hours: 52,
    thumbnail: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Introduction to HTML & CSS structures", duration: "45m", isPreview: true },
      { title: "Layouts using CSS Grid & Flexbox", duration: "1h 15m", isPreview: true },
      { title: "JavaScript Variables & Arithmetic Logic", duration: "55m", isPreview: false },
      { title: "Webpage interactions using DOM", duration: "1h 30m", isPreview: false },
      { title: "Building REST APIs using Express.js", duration: "2h 10m", isPreview: false },
      { title: "Database modeling with SQL", duration: "1h 50m", isPreview: false },
      { title: "State Management in React JS", duration: "3h 40m", isPreview: false },
      { title: "Deployment to Production Cloud", duration: "1h 20m", isPreview: false }
    ]
  },
  {
    id: 2,
    title: "Python for Data Science & ML Masterclass",
    subtitle: "From Zero to Hero — master Python, Pandas, and train real Machine Learning pipelines.",
    instructor: "Sarah Williams",
    category: "Development",
    level: "Intermediate",
    price: 7999,
    oldPrice: 12499,
    rating: 4.8,
    reviews: 9812,
    enrolled: 32400,
    isBestseller: true,
    hours: 40,
    thumbnail: "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Setting up Python environments", duration: "35m", isPreview: true },
      { title: "Python Arrays & Loop statements", duration: "1h 05m", isPreview: true },
      { title: "Data manipulation with Pandas", duration: "2h 20m", isPreview: false },
      { title: "Visualisation with Seaborn", duration: "1h 45m", isPreview: false },
      { title: "Building ML models with Scikit-Learn", duration: "3h 15m", isPreview: false },
      { title: "Deploying models inside Docker", duration: "2h 00m", isPreview: false }
    ]
  },
  {
    id: 3,
    title: "UI/UX Design Masterclass — Clean Layouts",
    subtitle: "Learn wireframes, Figma glassmorphism styles, and build sleek interactive prototypes.",
    instructor: "Sarah Williams",
    category: "Design",
    level: "Beginner",
    price: 6299,
    oldPrice: 8499,
    rating: 4.6,
    reviews: 4320,
    enrolled: 18900,
    isBestseller: false,
    hours: 28,
    thumbnail: "https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "UI vs UX — Designing with Purpose", duration: "40m", isPreview: true },
      { title: "Grid, Alignment & Typography Hierarchies", duration: "1h 10m", isPreview: true },
      { title: "Wireframes & High-Fidelity Prototypes", duration: "2h 00m", isPreview: false },
      { title: "Transitions & Micro-Animations in Figma", duration: "1h 50m", isPreview: false },
      { title: "User Testing & Layout Iteration", duration: "1h 15m", isPreview: false }
    ]
  },
  {
    id: 4,
    title: "Digital Marketing Full Comprehensive Course",
    subtitle: "SEO, SEM, Copywriting, Social Campaigns — master strategies that convert views into revenue.",
    instructor: "Alex Miller",
    category: "Marketing",
    level: "Beginner",
    price: 4999,
    oldPrice: 6799,
    rating: 4.5,
    reviews: 3120,
    enrolled: 12400,
    isBestseller: false,
    hours: 22,
    thumbnail: "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Modern Customer Marketing Funnels", duration: "30m", isPreview: true },
      { title: "SEO Optimization — Keywords & Rankings", duration: "1h 20m", isPreview: false },
      { title: "Social Ads that Convert Views to Sales", duration: "1h 45m", isPreview: false },
      { title: "Email Newsletter Automation Setup", duration: "1h 10m", isPreview: false },
      { title: "Analytics Reports & ROI Calculation", duration: "55m", isPreview: false }
    ]
  },
  {
    id: 5,
    title: "React JS Complete Guide (Hooks, Redux, Next)",
    subtitle: "Build responsive, high-performance apps using Hooks, Redux Toolkit, and Next.js.",
    instructor: "Dr. Angela Yu",
    category: "Development",
    level: "Advanced",
    price: 6999,
    oldPrice: 9999,
    rating: 4.9,
    reviews: 15480,
    enrolled: 62000,
    isBestseller: true,
    hours: 35,
    thumbnail: "https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Declarative React vs Traditional DOM", duration: "45m", isPreview: true },
      { title: "React State & Working with Hooks", duration: "1h 30m", isPreview: true },
      { title: "Modular UI Component Layouts", duration: "1h 10m", isPreview: false },
      { title: "Global State with Redux Toolkit", duration: "2h 45m", isPreview: false },
      { title: "Next.js Routing & Server-Side Rendering", duration: "2h 15m", isPreview: false },
      { title: "Deployment & Web Caching Optimizations", duration: "1h 00m", isPreview: false }
    ]
  },
  {
    id: 6,
    title: "Photography Masterclass: Professional Guide",
    subtitle: "Master camera exposure, lighting, framing, and Lightroom editing configurations.",
    instructor: "Chris Evans",
    category: "Photography",
    level: "Beginner",
    price: 4199,
    oldPrice: 5799,
    rating: 4.4,
    reviews: 2190,
    enrolled: 8900,
    isBestseller: false,
    hours: 18,
    thumbnail: "https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Exposure — Aperture, Shutter Speed & ISO", duration: "35m", isPreview: true },
      { title: "Composition & Rule of Thirds", duration: "50m", isPreview: true },
      { title: "Studio Lighting & Reflectors", duration: "1h 15m", isPreview: false },
      { title: "Post-Editing RAW Photos in Lightroom", duration: "1h 30m", isPreview: false }
    ]
  },
  {
    id: 7,
    title: "Financial Planning & Wealth Investing",
    subtitle: "Index funds, crypto analysis, tax structures, and portfolio balancing methods.",
    instructor: "Sarah Williams",
    category: "Finance",
    level: "Intermediate",
    price: 5799,
    oldPrice: 8299,
    rating: 4.7,
    reviews: 3100,
    enrolled: 14500,
    isBestseller: false,
    hours: 15,
    thumbnail: "https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Compound Interest & Index Funds", duration: "30m", isPreview: true },
      { title: "Stock Market Indexing & Dividends", duration: "1h 10m", isPreview: false },
      { title: "Balanced Asset Allocation Portfolio", duration: "1h 20m", isPreview: false },
      { title: "Tax Exemption & Pension Calculations", duration: "50m", isPreview: false }
    ]
  },
  {
    id: 8,
    title: "Graphic Design Bootcamp: Illustrator & Photoshop",
    subtitle: "Typography, brand logos, vector assets, and design mockups with Adobe Suite.",
    instructor: "Alex Miller",
    category: "Design",
    level: "Beginner",
    price: 5499,
    oldPrice: 7499,
    rating: 4.6,
    reviews: 4210,
    enrolled: 16700,
    isBestseller: false,
    hours: 24,
    thumbnail: "https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Vector Panels in Adobe Illustrator", duration: "45m", isPreview: true },
      { title: "Logo Layouts & Grid Systems", duration: "1h 15m", isPreview: true },
      { title: "Layers & Photo Masks in Photoshop", duration: "1h 50m", isPreview: false },
      { title: "Color Theory & Typography Styling", duration: "1h 10m", isPreview: false },
      { title: "Packaging Mockups & Asset Exporting", duration: "1h 00m", isPreview: false }
    ]
  }
];

const DEFAULT_STUDENT = {
  name: "Arjun Sharma",
  email: "alex@email.com",
  role: "student",
  phone: "+91 98765 43210",
  website: "https://arjunsharma.dev",
  bio: "Full Stack developer enthusiast learning on the Klean platform from Mumbai, India.",
  linkedin: "https://linkedin.com/in/arjunsharma",
  twitter: "https://twitter.com/arjunsharma",
  notifications: { email: true, push: true, sms: false },
  enrolled: [1, 5, 3],
  progress: {
    1: { completed: [0, 1, 2, 3, 4], percent: 65 },
    5: { completed: [0], percent: 20 },
    3: { completed: [0, 1, 2, 3, 4], percent: 100 }
  },
  notes: {
    1: "CSS Grid template areas make structuring responsive layouts simple.",
    5: "Always call Hooks at the top level — never inside loops or conditions."
  },
  cart: [2, 4],
  wishlist: [6, 7]
};

const DEFAULT_INSTRUCTOR = {
  name: "Sarah Williams",
  email: "sarah@email.com",
  role: "instructor",
  phone: "+91 91234 56789",
  website: "https://sarahwilliams.design",
  bio: "Professional design consultant specializing in wireframes and structured instruction, based in Bengaluru, India.",
  linkedin: "https://linkedin.com/in/sarah",
  twitter: "https://twitter.com/sarah",
  notifications: { email: true, push: true, sms: true },
  enrolled: [],
  progress: {},
  notes: {},
  cart: [],
  wishlist: [],
  createdCourses: [2, 3, 7],
  stats: { students: 12400, revenue: 4002000, rating: 4.8, courses: 3 }
};

// =====================================================
// APP STATE
// =====================================================
const state = {
  currentUser: null,
  courses: [],
  currentView: 'landing-view',
  couponApplied: false,
  couponCode: '',
  activeDetailCourseId: 1,
  activePlayerCourseId: 1,
  activePlayerLectureIdx: 0,
  activePlayerPlayState: false
};

// =====================================================
// LOCAL STORAGE STATE ENGINE
// =====================================================
const APP_VERSION = 'v2.1-INR';

function initAppState() {
  // Force clear old data when app version changes (e.g., USD → INR migration)
  const storedVersion = localStorage.getItem('klean_version');
  if (storedVersion !== APP_VERSION) {
    localStorage.removeItem('klean_state');
    localStorage.setItem('klean_version', APP_VERSION);
    setupDefaults();
    return;
  }

  try {
    const saved = JSON.parse(localStorage.getItem('klean_state') || 'null');
    if (saved) {
      state.courses       = saved.courses       || [...DEFAULT_COURSES];
      state.currentUser   = saved.currentUser   || null;
      state.couponApplied = saved.couponApplied || false;
      state.couponCode    = saved.couponCode    || '';
    } else {
      setupDefaults();
    }
  } catch (_) {
    setupDefaults();
  }

  if (!state.currentUser) {
    state.currentUser = { ...DEFAULT_STUDENT };
    saveState();
  }
}

function setupDefaults() {
  state.courses     = [...DEFAULT_COURSES];
  state.currentUser = { ...DEFAULT_STUDENT };
  saveState();
}

function saveState() {
  localStorage.setItem('klean_state', JSON.stringify({
    courses:       state.courses,
    currentUser:   state.currentUser,
    couponApplied: state.couponApplied,
    couponCode:    state.couponCode
  }));
}

// =====================================================
// ROUTER / VIEW CONTROLLER
// =====================================================
function switchView(viewId, params = {}) {
  // Fade out current
  const curr = document.querySelector('.view-container.active-view');
  if (curr) { curr.classList.remove('active-view'); curr.classList.add('d-none'); }

  // Show/hide footer (dashboards & player have their own layout)
  const footer = document.getElementById('global-footer');
  const noBoth = ['student-dashboard-view','instructor-dashboard-view','course-player-view'].includes(viewId);
  footer.classList.toggle('d-none', noBoth);

  const target = document.getElementById(viewId);
  if (target) {
    target.classList.remove('d-none');
    void target.offsetWidth; // reflow for CSS transition
    target.classList.add('active-view');
  }

  state.currentView = viewId;

  if (viewId === 'course-detail-view'  && params.courseId)   state.activeDetailCourseId   = params.courseId;
  if (viewId === 'course-player-view'  && params.courseId) {
    state.activePlayerCourseId    = params.courseId;
    state.activePlayerLectureIdx  = params.lessonIdx || 0;
    state.activePlayerPlayState   = false;
  }

  renderViewContents(viewId);
  updateNavbar();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  // Close mobile sidebar overlay if open
  closeMobileSidebar();
}

function renderViewContents(viewId) {
  const fn = {
    'landing-view':              renderLandingPage,
    'courses-view':              renderCoursesCatalog,
    'course-detail-view':        renderCourseDetail,
    'cart-view':                 renderCartPage,
    'payment-view':              renderPaymentPage,
    'student-dashboard-view':    renderStudentDashboard,
    'course-player-view':        renderCoursePlayer,
    'instructor-dashboard-view': renderInstructorDashboard,
    'settings-view':             renderSettingsPage,
    'wishlist-view':             renderWishlistPage
  }[viewId];
  if (fn) fn();
}

// =====================================================
// NAVBAR
// =====================================================
function updateNavbar() {
  const user = state.currentUser;
  const el = id => document.getElementById(id);

  const isLoggedIn = !!user;
  el('nav-login-btn').classList.toggle('d-none', isLoggedIn);
  el('nav-signup-btn').classList.toggle('d-none', isLoggedIn);
  el('nav-user-dropdown').classList.toggle('d-none', !isLoggedIn);

  if (!isLoggedIn) {
    el('nav-wishlist-item').classList.add('d-none');
    el('nav-cart-item').classList.add('d-none');
    el('nav-teach-item').classList.remove('d-none');
    return;
  }

  el('nav-user-name').textContent = user.name;
  el('nav-user-role-label').textContent = user.role === 'instructor' ? 'Instructor Account' : 'Student Account';

  const isStudent = user.role === 'student';
  el('nav-wishlist-item').classList.toggle('d-none', !isStudent);
  el('nav-cart-item').classList.toggle('d-none', !isStudent);
  el('nav-teach-item').classList.toggle('d-none', !isStudent);

  if (isStudent) {
    const cCount = user.cart.length;
    const wCount = user.wishlist.length;
    const cb = el('cart-count-badge'), wb = el('wishlist-count-badge');
    cb.textContent = cCount; cb.classList.toggle('d-none', cCount === 0);
    wb.textContent = wCount; wb.classList.toggle('d-none', wCount === 0);
    el('dropdown-dash-link').innerHTML = `<i class="bi bi-mortarboard-fill me-2 text-primary"></i>My Classroom`;
    el('nav-user-avatar').src = user.email === 'alex@email.com'
      ? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80'
      : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80';
  } else {
    el('dropdown-dash-link').innerHTML = `<i class="bi bi-speedometer2 me-2 text-primary"></i>Instructor Portal`;
    el('nav-user-avatar').src = user.email === 'sarah@email.com'
      ? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=100&h=100&q=80'
      : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80';
  }
}

function goToDashboard() {
  if (state.currentUser && state.currentUser.role === 'instructor') {
    switchView('instructor-dashboard-view');
  } else {
    switchView('student-dashboard-view');
  }
}

// =====================================================
// LANDING PAGE RENDER
// =====================================================
function renderLandingPage() {
  // Categories
  const cats = ["All Courses","Development","Design","Business","Marketing","Photography","Music","Finance","IT & Software"];
  const scrollCont = document.getElementById('landing-categories');
  scrollCont.innerHTML = '';
  cats.forEach((cat, i) => {
    const el = document.createElement('div');
    el.className = 'category-pill' + (i === 0 ? ' active' : '');
    el.textContent = cat;
    el.onclick = () => {
      if (cat === 'All Courses') filterByCategory('all');
      else filterByCategory(cat);
    };
    scrollCont.appendChild(el);
  });

  // Featured grid (6 cards)
  const grid = document.getElementById('featured-courses-grid');
  grid.innerHTML = '';
  state.courses.slice(0, 6).forEach(c => grid.appendChild(createCourseCardElement(c)));

  // Testimonials
  const testCont = document.getElementById('testimonials-container');
  testCont.innerHTML = `
    ${[
      { img:'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=80&h=80&q=80', name:'Emma Watson', course:'Full Stack Bootcamp', text:'"The Web Dev Bootcamp massively improved my layout skills. Klean completely removes fluff tutorials — pure learning."' },
      { img:'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=80&h=80&q=80', name:'Marcus Brody', course:'UI/UX Masterclass', text:'"Sarah\'s Figma guidelines are clean and pro-grade. Hands-down worth three times the enrolment price."' },
      { img:'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=80&h=80&q=80', name:'Sophia Martinez', course:'Python for Data Science', text:'"Python ML courses gave me immediate data-analytics confidence. Exercises load blazing fast."' }
    ].map(t => `
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="text-warning mb-3 fs-6"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
          <p class="text-muted small mb-4" style="line-height:1.7;">${t.text}</p>
          <div class="d-flex align-items-center gap-3">
            <img src="${t.img}" class="rounded-circle flex-shrink-0" style="width:44px;height:44px;object-fit:cover;" alt="${t.name}">
            <div><h6 class="mb-0 fw-700">${t.name}</h6><small class="text-muted fs-8">${t.course}</small></div>
          </div>
        </div>
      </div>
    `).join('')}
  `;
}

// =====================================================
// CURRENCY FORMATTER (INR)
// =====================================================
function fmt(amount) {
  return '\u20B9' + Number(amount).toLocaleString('en-IN');
}

// =====================================================
// COURSE CARD BUILDER
// =====================================================
function createCourseCardElement(course) {
  const div = document.createElement('div');
  div.className = 'col';
  const isWish = !!(state.currentUser && state.currentUser.wishlist.includes(course.id));
  const stars = renderStars(course.rating);
  div.innerHTML = `
    <div class="course-card">
      <div class="card-img-wrapper" style="cursor:pointer;" onclick="switchView('course-detail-view',{courseId:${course.id}})">
        ${course.isBestseller ? '<span class="badge-bestseller">BESTSELLER</span>' : ''}
        <button class="badge-wishlist-toggle${isWish?' active':''}" onclick="event.stopPropagation();handleWishlistToggle(${course.id},this)" aria-label="Toggle Wishlist">
          <i class="bi ${isWish?'bi-heart-fill':'bi-heart'}"></i>
        </button>
        <img src="${course.thumbnail}" alt="${course.title}" loading="lazy">
      </div>
      <div class="card-body p-3 d-flex flex-column flex-grow-1" style="cursor:pointer;" onclick="switchView('course-detail-view',{courseId:${course.id}})">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge-category">${course.category}</span>
          <span class="text-muted small fw-600"><i class="bi bi-clock text-primary me-1"></i>${course.hours}h</span>
        </div>
        <h6 class="fw-700 text-dark mb-1 text-truncate-2" style="min-height:38px;">${course.title}</h6>
        <p class="text-muted small mb-2 text-truncate">By ${course.instructor}</p>
        <div class="d-flex align-items-center gap-1 mb-3">
          <span class="text-warning small fw-700">${course.rating}</span>
          <span class="fs-8">${stars}</span>
          <span class="text-muted fs-8">(${course.reviews.toLocaleString()})</span>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
          <h5 class="fw-800 text-primary m-0">${fmt(course.price)}</h5>
          <button class="btn btn-outline-klean py-1 px-3 fs-8 fw-700 rounded-3" style="font-size:0.78rem;" onclick="event.stopPropagation();handleQuickCartAdd(${course.id})">
            <i class="bi bi-cart-plus me-1"></i><span class="d-none d-sm-inline">Add to </span>Cart
          </button>
        </div>
      </div>
    </div>
  `;
  return div;
}

function renderStars(rating) {
  let s = '';
  for (let i = 1; i <= 5; i++) {
    s += `<i class="bi ${i <= Math.floor(rating) ? 'bi-star-fill star-filled' : 'bi-star star-empty'}"></i>`;
  }
  return s;
}

// =====================================================
// SEARCH
// =====================================================
function handleNavSearch(e) { if (e.key === 'Enter') triggerNavSearch(); }
function triggerNavSearch() { dispatchSearch(document.getElementById('nav-search-input').value); }
function handleHeroSearch(e) { if (e.key === 'Enter') triggerHeroSearch(); }
function triggerHeroSearch() { dispatchSearch(document.getElementById('hero-search-input').value); }

function dispatchSearch(query) {
  query = query.trim();
  switchView('courses-view');
  document.getElementById('courses-heading').textContent = query ? `Results for "${query}"` : 'All Courses';
  const filtered = query
    ? state.courses.filter(c =>
        c.title.toLowerCase().includes(query.toLowerCase()) ||
        c.subtitle.toLowerCase().includes(query.toLowerCase()) ||
        c.instructor.toLowerCase().includes(query.toLowerCase()))
    : state.courses;
  renderCourseGrid(filtered);
}

// =====================================================
// CATALOG LISTING
// =====================================================
function renderCoursesCatalog() { runCatalogFilter(); }

function runCatalogFilter() {
  const g = id => document.getElementById(id).value;
  const cat     = g('filter-category');
  const level   = g('filter-level');
  const price   = g('filter-price');
  const rating  = g('filter-rating');
  const sort    = g('filter-sort');
  const checkedTopics   = [...document.querySelectorAll('.filter-topic-check:checked')].map(e => e.value);
  const durationVal     = (document.querySelector('.filter-duration-radio:checked') || {}).value || 'all';

  let filtered = [...state.courses];
  if (cat   !== 'all') filtered = filtered.filter(c => c.category === cat);
  if (level !== 'all') filtered = filtered.filter(c => c.level    === level);
  if (price === 'free') filtered = filtered.filter(c => c.price === 0);
  else if (price === 'paid') filtered = filtered.filter(c => c.price > 0);
  if (rating !== 'all') filtered = filtered.filter(c => c.rating >= parseFloat(rating));
  if (checkedTopics.length) filtered = filtered.filter(c => checkedTopics.some(t => c.title.toLowerCase().includes(t.toLowerCase())));
  if (durationVal === 'short') filtered = filtered.filter(c => c.hours <= 20);
  else if (durationVal === 'long') filtered = filtered.filter(c => c.hours > 20);

  if (sort === 'popular')    filtered.sort((a, b) => b.enrolled - a.enrolled);
  else if (sort === 'price-low')  filtered.sort((a, b) => a.price - b.price);
  else if (sort === 'price-high') filtered.sort((a, b) => b.price - a.price);
  else if (sort === 'rating')     filtered.sort((a, b) => b.rating - a.rating);

  renderCourseGrid(filtered);
}

function renderCourseGrid(filtered) {
  const grid = document.getElementById('catalog-courses-grid');
  grid.innerHTML = '';
  document.getElementById('courses-results-count').textContent = `Showing ${filtered.length} of ${state.courses.length} courses`;
  if (!filtered.length) {
    grid.innerHTML = `<div class="col-12 text-center py-5"><h5 class="text-muted fw-700"><i class="bi bi-funnel"></i> No courses match your filters</h5></div>`;
    return;
  }
  filtered.forEach(c => grid.appendChild(createCourseCardElement(c)));
}

function filterByCategory(cat) {
  switchView('courses-view');
  const sel = document.getElementById('filter-category');
  if (sel) sel.value = cat;
  runCatalogFilter();
}

// =====================================================
// WISHLIST TOGGLE
// =====================================================
function handleWishlistToggle(courseId, btn) {
  if (!state.currentUser) { showToast("Please log in to bookmark courses."); switchView('login-view'); return; }
  const wish = state.currentUser.wishlist;
  const idx  = wish.indexOf(courseId);
  const icon = btn.querySelector('i');
  if (idx > -1) {
    wish.splice(idx, 1);
    btn.classList.remove('active');
    icon.className = 'bi bi-heart';
    showToast("Removed from Wishlist");
  } else {
    wish.push(courseId);
    btn.classList.add('active');
    icon.className = 'bi bi-heart-fill';
    showToast("Added to Wishlist! ❤️");
  }
  saveState();
  updateNavbar();
  if (state.currentView === 'wishlist-view') renderWishlistPage();
}

// =====================================================
// CART
// =====================================================
function handleQuickCartAdd(courseId) {
  if (!state.currentUser) { showToast("Please log in to add to cart."); switchView('login-view'); return; }
  if (state.currentUser.role === 'instructor') { showToast("Instructors cannot purchase courses."); return; }
  if (state.currentUser.enrolled.includes(courseId)) { showToast("You're already enrolled in this course!"); return; }
  if (!state.currentUser.cart.includes(courseId)) {
    state.currentUser.cart.push(courseId);
    showToast("Course added to Cart! 🛒");
    saveState(); updateNavbar();
  } else {
    showToast("Already in your cart!");
  }
}

function renderCartPage() {
  const user = state.currentUser;
  if (!user) return;

  const cartRow = document.getElementById('cart-content-row');
  const emptyUi = document.getElementById('cart-empty-ui');
  const col     = document.getElementById('cart-items-column');

  if (!user.cart.length) {
    cartRow.classList.add('d-none');
    emptyUi.classList.remove('d-none');
    return;
  }
  cartRow.classList.remove('d-none');
  emptyUi.classList.add('d-none');
  col.innerHTML = '';

  let sum = 0;
  user.cart.forEach(cId => {
    const c = state.courses.find(x => x.id === cId);
    if (!c) return;
    sum += c.price;
    const el = document.createElement('div');
    el.className = 'card p-3 border rounded-4 bg-white mb-3 shadow-sm';
    el.innerHTML = `
      <div class="row align-items-center g-3">
        <div class="col-4 col-sm-3">
          <img src="${c.thumbnail}" class="img-fluid rounded-3 w-100" style="aspect-ratio:16/9;object-fit:cover;" alt="${c.title}">
        </div>
        <div class="col-8 col-sm-6">
          <h6 class="fw-700 text-dark mb-1 text-truncate-2">${c.title}</h6>
          <p class="text-muted small mb-0">Instructor: ${c.instructor} · ${c.hours}h</p>
        </div>
        <div class="col-sm-3 d-flex flex-row flex-sm-column align-items-center align-items-sm-end justify-content-between gap-2 mt-2 mt-sm-0">
          <h5 class="fw-800 text-primary m-0">${fmt(c.price)}</h5>
          <button class="btn btn-link text-danger text-decoration-none small p-0 fw-600" onclick="removeCartItem(${c.id})"><i class="bi bi-trash me-1"></i>Remove</button>
        </div>
      </div>
    `;
    col.appendChild(el);
  });
  calculateOrderSummary(sum);
}

function removeCartItem(courseId) {
  const idx = state.currentUser.cart.indexOf(courseId);
  if (idx > -1) {
    state.currentUser.cart.splice(idx, 1);
    showToast("Removed from Cart");
    saveState(); updateNavbar(); renderCartPage();
  }
}

function calculateOrderSummary(subtotal) {
  const discount = state.couponApplied ? subtotal * 0.20 : 0;
  const total    = subtotal - discount;
  document.getElementById('cart-subtotal').textContent = fmt(subtotal);
  document.getElementById('cart-discount').textContent = '-' + fmt(discount);
  document.getElementById('cart-total').textContent    = fmt(total);
  document.getElementById('cart-discount-row').classList.toggle('d-none', !state.couponApplied);
}

function applyCouponCode() {
  const val = document.getElementById('coupon-input').value.trim();
  if (val.toUpperCase() === 'KLEAN20') {
    state.couponApplied = true; state.couponCode = 'KLEAN20';
    saveState(); showToast("Coupon KLEAN20 applied — 20% off! 🎉"); renderCartPage();
  } else {
    showToast("Invalid coupon code.");
  }
}

function proceedToCheckout() { switchView('payment-view'); }

// =====================================================
// COURSE DETAIL
// =====================================================
function renderCourseDetail() {
  const course = state.courses.find(c => c.id === state.activeDetailCourseId);
  if (!course) return;

  const set = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  set('detail-bc-title',      course.title);
  set('detail-title',         course.title);
  set('detail-subtitle',      course.subtitle);
  set('detail-rating-num',    course.rating);
  set('detail-review-count',  course.reviews.toLocaleString());
  set('detail-student-count', course.enrolled.toLocaleString());
  set('detail-instructor',    course.instructor);
  document.getElementById('detail-bestseller-badge').classList.toggle('d-none', !course.isBestseller);

  // Overview
  document.getElementById('detail-learn-list').innerHTML = `
    <div class="col-sm-6 d-flex gap-2 text-muted small align-items-start"><i class="bi bi-check-circle text-success fs-6 flex-shrink-0"></i><span>Build real portfolio projects</span></div>
    <div class="col-sm-6 d-flex gap-2 text-muted small align-items-start"><i class="bi bi-check-circle text-success fs-6 flex-shrink-0"></i><span>Master core layout theories</span></div>
    <div class="col-sm-6 d-flex gap-2 text-muted small align-items-start"><i class="bi bi-check-circle text-success fs-6 flex-shrink-0"></i><span>Debug like a senior developer</span></div>
    <div class="col-sm-6 d-flex gap-2 text-muted small align-items-start"><i class="bi bi-check-circle text-success fs-6 flex-shrink-0"></i><span>Deploy to global cloud platforms</span></div>
  `;
  document.getElementById('detail-reqs-list').innerHTML = `
    <li>Stable internet and a modern browser</li>
    <li>No prior programming knowledge required — we start from scratch!</li>
  `;
  document.getElementById('detail-description').innerHTML = `
    <p>This premium structured program cuts out jargon so you retain maximum knowledge in half the traditional time.</p>
    <p>Join thousands of globally up-skilled students inside this verified curriculum today.</p>
  `;

  // Curriculum accordion
  const acc = document.getElementById('curriculumAccordion');
  acc.innerHTML = '';
  const isBought = state.currentUser && state.currentUser.enrolled.includes(course.id);
  course.lessons.forEach((les, idx) => {
    const icon = isBought
      ? `<i class="bi bi-play-circle-fill text-primary"></i>`
      : (les.isPreview ? `<i class="bi bi-eye text-success" title="Free Preview"></i>` : `<i class="bi bi-lock-fill text-muted"></i>`);
    const item = document.createElement('div');
    item.className = 'accordion-item border';
    item.innerHTML = `
      <h2 class="accordion-header" id="ch-${idx}">
        <button class="accordion-button collapsed py-3 bg-white text-dark small fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#cc-${idx}" aria-expanded="false" aria-controls="cc-${idx}">
          <span class="d-flex align-items-center justify-content-between w-100 pe-3">
            <span class="d-flex align-items-center gap-3">${icon}<span>Lesson ${idx+1}: ${les.title}</span></span>
            <span class="text-muted small fw-500 text-nowrap ms-2">${les.duration}</span>
          </span>
        </button>
      </h2>
      <div id="cc-${idx}" class="accordion-collapse collapse bg-light" aria-labelledby="ch-${idx}" data-bs-parent="#curriculumAccordion">
        <div class="accordion-body small text-muted">
          ${les.isPreview
            ? `Free preview available. <a href="#" class="fw-700 text-primary" onclick="showToast('Preview playing...');return false;">Watch now</a>`
            : `Enrol to unlock this lesson.`}
        </div>
      </div>
    `;
    acc.appendChild(item);
  });

  // Instructor
  const isAngela = course.instructor !== 'Sarah Williams';
  document.getElementById('detail-inst-name').textContent   = course.instructor;
  document.getElementById('detail-inst-avatar').src         = isAngela
    ? 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&h=150&q=80'
    : 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&h=150&q=80';
  document.getElementById('detail-inst-title').textContent  = isAngela ? 'Lead Engineer & Educator' : 'Consulting Designer & Teacher';
  document.getElementById('detail-inst-students').textContent = isAngela ? '48,200' : '12,400';
  document.getElementById('detail-inst-courses').textContent  = isAngela ? '5' : '3';
  document.getElementById('detail-inst-bio').textContent    = isAngela
    ? 'Dr. Angela Yu is a passionate educator dedicated to clean, structured full-stack curriculum delivery.'
    : 'Sarah Williams is a senior UX architect drafting structured data wireframes and visual systems globally.';

  // Reviews
  document.getElementById('review-stars-big').textContent  = course.rating;
  document.getElementById('review-rating-stars').innerHTML = renderStars(course.rating);
  document.getElementById('detail-reviews-list').innerHTML = `
    <div class="col-md-6"><div class="p-3 bg-light border rounded-3">
      <div class="d-flex align-items-center gap-2 mb-2"><span class="fw-700 small">Emma Watson</span><span class="text-warning small">${renderStars(5)}</span></div>
      <p class="text-muted small mb-0">Clean, crisp instructions — exactly what I needed to jump-start my skills.</p>
    </div></div>
    <div class="col-md-6"><div class="p-3 bg-light border rounded-3">
      <div class="d-flex align-items-center gap-2 mb-2"><span class="fw-700 small">Marcus Brody</span><span class="text-warning small">${renderStars(5)}</span></div>
      <p class="text-muted small mb-0">Exceptional exercises. The instructor makes complex frameworks feel lightweight.</p>
    </div></div>
  `;

  // Sidebar
  document.getElementById('detail-sidebar-img').src                   = course.thumbnail;
  document.getElementById('detail-sidebar-price').textContent         = fmt(course.price);
  document.getElementById('detail-sidebar-oldprice').textContent      = fmt(course.oldPrice);
  document.getElementById('detail-sidebar-discount').textContent      = `${Math.round(((course.oldPrice-course.price)/course.oldPrice)*100)}% OFF`;
  document.getElementById('detail-inc-hours').textContent             = `${course.hours} hours`;

  // Action buttons
  const actionCont = document.getElementById('detail-sidebar-actions');
  const user = state.currentUser;
  if (!user) {
    actionCont.innerHTML = `<button class="btn btn-primary-klean w-100 py-2" onclick="switchView('login-view')">Login to Enrol</button>`;
  } else if (user.role === 'instructor') {
    actionCont.innerHTML = `<button class="btn btn-secondary w-100 py-2" disabled>Instructor Account</button>`;
  } else if (user.enrolled.includes(course.id)) {
    actionCont.innerHTML = `<button class="btn btn-primary-klean w-100 py-2" onclick="switchView('course-player-view',{courseId:${course.id}})"><i class="bi bi-play-btn me-2"></i>Go to Learning Space</button>`;
  } else {
    const inCart = user.cart.includes(course.id);
    actionCont.innerHTML = `
      <button class="btn btn-primary-klean w-100 py-2 mb-2" onclick="handleBuyNow(${course.id})">Buy Now Securely</button>
      <button class="btn btn-outline-klean w-100 py-2" onclick="handleQuickCartAdd(${course.id})"><i class="bi bi-cart3 me-2"></i>${inCart ? 'Go to Cart' : 'Add to Cart'}</button>
    `;
  }
}

function handleBuyNow(courseId) {
  if (!state.currentUser) { switchView('login-view'); return; }
  if (!state.currentUser.cart.includes(courseId)) {
    state.currentUser.cart.push(courseId); saveState(); updateNavbar();
  }
  switchView('cart-view');
}

// =====================================================
// PAYMENT GATEWAY
// =====================================================
function renderPaymentPage() {
  const user = state.currentUser;
  if (!user) return;
  const pList = document.getElementById('payment-items-list');
  pList.innerHTML = '';
  let sum = 0;
  user.cart.forEach(cId => {
    const c = state.courses.find(x => x.id === cId);
    if (!c) return;
    sum += c.price;
    const el = document.createElement('div');
    el.className = 'd-flex align-items-center justify-content-between gap-2 small';
    el.innerHTML = `
      <div class="d-flex align-items-center gap-2 text-truncate flex-grow-1">
        <img src="${c.thumbnail}" class="rounded-2 flex-shrink-0" style="width:42px;height:30px;object-fit:cover;" alt="">
        <div class="text-truncate"><strong class="text-dark d-block text-truncate">${c.title}</strong><span class="text-muted">${c.instructor}</span></div>
      </div>
      <span class="fw-700 text-dark text-nowrap">${fmt(c.price)}</span>
    `;
    pList.appendChild(el);
  });
  const discount = state.couponApplied ? sum * 0.20 : 0;
  const total    = sum - discount;
  document.getElementById('pay-original-price').textContent   = fmt(sum);
  document.getElementById('pay-discount').textContent         = '-' + fmt(discount);
  document.getElementById('pay-total-price').textContent      = fmt(total);
  document.getElementById('pay-btn-amount').textContent       = fmt(total);
  document.getElementById('pay-btn-amount-upi').textContent   = fmt(total);
  document.getElementById('pay-discount-row').classList.toggle('d-none', !state.couponApplied);
}

function formatCardNumber(input) {
  let val = input.value.replace(/\D/g, '');
  let fmt = '';
  for (let i = 0; i < val.length; i++) { if (i && i % 4 === 0) fmt += ' '; fmt += val[i]; }
  input.value = fmt;
  const icon = document.getElementById('detected-card-icon');
  if      (val.startsWith('4')) icon.innerHTML = `<i class="bi bi-credit-card-2-front text-primary me-1"></i><span class="fs-8 fw-700 text-primary">VISA</span>`;
  else if (val.startsWith('5')) icon.innerHTML = `<i class="bi bi-credit-card-2-front text-warning me-1"></i><span class="fs-8 fw-700 text-warning">MASTERCARD</span>`;
  else                          icon.innerHTML = `<i class="bi bi-credit-card text-muted"></i>`;
}

function formatExpiryDate(input) {
  let val = input.value.replace(/\D/g, '');
  if (val.length > 2) input.value = val.slice(0,2) + '/' + val.slice(2,4);
  else                input.value = val;
}

function quickSelectUPI(app) {
  document.getElementById('upi-id-input').value = `alex@${app}`;
  showToast(`${app.toUpperCase()} UPI selected!`);
}

function handleSecurePayment(e) {
  e.preventDefault();
  const form = e.target;
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const loader = document.getElementById('global-loader-overlay');
  loader.classList.remove('d-none');
  setTimeout(() => {
    loader.classList.add('d-none');
    const user = state.currentUser;
    if (user) {
      const items = [...user.cart];
      items.forEach(cId => {
        if (!user.enrolled.includes(cId)) {
          user.enrolled.push(cId);
          user.progress[cId] = { completed: [0], percent: 12 };
        }
      });
      user.cart = [];
      state.couponApplied = false;
      saveState(); updateNavbar();
      const firstName = (state.courses.find(c => c.id === items[0]) || {}).title || 'Your Course';
      document.getElementById('success-course-name').textContent = firstName;
      document.getElementById('success-order-id').textContent    = `ORD-${randInt(1000,9999)}-${randInt(1000,9999)}`;
      const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
      modal.show();
      generateConfetti();
    }
  }, 1500);
}

function generateConfetti() {
  const container = document.getElementById('confetti-container');
  container.innerHTML = '';
  const colors = ['#6C3FF4','#F59E0B','#10B981','#EF4444','#3B82F6','#EC4899'];
  for (let i = 0; i < 45; i++) {
    const el = document.createElement('div');
    el.className = 'confetti-piece';
    el.style.cssText = `left:${Math.random()*100}%;background:${colors[Math.floor(Math.random()*colors.length)]};animation-delay:${(Math.random()*2).toFixed(2)}s;width:${randInt(6,14)}px;height:${randInt(6,14)}px;border-radius:${Math.random()>0.5?'50%':'3px'}`;
    container.appendChild(el);
  }
}

function goToMyLearningFromSuccess() {
  const inst = bootstrap.Modal.getInstance(document.getElementById('paymentSuccessModal'));
  if (inst) inst.hide();
  switchView('student-dashboard-view');
}

// =====================================================
// STUDENT DASHBOARD
// =====================================================
function renderStudentDashboard() {
  const user = state.currentUser;
  if (!user) return;
  document.getElementById('dash-greeting-name').textContent = user.name;

  const completedCount = user.enrolled.filter(id => (user.progress[id] || {}).percent === 100).length;
  document.getElementById('stat-enrolled').textContent = user.enrolled.length;
  document.getElementById('stat-certs').textContent    = completedCount;
  document.getElementById('stat-hours').textContent    = completedCount * 18 + user.enrolled.length * 12;

  const progGrid = document.getElementById('inprogress-courses-grid');
  const compGrid = document.getElementById('completed-courses-grid');
  const recGrid  = document.getElementById('dash-recommended-grid');
  progGrid.innerHTML = compGrid.innerHTML = recGrid.innerHTML = '';

  let hasInp = false, hasComp = false;
  user.enrolled.forEach(cId => {
    const c   = state.courses.find(x => x.id === cId);
    if (!c) return;
    const pct = (user.progress[cId] || {}).percent || 0;
    if (pct < 100) {
      hasInp = true;
      progGrid.innerHTML += `
        <div class="col-md-6 col-lg-4">
          <div class="card border rounded-4 bg-white shadow-sm overflow-hidden h-100">
            <img src="${c.thumbnail}" class="card-img-top" style="height:120px;object-fit:cover;" alt="${c.title}">
            <div class="card-body p-3">
              <h6 class="fw-700 text-dark mb-2 text-truncate-2">${c.title}</h6>
              <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Progress</span><strong class="text-primary small">${pct}%</strong></div>
              <div class="progress mb-3" style="height:6px;"><div class="progress-bar bg-primary" style="width:${pct}%;"></div></div>
              <button class="btn btn-primary-klean btn-sm w-100 rounded-3" onclick="switchView('course-player-view',{courseId:${c.id}})">Resume Learning</button>
            </div>
          </div>
        </div>`;
    } else {
      hasComp = true;
      compGrid.innerHTML += `
        <div class="col-md-6 col-lg-4">
          <div class="card border rounded-4 bg-white shadow-sm overflow-hidden h-100">
            <img src="${c.thumbnail}" class="card-img-top" style="height:120px;object-fit:cover;" alt="${c.title}">
            <div class="card-body p-3 text-center">
              <h6 class="fw-700 text-dark mb-2 text-truncate-2">${c.title}</h6>
              <span class="badge bg-success bg-opacity-10 text-success fw-700 rounded-pill px-3 py-1 mb-3 d-block">COMPLETED <i class="bi bi-check-lg"></i></span>
              <button class="btn btn-accent-klean btn-sm w-100 rounded-3" onclick="triggerCertificateModal(${c.id})"><i class="bi bi-award me-1"></i>View Certificate</button>
            </div>
          </div>
        </div>`;
    }
  });

  if (!hasInp) progGrid.innerHTML = `<div class="col-12"><p class="text-muted small">No courses in progress yet. <a href="#" onclick="switchView('courses-view');return false;" class="text-primary fw-700">Browse courses</a></p></div>`;
  if (!hasComp) compGrid.innerHTML = `<div class="col-12"><p class="text-muted small">No completed courses yet. Work through lessons to graduate!</p></div>`;

  // Recommended
  state.courses.filter(c => !user.enrolled.includes(c.id)).slice(0,3).forEach(c => recGrid.appendChild(createCourseCardElement(c)));
}

function toggleDashboardTab(tab, el) {
  document.querySelectorAll('#student-sidebar .nav-link-klean').forEach(e => e.classList.remove('active'));
  el.classList.add('active');
}

function triggerCertificateModal(courseId) {
  const c = state.courses.find(x => x.id === courseId);
  if (!c) return;
  document.getElementById('cert-student-name').textContent = state.currentUser.name;
  document.getElementById('cert-course-title').textContent = c.title;
  document.getElementById('cert-date').textContent = new Date().toLocaleDateString('en-US',{month:'long',year:'numeric'});
  new bootstrap.Modal(document.getElementById('certificateModal')).show();
}

// =====================================================
// COURSE PLAYER
// =====================================================
function renderCoursePlayer() {
  const course = state.courses.find(c => c.id === state.activePlayerCourseId);
  const user   = state.currentUser;
  if (!course || !user) return;

  const prog = user.progress[course.id] || { completed: [], percent: 0 };
  document.getElementById('player-top-course-title').textContent = course.title;
  document.getElementById('player-progress-num').textContent     = `${prog.percent}%`;
  document.getElementById('player-progress-bar').style.width    = `${prog.percent}%`;

  const activeLesson = course.lessons[state.activePlayerLectureIdx] || course.lessons[0];
  document.getElementById('player-lecture-title').textContent = `Lesson ${state.activePlayerLectureIdx+1}: ${activeLesson.title}`;
  document.getElementById('player-video-time').textContent    = `00:00 / ${activeLesson.duration}`;
  document.getElementById('player-lessons-summary').textContent = `${prog.completed.length}/${course.lessons.length} Done`;

  const playList = document.getElementById('player-playlist-container');
  playList.innerHTML = '';
  course.lessons.forEach((les, idx) => {
    const isActive   = idx === state.activePlayerLectureIdx;
    const isChecked  = prog.completed.includes(idx);
    const item = document.createElement('div');
    item.className = `player-playlist-item${isActive?' active-lesson':''}`;
    item.onclick   = () => { state.activePlayerLectureIdx = idx; state.activePlayerPlayState = false; renderCoursePlayer(); };
    item.innerHTML = `
      <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" ${isChecked?'checked':''} onclick="event.stopPropagation();toggleLessonProgress(${idx})">
      <div class="flex-grow-1 text-truncate">
        <span class="small fw-600 text-white d-block text-truncate">${les.title}</span>
        <span class="fs-8 text-white-50"><i class="bi bi-clock me-1"></i>${les.duration}</span>
      </div>
      ${isChecked?`<i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>`:`<i class="bi bi-circle text-white-50 flex-shrink-0"></i>`}
    `;
    playList.appendChild(item);
  });

  const noteEl = document.getElementById('player-notes-textarea');
  if (noteEl) noteEl.value = (user.notes || {})[course.id] || '';
}

function toggleLessonProgress(idx) {
  const user   = state.currentUser;
  const course = state.courses.find(c => c.id === state.activePlayerCourseId);
  if (!user || !course) return;
  const prog = user.progress[course.id] || { completed: [], percent: 0 };
  const pos  = prog.completed.indexOf(idx);
  if (pos > -1) { prog.completed.splice(pos, 1); showToast("Lesson marked incomplete."); }
  else          { prog.completed.push(idx);       showToast("Lesson completed! 🎉"); }
  prog.percent = Math.round((prog.completed.length / course.lessons.length) * 100);
  user.progress[course.id] = prog;
  saveState(); renderCoursePlayer();
}

function togglePlayState() {
  state.activePlayerPlayState = !state.activePlayerPlayState;
  const playIcon = document.querySelector('#player-screen i.bi-play-circle, #player-screen i.bi-pause-circle');
  const btnIcon  = document.querySelector('#player-btn-play i');
  if (state.activePlayerPlayState) {
    if (playIcon) playIcon.className = 'bi bi-pause-circle display-1 text-primary';
    if (btnIcon)  btnIcon.className  = 'bi bi-pause-fill';
    showToast("Lesson video playing...");
  } else {
    if (playIcon) playIcon.className = 'bi bi-play-circle display-1 text-primary';
    if (btnIcon)  btnIcon.className  = 'bi bi-play-fill';
    showToast("Lesson video paused.");
  }
}

function saveActivePlayerNotes() {
  const val  = document.getElementById('player-notes-textarea').value;
  const user = state.currentUser;
  if (user) {
    if (!user.notes) user.notes = {};
    user.notes[state.activePlayerCourseId] = val;
    saveState(); showToast("Notes saved! 📝");
  }
}

// =====================================================
// INSTRUCTOR DASHBOARD
// =====================================================
function renderInstructorDashboard() {
  const user = state.currentUser;
  if (!user) return;
  const myCourses = state.courses.filter(c => c.instructor === user.name);
  document.getElementById('inst-stat-students').textContent = (user.stats || {}).students?.toLocaleString('en-IN') || '0';
  document.getElementById('inst-stat-revenue').textContent  = fmt((user.stats || {}).revenue || 0);
  document.getElementById('inst-stat-courses').textContent  = myCourses.length;

  const body = document.getElementById('inst-courses-table-body');
  body.innerHTML = '';
  myCourses.forEach(c => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="ps-3">
        <div class="d-flex align-items-center gap-2">
          <img src="${c.thumbnail}" class="rounded-2 d-none d-sm-block" style="width:48px;height:32px;object-fit:cover;" alt="">
          <strong class="text-dark small">${c.title}</strong>
        </div>
      </td>
      <td class="text-muted small">${c.enrolled.toLocaleString()}</td>
      <td><strong class="text-warning small"><i class="bi bi-star-fill"></i> ${c.rating}</strong></td>
      <td><strong class="text-success small">${fmt(Math.round(c.price * c.enrolled * 0.7))}</strong></td>
      <td><span class="badge bg-success bg-opacity-10 text-success fw-700 py-1 px-2 fs-8">PUBLISHED</span></td>
      <td class="text-center pe-3"><button class="btn btn-outline-klean btn-sm rounded-pill" onclick="showToast('Editor simulated!')">Edit</button></td>
    `;
    body.appendChild(tr);
  });
}

function toggleInstructorSubtab(tab, el) {
  document.querySelectorAll('#instructor-sidebar .nav-link-klean').forEach(e => e.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.instructor-subtab').forEach(e => e.classList.add('d-none'));
  document.getElementById(tab === 'dashboard' ? 'inst-subtab-dashboard' : 'inst-subtab-my-courses').classList.remove('d-none');
}

function triggerCreateCourseModal() {
  new bootstrap.Modal(document.getElementById('createCourseModal')).show();
}

function handleCreateCourseSubmit(e) {
  e.preventDefault();
  const form = e.target;
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const newCourse = {
    id:          state.courses.length + 1,
    title:       document.getElementById('course-create-title').value,
    subtitle:    document.getElementById('course-create-desc').value,
    instructor:  state.currentUser.name,
    category:    document.getElementById('course-create-cat').value,
    level:       document.getElementById('course-create-level').value,
    price:       parseFloat(document.getElementById('course-create-price').value),
    oldPrice:    parseFloat(document.getElementById('course-create-price').value) + 20,
    rating:      5.0, reviews: 1, enrolled: 0, isBestseller: false, hours: 10,
    thumbnail:   "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=400&q=80",
    lessons: [
      { title: "Course Overview Introduction", duration: "15m", isPreview: true },
      { title: "Module 1: Core Concepts",       duration: "30m", isPreview: false }
    ]
  };
  state.courses.push(newCourse);
  saveState();
  bootstrap.Modal.getInstance(document.getElementById('createCourseModal')).hide();
  showToast("New course published to Klean catalogue! 🎓");
  form.reset(); form.classList.remove('was-validated');
  renderInstructorDashboard();
}

// =====================================================
// SETTINGS
// =====================================================
function renderSettingsPage() {
  const user = state.currentUser;
  if (!user) return;
  document.getElementById('settings-profile-fullname').textContent = user.name;
  const f = (id, v) => { const e = document.getElementById(id); if (e) e.value = v || ''; };
  f('settings-name-input',  user.name);
  f('settings-email-input', user.email);
  f('settings-phone-input', user.phone);
  f('settings-web-input',   user.website);
  f('settings-bio-input',   user.bio);
  f('settings-link-input',  user.linkedin);
  f('settings-twit-input',  user.twitter);
  const n = user.notifications || {};
  document.getElementById('check-alert-email').checked = n.email || false;
  document.getElementById('check-alert-push').checked  = n.push  || false;
  document.getElementById('check-alert-sms').checked   = n.sms   || false;
}

function handleSaveProfile(e) {
  e.preventDefault();
  const form = e.target;
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const user = state.currentUser;
  if (user) {
    user.name    = document.getElementById('settings-name-input').value;
    user.phone   = document.getElementById('settings-phone-input').value;
    user.website = document.getElementById('settings-web-input').value;
    user.bio     = document.getElementById('settings-bio-input').value;
    user.linkedin = document.getElementById('settings-link-input').value;
    user.twitter  = document.getElementById('settings-twit-input').value;
    saveState(); showToast("Profile saved successfully! ✅"); renderSettingsPage(); updateNavbar();
  }
}

function handleSavePassword(e) {
  e.preventDefault();
  const form = e.target;
  const newP = document.getElementById('settings-new-pass').value;
  const conf = document.getElementById('settings-conf-pass').value;
  const fb   = document.getElementById('settings-conf-pass-feedback');
  const inp  = document.getElementById('settings-conf-pass');
  if (newP !== conf) { fb.textContent = "Passwords do not match!"; inp.setCustomValidity("mismatch"); }
  else               { inp.setCustomValidity(""); }
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  showToast("Password updated! 🔒"); form.reset(); form.classList.remove('was-validated');
}

// =====================================================
// WISHLIST
// =====================================================
function renderWishlistPage() {
  const user = state.currentUser;
  if (!user) return;
  const grid    = document.getElementById('wishlist-grid');
  const emptyUi = document.getElementById('wishlist-empty-ui');
  if (!user.wishlist.length) {
    grid.innerHTML = '';
    grid.classList.add('d-none');
    emptyUi.classList.remove('d-none');
    return;
  }
  grid.classList.remove('d-none');
  emptyUi.classList.add('d-none');
  grid.innerHTML = '';
  user.wishlist.forEach(cId => {
    const c = state.courses.find(x => x.id === cId);
    if (!c) return;
    grid.appendChild(createCourseCardElement(c));
  });
}

// =====================================================
// AUTH
// =====================================================
function handleLoginFormSubmit(e) {
  e.preventDefault();
  const form = e.target;
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const email = document.getElementById('login-email').value;
  const pass  = document.getElementById('login-password').value;
  if      (email === 'sarah@email.com' && pass === 'password') { state.currentUser = { ...DEFAULT_INSTRUCTOR }; showToast("Welcome back, Sarah! 👩‍💻"); }
  else if (email === 'alex@email.com'  && pass === 'password') { state.currentUser = { ...DEFAULT_STUDENT };    showToast("Welcome back, Alex! 👋"); }
  else {
    state.currentUser = { name: email.split('@')[0], email, role: 'student', enrolled:[1], progress:{1:{completed:[0],percent:12}}, notes:{}, cart:[], wishlist:[], notifications:{email:true,push:true,sms:false} };
    showToast(`Logged in as ${state.currentUser.name}!`);
  }
  saveState(); updateNavbar(); goToDashboard();
}

function handleSignupFormSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const pass = document.getElementById('signup-password').value;
  const conf = document.getElementById('signup-confirm-password').value;
  const confInp = document.getElementById('signup-confirm-password');
  const fb      = document.getElementById('confirm-pass-feedback');
  if (pass !== conf) { fb.textContent = "Passwords do not match!"; confInp.setCustomValidity("mismatch"); }
  else               { confInp.setCustomValidity(""); }
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const name = document.getElementById('signup-name').value;
  const email= document.getElementById('signup-email').value;
  const role = (document.querySelector('input[name="signup-role"]:checked') || {}).value || 'student';
  state.currentUser = role === 'instructor'
    ? { name, email, role:'instructor', enrolled:[], progress:{}, notes:{}, cart:[], wishlist:[], createdCourses:[], stats:{students:0,revenue:0,rating:5.0,courses:0}, notifications:{email:true,push:true,sms:false} }
    : { name, email, role:'student',    enrolled:[], progress:{}, notes:{}, cart:[], wishlist:[],                                                                         notifications:{email:true,push:true,sms:false} };
  showToast(`Account created for ${name}! 🎉`);
  saveState(); updateNavbar(); goToDashboard();
}

function handleLogout() {
  state.currentUser = null; state.couponApplied = false;
  saveState(); showToast("Logged out successfully."); updateNavbar(); switchView('landing-view');
}

function triggerForgotPasswordModal() {
  new bootstrap.Modal(document.getElementById('forgotPasswordModal')).show();
}

function handleForgotPassSubmit(e) {
  e.preventDefault();
  const form = e.target;
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const email = document.getElementById('forgot-email').value;
  document.getElementById('sent-forgot-email').textContent = email;
  document.getElementById('forgot-success-alert').classList.remove('d-none');
  setTimeout(() => {
    const inst = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
    if (inst) inst.hide();
    document.getElementById('forgot-success-alert').classList.add('d-none');
    form.reset();
    showToast("Reset link sent — check your inbox!");
  }, 2500);
}

// =====================================================
// MOBILE SIDEBAR HELPERS
// =====================================================
function openMobileSidebar(sidebarId) {
  const sidebar  = document.getElementById(sidebarId);
  const overlay  = document.getElementById('sidebar-overlay');
  if (sidebar) sidebar.classList.add('show-sidebar');
  if (overlay) overlay.classList.add('show');
}

function closeMobileSidebar() {
  document.querySelectorAll('.dashboard-sidebar').forEach(s => s.classList.remove('show-sidebar'));
  const overlay = document.getElementById('sidebar-overlay');
  if (overlay) overlay.classList.remove('show');
}

// =====================================================
// TOAST SYSTEM
// =====================================================
function showToast(message) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = 'toast align-items-center klean-toast border-0 p-2 show';
  toast.innerHTML = `
    <div class="d-flex align-items-center gap-2">
      <div class="toast-body small flex-grow-1"><i class="bi bi-info-circle-fill me-1 text-warning"></i>${message}</div>
      <button type="button" class="btn-close btn-close-white me-1 flex-shrink-0" onclick="this.closest('.toast').remove()"></button>
    </div>`;
  container.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3200);
}

// =====================================================
// UTILS
// =====================================================
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

// =====================================================
// EVENT LISTENERS & STARTUP
// =====================================================
window.addEventListener('scroll', () => {
  document.querySelector('.klean-navbar')?.classList.toggle('navbar-scrolled', window.scrollY > 50);
}, { passive: true });

document.addEventListener('DOMContentLoaded', () => {
  initAppState();
  switchView('landing-view');

  // Close mobile sidebar when overlay is clicked
  const overlay = document.getElementById('sidebar-overlay');
  if (overlay) overlay.addEventListener('click', closeMobileSidebar);
});
