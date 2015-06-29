/**
 *description:artTemplate
 *date:2014/04/04
 */

/*
    1.末尾添加help方法 cut 截取输出的字符串;
      {{cut content 7}}
    
    https://github.com/aui/artTemplate 
    如需自定义请参考上面的网址; 
*/


    !function(a) {
        "use strict";
        var e, f, g, h, d = function(a, b) {
            return d["string" == typeof b ? "compile": "render"].apply(d, arguments)
        };
        d.version = "2.0.2",
        d.openTag = "<%",
        d.closeTag = "%>",
        d.isEscape = !0,
        d.isCompress = !1,
        d.parser = null,
        d.render = function(a, b) {
            var c = d.get(a) || g({
                id: a,
                name: "Render Error",
                message: "No Template"
            });
            return c(b)
        },
        d.compile = function(a, b) {
            function l(c) {
                try {
                    return new j(c, a) + ""
                } catch(e) {
                    return f ? g(e)() : d.compile(a, b, !0)(c)
                }
            }
            var j, c = arguments,
            f = c[2],
            i = "anonymous";
            "string" != typeof b && (f = c[1], b = c[0], a = i);
            try {
                j = h(a, b, f)
            } catch(k) {
                return k.id = a || b,
                k.name = "Syntax Error",
                g(k)
            }
            return l.prototype = j.prototype,
            l.toString = function() {
                return j.toString()
            },
            a !== i && (e[a] = l),
            l
        },
        e = d.cache = {},
        f = d.helpers = {
            $include: d.render,
            $string: function(a, b) {
                return "string" != typeof a && (b = typeof a, "number" === b ? a += "": a = "function" === b ? f.$string(a()) : ""),
                a
            },
            $escape: function(a) {
                var b = {
                    "<": "&#60;",
                    ">": "&#62;",
                    '"': "&#34;",
                    "'": "&#39;",
                    "&": "&#38;"
                };
                return f.$string(a).replace(/&(?![\w#]+;)|[<>"']/g,
                function(a) {
                    return b[a]
                })
            },
            $each: function(a, b) {
                var d, e, c = Array.isArray ||
                function(a) {
                    return "[object Array]" === {}.toString.call(a)
                };
                if (c(a)) for (d = 0, e = a.length; e > d; d++) b.call(a, a[d], d, a);
                else for (d in a) b.call(a, a[d], d)
            }
        },
        d.helper = function(a, b) {
            f[a] = b
        },
        d.onerror = function(b) {
            var d, c = "Template Error\n\n";
            for (d in b) c += "<" + d + ">\n" + b[d] + "\n\n";
            a.console && console.error(c)
        },
        d.get = function(b) {
            var c, f, g;
            return e.hasOwnProperty(b) ? c = e[b] : "document" in a && (f = document.getElementById(b), f && (g = f.value || f.innerHTML, c = d.compile(b, g.replace(/^\s*|\s*$/g, "")))),
            c
        },
        g = function(a) {
            return d.onerror(a),
            function() {
                return "{Template Error}"
            }
        },
        h = function() {
            var a = f.$each,
            b = "break,case,catch,continue,debugger,default,delete,do,else,false,finally,for,function,if,in,instanceof,new,null,return,switch,this,throw,true,try,typeof,var,void,while,with,abstract,boolean,byte,char,class,const,double,enum,export,extends,final,float,goto,implements,import,int,interface,long,native,package,private,protected,public,short,static,super,synchronized,throws,transient,volatile,arguments,let,yield,undefined",
            c = /\/\*[\w\W]*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|"(?:[^"\\]|\\[\w\W])*"|'(?:[^'\\]|\\[\w\W])*'|[\s\t\n]*\.[\s\t\n]*[$\w\.]+/g,
            e = /[^\w$]+/g,
            g = new RegExp(["\\b" + b.replace(/,/g, "\\b|\\b") + "\\b"].join("|"), "g"),
            h = /^\d[^,]*|,\d[^,]*/g,
            i = /^,+|,+$/g,
            j = function(a) {
                return a.replace(c, "").replace(e, ",").replace(g, "").replace(h, "").replace(i, "").split(/^$|,+/)
            };
            return function(b, c, e) {
                function x(a) {
                    return m += a.split(/\n/).length - 1,
                    d.isCompress && (a = a.replace(/[\n\r\t\s]+/g, " ").replace(/<!--.*?-->/g, "")),
                    a && (a = r[1] + B(a) + r[2] + "\n"),
                    a
                }
                function y(a) {
                    var c, g, b = m;
                    return i ? a = i(a) : e && (a = a.replace(/\n/g,
                    function() {
                        return m++,
                        "$line=" + m + ";"
                    })),
                    0 === a.indexOf("=") && (c = 0 !== a.indexOf("=="), a = a.replace(/^=*|[\s;]*$/g, ""), c && d.isEscape ? (g = a.replace(/\s*\([^\)]+\)/, ""), f.hasOwnProperty(g) || /^(include|print)$/.test(g) || (a = "$escape(" + a + ")")) : a = "$string(" + a + ")", a = r[1] + a + r[2]),
                    e && (a = "$line=" + b + ";" + a),
                    z(a),
                    a + "\n"
                }
                function z(b) {
                    b = j(b),
                    a(b,
                    function(a) {
                        n.hasOwnProperty(a) || (A(a), n[a] = !0)
                    })
                }
                function A(a) {
                    var b;
                    "print" === a ? b = t: "include" === a ? (o.$include = f.$include, b = u) : (b = "$data." + a, f.hasOwnProperty(a) && (o[a] = f[a], b = 0 === a.indexOf("$") ? "$helpers." + a: b + "===undefined?$helpers." + a + ":" + b)),
                    p += a + "=" + b + ","
                }
                function B(a) {
                    return "'" + a.replace(/('|\\)/g, "\\$1").replace(/\r/g, "\\r").replace(/\n/g, "\\n") + "'"
                }
                var v, g = d.openTag,
                h = d.closeTag,
                i = d.parser,
                k = c,
                l = "",
                m = 1,
                n = {
                    $data: 1,
                    $id: 1,
                    $helpers: 1,
                    $out: 1,
                    $line: 1
                },
                o = {},
                p = "var $helpers=this," + (e ? "$line=0,": ""),
                q = "".trim,
                r = q ? ["$out='';", "$out+=", ";", "$out"] : ["$out=[];", "$out.push(", ");", "$out.join('')"],
                s = q ? "if(content!==undefined){$out+=content;return content;}": "$out.push(content);",
                t = "function(content){" + s + "}",
                u = "function(id,data){data=data||$data;var content=$helpers.$include(id,data,$id);" + s + "}";
                a(k.split(g),
                function(a) {
                    var c, d;
                    a = a.split(h),
                    c = a[0],
                    d = a[1],
                    1 === a.length ? l += x(c) : (l += y(c), d && (l += x(d)))
                }),
                k = l,
                e && (k = "try{" + k + "}catch(e){" + "throw {" + "id:$id," + "name:'Render Error'," + "message:e.message," + "line:$line," + "source:" + B(c) + ".split(/\\n/)[$line-1].replace(/^[\\s\\t]+/,'')" + "};" + "}"),
                k = p + r[0] + k + "return new String(" + r[3] + ");";
                try {
                    return v = new Function("$data", "$id", k),
                    v.prototype = o,
                    v
                } catch(w) {
                    throw w.temp = "function anonymous($data,$id) {" + k + "}",
                    w
                }
            }
        } (),
        "function" == typeof define ? define(function() {
            return d
        }) : "undefined" != typeof b && (c.exports = d),
        a.template = d;
    } (this),
    function(a) {
        a.openTag = "{{",
        a.closeTag = "}}",
        a.parser = function(b) {
            var c, d, e, f, g, h, i, j;
            switch (b = b.replace(/^\s/, ""), c = b.split(" "), d = c.shift(), e = c.join(" "), d) {
            case "if":
                b = "if(" + e + "){";
                break;
            case "else":
                c = "if" === c.shift() ? " if(" + c.join(" ") + ")": "",
                b = "}else" + c + "{";
                break;
            case "/if":
                b = "}";
                break;
            case "each":
                f = c[0] || "$data",
                g = c[1] || "as",
                h = c[2] || "$value",
                i = c[3] || "$index",
                j = h + "," + i,
                "as" !== g && (f = "[]"),
                b = "$each(" + f + ",function(" + j + "){";
                break;
            case "/each":
                b = "});";
                break;
            case "echo":
                b = "print(" + e + ");";
                break;
            case "include":
                b = "include(" + c.join(",") + ");";
                break;
            default:
                a.helpers.hasOwnProperty(d) ? b = "==" + d + "(" + c.join(",") + ");": (b = b.replace(/[\s;]*$/, ""), b = "=" + b)
            }
            return b
        }
    } (template);

    //help方法
    template.helper('cut', function(content, num, isdot){

        var len;

        if( typeof content !== 'string' ) {
            return content;
        } else {
            
            len = content.length;

            if( len <= num ) {

                return content;

            } else {

                if(!isdot){
                    return content.substring(0, num) + '...';
                }else{
                     return content.substring(0, num);
                }
               

            }

        }

    });

    template.helper('compare',function(statrs, ends){

        var statrs = parseInt(statrs,10);

        var ends = parseInt(ends,10);

        var values = statrs-ends;

        if( values > 0){

            return 1;

        }else if(values == 0 ){

            return 0;

        }else{

            return 2;

        }

    });


/** 
 * 对日期进行格式化， 
 * @param date 要格式化的日期 
 * @param format 进行格式化的模式字符串
 *     支持的模式字母有： 
 *     y:年, 
 *     M:年中的月份(1-12), 
 *     d:月份中的天(1-31), 
 *     h:小时(0-23), 
 *     m:分(0-59), 
 *     s:秒(0-59), 
 *     S:毫秒(0-999),
 *     q:季度(1-4)
 * @return String
 * @author yanis.wang
 * @see http://yaniswang.com/frontend/2013/02/16/dateformat-performance/
 */
template.helper('dateFormat', function (date, format) {

    date = new Date(date);

    var map = {
        "M": date.getMonth() + 1, //月份 
        "d": date.getDate(), //日 
        "h": date.getHours(), //小时 
        "m": date.getMinutes(), //分 
        "s": date.getSeconds(), //秒 
        "q": Math.floor((date.getMonth() + 3) / 3), //季度 
        "S": date.getMilliseconds() //毫秒 
    };
    format = format.replace(/([yMdhmsqS])+/g, function(all, t){
        var v = map[t];
        if(v !== undefined){
            if(all.length > 1){
                v = '0' + v;
                v = v.substr(v.length-2);
            }
            return v;
        }
        else if(t === 'y'){
            return (date.getFullYear() + '').substr(4 - all.length);
        }
        return all;
    });
    return format;
});

// --------

/*var data = {
    time: (new Date).toString(),
};
var html = template('test', data);
document.getElementById('content').innerHTML = html;*/
