(() => {
  'use strict';

  const DEFAULT_VIEW_MS = 10000;
  const PAGE_SIZES = { announcements: 4, news: 3, programmes: 1, leave: 4 };
  const $ = (id) => document.getElementById(id);
  const base = window.BOARD_BASE || '';
  const views = Array.from(document.querySelectorAll('.board-view'));

  function queryValue(name) {
    const pairs = window.location.search ? window.location.search.substring(1).split('&') : [];
    for (let index = 0; index < pairs.length; index += 1) {
      const parts = pairs[index].split('=');
      try {
        const key = decodeURIComponent(parts.shift() || '');
        const value = decodeURIComponent(parts.join('=').replace(/\+/g, ' '));
        if (key === name) return value;
      } catch (_) { /* Ignore malformed query text. */ }
    }
    return null;
  }

  function clearElement(node) {
    while (node && node.firstChild) node.removeChild(node.firstChild);
  }

  if (!Element.prototype.append) {
    Element.prototype.append = function () {
      for (let index = 0; index < arguments.length; index += 1) {
        const child = arguments[index];
        this.appendChild(child instanceof Node ? child : document.createTextNode(String(child)));
      }
    };
  }

  const tvRequest = queryValue('tv');
  const tvMode = tvRequest === '1' || (tvRequest !== '0' && /SMART-TV|Tizen|Samsung/i.test(navigator.userAgent || ''));
  if (tvMode) document.body.classList.add('tv-mode');

  let data = window.BOARD_DATA || {};
  let pendingData = null;
  let activeView = 0;
  let viewTimer = null;
  let programmeTimer = null;
  let refreshTimer = null;
  let mediaIndex = 0;
  let announcementPage = 0;
  let newsPage = 0;
  let programmePage = 0;
  let leavePage = 0;
  let isPaused = false;
  let tvHelpTimer = null;
  let pointerTimer = null;
  let lastRemoteAction = 0;
  const viewVisits = new Array(views.length).fill(0);

  const path = (value) => value
    ? `${base}/${String(value).replace(/^\//, '')}`
    : `${base}/assets/images/placeholder.svg`;

  const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };

  function safeDate(value) {
    if (!value) return 'Ongoing';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return 'Date to be confirmed';
    return new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
  }

  function setImageSource(image, value) {
    image.setAttribute('loading', 'eager');
    image.setAttribute('decoding', 'async');
    image.onerror = () => {
      if (image._boardFallbackApplied) return;
      image._boardFallbackApplied = true;
      image.src = path('assets/images/placeholder.svg');
    };
    image.src = path(value);
  }

  function clock() {
    const now = new Date();
    $('live-time').textContent = now.toLocaleTimeString('en-GB', {
      hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    });
    $('live-date').textContent = now.toLocaleDateString('en-GB', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
  }

  function pagedItems(items, page, size) {
    if (!items.length) return [];
    const pages = Math.max(1, Math.ceil(items.length / size));
    const safePage = page % pages;
    return items.slice(safePage * size, safePage * size + size);
  }

  function nextPage(current, items, size) {
    const pages = Math.max(1, Math.ceil(items.length / size));
    return (current + 1) % pages;
  }

  function pageSize(section) {
    if (window.matchMedia('(max-width: 700px)').matches) {
      return { announcements: 2, news: 1, programmes: 1, leave: 2 }[section];
    }
    return PAGE_SIZES[section];
  }

  function arrayValue(value) {
    return Array.isArray(value) ? value : [];
  }

  function hasText(value) {
    return value !== null && value !== undefined && String(value).trim() !== '';
  }

  function isPositive(value) {
    const number = Number(value);
    return isFinite(number) && number > 0;
  }

  function validItems(key, field) {
    return arrayValue(data[key]).filter((item) => item && hasText(item[field]));
  }

  function mediaSlides() {
    return validItems('media_slides', 'media_path');
  }

  function announcements() {
    return validItems('announcements', 'title');
  }

  function newsItems() {
    return validItems('news', 'title');
  }

  function programmes() {
    return validItems('programmes', 'name');
  }

  function leaveItems() {
    return validItems('staff_leave', 'name');
  }

  function hasProfileStatistics() {
    const students = data.student_statistics || {};
    const staff = data.staff_statistics || {};
    return isPositive(students.total_students)
      || isPositive(staff.teaching_staff)
      || isPositive(staff.admin_support_staff);
  }

  function hasPurpose() {
    const settings = data.settings || {};
    return hasText(settings.vision);
  }

  function settingIsEnabled(value, fallback) {
    if (value === null || value === undefined) return fallback;
    return ['1', 'true', 'yes', 'on'].indexOf(String(value).trim().toLowerCase()) !== -1;
  }

  function welcomeValue(key, fallback) {
    const settings = data.settings || {};
    return hasText(settings[key]) ? String(settings[key]).trim() : fallback;
  }

  function hasWelcomeGreeting() {
    const settings = data.settings || {};
    return settingIsEnabled(settings.welcome_enabled, true)
      && hasText(welcomeValue('welcome_title', 'Welcome to Trongsa College'))
      && hasText(welcomeValue('welcome_message', 'A place where Bhutanese heritage, curiosity, and purposeful learning come together.'));
  }

  function fallbackViewIndex() {
    for (let index = 0; index < views.length; index += 1) {
      if ((views[index].getAttribute('data-view') || '') === 'Highlights') return index;
    }
    return 0;
  }

  function viewHasContent(index) {
    const name = views[index] ? views[index].getAttribute('data-view') : '';
    if (name === 'Welcome') return hasWelcomeGreeting();
    if (name === 'Highlights') return mediaSlides().length > 0;
    if (name === 'Announcements') return announcements().length > 0;
    if (name === 'Latest news') return newsItems().length > 0;
    if (name === 'College profile') return hasProfileStatistics();
    if (name === 'Academic programmes') return programmes().length > 0;
    if (name === 'Vision') return hasPurpose();
    if (name === 'Staff on leave') return leaveItems().length > 0;
    return false;
  }

  function availableViewIndexes() {
    const available = [];
    for (let index = 0; index < views.length; index += 1) {
      if (viewHasContent(index)) available.push(index);
    }
    return available.length ? available : [fallbackViewIndex()];
  }

  function adjacentAvailableView(from, direction) {
    const available = availableViewIndexes();
    if (available.length === 1) return available[0];
    for (let step = 1; step <= views.length; step += 1) {
      const candidate = (from + (direction * step) + views.length) % views.length;
      if (available.indexOf(candidate) !== -1) return candidate;
    }
    return available[0];
  }

  function updatePanelVisibility() {
    const validProgrammes = programmes().length > 0;
    const validStatistics = hasProfileStatistics();
    const statisticsPanel = document.querySelector('.profile-statistics');
    const programmesPanel = document.querySelector('.profile-programmes');
    if (statisticsPanel) statisticsPanel.hidden = !validStatistics;
    if (programmesPanel) programmesPanel.hidden = !validProgrammes;
  }

  function renderMedia() {
    const items = mediaSlides();
    const current = items.length
      ? items[mediaIndex % items.length]
      : {
          title: 'Welcome to Trongsa College',
          caption: 'Learning rooted in Bhutanese heritage, ready for a changing world.',
          media_path: 'assets/images/placeholder.svg',
          media_type: 'image'
        };

    const stage = $('media-stage');
    clearElement(stage);
    const media = el(current.media_type === 'video' ? 'video' : 'img');
    if (current.media_type === 'video') {
      media.src = path(current.media_path);
      media.autoplay = true;
      media.muted = true;
      media.loop = true;
      media.preload = 'auto';
      media.playsInline = true;
      media.setAttribute('playsinline', '');
      media.setAttribute('webkit-playsinline', '');
      media.poster = path(current.poster_path || 'assets/images/placeholder.svg');
      media.onerror = () => {
        const fallback = el('img');
        fallback.alt = current.title || 'College highlight';
        setImageSource(fallback, current.poster_path || 'assets/images/placeholder.svg');
        clearElement(stage);
        stage.append(fallback);
      };
    } else {
      media.alt = current.title || 'College highlight';
      setImageSource(media, current.media_path);
    }
    stage.append(media);
    $('media-title').textContent = current.title || 'College highlight';
    $('media-caption').textContent = current.caption || '';

    const dots = $('media-dots');
    clearElement(dots);
    items.forEach((_, index) => dots.append(el('span', index === mediaIndex % items.length ? 'active' : '')));
  }

  function renderHighlightNotice() {
    const target = $('highlight-notice');
    clearElement(target);
    const currentAnnouncements = announcements();
    const item = currentAnnouncements.find((announcement) => announcement.priority === 'urgent') || currentAnnouncements[0];
    if (!item) return;
    const card = el('div', `highlight-notice-card ${item.priority || 'normal'}`);
    card.append(
      el('span', '', item.priority === 'urgent' ? 'Urgent notice' : 'From the noticeboard'),
      el('strong', '', item.title)
    );
    target.append(card);
  }

  function renderAnnouncements() {
    const target = $('announcement-list');
    clearElement(target);
    const allItems = announcements();
    const items = pagedItems(allItems, announcementPage, pageSize('announcements'));
    if (!items.length) return target.append(empty('There are no current announcements.'));

    items.forEach((item) => {
      const card = el('article', `announcement-item ${item.priority || 'normal'}`);
      const visual = el('div', 'announcement-visual');
      const visualMark = el('span', 'announcement-visual-mark');
      visualMark.append(el('span', '', 'i'));
      visual.append(
        el('span', 'announcement-visual-kicker', 'Official notice'),
        visualMark,
        el('span', 'announcement-visual-label', 'Please take note')
      );

      const copy = el('div', 'announcement-copy');
      const top = el('div', 'announcement-top');
      top.append(el('span', 'priority-pill', item.priority || 'normal'), el('span', 'announcement-rule'));

      const meta = el('div', 'announcement-meta');
      const published = el('span', 'announcement-date');
      published.append(el('small', '', 'Published'), el('strong', '', safeDate(item.publish_date)));
      const expiry = el('span', 'announcement-date');
      expiry.append(el('small', '', 'Valid until'), el('strong', '', safeDate(item.expiry_date)));
      meta.append(published, el('span', 'announcement-meta-divider'), expiry);

      copy.append(
        top,
        el('h3', '', item.title),
        el('p', '', item.description),
        meta
      );
      card.append(visual, copy);
      target.append(card);
    });
  }

  function renderNews() {
    const target = $('news-stage');
    clearElement(target);
    const allItems = newsItems();
    const items = pagedItems(allItems, newsPage, pageSize('news'));
    target.classList.toggle('single-news', items.length === 1);
    if (!items.length) return target.append(empty('There is no recent college news.'));

    items.forEach((item, index) => {
      const hasImage = hasText(item.featured_image);
      const card = el('article', `news-item ${index === 0 ? 'featured' : ''} ${items.length === 1 ? 'single-news-item' : ''} ${hasImage ? 'has-image' : 'without-image'}`);
      const copy = el('div', 'news-copy');
      copy.append(
        el('div', 'news-date', safeDate(item.publication_date)),
        el('h3', '', item.title),
        el('p', '', item.description)
      );
      if (hasImage) {
        const image = el('img', 'news-image');
        image.alt = '';
        setImageSource(image, item.featured_image);
        card.append(image);
      }
      card.append(copy);
      target.append(card);
    });
  }

  function renderStats() {
    const students = data.student_statistics || {};
    const staff = data.staff_statistics || {};
    const stats = [];
    if (isPositive(students.total_students)) stats.push(['ST', Number(students.total_students), 'Total students', 'students', 'Enrolment']);
    if (isPositive(staff.teaching_staff)) stats.push(['T', Number(staff.teaching_staff), 'Teaching staff', 'teaching', 'Academic team']);
    if (isPositive(staff.admin_support_staff)) stats.push(['A', Number(staff.admin_support_staff), 'Admin & support', 'support', 'Campus services']);

    const target = $('stats-grid');
    clearElement(target);
    target.className = `stats-grid count-${stats.length}`;
    stats.forEach((stat) => {
      const icon = stat[0];
      const value = stat[1];
      const label = stat[2];
      const variant = stat[3];
      const kicker = stat[4];
      const card = el('div', `stat-item stat-${variant}`);
      const cardKicker = el('div', 'stat-card-kicker', kicker);
      const iconBox = el('div', 'stat-icon', icon);
      const copy = el('div');
      const number = el('div', 'stat-number', '0');
      copy.append(number, el('div', 'stat-label', label));
      card.append(cardKicker, iconBox, copy);
      target.append(card);
      animateNumber(number, Number(value));
    });
  }

  function animateNumber(node, target) {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      node.textContent = target.toLocaleString();
      return;
    }
    const start = performance.now();
    const duration = 800;
    const tick = (time) => {
      if (!document.documentElement.contains(node)) return;
      const progress = Math.min((time - start) / duration, 1);
      node.textContent = Math.floor(target * (1 - Math.pow(1 - progress, 3))).toLocaleString();
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  }

  function renderProgrammes() {
    const target = $('programme-list');
    clearElement(target);
    const allItems = programmes();
    const items = pagedItems(allItems, programmePage, pageSize('programmes'));
    if (!items.length) return target.append(empty('Programme information is being updated.'));

    items.forEach((item, itemIndex) => {
      const currentIndex = ((programmePage * pageSize('programmes')) + itemIndex) % allItems.length;
      const currentNumber = currentIndex + 1 < 10 ? `0${currentIndex + 1}` : String(currentIndex + 1);
      const totalNumber = allItems.length < 10 ? `0${allItems.length}` : String(allItems.length);
      const row = el('article', 'programme-item programme-feature');
      const mark = el('div', 'programme-mark');
      mark.append(
        el('span', 'programme-sequence', currentNumber),
        el('div', 'programme-abbr', item.abbreviation),
        el('span', 'programme-mark-label', 'Academic programme')
      );
      const copy = el('div', 'programme-copy');
      copy.append(
        el('span', 'programme-kicker', 'Shape your future'),
        el('h3', '', item.name),
        el('div', 'programme-duration', item.duration),
        el('p', '', item.description || 'Programme details are available from the college academic office.')
      );
      row.append(mark, copy, el('div', 'programme-position', `${currentNumber} / ${totalNumber}`));
      target.append(row);
    });
  }

  function animateProgramme() {
    renderProgrammes();
    const target = $('programme-list');
    target.classList.remove('animate-programme');
    void target.offsetWidth;
    target.classList.add('animate-programme');
  }

  function startProgrammeRotation() {
    const items = programmes();
    if (!items.length) return renderProgrammes();
    animateProgramme();
    if (items.length < 2 || isPaused || document.hidden) return;
    programmeTimer = setInterval(() => {
      programmePage = nextPage(programmePage, items, 1);
      animateProgramme();
    }, 4000);
  }

  function renderLeave() {
    const target = $('leave-list');
    clearElement(target);
    const allItems = leaveItems();
    const items = pagedItems(allItems, leavePage, pageSize('leave'));
    target.className = `leave-list count-${items.length}`;
    if (!items.length) return target.append(empty('No staff leave is listed today.'));

    items.forEach((item) => {
      const row = el('div', 'leave-person');
      const image = el('img', 'leave-avatar');
      image.alt = '';
      setImageSource(image, item.photo);
      const copy = el('div', 'leave-copy');
      copy.append(el('h3', '', item.name), el('p', '', `${item.position}${item.department ? ` • ${item.department}` : ''}`));
      const dates = el('div', 'leave-dates');
      dates.append(el('div', 'leave-type', item.leave_type), el('div', '', `${safeDate(item.start_date)} – ${safeDate(item.end_date)}`));
      row.append(image, copy, dates);
      target.append(row);
    });
  }

  function renderTicker() {
    const target = $('ticker-track');
    clearElement(target);
    let items = data.ticker_notices || [];
    if (!items.length) items = [{ message: 'Welcome to Trongsa College of Heritage and Contemporary Studies.', priority: 'normal' }];
    const characters = items.reduce((total, item) => total + item.message.length, 0);
    const makeSequence = (hidden) => {
      const sequence = el('span', 'ticker-sequence');
      if (hidden) sequence.setAttribute('aria-hidden', 'true');
      items.forEach((item) => sequence.append(el('span', `ticker-message ${item.priority || 'normal'}`, item.message)));
      return sequence;
    };
    const firstSequence = makeSequence(false);
    target.append(firstSequence);

    const tickerWindow = target.parentNode;
    const canScroll = tickerWindow && tickerWindow.clientWidth > 0
      && firstSequence.scrollWidth > tickerWindow.clientWidth;
    target.classList.toggle('ticker-static', !canScroll);
    if (canScroll) target.append(makeSequence(true));
    target.style.animationDuration = canScroll ? `${Math.max(34, characters / 3.1)}s` : '0s';
  }

  function renderWelcomeGreeting() {
    const settings = data.settings || {};
    const greeting = $('welcome-kicker');
    const title = $('welcome-title');
    const message = $('welcome-message');
    const footer = $('welcome-footer-text');
    const logo = $('welcome-logo');

    if (greeting) greeting.textContent = welcomeValue('welcome_kicker', 'Kuzuzangpo la');
    if (title) title.textContent = welcomeValue('welcome_title', 'Welcome to Trongsa College');
    if (message) message.textContent = welcomeValue('welcome_message', 'A place where Bhutanese heritage, curiosity, and purposeful learning come together.');
    if (footer) footer.textContent = settings.footer_text || 'Knowledge • Culture • Service';
    if (logo) {
      logo.alt = `${settings.college_name || 'Trongsa College'} logo`;
      setImageSource(logo, settings.logo_path || 'assets/images/college-logo.png');
    }
  }

  function renderIdentity() {
    const settings = data.settings || {};
    $('college-name').textContent = settings.college_name || 'Trongsa College of Heritage and Contemporary Studies';
    $('university-name').textContent = settings.university_name || 'Royal University of Bhutan';
    $('footer-text').textContent = settings.footer_text || '';
    $('college-logo').src = path(settings.logo_path || 'assets/images/college-logo.png');
    $('welcome-vision').textContent = settings.vision || '';
    $('vision-text').textContent = settings.vision || '';
    document.documentElement.style.setProperty('--primary', settings.primary_colour || '#173f2f');
    document.documentElement.style.setProperty('--accent', settings.accent_colour || '#d7a536');
  }

  function renderAll() {
    renderWelcomeGreeting();
    renderIdentity();
    renderMedia();
    renderHighlightNotice();
    renderAnnouncements();
    renderNews();
    renderStats();
    renderProgrammes();
    renderLeave();
    renderTicker();
    updatePanelVisibility();
  }

  function empty(message) {
    const state = el('div', 'empty-state', message);
    state.style.gridColumn = '1 / -1';
    return state;
  }

  function advanceContentForView(index) {
    const name = views[index] ? views[index].getAttribute('data-view') : '';
    if (name === 'Highlights') {
      const slides = mediaSlides();
      if (slides.length) mediaIndex = (mediaIndex + 1) % slides.length;
      renderMedia();
    } else if (name === 'Announcements') {
      const items = announcements();
      announcementPage = nextPage(announcementPage, items, pageSize('announcements'));
      renderAnnouncements();
    } else if (name === 'Latest news') {
      const items = newsItems();
      newsPage = nextPage(newsPage, items, pageSize('news'));
      renderNews();
    } else if (name === 'Staff on leave') {
      const items = leaveItems();
      leavePage = nextPage(leavePage, items, pageSize('leave'));
      renderLeave();
    }
  }

  function applyPendingData() {
    if (!pendingData) return;
    data = pendingData;
    pendingData = null;
    announcementPage = 0;
    newsPage = 0;
    programmePage = 0;
    leavePage = 0;
    for (let index = 0; index < viewVisits.length; index += 1) viewVisits[index] = 0;
    mediaIndex %= Math.max(1, mediaSlides().length);
    renderAll();
  }

  function safePlay(video) {
    if (!video || typeof video.play !== 'function') return;
    try {
      const playResult = video.play();
      if (playResult && typeof playResult.catch === 'function') playResult.catch(() => {});
    } catch (_) { /* Autoplay can be restricted; rotation should still continue. */ }
  }

  function clearViewTimer() {
    if (viewTimer !== null) clearTimeout(viewTimer);
    if (programmeTimer !== null) clearInterval(programmeTimer);
    viewTimer = null;
    programmeTimer = null;
  }

  function showView(index, options) {
    if (!views.length) return;
    options = options || {};
    const requested = (index + views.length) % views.length;
    const available = availableViewIndexes();
    const normalized = available.indexOf(requested) !== -1 ? requested : available[0];
    const previous = activeView;
    activeView = normalized;

    const oldVideo = views[previous] ? views[previous].querySelector('video') : null;
    if (oldVideo && previous !== normalized) oldVideo.pause();

    if (options.advanceContent && viewVisits[normalized] > 0) advanceContentForView(normalized);
    viewVisits[normalized] += 1;

    views.forEach((view, viewIndex) => {
      const active = viewIndex === normalized;
      view.classList.toggle('active', active);
      view.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
    if ((views[normalized].getAttribute('data-view') || '') === 'College profile') {
      renderStats();
      const statsGrid = $('stats-grid');
      statsGrid.classList.remove('animate-stat-cards');
      void statsGrid.offsetWidth;
      statsGrid.classList.add('animate-stat-cards');
    }
    if ((views[normalized].getAttribute('data-view') || '') === 'Academic programmes' && previous !== normalized) {
      programmePage = 0;
    }
    const currentVideo = views[normalized] ? views[normalized].querySelector('video') : null;
    safePlay(currentVideo);
    restartViewTimer();
  }

  function restartViewTimer() {
    clearViewTimer();
    const media = mediaSlides()[mediaIndex];
    const settings = data.settings || {};
    const activeName = views[activeView] ? views[activeView].getAttribute('data-view') : '';
    if (activeName === 'Academic programmes') startProgrammeRotation();
    const seconds = activeName === 'Welcome'
      ? Number(settings.welcome_duration || settings.default_slide_duration || 10)
      : activeName === 'Highlights'
      ? Number((media && media.duration_seconds) || settings.default_slide_duration || 10)
      : activeName === 'Academic programmes'
        ? Math.max(Number(settings.default_slide_duration || 10), programmes().length * 4)
        : Number(settings.default_slide_duration || 10);
    const duration = Math.max(3, isFinite(seconds) ? seconds : 10) * 1000 || DEFAULT_VIEW_MS;
    if (document.hidden || isPaused) return;
    viewTimer = setTimeout(() => {
      viewTimer = null;
      applyPendingData();
      showView(adjacentAvailableView(activeView, 1), { advanceContent: true });
    }, duration);
  }

  function refresh() {
    clearTimeout(refreshTimer);
    refreshTimer = null;
    if (typeof window.fetch !== 'function') {
      $('sync-indicator').style.background = '#d7a536';
      return;
    }
    window.fetch(`${base}/api/board-data.php`, {
      headers: { Accept: 'application/json' }, cache: 'no-store'
    }).then((response) => {
      if (!response.ok) throw new Error('Refresh failed');
      return response.json();
    }).then((nextData) => {
      pendingData = nextData;
      $('sync-indicator').style.background = '#48c985';
      scheduleRefresh();
    }, () => {
      $('sync-indicator').style.background = '#d7a536';
      scheduleRefresh();
    });
  }

  function scheduleRefresh() {
    clearTimeout(refreshTimer);
    if (typeof window.fetch !== 'function') return;
    const source = pendingData || data;
    const settings = source.settings || {};
    const seconds = Math.max(30, Number(settings.refresh_interval || 180));
    refreshTimer = setTimeout(refresh, seconds * 1000);
  }

  function initialViewIndex() {
    const available = availableViewIndexes();
    const requested = queryValue('view');
    if (!requested) return available[0];
    if (/^\d+$/.test(requested) && Number(requested) >= 1 && Number(requested) <= views.length) {
      const requestedIndex = Number(requested) - 1;
      return available.indexOf(requestedIndex) !== -1 ? requestedIndex : available[0];
    }
    for (let index = 0; index < views.length; index += 1) {
      const name = (views[index].getAttribute('data-view') || '').toLowerCase().replace(/\s+/g, '-');
      if (name === requested.toLowerCase() && available.indexOf(index) !== -1) return index;
    }
    return available[0];
  }

  function manualView(index) {
    applyPendingData();
    const normalized = (index + views.length) % views.length;
    const available = availableViewIndexes();
    if (available.indexOf(normalized) === -1) {
      if (available.indexOf(activeView) === -1) showView(available[0]);
      showTvHelp('No current information in that section', 2400);
      return;
    }
    showView(normalized, { advanceContent: true });
  }

  function manualStep(direction) {
    applyPendingData();
    showView(adjacentAvailableView(activeView, direction), { advanceContent: true });
  }

  function showTvHelp(message, duration) {
    const help = $('tv-help');
    if (!tvMode || !help) return;
    clearTimeout(tvHelpTimer);
    help.innerHTML = message;
    help.hidden = false;
    if (duration) tvHelpTimer = setTimeout(() => { help.hidden = true; }, duration);
  }

  function setRotationPaused(paused) {
    const state = $('rotation-state');
    isPaused = paused;
    document.body.classList.toggle('rotation-paused', paused);
    if (state) state.hidden = !paused;
    if (paused) {
      clearViewTimer();
      showTvHelp('Rotation paused&nbsp;&nbsp;·&nbsp;&nbsp;Press OK to resume', 3500);
    } else {
      restartViewTimer();
      showTvHelp('Auto rotation resumed&nbsp;&nbsp;·&nbsp;&nbsp;10-second views', 3000);
    }
  }

  function toggleRotation() {
    setRotationPaused(!isPaused);
  }

  function enterFullscreen() {
    const root = document.documentElement;
    const active = document.fullscreenElement || document.webkitFullscreenElement;
    const request = root.requestFullscreen || root.webkitRequestFullscreen || root.webkitRequestFullScreen;
    if (active || !request) return;
    try {
      const result = request.call(root);
      if (result && typeof result.catch === 'function') result.catch(() => {});
    } catch (_) { /* Some TV browser policies disable page-controlled fullscreen. */ }
  }

  function initTvMode() {
    if (!tvMode) return;
    enterFullscreen();

    document.addEventListener('click', enterFullscreen);

    document.addEventListener('mousemove', () => {
      document.body.classList.add('show-pointer');
      clearTimeout(pointerTimer);
      pointerTimer = setTimeout(() => { document.body.classList.remove('show-pointer'); }, 2500);
    });

    document.addEventListener('keydown', (event) => {
      const code = event.keyCode || event.which;
      const now = Date.now();
      const isNumberedView = code >= 49 && code <= 57 && (code - 49) < views.length;
      if (code === 37 || code === 39 || isNumberedView || code === 13 || code === 32 || code === 10252 || code === 415 || code === 19) {
        enterFullscreen();
      }
      if ((code === 37 || code === 39) && now - lastRemoteAction < 220) return;
      if (code === 37) {
        event.preventDefault();
        lastRemoteAction = now;
        manualStep(-1);
      } else if (code === 39) {
        event.preventDefault();
        lastRemoteAction = now;
        manualStep(1);
      } else if (isNumberedView) {
        event.preventDefault();
        manualView(code - 49);
      } else if (code === 13 || code === 32 || code === 10252) {
        event.preventDefault();
        toggleRotation();
      } else if (code === 415) {
        event.preventDefault();
        if (isPaused) setRotationPaused(false);
      } else if (code === 19) {
        event.preventDefault();
        if (!isPaused) setRotationPaused(true);
      }
    });

    try {
      if (window.tizen && window.tizen.tvinputdevice && typeof window.tizen.tvinputdevice.registerKey === 'function') {
        window.tizen.tvinputdevice.registerKey('MediaPlayPause');
      }
    } catch (_) { /* The regular Samsung TV browser does not expose packaged-app APIs. */ }
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearViewTimer();
    } else if (!isPaused) {
      restartViewTimer();
    }
  });

  clock();
  setInterval(clock, 1000);
  renderAll();
  showView(initialViewIndex());
  scheduleRefresh();
  initTvMode();
  setInterval(() => {
    if (!document.hidden && !isPaused && viewTimer === null) restartViewTimer();
  }, 15000);
})();
