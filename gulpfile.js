/*----------------------------------------------------------------------
  Gulp customizations for this installation
----------------------------------------------------------------------*/
// Direct url to the localhost installation for this site.
const localhostURL = 'http://localhost/alifewelllit'

// Additional javascript files you'd like to include in scripts.min.js
// See builtInJavascript below to learn what's already included
// Use this to include additional packages in node_modules or other
// libraries you bring in. This should be a full path relative to
// this site's root WordPress installation.
const scripts = []

const parseUrl = function(url) {
  var m = url.match(/^(([^:\/?#]+:)?(?:\/\/(([^/?#:]*)(?::([^\/?#:]*))?)))?([^?#]*)(\?[^#]*)?(#.*)?$/),
      r = {
          hash: m[8] || "",                    // #asd
          host: m[3] || "",                    // localhost:257
          hostname: m[4] || "",                // localhost
          href: m[0] || "",                    // http://localhost:257/deploy/?asd=asd#asd
          origin: m[1] || "",                  // http://localhost:257
          pathname: m[6] || (m[1] ? "/" : ""), // /deploy/
          port: m[5] || "",                    // 257
          protocol: m[2] || "",                // http:
          search: m[7] || ""                   // ?asd=asd
      };
  if (r.protocol.length == 2) {
      r.protocol = "file:///" + r.protocol.toUpperCase();
      r.origin = r.protocol + "//" + r.host;
  }
  r.href = r.origin + r.pathname + r.search + r.hash;
  return m && r;
};

const localhostURLParse = parseUrl(localhostURL);
const localhostURLPathname = localhostURLParse.pathname;

/*----------------------------------------------------------------------
  You should not have to edit anything below this line
----------------------------------------------------------------------*/
const gulp         = require('gulp')
const browserSync  = require('browser-sync').create()
const autoprefixer = require('autoprefixer')
const concat       = require('gulp-concat')
const jshint       = require('gulp-jshint')
const notify       = require('gulp-notify')
const postcss      = require('gulp-postcss')
const sass         = require('gulp-sass')(require('sass'))
const sourcemaps   = require('gulp-sourcemaps')
const uglify       = require('gulp-uglify')
const watch        = require('gulp-watch')

const builtInJavascript = [
  './node_modules/magnific-popup/dist/jquery.magnific-popup.js',
  './node_modules/slick-carousel/slick/slick.js',
  'wp-content/themes/omythic-child/src/js/optimized-events.js',
  'wp-content/themes/omythic-child/src/js/framework.js',
  'wp-content/themes/omythic-child/src/js/theme.js'
]

const logError = ( error ) => {
  console.log(error.toString())
  this.emit('end')
}

gulp.task('font-awesome', () => {
  return gulp.src( './wp-content/themes/omythic-framework/includes/fontawesome-pro/web-fonts-with-css/webfonts/**.*' )
    .pipe( gulp.dest( 'wp-content/themes/omythic-child/fonts' ) )
})

gulp.task('lint', () => {
  return gulp.src('wp-content/themes/omythic-child/src/js/**')
    .pipe(jshint())
    .pipe(notify((file) => {
    if (file.jshint.success) {
      return false;
    }
    const errors = file.jshint.results.map(( data ) => {
      if (data.error) {
        return `Line ${data.error.line}: ${data.error.reason}`
      }
    }).join('\n')
    return '\n-----------------\n' + file.relative + ' (' + file.jshint.results.length + ' errors)\n-----------------\n' + errors + '\n';
  }))
})

gulp.task('scripts', gulp.series('lint', () => {
  return gulp.src([...builtInJavascript, ...scripts])
  .pipe(sourcemaps.init())
  .pipe(uglify().on('error', notify.onError('Error: <%= error.message %>')))
  .pipe(concat('wp-content/themes/omythic-child/scripts.min.js').on('error', notify.onError('Error: <%= error.message %>')))
  .pipe(sourcemaps.write('./'))
  .pipe(gulp.dest('./'))
}))

gulp.task('scripts-watch',  gulp.series('scripts', (done) => {
  browserSync.reload()
  done()
}))

gulp.task('sass', () => {
  return gulp.src('wp-content/themes/omythic-child/src/scss/style.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', notify.onError('Error: <%= error.message %>')))
    .pipe(postcss([autoprefixer({
      browsers: ['last 2 versions'],
      cascade: false,
      grid: true
    })]))
    .pipe(sourcemaps.write('./', {
      includeContent: false,
      sourceRoot: './wp-content/themes/omythic-child/src/scss'
    }))
    .pipe(gulp.dest('./wp-content/themes/omythic-child/'))
    .pipe(browserSync.stream())
})

gulp.task('serve',  gulp.series('font-awesome', 'sass', 'scripts', (done) => {
  browserSync.init({
    proxy: localhostURL,
    snippetOptions: {
      ignorePaths: [localhostURLPathname + '/wp-admin/**']
    },
  })
  gulp.watch('./wp-content/themes/omythic-child/src/scss/**',  gulp.series('sass'))
  gulp.watch('./wp-content/themes/omythic-child/src/js/**',  gulp.series('scripts-watch'))
  gulp.watch([
    'wp-content/themes/omythic-child/.gulpwatch',
    'wp-content/themes/omythic-child/*.php',
    'wp-content/themes/omythic-child/**/*.php'
  ]).on('change', browserSync.reload)
  done()
}))

gulp.task('default', gulp.series('serve'))