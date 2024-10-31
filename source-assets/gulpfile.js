var gulp                = require( 'gulp' );
var sass                = require( 'gulp-sass' );
var livereload          = require( 'gulp-livereload' );
var cssnano             = require( 'cssnano' );
var sourcemaps          = require( 'gulp-sourcemaps' );
var postcss             = require( 'gulp-postcss' );
var autoprefixer        = require( 'autoprefixer' );
//var concat              = require( 'gulp-concat' );
var minify              = require( 'gulp-minify' );
var del                 = require( 'del' );
var rename              = require( 'gulp-rename' );
//var resolveDependencies = require( 'gulp-resolve-dependencies' );

/*sass*/
function MyWPGlossaryMinCss() {
    return (
        gulp
            .src( './scss/**/*.scss' )
            .pipe( sourcemaps.init() )
            .pipe( sass() )
            .on( "error", sass.logError )
            .pipe( postcss([ autoprefixer(), cssnano() ] ) )
            .pipe( sourcemaps.write('.') )
            .pipe( gulp.dest( "../css" ) )
    );
}

function MyWPGlossaryRenameStyle() {
    return (
        gulp
            .src( '../css/style.css' )
            .pipe( rename("style.min.css" ) )
            .pipe( gulp.dest( "../css" ) )
    )
}

function MyWPGlossaryReloadStyle() {
    return (
        gulp
            .src( '../css/style.min.css' )
            .pipe( livereload({start:true} ) )
    )
}

function MyWPGlossaryDelCss() {
    return del(['../css/style.css'], {force:true})
}

function MyWPGlossaryMinJs() {
    let src = [
        'js/mywpglossary_block.js',
        'js/mywpglossary_modal.js',
        'js/mywpglossary_glossary.js',
        'js/mywpglossary_list_indexation.js'
    ];
    return (
        gulp
            .src( src )
            .pipe( minify({
                ext: {
                    min:'.min.js'
                },
                noSource: true,
                ignoreFiles: [ '.min.js' ]
            } ) )
            .pipe( gulp.dest( '../js' ) )
            .pipe( livereload( { start:true } ) )
    )
}

function watch(){
    livereload.listen();
    gulp.watch('scss/**/*.scss',gulp.series(
        MyWPGlossaryMinCss,
        MyWPGlossaryRenameStyle,
        MyWPGlossaryDelCss,
        MyWPGlossaryReloadStyle
    ));
    gulp.watch('js/*.js', gulp.series(
        MyWPGlossaryMinJs,
    ));
}

exports.MyWPGlossaryMinCssTask = gulp.series(
    MyWPGlossaryMinCss,
    MyWPGlossaryRenameStyle,
    MyWPGlossaryDelCss,
    MyWPGlossaryReloadStyle
);

exports.MyWPGlossaryMinJsTask = gulp.series(
    MyWPGlossaryMinJs
);

exports.default = watch;
