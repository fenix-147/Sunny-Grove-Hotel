/*
 * Sunny Grove – tạo mã QR ngay trong trình duyệt (không gọi dịch vụ bên ngoài).
 * Hỗ trợ chế độ byte (UTF-8), mức sửa lỗi M, phiên bản 1–10 (đủ cho đường dẫn otpauth:// của xác thực 2 lớp).
 * Thuật toán theo chuẩn ISO/IEC 18004: Reed–Solomon trên GF(256), xếp mô-đun, chọn mặt nạ có điểm phạt thấp nhất.
 *
 * Dùng: window.sgQr.svg( 'otpauth://…', 4 ) → chuỗi SVG.
 */
( function () {
	'use strict';

	// Mức M, phiên bản 1–10: [ số mã sửa lỗi mỗi khối, [ số khối nhóm 1, dữ liệu mỗi khối ], [ nhóm 2 ] ].
	var BLOCKS = [
		null,
		[ 10, [ 1, 16 ] ], [ 16, [ 1, 28 ] ], [ 26, [ 1, 44 ] ], [ 18, [ 2, 32 ] ], [ 24, [ 2, 43 ] ],
		[ 16, [ 4, 27 ] ], [ 18, [ 4, 31 ] ], [ 22, [ 2, 38 ], [ 2, 39 ] ], [ 22, [ 3, 36 ], [ 2, 37 ] ], [ 26, [ 4, 43 ], [ 1, 44 ] ]
	];
	var ALIGN = [ null, [], [ 6, 18 ], [ 6, 22 ], [ 6, 26 ], [ 6, 30 ], [ 6, 34 ], [ 6, 22, 38 ], [ 6, 24, 42 ], [ 6, 26, 46 ], [ 6, 28, 50 ] ];

	/* ---------- GF(256) & Reed–Solomon ---------- */
	var EXP = new Array( 512 );
	var LOG = new Array( 256 );
	( function () {
		var x = 1;
		for ( var i = 0; i < 255; i++ ) {
			EXP[ i ] = x;
			LOG[ x ] = i;
			x <<= 1;
			if ( x & 0x100 ) {
				x ^= 0x11d;
			}
		}
		for ( var j = 255; j < 512; j++ ) {
			EXP[ j ] = EXP[ j - 255 ];
		}
	}() );
	function mul( a, b ) {
		return a === 0 || b === 0 ? 0 : EXP[ LOG[ a ] + LOG[ b ] ];
	}
	function generator( degree ) {
		var poly = [ 1 ];
		for ( var i = 0; i < degree; i++ ) {
			var next = new Array( poly.length + 1 ).fill( 0 );
			for ( var j = 0; j < poly.length; j++ ) {
				next[ j ] ^= poly[ j ];
				next[ j + 1 ] ^= mul( poly[ j ], EXP[ i ] );
			}
			poly = next;
		}
		return poly;
	}
	function remainder( data, degree ) {
		var gen = generator( degree );
		var res = data.concat( new Array( degree ).fill( 0 ) );
		for ( var i = 0; i < data.length; i++ ) {
			var coef = res[ i ];
			if ( coef !== 0 ) {
				for ( var j = 0; j < gen.length; j++ ) {
					res[ i + j ] ^= mul( gen[ j ], coef );
				}
			}
		}
		return res.slice( data.length );
	}

	/* ---------- Mã hóa dữ liệu ---------- */
	function dataCapacity( v ) {
		var b = BLOCKS[ v ];
		return b[ 1 ][ 0 ] * b[ 1 ][ 1 ] + ( b[ 2 ] ? b[ 2 ][ 0 ] * b[ 2 ][ 1 ] : 0 );
	}
	function encodeData( bytes, v ) {
		var bits = [];
		function put( value, len ) {
			for ( var i = len - 1; i >= 0; i-- ) {
				bits.push( ( value >>> i ) & 1 );
			}
		}
		put( 4, 4 ); // Chế độ byte.
		put( bytes.length, v < 10 ? 8 : 16 );
		bytes.forEach( function ( b ) {
			put( b, 8 );
		} );
		var capBits = dataCapacity( v ) * 8;
		put( 0, Math.min( 4, capBits - bits.length ) );
		while ( bits.length % 8 ) {
			bits.push( 0 );
		}
		var out = [];
		for ( var i = 0; i < bits.length; i += 8 ) {
			var byte = 0;
			for ( var k = 0; k < 8; k++ ) {
				byte = ( byte << 1 ) | bits[ i + k ];
			}
			out.push( byte );
		}
		for ( var pad = 0; out.length < dataCapacity( v ); pad++ ) {
			out.push( pad % 2 ? 0x11 : 0xec );
		}
		return out;
	}
	function interleave( data, v ) {
		var spec = BLOCKS[ v ];
		var ecLen = spec[ 0 ];
		var blocks = [];
		var pos = 0;
		[ spec[ 1 ], spec[ 2 ] ].forEach( function ( group ) {
			if ( ! group ) {
				return;
			}
			for ( var i = 0; i < group[ 0 ]; i++ ) {
				var d = data.slice( pos, pos + group[ 1 ] );
				pos += group[ 1 ];
				blocks.push( { data: d, ec: remainder( d, ecLen ) } );
			}
		} );
		var out = [];
		var maxData = Math.max.apply( null, blocks.map( function ( b ) {
			return b.data.length;
		} ) );
		for ( var i = 0; i < maxData; i++ ) {
			blocks.forEach( function ( b ) {
				if ( i < b.data.length ) {
					out.push( b.data[ i ] );
				}
			} );
		}
		for ( var j = 0; j < ecLen; j++ ) {
			blocks.forEach( function ( b ) {
				out.push( b.ec[ j ] );
			} );
		}
		return out;
	}

	/* ---------- Xếp mô-đun ---------- */
	function Matrix( v ) {
		this.size = v * 4 + 17;
		this.v = v;
		this.m = [];
		this.fn = [];
		for ( var i = 0; i < this.size; i++ ) {
			this.m.push( new Array( this.size ).fill( false ) );
			this.fn.push( new Array( this.size ).fill( false ) );
		}
	}
	Matrix.prototype.set = function ( x, y, dark ) {
		this.m[ y ][ x ] = dark;
		this.fn[ y ][ x ] = true;
	};
	Matrix.prototype.functionPatterns = function () {
		var s = this.size;
		var self = this;
		for ( var i = 0; i < s; i++ ) {
			this.set( 6, i, i % 2 === 0 );
			this.set( i, 6, i % 2 === 0 );
		}
		function finder( cx, cy ) {
			for ( var dy = -4; dy <= 4; dy++ ) {
				for ( var dx = -4; dx <= 4; dx++ ) {
					var x = cx + dx;
					var y = cy + dy;
					if ( x >= 0 && x < s && y >= 0 && y < s ) {
						var d = Math.max( Math.abs( dx ), Math.abs( dy ) );
						self.set( x, y, d !== 2 && d !== 4 );
					}
				}
			}
		}
		finder( 3, 3 );
		finder( s - 4, 3 );
		finder( 3, s - 4 );
		var al = ALIGN[ this.v ];
		for ( var a = 0; a < al.length; a++ ) {
			for ( var b = 0; b < al.length; b++ ) {
				if ( ( a === 0 && b === 0 ) || ( a === 0 && b === al.length - 1 ) || ( a === al.length - 1 && b === 0 ) ) {
					continue;
				}
				for ( var dy2 = -2; dy2 <= 2; dy2++ ) {
					for ( var dx2 = -2; dx2 <= 2; dx2++ ) {
						this.set( al[ a ] + dx2, al[ b ] + dy2, Math.max( Math.abs( dx2 ), Math.abs( dy2 ) ) !== 1 );
					}
				}
			}
		}
		this.format( 0 ); // Giữ chỗ.
		if ( this.v >= 7 ) {
			var rem = this.v;
			for ( var k = 0; k < 12; k++ ) {
				rem = ( rem << 1 ) ^ ( ( rem >>> 11 ) * 0x1f25 );
			}
			var bits = ( this.v << 12 ) | rem;
			for ( var n = 0; n < 18; n++ ) {
				var bit = ( ( bits >>> n ) & 1 ) === 1;
				var p = s - 11 + ( n % 3 );
				var q = Math.floor( n / 3 );
				this.set( p, q, bit );
				this.set( q, p, bit );
			}
		}
	};
	Matrix.prototype.format = function ( mask ) {
		var data = ( 0 << 3 ) | mask; // Mức M = 00.
		var rem = data;
		for ( var i = 0; i < 10; i++ ) {
			rem = ( rem << 1 ) ^ ( ( rem >>> 9 ) * 0x537 );
		}
		var bits = ( ( data << 10 ) | rem ) ^ 0x5412;
		var s = this.size;
		var bit = function ( n ) {
			return ( ( bits >>> n ) & 1 ) === 1;
		};
		for ( var a = 0; a <= 5; a++ ) {
			this.set( 8, a, bit( a ) );
		}
		this.set( 8, 7, bit( 6 ) );
		this.set( 8, 8, bit( 7 ) );
		this.set( 7, 8, bit( 8 ) );
		for ( var b = 9; b < 15; b++ ) {
			this.set( 14 - b, 8, bit( b ) );
		}
		for ( var c = 0; c < 8; c++ ) {
			this.set( s - 1 - c, 8, bit( c ) );
		}
		for ( var d = 8; d < 15; d++ ) {
			this.set( 8, s - 15 + d, bit( d ) );
		}
		this.set( 8, s - 8, true ); // Mô-đun tối cố định.
	};
	Matrix.prototype.place = function ( codewords ) {
		var s = this.size;
		var i = 0;
		var total = codewords.length * 8;
		for ( var right = s - 1; right >= 1; right -= 2 ) {
			if ( right === 6 ) {
				right = 5;
			}
			for ( var vert = 0; vert < s; vert++ ) {
				for ( var j = 0; j < 2; j++ ) {
					var x = right - j;
					var upward = ( ( right + 1 ) & 2 ) === 0;
					var y = upward ? s - 1 - vert : vert;
					if ( ! this.fn[ y ][ x ] && i < total ) {
						this.m[ y ][ x ] = ( ( codewords[ i >>> 3 ] >>> ( 7 - ( i & 7 ) ) ) & 1 ) === 1;
						i++;
					}
				}
			}
		}
	};
	Matrix.prototype.applyMask = function ( mask ) {
		var s = this.size;
		for ( var y = 0; y < s; y++ ) {
			for ( var x = 0; x < s; x++ ) {
				if ( this.fn[ y ][ x ] ) {
					continue;
				}
				var invert;
				switch ( mask ) {
					case 0: invert = ( x + y ) % 2 === 0; break;
					case 1: invert = y % 2 === 0; break;
					case 2: invert = x % 3 === 0; break;
					case 3: invert = ( x + y ) % 3 === 0; break;
					case 4: invert = ( Math.floor( x / 3 ) + Math.floor( y / 2 ) ) % 2 === 0; break;
					case 5: invert = ( x * y ) % 2 + ( x * y ) % 3 === 0; break;
					case 6: invert = ( ( x * y ) % 2 + ( x * y ) % 3 ) % 2 === 0; break;
					default: invert = ( ( x + y ) % 2 + ( x * y ) % 3 ) % 2 === 0;
				}
				if ( invert ) {
					this.m[ y ][ x ] = ! this.m[ y ][ x ];
				}
			}
		}
	};
	Matrix.prototype.penalty = function () {
		var s = this.size;
		var m = this.m;
		var score = 0;
		var x;
		var y;
		// Quy tắc 1: chuỗi ≥ 5 mô-đun cùng màu theo hàng/cột.
		for ( var dir = 0; dir < 2; dir++ ) {
			for ( var a = 0; a < s; a++ ) {
				var run = 1;
				for ( var b = 1; b < s; b++ ) {
					var cur = dir ? m[ b ][ a ] : m[ a ][ b ];
					var prev = dir ? m[ b - 1 ][ a ] : m[ a ][ b - 1 ];
					if ( cur === prev ) {
						run++;
						if ( run === 5 ) {
							score += 3;
						} else if ( run > 5 ) {
							score++;
						}
					} else {
						run = 1;
					}
				}
			}
		}
		// Quy tắc 2: khối 2×2 cùng màu.
		for ( y = 0; y < s - 1; y++ ) {
			for ( x = 0; x < s - 1; x++ ) {
				var c = m[ y ][ x ];
				if ( c === m[ y ][ x + 1 ] && c === m[ y + 1 ][ x ] && c === m[ y + 1 ][ x + 1 ] ) {
					score += 3;
				}
			}
		}
		// Quy tắc 3: mẫu giống ô định vị (1:1:3:1:1) có 4 ô sáng bên cạnh.
		var patA = [ true, false, true, true, true, false, true, false, false, false, false ];
		var patB = patA.slice().reverse();
		for ( y = 0; y < s; y++ ) {
			for ( x = 0; x <= s - 11; x++ ) {
				var rowA = true;
				var rowB = true;
				var colA = true;
				var colB = true;
				for ( var k = 0; k < 11; k++ ) {
					rowA = rowA && m[ y ][ x + k ] === patA[ k ];
					rowB = rowB && m[ y ][ x + k ] === patB[ k ];
					colA = colA && m[ x + k ][ y ] === patA[ k ];
					colB = colB && m[ x + k ][ y ] === patB[ k ];
				}
				score += 40 * ( rowA + rowB + colA + colB );
			}
		}
		// Quy tắc 4: tỉ lệ mô-đun tối lệch khỏi 50%.
		var dark = 0;
		for ( y = 0; y < s; y++ ) {
			for ( x = 0; x < s; x++ ) {
				dark += m[ y ][ x ] ? 1 : 0;
			}
		}
		score += Math.floor( Math.abs( dark * 20 - s * s * 10 ) / ( s * s ) ) * 10;
		return score;
	};

	function encode( text ) {
		var bytes = Array.from( new TextEncoder().encode( text ) );
		var v = 1;
		while ( v <= 10 && 4 + ( v < 10 ? 8 : 16 ) + bytes.length * 8 > dataCapacity( v ) * 8 ) {
			v++;
		}
		if ( v > 10 ) {
			throw new Error( 'QR: dữ liệu quá dài' );
		}
		var codewords = interleave( encodeData( bytes, v ), v );
		var best = null;
		var bestScore = Infinity;
		for ( var mask = 0; mask < 8; mask++ ) {
			var mx = new Matrix( v );
			mx.functionPatterns();
			mx.place( codewords );
			mx.applyMask( mask );
			mx.format( mask );
			var sc = mx.penalty();
			if ( sc < bestScore ) {
				bestScore = sc;
				best = mx;
			}
		}
		return best;
	}

	function svg( text, scale ) {
		var mx = encode( text );
		var quiet = 4;
		var n = mx.size + quiet * 2;
		var path = '';
		for ( var y = 0; y < mx.size; y++ ) {
			for ( var x = 0; x < mx.size; x++ ) {
				if ( mx.m[ y ][ x ] ) {
					path += 'M' + ( x + quiet ) + ',' + ( y + quiet ) + 'h1v1h-1z';
				}
			}
		}
		var px = n * ( scale || 4 );
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + n + ' ' + n + '" width="' + px + '" height="' + px + '" shape-rendering="crispEdges" role="img"><rect width="100%" height="100%" fill="#fff"/><path d="' + path + '" fill="#000"/></svg>';
	}

	window.sgQr = { svg: svg, encode: encode };
}() );
