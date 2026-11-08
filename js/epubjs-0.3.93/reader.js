/* In-page epub reader around epub.js: reader.html?file=<epub url>&search=<term>. Same-origin file only. */
( function() {
	// the query string read by hand: an older browser (a TV's) has no URLSearchParams
	var query = {};
	location.search.replace( /^\?/, '' ).split( '&' ).forEach( function( pair ) {
		var i = pair.indexOf( '=' );
		if( pair ) { query[ decodeURIComponent( ( i < 0 ? pair : pair.slice( 0, i ) ).replace( /\+/g, ' ' ) ) ] = i < 0 ? '' : decodeURIComponent( pair.slice( i + 1 ).replace( /\+/g, ' ' ) ); }
	} );
	var file = query.file || '';
	var term = query.search || '';
	var $ = function( id ) { return document.getElementById( id ); };
	// a book on this site only: a relative address, or one that starts with this site's own origin
	if( !file || ( /^[a-z][a-z0-9+.-]*:/i.test( file ) && file.indexOf( location.origin ) !== 0 ) ) {
		$( 'viewer' ).innerHTML = '<p id="msg">No book.</p>';
		return;
	}
	var book = ePub( file, { openAs: 'epub' } );
	var rendition = book.renderTo( 'viewer', { width: '100%', height: '100%', flow: 'paginated', spread: 'none' } );
	var size = 100, marked = null;

	book.ready.then( function() { return rendition.display(); } ).catch( function() {
		$( 'viewer' ).innerHTML = '<p id="msg">This book could not be opened.</p>';
	} );
	book.loaded.metadata.then( function( meta ) { if( meta && meta.title ) { document.title = meta.title; } } );
	book.loaded.navigation.then( function( nav ) {
		var sel = $( 'toc' );
		( function add( items, depth ) {
			items.forEach( function( it ) {
				var o = document.createElement( 'option' );
				o.value = it.href; o.textContent = new Array( depth + 1 ).join( '  ' ) + ( it.label || '' ).trim();
				sel.appendChild( o );
				if( it.subitems && it.subitems.length ) { add( it.subitems, depth + 1 ); }
			} );
		} )( nav.toc, 0 );
	} );
	$( 'toc' ).addEventListener( 'change', function() { if( this.value ) { rendition.display( this.value ); } this.selectedIndex = 0; } );
	$( 'prev' ).addEventListener( 'click', function() { rendition.prev(); } );
	$( 'next' ).addEventListener( 'click', function() { rendition.next(); } );
	$( 'smaller' ).addEventListener( 'click', function() { size = Math.max( 60, size - 10 ); rendition.themes.fontSize( size + '%' ); } );
	$( 'larger' ).addEventListener( 'click', function() { size = Math.min( 220, size + 10 ); rendition.themes.fontSize( size + '%' ); } );
	// Page turning from the keyboard. A chapter is drawn in an iframe of its own and replaced at every chapter change, which takes the keyboard focus with it:
	// so the keys are heard on this page and on every chapter as it is made (keydown, so nothing is lost between a press and its release), and the focus
	// is taken back to this page after each move and whenever the pointer is over the reader.
	var lastKey = 0;
	function key( e ) {
		if( e.altKey || e.ctrlKey || e.metaKey || ( e.target && /^(input|select|textarea)$/i.test( e.target.tagName || '' ) ) ) { return; }
		// e.key where there is one, the key code where there is not (a TV remote's arrows send 39 and 37)
		var k = e.key || ( { 39: 'ArrowRight', 37: 'ArrowLeft', 34: 'PageDown', 33: 'PageUp' } )[ e.keyCode ];
		var dir = k === 'ArrowRight' || k === 'PageDown' || k === 'Right' ? 1 : ( k === 'ArrowLeft' || k === 'PageUp' || k === 'Left' ? -1 : 0 );
		if( !dir || e.timeStamp === lastKey ) { return; }
		lastKey = e.timeStamp;   // the same press can arrive from the chapter and from this page
		e.preventDefault();
		if( dir > 0 ) { rendition.next(); } else { rendition.prev(); }
	}
	function focusReader() { try { window.focus(); } catch( e ) {} }
	document.addEventListener( 'keydown', key );
	document.documentElement.addEventListener( 'mouseenter', focusReader );
	rendition.hooks.content.register( function( contents ) {
		contents.document.addEventListener( 'keydown', key );
		contents.document.addEventListener( 'mouseenter', focusReader );
	} );
	rendition.on( 'relocated', focusReader );
	rendition.on( 'rendered', focusReader );

	function show( cfi ) {
		if( marked ) { try { rendition.annotations.remove( marked, 'highlight' ); } catch( e ) {} }
		marked = cfi;
		rendition.display( cfi ).then( function() { rendition.annotations.highlight( cfi, {}, null, 'hl', { fill: 'yellow', 'fill-opacity': '0.4' } ); } );
	}
	function search( q ) {
		q = ( q || '' ).trim();
		if( !q ) { return; }
		var hits = $( 'hits' ), count = $( 'count' ), found = [];
		hits.style.display = 'block'; hits.textContent = 'Searching...'; count.textContent = '';
		var jobs = [];
		book.spine.each( function( item ) {
			jobs.push( item.load( book.load.bind( book ) ).then( function() {
				( item.find( q ) || [] ).forEach( function( r ) { found.push( r ); } );
				item.unload();
			} ).catch( function() {} ) );
		} );
		Promise.all( jobs ).then( function() {
			hits.textContent = '';
			count.textContent = found.length + ' found';
			if( !found.length ) { hits.textContent = 'Nothing found.'; return; }
			found.slice( 0, 300 ).forEach( function( r ) {
				var a = document.createElement( 'a' );
				a.textContent = r.excerpt; a.addEventListener( 'click', function() { show( r.cfi ); } );
				hits.appendChild( a );
			} );
			show( found[0].cfi );
		} );
	}
	$( 'go' ).addEventListener( 'click', function() { search( $( 'q' ).value ); } );
	$( 'q' ).addEventListener( 'keydown', function( e ) { if( e.key === 'Enter' ) { search( this.value ); } } );
	if( term ) { $( 'q' ).value = term; book.ready.then( function() { search( term ); } ); }
} )();
