#!/usr/bin/env python2.7
# -*- coding: utf-8 -*-

###################################################
##  Evgeny Muravjev Typograph, http://mdash.ru   ##
##  Version: 3.5-py                              ##
##  Release Date: Jyly 2, 2015                   ##
##  Authors: Evgeny Muravjev & Alexander Drutsa  ## 
###################################################

import sys
import re
import base64
import types

LAYOUT_STYLE = 1
LAYOUT_CLASS = 2
INTERNAL_BLOCK_OPEN = u'%%%INTBLOCKO235978%%%'
INTERNAL_BLOCK_CLOSE = u'%%%INTBLOCKC235978%%%'

#static (TO BE DONE: protected)
_typographSpecificTagId = False

    
class _EMT_Lib:

    _charsTable = {
        '"'     : {'html' : {'&laquo;', '&raquo;', '&rdquo;', '&lsquo;', '&bdquo;', '&ldquo;', '&quot;', '&#171;', '&#187;'},
                          'utf8' : {0x201E, 0x201C, 0x201F, 0x201D, 0x00AB, 0x00BB}},
        ' '     : {'html' : {'&nbsp;', '&thinsp;', '&#160;'},
                          'utf8' : {0x00A0, 0x2002, 0x2003, 0x2008, 0x2009}},
        '-'     : {'html' : { '&ndash;', '&minus;', '&#151;', '&#8212;', '&#8211;'}, #'&mdash;',
                          'utf8' : {0x002D, 0x2010, 0x2012, 0x2013}}, #0x2014,
        u'—'     : {'html' : {'&mdash;'},
                          'utf8' : {0x2014}},
        '=='     : {'html' : {'&equiv;'},
                         'utf8' : {0x2261}},
        '...'     : {'html' : {'&hellip;', '&#0133;'},
                         'utf8' : {0x2026}},
        '!='     : {'html' : {'&ne;', '&#8800;'},
                         'utf8' : {0x2260}},
        '<='     : {'html' : {'&le;', '&#8804;'},
                         'utf8' : {0x2264}},
        '>='     : {'html' : {'&ge;', '&#8805;'},
                         'utf8' : {0x2265}},
        '1/2'     : {'html' : {'&frac12;', '&#189;'},
                         'utf8' : {0x00BD}},
        '1/4'     : {'html' : {'&frac14;', '&#188;'},
                         'utf8' : {0x00BC}},
        '3/4'     : {'html' : {'&frac34;', '&#190;'},
                         'utf8' : {0x00BE}},
        '+-'     : {'html' : {'&plusmn;', '&#177;'},
                         'utf8' : {0x00B1}},
        '&'     : {'html' : {'&amp;', '&#38;'}},
        '(tm)'     : {'html' : {'&trade;', '&#153;'},
                         'utf8' : {0x2122}},
        #'(r)'     : {'html' : {'<sup>&reg;</sup>', '&reg;', '&#174;'}, 
        '(r)'     : {'html' : {'&reg;', '&#174;'}, 
                         'utf8' : {0x00AE}},
        '(c)'     : {'html' : {'&copy;', '&#169;'}, 
                         'utf8' : {0x00A9}},
        u'§'     : {'html' : {'&sect;', '&#167;'}, 
                         'utf8' : {0x00A7}},
        u'`'     : {'html' : {'&#769;'}},
        '\''     : {'html' : {'&rsquo;', u'’'}},
        u'x'     : {'html' : {'&times;', '&#215;'}, 
                         'utf8' : {u'×'} }, # ????? ?? ? ???? ????? ???? ????         
    }
    #Добавление к тегам атрибута 'id', благодаря которому
    #при повторном типографирование текста будут удалены теги,
    #Расставленные данным типографом
    
# Удаление кодов HTML из текста
#
# <code>
#  // Remove UTF-8 chars:
#     $str = EMT_Lib::clear_special_chars('your text', 'utf8');
#  // ... or HTML codes only:
#     $str = EMT_Lib::clear_special_chars('your text', 'html');
#     // ... or combo:
#  $str = EMT_Lib::clear_special_chars('your text');
# </code>
#
# @param     string $text
# @param   mixed $mode
# @return     string|bool
#/
#static public
    def clear_special_chars(self, text, mode = None):
        if isinstance(mode, basestring):
            mode = [mode]
            
        if mode == None:
            mode = ['utf8', 'html']
            
        if(not (isinstance(mode, (list, tuple)) and not isinstance(mode, basestring))):
            return False
        
        moder = []
        for mod in mode:
            if (mod in ['utf8','html']):
                moder.append(mod)
                
        if (len(moder)==0):
            return False
        
        for char in self._charsTable:
            vals = self._charsTable[char]
            for type in mode:
                if (type in vals): 
                    for v in vals[type]:
                        if ('utf8' == type and isinstance(v, int)): 
                            v = unichr(v)
                            
                        if ('html' == type): 
                            if(re.search(u"<[a-z]+>",v, re.I)): #OK
                                v = self.safe_tag_chars(v, True)
                                
                        text = text.replace(v, char) #OK    
        return text

# NOTUSED
# Удаление тегов HTML из текста
# Тег <br /> будет преобразов в перенос строки \n, сочетание тегов </p><p> -
# в двойной перенос
#
# @param     string $text
# @param     array $allowableTag массив из тегов, которые будут проигнорированы
# @return     string
#/
    def remove_html_tags(self, text, allowableTag = None):
        ignore = None
        
        if (None != allowableTag):
            if (isinstance(allowableTag, basestring)):
                allowableTag = [allowableTag]
    
            if (not (isinstance(allowableTag, (list, tuple)) and not isinstance(allowableTag, basestring))):
                tags = []
                for tag in allowableTag:
                    if '<' != tag[0:1] or  '>' != tag[-1]: #OK
                        continue
                    
                    if '/' == tag[1:1]: #OK
                        continue
                    
                    tags.append(tag)
                
                ignore = ''.join('', tags) #OK    
        text = re.sub('\<br\s*/?>', "\n", text, 0, re.I) #OK
        text = re.sub('\</p\>\s*\<p\>', "\n\n", text ) #OK
        #text = strip_tags(text, ignore) #TODO    
        return text

    
# Сохраняем содержимое тегов HTML
#
# Тег 'a' кодируется со специальным префиксом для дальнейшей
# возможности выносить за него кавычки.
# 
# @param     string $text
# @param     bool $safe
# @return  string
#/
    def safe_tag_chars(self, text, way):
        if (way):
             #OK:
            text = re.sub('(\</?)([^<>]+?)(\>)', lambda m: m.group(0) if (len(m.group(1))==1 and m.group(2).strip()[0:1] == '-' and m.group(2).strip()[1:2] != '-') else  (m.group(1)+(u"%%___" if m.group(2).strip()[0:1] == u'a' else u"") + EMT_Lib.encrypt_tag(m.group(2).strip()) + m.group(3)), text, 0, re.S |re.U)        
        else:
             #OK:
            text = re.sub('(\</?)([^<>]+?)(\>)', lambda m: m.group(0) if (len(m.group(1))==1 and m.group(2).strip()[0:1] == '-' and m.group(2).strip()[1:2] != '-') else  (m.group(1)+(EMT_Lib.decrypt_tag(m.group(2).strip()[4:]) if m.group(2).strip()[0:3] == u'%%___' else EMT_Lib.decrypt_tag(m.group(2).strip())) + m.group(3)), text, 0, re.S|re.U)        
        return text




# Декодриует спец блоки
#
# @param     string $text
# @return  string
#/
    def decode_internal_blocks(self, text):    
        text = re.sub(INTERNAL_BLOCK_OPEN+'([a-zA-Z0-9/=]+?)'+INTERNAL_BLOCK_CLOSE, lambda m: EMT_Lib.decrypt_tag(m.group(1)), text, 0, re.S)    
        return text


# Кодирует спец блок
#
# @param     string $text
# @return  string
#/
    def iblock(self, text):
        return INTERNAL_BLOCK_OPEN + EMT_Lib.encrypt_tag(text) + INTERNAL_BLOCK_CLOSE



# Создание тега с защищенным содержимым 
#
# @param     string $content текст, который будет обрамлен тегом
# @param     string $tag тэг 
# @param     array $attribute список атрибутов, где ключ - имя атрибута, а значение - само значение данного атрибута
# @return     string
#/
#static public
    def build_safe_tag(self, content, tag = u'span', attribute = {}, layout = LAYOUT_STYLE ): #TODO: attributr - list or dict ?? 
        htmlTag = tag
        
        if (_typographSpecificTagId):
            if(not 'id' in attribute):
                attribute['id'] = u'emt-2' + mt_rand(1000,9999) #TODO
        
        classname = ""
        if (len(attribute)): 
            if(layout & LAYOUT_STYLE):
                if('__style' in attribute and attribute['__style']):
                    if('style' in attribute and attribute['style']):
                        st = attribute['style'].strip() #TODO
                        if(st[-1] != ";"): #OK
                            st += ";"
    
                        st += attribute['__style']
                        attribute['style'] = st
                    else:
                        attribute['style'] = attribute['__style']
    
                    del attribute['__style']
    
            for attr in attribute:
                value = attribute[attr]
                if(attr == u"__style"):
                    continue
                
                if(attr == u"class"):
                    classname = str(value)
                    continue
                
                htmlTag += u" %s=\"%s\"" % (str(attr), str(value))
            
        
        if( (layout & LAYOUT_CLASS ) and classname):
            htmlTag += u" class=\"%s\"" % classname
        
        return u"<" + EMT_Lib.encrypt_tag(htmlTag) + u">" + content +u"</" + EMT_Lib.encrypt_tag(tag) + u">"


# Метод, осуществляющий кодирование (сохранение) информации
# с целью невозможности типографировать ее
#
# @param     string $text
# @return     string
#/
    def encrypt_tag(self, text):
        return unicode(base64.b64encode(text.encode('utf-8')))+'=' #TODO


# Метод, осуществляющий декодирование информации
#
# @param     string $text
# @return     string
#/
    def decrypt_tag(self, text):
        return unicode(base64.b64decode(text[:-1]).decode('utf-8')) #TODO


    def strpos_ex(self, haystack, needle, offset = None): #TODO: &$haystack - '&' couldn't work
        if((isinstance(needle, (list, tuple)) and not isinstance(needle, basestring)) ):
            m = -1
            w = -1
            for n in needle:
                p = haystack.find(n , offset) #TODO
                if(p==-1):
                    continue
                if(m == -1):
                    m = p
                    w = n
                    continue
                if(p < m):
                    m = p
                    w = n
    
            if (m ==-1):
                return False
            
            return {'pos' : m, 'str' : w}
        
        return haystack.find(needle, offset)    #TODO   


    def process_selector_pattern(self, pattern): #TODO: &$pattern - '&' couldn't work
        if(pattern==False):
            return False
        #pattern = preg_quote(pattern , '/') #TODO 
        pattern = pattern.replace("*", "[a-z0-9_\-]*") #TODO 
        return pattern

    def test_pattern(self, pattern, text):
        if(pattern == False or pattern == None):
            return True
    
        return re.match(pattern, text) #TODO 

    def strtolower(self, string):
        return string.lower()    

# взято с http://www.w3.org/TR/html4/sgml/entities.html
    html4_char_ents = {
        'nbsp' : 160,
        'iexcl' : 161,
        'cent' : 162,
        'pound' : 163,
        'curren' : 164,
        'yen' : 165,
        'brvbar' : 166,
        'sect' : 167,
        'uml' : 168,
        'copy' : 169,
        'ordf' : 170,
        'laquo' : 171,
        'not' : 172,
        'shy' : 173,
        'reg' : 174,
        'macr' : 175,
        'deg' : 176,
        'plusmn' : 177,
        'sup2' : 178,
        'sup3' : 179,
        'acute' : 180,
        'micro' : 181,
        'para' : 182,
        'middot' : 183,
        'cedil' : 184,
        'sup1' : 185,
        'ordm' : 186,
        'raquo' : 187,
        'frac14' : 188,
        'frac12' : 189,
        'frac34' : 190,
        'iquest' : 191,
        'Agrave' : 192,
        'Aacute' : 193,
        'Acirc' : 194,
        'Atilde' : 195,
        'Auml' : 196,
        'Aring' : 197,
        'AElig' : 198,
        'Ccedil' : 199,
        'Egrave' : 200,
        'Eacute' : 201,
        'Ecirc' : 202,
        'Euml' : 203,
        'Igrave' : 204,
        'Iacute' : 205,
        'Icirc' : 206,
        'Iuml' : 207,
        'ETH' : 208,
        'Ntilde' : 209,
        'Ograve' : 210,
        'Oacute' : 211,
        'Ocirc' : 212,
        'Otilde' : 213,
        'Ouml' : 214,
        'times' : 215,
        'Oslash' : 216,
        'Ugrave' : 217,
        'Uacute' : 218,
        'Ucirc' : 219,
        'Uuml' : 220,
        'Yacute' : 221,
        'THORN' : 222,
        'szlig' : 223,
        'agrave' : 224,
        'aacute' : 225,
        'acirc' : 226,
        'atilde' : 227,
        'auml' : 228,
        'aring' : 229,
        'aelig' : 230,
        'ccedil' : 231,
        'egrave' : 232,
        'eacute' : 233,
        'ecirc' : 234,
        'euml' : 235,
        'igrave' : 236,
        'iacute' : 237,
        'icirc' : 238,
        'iuml' : 239,
        'eth' : 240,
        'ntilde' : 241,
        'ograve' : 242,
        'oacute' : 243,
        'ocirc' : 244,
        'otilde' : 245,
        'ouml' : 246,
        'divide' : 247,
        'oslash' : 248,
        'ugrave' : 249,
        'uacute' : 250,
        'ucirc' : 251,
        'uuml' : 252,
        'yacute' : 253,
        'thorn' : 254,
        'yuml' : 255,
        'fnof' : 402,
        'Alpha' : 913,
        'Beta' : 914,
        'Gamma' : 915,
        'Delta' : 916,
        'Epsilon' : 917,
        'Zeta' : 918,
        'Eta' : 919,
        'Theta' : 920,
        'Iota' : 921,
        'Kappa' : 922,
        'Lambda' : 923,
        'Mu' : 924,
        'Nu' : 925,
        'Xi' : 926,
        'Omicron' : 927,
        'Pi' : 928,
        'Rho' : 929,
        'Sigma' : 931,
        'Tau' : 932,
        'Upsilon' : 933,
        'Phi' : 934,
        'Chi' : 935,
        'Psi' : 936,
        'Omega' : 937,
        'alpha' : 945,
        'beta' : 946,
        'gamma' : 947,
        'delta' : 948,
        'epsilon' : 949,
        'zeta' : 950,
        'eta' : 951,
        'theta' : 952,
        'iota' : 953,
        'kappa' : 954,
        'lambda' : 955,
        'mu' : 956,
        'nu' : 957,
        'xi' : 958,
        'omicron' : 959,
        'pi' : 960,
        'rho' : 961,
        'sigmaf' : 962,
        'sigma' : 963,
        'tau' : 964,
        'upsilon' : 965,
        'phi' : 966,
        'chi' : 967,
        'psi' : 968,
        'omega' : 969,
        'thetasym' : 977,
        'upsih' : 978,
        'piv' : 982,
        'bull' : 8226,
        'hellip' : 8230,
        'prime' : 8242,
        'Prime' : 8243,
        'oline' : 8254,
        'frasl' : 8260,
        'weierp' : 8472,
        'image' : 8465,
        'real' : 8476,
        'trade' : 8482,
        'alefsym' : 8501,
        'larr' : 8592,
        'uarr' : 8593,
        'rarr' : 8594,
        'darr' : 8595,
        'harr' : 8596,
        'crarr' : 8629,
        'lArr' : 8656,
        'uArr' : 8657,
        'rArr' : 8658,
        'dArr' : 8659,
        'hArr' : 8660,
        'forall' : 8704,
        'part' : 8706,
        'exist' : 8707,
        'empty' : 8709,
        'nabla' : 8711,
        'isin' : 8712,
        'notin' : 8713,
        'ni' : 8715,
        'prod' : 8719,
        'sum' : 8721,
        'minus' : 8722,
        'lowast' : 8727,
        'radic' : 8730,
        'prop' : 8733,
        'infin' : 8734,
        'ang' : 8736,
        'and' : 8743,
        'or' : 8744,
        'cap' : 8745,
        'cup' : 8746,
        'int' : 8747,
        'there4' : 8756,
        'sim' : 8764,
        'cong' : 8773,
        'asymp' : 8776,
        'ne' : 8800,
        'equiv' : 8801,
        'le' : 8804,
        'ge' : 8805,
        'sub' : 8834,
        'sup' : 8835,
        'nsub' : 8836,
        'sube' : 8838,
        'supe' : 8839,
        'oplus' : 8853,
        'otimes' : 8855,
        'perp' : 8869,
        'sdot' : 8901,
        'lceil' : 8968,
        'rceil' : 8969,
        'lfloor' : 8970,
        'rfloor' : 8971,
        'lang' : 9001,
        'rang' : 9002,
        'loz' : 9674,
        'spades' : 9824,
        'clubs' : 9827,
        'hearts' : 9829,
        'diams' : 9830,
        'quot' : 34,
        'amp' : 38,
        'lt' : 60,
        'gt' : 62,
        'OElig' : 338,
        'oelig' : 339,
        'Scaron' : 352,
        'scaron' : 353,
        'Yuml' : 376,
        'circ' : 710,
        'tilde' : 732,
        'ensp' : 8194,
        'emsp' : 8195,
        'thinsp' : 8201,
        'zwnj' : 8204,
        'zwj' : 8205,
        'lrm' : 8206,
        'rlm' : 8207,
        'ndash' : 8211,
        'mdash' : 8212,
        'lsquo' : 8216,
        'rsquo' : 8217,
        'sbquo' : 8218,
        'ldquo' : 8220,
        'rdquo' : 8221,
        'bdquo' : 8222,
        'dagger' : 8224,
        'Dagger' : 8225,
        'permil' : 8240,
        'lsaquo' : 8249,
        'rsaquo' : 8250,
        'euro' : 8364,
    }

# Вернуть уникод символ по html entinty
#
# @param string $entity
# @return string
#/
    def html_char_entity_to_unicode(self, entity):
        if(EMT_Lib.html4_char_ents.get(entity)):
            return unichr(EMT_Lib.html4_char_ents[entity])
        
        return False


# Сконвериторвать все html entity в соответсвующие юникод символы
#
# @param string $text
#/
    def convert_html_entities_to_unicode(self, text):  #TODO: &$text - '&' couldn't work
        text = re.sub("\&#([0-9]+)\;", 
                lambda m: unichr(int(m.group(1)))
                , text) #TODO
        text = re.sub("\&#x([0-9A-F]+)\;", 
                lambda m: unichr(int(m.group(1),16))
                , text) #TODO
        text = re.sub("\&([a-zA-Z0-9]+)\;", 
                lambda m: EMT_Lib.html_char_entity_to_unicode(m.group(1)) if  EMT_Lib.html_char_entity_to_unicode(m.group(1)) else m.group(0)
                , text) #TODO
        return text
    def process_preg_replacement(self, r):
        return re.sub(u'\\\\([0-9]+)',u'\\\\g<\g<1>>', r, 0, re.U)
    
    def parse_preg_pattern(self, pattern):
        es = pattern[0:1]
        modifiers = pattern.split(es).pop()
        b = {'i': re.I, 's': re.S, 'm': re.M, 'u' : re.U }
        flags = re.U
        xeval = False
        for i in modifiers:
            if b.has_key(i):
                flags |= b[i]
            if i=='e':
                xeval = True
        newpattern = pattern[1:-1-len(modifiers)]
        newpattern = newpattern.replace('\\'+es, es)
        return {'pattern' : newpattern, 'flags': flags, 'eval' : xeval }
    
    def preg_replace_one(self, pattern, replacement, text):
        p = EMT_Lib.parse_preg_pattern(pattern)
       
        if not p['eval']:
            return re.sub(p['pattern'], EMT_Lib.process_preg_replacement(replacement), text, 0, p['flags'])
            
        exec "f = lambda m: " + replacement
        return re.sub(p['pattern'], f, text, 0, p['flags'])
    
    def preg_replace(self, pattern, replacement, text):
        if isinstance(pattern, basestring):
            return EMT_Lib.preg_replace_one(pattern, replacement, text)        
        for k, i in enumerate(pattern):
            if isinstance(replacement, basestring):
                repl = replacement
            else:
                repl = replacement[k]
            text = EMT_Lib.preg_replace_one(i, repl, text)
        return text
    
    def preg_replace_ex(self, pattern, replacement, text, cycled = False):        
        while True:
            texto = text
            text = EMT_Lib.preg_replace(pattern, replacement, text)
            if not cycled:
                break
            if text==texto:
                break
        return text
    
    def str_replace_one(self, pattern, replacement, text):
        return text.replace(pattern, replacement)
    
    def str_replace(self, pattern, replacement, text):
        if isinstance(pattern, basestring):
            return EMT_Lib.str_replace_one(pattern, replacement, text)        
        for k, i in enumerate(pattern):
            if isinstance(replacement, basestring):
                repl = replacement
            else:
                repl = replacement[k]
            text = EMT_Lib.str_replace_one(i, repl, text)
        return text
    
    def str_ireplace_one(self, pattern, replacement, text):
        return re.sub(re.escape(pattern), lambda m: replacement, text, 0, re.I)
        #return re.sub(re.escape(pattern), re.escape(replacement), text, 0, re.I)
        
    
    def str_ireplace(self, pattern, replacement, text):
        if isinstance(pattern, basestring):
            return EMT_Lib.str_ireplace_one(pattern, replacement, text)        
        for k, i in enumerate(pattern):
            if isinstance(replacement, basestring):
                repl = replacement
            else:
                repl = replacement[k]
            text = EMT_Lib.str_ireplace_one(i, repl, text)
        return text
    
    def substr (self, s, start, length = None):
        if len(s) <= start:
            return u""
        if length is None:
            return s[start:]
        elif length == 0:
            return u""
        elif length > 0:
            return s[start:start + length]
        else:
            return s[start:length]
    
    def ifop(self, cond, ontrue, onfalse):
        return ontrue if cond else onfalse
    
    def re_sub(self, pattern, replacement, string, count , flags):
        def _r(m):
            # Now this is ugly.
            # Python has a "feature" where unmatched groups return None
            # then re.sub chokes on this.
            # see http://bugs.python.org/issue1519638            
            # this works around and hooks into the internal of the re module...    
            # the match object is replaced with a wrapper that
            # returns "" instead of None for unmatched groups    
            class _m():
                def __init__(self, m):
                    self.m=m
                    self.string=m.string
                def group(self, n):
                    return m.group(n) or ""
    
            return re._expand(pattern, _m(m), replacement)
        return re.sub(pattern, _r, string, count , flags)
    
    def split_number(self, num):
        repl = ""
        for i in range(len(num),-1,-3):
            if i-3>=0:
                repl = ("&thinsp;" if i>3 else "") + num[i-3:i] + repl
            else:
                repl = num[0:i] + repl
        return repl
     
	# https://mathiasbynens.be/demo/url-regex
	# @gruber v2 (218 chars)
    def url_regex(self):
        #return u"""(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'".,<>?«»“”‘’]))"""
        return u"""((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'".,<>?«»“”‘’]))"""
	
	# https://emailregex.com/
    def email_regex(self):
        z = u"""(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])"""
        return z
	
    
EMT_Lib = _EMT_Lib()

BASE64_PARAGRAPH_TAG = 'cA===' 
BASE64_BREAKLINE_TAG = 'YnIgLw===' 
BASE64_NOBR_OTAG = 'bm9icg===' 
BASE64_NOBR_CTAG = 'L25vYnI==' 

QUOTE_FIRS_OPEN = '&laquo;'
QUOTE_FIRS_CLOSE = '&raquo;'
QUOTE_CRAWSE_OPEN = '&bdquo;'
QUOTE_CRAWSE_CLOSE = '&ldquo;'





#/*
# * Базовый класс для группы правил обработки текста
# * Класс группы должен наследовать, данный класс и задавать
# * в нём EMT_Tret::rules и EMT_Tret::$name
# * 
# */
class EMT_Tret:	
    #
    # Набор правил в данной группе, который задан изначально
    # Его можно менять динамически добавляя туда правила с помощью put_rule
    #
    # @var unknown_type
    #
    def __init__(self):
        self.rules = {}
        self.rule_order = []
        self.title = ""
        
        
        self.disabled = {}
        self.enabled  = {}
        self._text= ''
        self.logging = False
        self.logs    = []
        self.errors  = []
        self.debug_enabled  = False
        self.debug_info     = []
        
        
        self.use_layout = False
        self.use_layout_set = False
        self.class_layout_prefix = False
        
        self.class_names     = {}
        self.classes         = {}
        self.settings        = {}
        self.intrep = ""
    
    def log(self, str, data = None):
        if not self.logging:
            return
        self.logs.append({'info': str, 'data': data})
    
    def error(self, info, data = None):
        self.errors.append({'info': info, 'data': data})
        self.log('ERROR: ' + info , data)

    
    def debug(self, place, after_text):
        if not self.debug_info:
            return
        self.debug_info.append({'place': place,'text': after_text})
    
    
    #/**
    # * Установить режим разметки для данного Трэта если не было раньше установлено,
    # *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
    # *   EMT_Lib::LAYOUT_CLASS - с помощью классов
    # *
    # * @param int $kind
    # */
    def set_tag_layout_ifnotset(layout):
        if self.use_layout_set:
            return
        self.use_layout = layout
    
    #/**
    # * Установить режим разметки для данного Трэта,
    # *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
    # *   EMT_Lib::LAYOUT_CLASS - с помощью классов
    # *   EMT_Lib::LAYOUT_STYLE|EMT_Lib::LAYOUT_CLASS - оба метода
    # *
    # * @param int $kind
    # */
    def set_tag_layout(layout = LAYOUT_STYLE):
        self.use_layout = layout
        self.use_layout_set = True
    
    def set_class_layout_prefix(self, prefix):
        self.class_layout_prefix = prefix


    def debug_on(self):
        self.debug_enabled = True

    def log_on(self):
        self.debug_enabled = True


    #def getmethod(self, name):
    #    if not name: return False
    #    if not method_exists(his, $name)) return False;
    #    return array($this, $name);

    def _pre_parse(self):
        self.pre_parse()
        #foreach($this->rules as $rule)
        #{
        #        if(!isset($rule['init'])) continue;
        #        $m = $this->getmethod($rule['init']);
        #        if(!$m) continue;
        #        call_user_func($m);
        #}

    def _post_parse(self):
        #foreach($this->rules as $rule)
        #{
        #        if(!isset($rule['deinit'])) continue;
        #        $m = $this->getmethod($rule['deinit']);
        #        if(!$m) continue;
        #        call_user_func($m);
        #}
        self.post_parse()


    def intrepfun(self, m):    	
        exec u'x = ' + self.intrep +u''
        return x

    def preg_replace_one(self, pattern, replacement, text):
        p = EMT_Lib.parse_preg_pattern(pattern)
       
        if not p['eval']:
            #print p['pattern']
            #print EMT_Lib.process_preg_replacement(replacement)
            #EMT_Lib.process_preg_replacement
            return EMT_Lib.re_sub(p['pattern'], (replacement), text, 0, p['flags'])

        
        self.intrep = replacement
        return re.sub(p['pattern'], self.intrepfun, text, 0, p['flags'])
    
    def preg_replace(self, pattern, replacement, text):
        if isinstance(pattern, basestring):
            return self.preg_replace_one(pattern, replacement, text)        
        for k, i in enumerate(pattern):
            if isinstance(replacement, basestring):
                repl = replacement
            else:
                repl = replacement[k]
            text = self.preg_replace_one(i, repl, text)
        return text
    
    def preg_replace_ex(self, pattern, replacement, text, cycled = False):        
        while True:
            texto = text
            text = self.preg_replace(pattern, replacement, text)
            if not cycled:
                break
            if text==texto:
                break
        return text
    
    #def rule_order_sort(self, $a, $b):
    #    if($a['order'] == $b['order']) return 0;
    #    if($a['order'] < $b['order']) return -1;
    #    return 1;

    def apply_rule(self, rule):
        name = rule['id']        
        disabled = self.disabled.get(rule['id']) or (rule.get('disabled') and not self.enabled.get(rule['id']))
        if disabled:
            self.log(u"Правило $name", u"Правило отключено" + u" (по умолчанию)" if self.disabled.get(rule['id']) else "")
            return
        
        if rule.get('function'):
            if not rule.get('pattern'):
                if rule['function'] in dir(self):
                        self.log(u"Правило "+name, u"Используется метод " + rule['function'] + u" в правиле")
                        getattr(self,rule['function'])()
                        return
                    
                if globals().has_key(rule['function']):
                        self.log(u"Правило " + name, u"Используется функция " + rule['function'] + u" в правиле")
                        globals()[rule['function']]()
                        return
                
                self.error(u'Функция '+rule['function']+u' из правила '+rule['id']+u" не найдена")
                return
            else:
                if re.match("^[a-z_0-9]+$", rule['function'], re.I):
                    p = EMT_Lib.parse_preg_pattern(rule['pattern'])
                    if rule['function'] in dir(self):
                            self.log(u"Правило "+name, u"Замена с использованием preg_replace_callback с методом " + rule['function'] )                            
                            self._text = re.sub(p['pattern'], getattr(self,rule['function']), self._text, 0, p['flags'])
                            return
                        
                    if globals().has_key(rule['function']):
                            self.log(u"Правило " + name, u"Замена с использованием preg_replace_callback с функцией " + rule['function'] + u" в правиле")
                            self._text = re.sub(p['pattern'], globals()[rule['function']], self._text, 0, p['flags'])
                            return
                    
                    self.error(u'Функция '+rule['function']+' из правила '+rule['id']+u" не найдена")
                else:
                    self.preg_replace(rule['pattern']+'e', rule['function'], self._text)
                    self.log(u'Замена с использованием preg_replace_callback с инлайн функцией из правила ' + rule['id'])
                    return
                return
        
        if rule.get('simple_replace'):
            if rule.get('case_sensitive'):
                self.log(u"Правило "+name, u"Простая замена с использованием str_replace")
                self._text = EMT_Lib.str_replace(rule['pattern'], rule['replacement'], self._text)
                return
            self.log(u"Правило "+name, u"Простая замена с использованием str_ireplace")
            self._text = EMT_Lib.str_ireplace(rule['pattern'], rule['replacement'], self._text)
            return
        
        cycled = False
        if rule.get('cycled'):
            cycled = True
            
        pattern = rule['pattern']
        #p = EMT_Lib.parse_preg_pattern(pattern)
        #if isinstance(pattern, basestring):
        #    pattern = [pattern]
        #if not p['eval']:
        #    self.log("Правило "+name, "Замена с использованием preg_replace")
        #    self._text = EMT_Lib.preg_replace_ex( rule['pattern'], rule['replacement'], self._text, cycled )
        #    return
        
        self.log(u"Правило "+name, u"Замена с использованием preg_replace или preg_replace_callback вместо eval")
        self._text = self.preg_replace_ex( rule['pattern'], rule['replacement'], self._text, cycled )


    def _apply(self, xlist):
        self.errors = []
        self._pre_parse()
        self.log("Применяется набор правил", ','.join(xlist))
        rulelist = []
        for k in xlist:
            rule = self.rules[k]
            rule['id']    = k
            if not rule.has_key('order'):
                rule['order'] = 5 
            rulelist.append(rule)
        
        for rule in rulelist:
            self.apply_rule(rule)	
            self.debug(rule['id'], self._text)
        
        self._post_parse()


    
    #/**
    # * Создание защищенного тега с содержимым
    # *
    # * @see 	EMT_lib::build_safe_tag
    # * @param 	string $content
    # * @param 	string $tag
    # * @param 	array $attribute
    # * @return 	string
    # */
    def tag(self, content, tag = 'span', attribute = {} ):
        if attribute.has_key('class'):
            classname = attribute['class']
            if classname == "nowrap":
                if not self.is_on('nowrap'):
                    tag = u"nobr"
                    attribute = {}
                    classname = ""
            if self.classes.has_key(classname):
                style_inline = self.classes[classname]
                if style_inline:
                    attribute['__style'] = style_inline
                
            if self.class_names.has_key(classname):
                classname = class_names(classname)
                
            classname = (self.class_layout_prefix if self.class_layout_prefix else "" ) + classname
            attribute['class'] = classname
        layout = LAYOUT_STYLE
        if self.use_layout:
            layout = self.use_layout
        return EMT_Lib.build_safe_tag(content, tag, attribute, layout )


    #/**
    # * Добавить правило в группу
    # *
    # * @param string $name
    # * @param array $params
    # */
    def put_rule(self, name, params):
        self.rules[name] = params
        return self
    
    # /**
    # * Отключить правило, в обработке
    # *
    # * @param string $name
    # */
    def disable_rule(self, name):
        self.disabled[name] = True
        if self.enabled.has_key(name):
            del self.enabled[name]

    #/**
    # * Включить правило
    # *
    # * @param string $name
    # */
    def enable_rule(self, name):
        self.enabled[name] = True
        if self.disabled.has_key(name):
            del self.disabled[name]

    # /**
    # * Добавить настройку в трет
    # *
    # * @param string $key ключ
    # * @param mixed $value значение
    # */
    def set(self, key, value):
        self.settings[key] = value

    # /**
    # * Установлена ли настройка
    # *
    # * @param string $key
    # */
    def is_on(self, key):
        if not self.settings.has_key(key):
            return False
        kk = self.settings[key]
        if isinstance(kk, basestring) and kk.lower() == "on": return True
        if isinstance(kk, basestring) and kk == "1": return True
        if isinstance(kk, bool) and kk: return True
        if isinstance(kk, int) and kk == 1: return True
        return False

    # /**
    # * Получить строковое значение настройки
    # *
    # * @param unknown_type $key
    # * @return unknown
    # */
    def ss(self, key):
        if not self.settings.has_key(key): return ""
        return self.settings[key]

    # /**
    # * Добавить настройку в правило
    # *
    # * @param string $rulename идентификатор правила 
    # * @param string $key ключ
    # * @param mixed $value значение
    # */
    def set_rule(self, rulename, key, value):
        if not self.rules.has_key(rulename):
            self.rules[rulename] = {}
        self.rules[rulename][key] = value

    #/**
    # * Включить правила, согласно списку
    # *
    # * @param array $list список правил
    # * @param boolean $disable выкллючить их или включить
    # * @param boolean $strict строго, т.е. те которые не в списку будут тоже обработаны
    # */
    def activate(self, xlist, disable =False, xstrict = True):
        for rulename in xlist:
            if disable:
                self.disable_rule(rulename)
            else:
                self.enable_rule(rulename)
        
        if xstrict:
            for rulename in self.rules:
                y = self.rules[rulename]
                if rulename in xlist:
                    continue
                if not disable:
                    self.disable_rule(rulename)
                else:
                    self.enable_rule(rulename)

    def set_text(self, text):
        self._text = text
        self.debug_info = []
        self.logs = []


    # /**
    # * Применить к тексту
    # *
    # * @param string $text - текст к которому применить
    # * @param mixed $list - список правил, null - все правила
    # * @return string
    # */
    def apply(self, xlist = None):
        if isinstance(xlist, basestring):
            rlist = [xlist]
        elif isinstance(xlist, (list, tuple)):
            rlist = xlist
        else:
            rlist = self.rule_order
        self._apply(rlist)
        return self._text


    # /**
    # * Код, выполняем до того, как применить правила
    # *
    # */
    def pre_parse(self):
        return

    #/**
    # * После выполнения всех правил, выполняется этот метод
    # *
    # */
    def post_parse(self):
        return
    
    
    
    

# EMT_Lib.preg_replace('/aaa/msi', 'bbb', 'xxx aaa yyy')




#####EMT_TRETS#####









# /**
# * Evgeny Muravjev Typograph, http://mdash.ru
# * Version: 3.5 Gold Master
# * Release Date: July 2, 2015
# * Authors: Evgeny Muravjev & Alexander Drutsa  
# */



# /**
# * Основной класс типографа Евгения Муравьёва
# * реализует основные методы запуска и рабыоты типографа
# *
# */
class EMT_Base:
    def __init__(self):
        self._text = ""
        self.inited = False
    
        #/**
        # * Список Трэтов, которые надо применить к типогрфированию
        # *
        # * @var array
        # */
        self.trets = []
        self.trets_index = []
        self.tret_objects = {}
    
        self.ok             = False
        self.debug_enabled  = False
        self.logging        = False
        self.logs           = [] 
        self.errors         = []
        self.debug_info     = []
        
        self.use_layout = False
        self.class_layout_prefix = False
        self.use_layout_set = False
        self.disable_notg_replace = False
        self.remove_notg = False
        
        self.settings = {}
        self._safe_blocks = []	
        self._safe_sequences = []	
        self._safe_sequence_mark = u"SAFESEQUENCENUM";

    def log(self, xstr, data = None):
        if not self.logging:
            return
        self.logs.append({'class': '', 'info': xstr, 'data': data})
    
    def tret_log(self, tret, xstr, data = None):
        self.logs.append({'class': tret, 'info': xstr, 'data': data})
            
    def error(self, info, data = None):
        self.errors.append({'class': '', 'info': info, 'data': data})
        self.log("ERROR "+info, data )
    
    def tret_error(self, tret, info, data = None):
        self.errors.append({'class': tret, 'info': info, 'data': data})
    
    def debug(self, xclass, place, after_text, after_text_raw = ""):
        if not self.debug_enabled: return
        if isinstance(xclass, basestring):
            classname = xclass
        else:
            classname = xclass.__class__.__name__
        self.debug_info.append({
                        'tret'  : False if xclass == self else True,
                        'class' : classname,
                        'place' : place,
                        'text'  : after_text,
                        'text_raw' : after_text_raw,
                })
    
    
    
    
    
    
    # /**
    # * Включить режим отладки, чтобы посмотреть последовательность вызовов
    # * третов и правил после
    # *
    # */
    def debug_on(self):
        self.debug_enabled = True
    
    # /**
    # * Включить режим отладки, чтобы посмотреть последовательность вызовов
    # * третов и правил после
    # *
    # */
    def log_on(self):
        self.logging = True
    
    # /**
    # * Добавление защищенного блока
    # *
    # * <code>
    # *  Jare_Typograph_Tool::addCustomBlocks('<span>', '</span>');
    # *  Jare_Typograph_Tool::addCustomBlocks('\<nobr\>', '\<\/span\>', True);
    # * </code>
    # * 
    # * @param 	string $id идентификатор
    # * @param 	string $open начало блока
    # * @param 	string $close конец защищенного блока
    # * @param 	string $tag тэг
    # * @return  void
    # */
    def _add_safe_block(self, xid, xopen, close, tag):
        self._safe_blocks.append({
                    'id' : xid,
                    'tag' : tag,
                    'open' :  xopen,
                    'close' :  close
            })
            
    # /**
    # * Добавление защищенного блока
    # *
    # * @param 	string $type тип последовательности
    # *            0 - URL
    # *            1 - почта
    # * @param 	string $content реальное содержимое
    # * @return  void
    # */
    def _add_safe_sequence(self, type, content):
        self._safe_sequences.append({
                    'type' : type,
                    'content' :  content
            })
    
    # /**
    # * Вычисляем тэг, которого нет в заданном тексте
    # *
    # * @return 	array
    # */
    def detect_safe_mark(self):
    	seq = self._safe_sequence_mark
    	i = 0
    	while self._text.find(seq) != -1:
    		seq = EMT_Lib.str_replace(u"SAFESEQUENCENUM",u"SAFESEQUENCE"+str(i)+u"NUM", self._safe_sequence_mark);
    		i += 1
    	self._safe_sequence_mark = seq
    
    # /**
    # * Список защищенных блоков
    # *
    # * @return 	array
    # */
    def get_all_safe_blocks(self):
       return self._safe_blocks
    
    # /**
    # * Список защищенных последовательностей
    # *
    # * @return 	array
    # */
    def get_all_safe_sequences(self):
    	return self._safe_sequences
    
    # /**
    # * Удаленного блока по его номеру ключа
    # *
    # * @param 	string $id идентифиактор защищённого блока 
    # * @return  void
    # */
    def remove_safe_block(self, xid):
        i = 0
        for x in self._safe_blocks:            
            if x['id'] == xid:
                break
            i += 1
        if i == len(self._safe_blocks):
            return
        del self._safe_blocks[i]
    
    
    # /**
    # * Добавление защищенного блока
    # *
    # * @param 	string $tag тэг, который должен быть защищён
    # * @return  void
    # */
    def add_safe_tag(self, tag):
        xopen = re.escape("<") + tag + "[^>]*?" + re.escape(">")
        close = re.escape("</"+tag+">")
        self._add_safe_block(tag, xopen, close, tag)
        return True
    
    
    # /**
    # * Добавление защищенного блока
    # *
    # * @param 	string $open начало блока
    # * @param 	string $close конец защищенного блока
    # * @param 	bool $quoted специальные символы в начале и конце блока экранированы
    # * @return  void
    # */
    def add_safe_block(self, xid, xopen, close, quoted = False):
        xopen = xopen.strip()
        close = close.strip()
        
        if xopen == "" or close == "":
            return False
        
        if not quoted: 
            xopen = re.escape(xopen)
            close = re.escape(close)
        
        self._add_safe_block(xid, xopen, close, "")
        return True
    
    
    # /**
    # * Сохранение содержимого защищенных блоков
    # *
    # * @param   string $text
    # * @param   bool $safe если True, то содержимое блоков будет сохранено, иначе - раскодировано. 
    # * @return  string
    # */
    def safe_blocks(self, text, way, show = True):
        if len(self._safe_blocks): 
            safeType =  "EMT_Lib.encrypt_tag(m.group(2))" if True == way else "stripslashes(EMT_Lib.decrypt_tag(m.group(2)))"
            selfblocks = self._safe_blocks
            if not way:
                selfblocks.reverse()
            def safereplace(m):
                return m.group(1)+(EMT_Lib.encrypt_tag(m.group(2)) if True == way else EMT_Lib.decrypt_tag(m.group(2)).replace("\\n","\n").replace("\\r","\n").replace("\\",""))+m.group(3)
            for idx in selfblocks:
                block = idx
                #text = EMT_Lib.preg_replace(u"/("+block['open']+u")(.+?)("+block['close']+u")/ue", 'm.group(1)+' + safeType + '+m.group(3)', text)
                text = re.sub(u"("+block['open']+u")(.+?)("+block['close']+u")", safereplace, text, 0, re.U)
        return text
    
    
    # /**
    # * Кодирование УРЛа
    # *
    # * @param regex array $m
    # * @return unknown
    # */
    def safe_sequence_url(self, m):
    	id = len(self._safe_sequences);
    	self._add_safe_sequence(0, m.group(0));
    	return u"http://mdash.ru/A0"+self._safe_sequence_mark+str(id)+u"ID";
    
    # /**
    # * Кодирование Почты
    # *
    # * @param regex array $m
    # * @return unknown
    # */
    def safe_sequence_email(self, m):
    	id = len(self._safe_sequences);
    	self._add_safe_sequence(1, m.group(0));
    	return u"A1"+self._safe_sequence_mark+str(id)+u"ID@mdash.ru";
     
    # /**
    # * Декодирование УРЛа
    # *
    # * @param regex array $m
    # * @return unknown
    # */
    def unsafe_sequence_url(self, m):
    	return self._safe_sequences[int(m.group(1))]['content'];
    
    # /**
    # * Декодирование УРЛа с удалением http://
    # *
    # * @param regex array $m
    # * @return unknown
    # */
    def unsafe_sequence_url_nohttp(self, m):
    	z = self._safe_sequences[int(m.group(1))]['content'];
    	return re.sub(u"([^:]+)://", "", z);
    
    
    # /**
    # * Декодирование Почты
    # *
    # * @param regex array $m
    # * @return unknown
    # */
    def unsafe_sequence_email(self, m):
    	return self._safe_sequences[int(m.group(1))]['content'];
    
    # /**
    # * Сохранение защищенных последовательностей
    # *
    # * @param   string $text
    # * @param   bool $safe если true, то содержимое блоков будет сохранено, иначе - раскодировано. 
    # * @return  string
    # */
    def safe_sequences(self, text, way, show = True):
    	if way:
            def repl1(m):
                return self.safe_sequence_url(m) #text = preg_replace_callback(EMT_Lib.url_regex(), repl1, text);
            text = re.sub(EMT_Lib.url_regex(), repl1, text, 0, re.U | re.I | re.S)
            
            def repl2(m):
                return self.safe_sequence_email(m) #text = preg_replace_callback(EMT_Lib::email_regex(), array($this, "safe_sequence_email") , $text);
            text = re.sub(EMT_Lib.email_regex(), repl2, text, 0, re.U | re.I | re.S)
            
            
    	else:
            def repl3(m):
                return self.unsafe_sequence_url(m) #$text = preg_replace_callback('~http://mdash.ru/A0'.$this->_safe_sequence_mark.'(\d+)ID~ims', array($this, "unsafe_sequence_url") , $text);
            text = re.sub(u'http://mdash.ru/A0'+self._safe_sequence_mark+u'(\d+)ID', repl3, text, 0, re.U | re.I | re.S)
            def repl4(m):
                return self.unsafe_sequence_url_nohttp(m) #$text = preg_replace_callback('~mdash.ru/A0'.$this->_safe_sequence_mark.'(\d+)ID~ims', array($this, "unsafe_sequence_url_nohttp") , $text);
            text = re.sub(u'mdash.ru/A0'+self._safe_sequence_mark+u'(\d+)ID', repl4, text, 0, re.U | re.I | re.S)
            def repl5(m):
                return self.unsafe_sequence_email(m) #$text = preg_replace_callback('~A1'.$this->_safe_sequence_mark.'(\d+)ID@mdash.ru~ims', array($this, "unsafe_sequence_email") , $text);
            text = re.sub(u'A1'+self._safe_sequence_mark+u'(\d+)ID@mdash.ru', repl5, text, 0, re.U | re.I | re.S)
    		
    	return text
    
    
    
    # /**
    # * Декодирование блоков, которые были скрыты в момент типографирования
    # *
    # * @param   string $text
    # * @return  string
    # */
    def decode_internal_blocks(self, text):
        return EMT_Lib.decode_internal_blocks(text)
    
    
    def create_object(self, tret):
        # если класса нету, попытаемся его прогрузить, например, если стандартный
        try:
            obj = globals()[tret]()
            obj.EMT     = self
            obj.logging = self.logging
            return obj
        except:
            self.error("Класс "+tret + " не найден. Пожалуйста, подргузите нужный файл.")
            return None
        return None
    
    def get_short_tret(self, tretname):
        m = re.match('^EMT_Tret_([a-zA-Z0-9_]+)$', tretname)
        if m:
            return m.group(1)
        return tretname
    
    def _init(self):
        for tret in self.trets:
            if self.tret_objects.has_key(tret):
                continue
            obj = self.create_object(tret)
            if obj == None:
                continue
            self.tret_objects[tret] = obj
        
        if not self.inited:
            self.add_safe_tag('pre')
            self.add_safe_tag('script')
            self.add_safe_tag('style')
            self.add_safe_tag('notg')
            self.add_safe_block('span-notg', '<span class="_notg_start"></span>', '<span class="_notg_end"></span>')
        self.inited = True
        
        self.detect_safe_mark()
    
    
    
    # /**
    # * Инициализация класса, используется чтобы задать список третов или
    # * спсиок защищённых блоков, которые можно использовать.
    # * Такде здесь можно отменить защищённые блоки по умлочнаию
    # *
    # */
    def init(self):
        return
    
    # /**
    # * Добавить Трэт, 
    # *
    # * @param mixed $class - имя класса трета, или сам объект
    # * @param string $altname - альтернативное имя, если хотим например иметь два одинаоковых терта в обработке
    # * @return unknown
    # */
    def add_tret(self, xclass, altname = False):
        if isinstance(xclass, basestring):
            obj = self.create_object(xclass)
            if obj == None:
                    return False
            self.tret_objects[altname if altname else xclass] = obj
            self.trets.append(altname if altname else xclass)
            return True
        try:
            if not issubclass(xclass, EMT_Tret):
                self.error("You are adding Tret that doesn't inherit base class EMT_Tret", xclass.__class__.__name__)
                return False                
            xclass.EMT     = self
            xclass.logging = self.logging
            self.tret_objects[ altname if altname else xclass.__class__.__name__] = xclass
            self.trets.append(altname if altname else xclass.__class__.__name__)
            return True
        except:
            self.error("Чтобы добавить трэт необходимо передать имя или объект")
        return False
    
    # /**
    # * Получаем ТРЕТ по идентивикатору, т.е. заванию класса
    # *
    # * @param unknown_type $name
    # */
    def get_tret(self, name):
        if self.tret_objects.has_key(name):
            return self.tret_objects[name]
        for tret in self.trets:
            if tret == name:
                self._init()
                return self.tret_objects[name]
            
            if self.get_short_tret(tret) == name:
                    self._init()
                    return self.tret_objects[tret]
                
        self.error("Трэт с идентификатором "+name+" не найден")
        return False
    
    #/**
    # * Задаём текст для применения типографа
    # *
    # * @param string $text
    # */
    def set_text(self, text):
        self._text = text
    
    
    
    #/**
    # * Запустить типограф на выполнение
    # *
    # */
    def apply(self, trets = None):
        self.ok = False
        
        self.init()
        self._init()
        
        atrets = self.trets
        if isinstance(trets, basestring):
            atrets = [trets]
        elif isinstance(trets, (list, tuple)):
            atrets = trets
        
        self.debug(self, 'init', self._text)
        
        self._text = self.safe_sequences(self._text, True)
        self.debug(self, 'safe_sequences', self._text)
        
        self._text = self.safe_blocks(self._text, True)
        self.debug(self, 'safe_blocks', self._text)
        
        self._text = EMT_Lib.safe_tag_chars(self._text, True)
        self.debug(self, 'safe_tag_chars', self._text)
        
        self._text = EMT_Lib.clear_special_chars(self._text)
        self.debug(self, 'clear_special_chars', self._text)
        
        for tret in atrets:
            # если установлен режим разметки тэгов то выставим его
            if self.use_layout_set:
                self.tret_objects[tret].set_tag_layout_ifnotset(self.use_layout)
                    
            if self.class_layout_prefix:
                self.tret_objects[tret].set_class_layout_prefix(self.class_layout_prefix)
            
            # влючаем, если нужно
            if self.debug_enabled:
                self.tret_objects[tret].debug_on()
            if self.logging:
                self.tret_objects[tret].logging = True
                                    
            # применяем трэт
            self.tret_objects[tret].set_text(self._text)
            self.tret_objects[tret].apply()
            self._text = self.tret_objects[tret]._text
            
            # соберём ошибки если таковые есть
            if len(self.tret_objects[tret].errors)>0:
                for err in self.tret_objects[tret].errors:
                    self.tret_error(tret, err['info'], err['data'])
            
            # логгирование 
            if self.logging:
                if len(self.tret_objects[tret].logs)>0:
                    for log in self.tret_objects[tret].logs:
                        self.tret_log(tret, log['info'], log['data'])
            
            # отладка
            if self.debug_enabled:
                for di in self.tret_objects[tret].debug_info:
                    unsafetext = di['text']
                    unsafetext = EMT_Lib.safe_tag_chars(unsafetext, False)
                    unsafetext = self.safe_blocks(unsafetext, False)
                    self.debug(tret, di['place'], unsafetext, di['text'])
            
        self._text = self.decode_internal_blocks(self._text)
        self.debug(self, 'decode_internal_blocks', self._text)
        
        if self.is_on('dounicode'):
            self._text = EMT_Lib.convert_html_entities_to_unicode(self._text)
        
        self._text = EMT_Lib.safe_tag_chars(self._text, False)
        self.debug(self, 'unsafe_tag_chars', self._text)
        
        self._text = self.safe_blocks(self._text, False)	
        self.debug(self, 'unsafe_blocks', self._text)
        
        self._text = self.safe_sequences(self._text, False)	
        self.debug(self, 'unsafe_sequences', self._text)
        
        if not self.disable_notg_replace:
            repl = ['<span class="_notg_start"></span>', '<span class="_notg_end"></span>']
            if self.remove_notg:
                repl = ""
            self._text = EMT_Lib.str_replace( ['<notg>','</notg>'], repl , self._text)
            
        self._text = self._text.strip()
        self.ok = len(self.errors)==0
        return self._text
    
    #/**
    # * Получить содержимое <style></style> при использовании классов
    # * 
    # * @param bool $list False - вернуть в виде строки для style или как массив
    # * @param bool $compact не выводить пустые классы
    # * @return string|array
    # */
    def get_style(self, xlist = False, compact = False):
        self._init()
        
        res = {}
        for tret in self.trets:
            arr = self.tret_objects[tret].classes
            if not isinstance(arr, (list,tuple,dict)):
                continue
            for classname in arr:
                xstr = arr[classname]
                if compact and not xstr:
                    continue
                z = classname
                if self.tret_objects[tret].class_names.has_key(classname):
                    z = self.tret_objects[tret].class_names[classname]
                clsname = (self.class_layout_prefix if self.class_layout_prefix else "" ) + z
                res[clsname] = xstr
                
        if xlist:
            return res
        xstr = ""
        for k in res:
            v = res[k]
            xstr = xstr + "."+k+" { "+v+" }\n"
        return xstr
    
    #/**
    # * Установить режим разметки,
    # *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
    # *   EMT_Lib::LAYOUT_CLASS - с помощью классов
    # *   EMT_Lib::LAYOUT_STYLE|EMT_Lib::LAYOUT_CLASS - оба метода
    # *
    # * @param int $layout
    # */
    def set_tag_layout(self, layout = LAYOUT_STYLE):
        self.use_layout = layout
        self.use_layout_set = True
    
    #/**
    # * Установить префикс для классов
    # *
    # * @param string|bool $prefix если True то префикс 'emt_', иначе то, что передали
    # */
    def set_class_layout_prefix(self, prefix ):
        self.class_layout_prefix = prefix if isinstance(prefix,basestring) else  "emt_"
    
    #/**
    # * Включить/отключить правила, согласно карте
    # * Формат карты:
    # *    'Название трэта 1' => array ( 'правило1', 'правило2' , ...  )
    # *    'Название трэта 2' => array ( 'правило1', 'правило2' , ...  )
    # *
    # * @param array $map
    # * @param boolean $disable если ложно, то $map соотвествует тем правилам, которые надо включить
    # *                         иначе это список правил, которые надо выключить
    # * @param boolean $strict строго, т.е. те которые не в списку будут тоже обработаны
    # */
    def set_enable_map(self, xmap, disable = False, xstrict = True):
        if not isinstance(xmap, (list,tuple,dict)):
            return
        trets = []
        for tret in xmap:
            xlist = xmap[tret]
            tretx = self.get_tret(tret)
            if not tretx:
                self.log("Трэт " + tret + " не найден при применении карты включаемых правил")
                continue
            
            trets.append(tretx)
            
            if isinstance(xlist , bool) and xlist: # все
                tretx.activate([], not disable,  True)
            elif isinstance(xlist, basestring):
                tretx.activate([xlist], disable,  xstrict)
            elif isinstance(xlist , (list, tuple)):
                tretx.activate(xlist, disable,  xstrict)
            
        if xstrict:
            for tret in self.trets:
                if self.tret_objects[tret] in trets:
                    continue
                self.tret_objects[tret].activate([], disable , True)
    
    #/**
    # * Установлена ли настройка
    # *
    # * @param string $key
    # */
    def is_on(self, key):
        if not self.settings.has_key(key):
            return False
        kk = self.settings[key]
        if isinstance(kk, basestring) and kk.lower() == "on": return True
        if isinstance(kk, basestring) and kk == "1": return True
        if isinstance(kk, bool) and kk: return True
        if isinstance(kk, int) and kk == 1: return True
        return False
    
    #/**
    # * Установить настройку
    # *
    # * @param mixed $selector
    # * @param string $setting
    # * @param mixed $value
    # */
    def doset(self, selector, key, value):
        tret_pattern = False
        rule_pattern = False
        #if(($selector === False) || ($selector === null) || ($selector === False) || ($selector === "*")) $type = 0
        if isinstance(selector, basestring):
            if selector.find(".")==-1:
                tret_pattern = selector
            else:
                pa = selector.split(".")
                tret_pattern = pa[0]
                pa.pop(0)
                rule_pattern = ".".join(pa)
        tret_pattern = EMT_Lib.process_selector_pattern(tret_pattern)
        rule_pattern = EMT_Lib.process_selector_pattern(rule_pattern)
        if selector == "*":
            self.settings[key] = value
        
        for tret in self.trets:
            t1 = self.get_short_tret(tret)
            if not EMT_Lib.test_pattern(tret_pattern, t1):
                if not EMT_Lib.test_pattern(tret_pattern, tret):
                    continue
            tret_obj = self.get_tret(tret)
            if key == "active":
                for rulename in tret_obj.rules:
                    if not EMT_Lib.test_pattern(rule_pattern, rulename):
                        continue
                    is_on = False
                    is_off = False
                    if isinstance(value, basestring) and value.lower() == "on": is_on = True
                    elif isinstance(value, basestring) and value == "1": is_on = True
                    elif isinstance(value, bool) and value: is_on = True
                    elif isinstance(value, int) and value == 1: is_on = True
                    if isinstance(value, basestring) and value.lower() == "off": is_off = True
                    elif isinstance(value, basestring) and value == "0": is_off = True
                    elif isinstance(value, bool) and not value: is_off = True
                    elif isinstance(value, int) and value == 0: is_off = True
                    if is_on:
                        tret_obj.enable_rule(rulename)
                    if is_off:
                        tret_obj.disable_rule(rulename)
            else:
                if isinstance(rule_pattern, bool) and not rule_pattern:
                    tret_obj.set(key, value)
                else:
                    for rulename in tret_obj.rules:
                        if not EMT_Lib.test_pattern(rule_pattern, rulename):
                            continue
                        tret_obj.set_rule(rulename, key, value)
    
    #/**
    # * Установить настройки для тертов и правил
    # * 	1. если селектор является массивом, то тогда утсановка правил будет выполнена для каждого
    # *     элемента этого массива, как отдельного селектора.
    # *  2. Если $key не является массивом, то эта настрока будет проставлена согласно селектору
    # *  3. Если $key массив - то будет задана группа настроек
    # *       - если $value массив , то настройки определяются по ключам из массива $key, а значения из $value
    # *       - иначе, $key содержит ключ-значение как массив  
	# *  4. $exact_match - если true тогда array selector будет соответсвовать array $key, а не произведению массивов
    # *
    # * @param mixed $selector
    # * @param mixed $key
    # * @param mixed $value
	# * @param mixed $exact_match
    # */
    def set(self, selector, key , value = False, exact_match = False):
        if exact_match and isinstance(selector, (list,tuple,set)) and isinstance(key, (list,tuple,dict,set)) and len(selector)==len(key):
            ind = 0
            for xx in key:
                if isinstance(key, dict):
                    x = xx
                    y = key[x]
                else:
                    x = ind
                    y = xx
                if isinstance(value, dict):
                    kk = y
                    vv = value[x]
                else:
                    kk = y if value else x ;
                    vv = value if value else y ;
                self.set(selector[ind], kk, vv)
                ind += 1
            return 
        if isinstance(selector, (list,tuple,set)):
            for val in selector:
                self.set(val, key, value)
            return
        if isinstance(key, (list,tuple,dict,set)):
            ind = 0
            for xx in key:
                if isinstance(key, dict):
                    x = xx
                    y = key[x]
                else:
                    x = ind
                    y = xx
                if isinstance(value, dict):
                    kk = y
                    vv = value[x]
                else:
                    kk = y if value else x ;
                    vv = value if value else y ;
				
                self.set(selector, kk, vv)
                ind += 1
            return 
        self.doset(selector, key, value)
    
    
    #/**
    # * Возвращает список текущих третов, которые установлены
    # *
    # */
    def get_trets_list(self):
        return self.trets
    
    #/**
    # * Установка одной метанастройки
    # *
    # * @param string $name
    # * @param mixed $value
    # */
    def do_setup(self, name, value):
        return
    
    
    # /**
    # * Установить настройки
    # *
    # * @param array $setupmap
    # */
    def setup(self, setupmap):
        if not isinstance(setupmap, dict):
            return
        
        if setupmap.has_key('map') or setupmap.has_key('maps'):
            #if setupmap.has_key('map'):
            #    ret['map'] = test['params']['map']
            #    ret['disable'] = test['params']['map_disable']
            #    ret['strict'] = test['params']['map_strict']
            #    test['params']['maps'] = [ret]
            #    del setupmap['map']
            #    del setupmap['map_disable']
            #    del setupmap['map_strict']

            if setupmap.has_key('maps'):
                for xmap in setupmap['maps']:
                    self.set_enable_map(xmap['map'], 
                                        xmap['disable'] if xmap.has_key('disable') else False,
                                        xmap['strict'] if xmap.has_key('strict') else False
                                    )
            del setupmap['maps']
        
        for k in setupmap:
            v = setupmap[k]
            self.do_setup(k , v)
    
    
class EMTypograph(EMT_Base):
    def __init__(self):
        EMT_Base.__init__(self)
        self.trets = ['EMT_Tret_Quote', 'EMT_Tret_Dash', 'EMT_Tret_Symbol', 'EMT_Tret_Punctmark', 'EMT_Tret_Number',  'EMT_Tret_Space', 'EMT_Tret_Abbr',  'EMT_Tret_Nobr', 'EMT_Tret_Date', 'EMT_Tret_OptAlign', 'EMT_Tret_Etc', 'EMT_Tret_Text']
        
        self.group_list  = {
                'Quote'     : True,
                'Dash'      : True,
                'Nobr'      : True,
                'Symbol'    : True,
                'Punctmark' : True,
                'Number'    : True,
                'Date'      : True,
                'Space'     : True,
                'Abbr'      : True,		
                'OptAlign'  : True,
                'Text'      : True,
                'Etc'       : True,	
            }
        self.all_options = {
                'Quote.quotes' :  {'description' : 'Расстановка «кавычек-елочек» первого уровня', 'selector' : "Quote.*quote" },
                'Quote.quotation' : { 'description' : 'Внутренние кавычки-лапки', 'selector' : "Quote", 'setting' : 'no_bdquotes', 'reversed' : True },
                                                        
                'Dash.to_libo_nibud' : 'direct',
                'Dash.iz_za_pod' : 'direct',
                'Dash.ka_de_kas' : 'direct',
                
                'Nobr.super_nbsp' : 'direct',
                'Nobr.nbsp_in_the_end' : 'direct',
                'Nobr.phone_builder' : 'direct',
                'Nobr.phone_builder_v2' : 'direct',
                'Nobr.ip_address' : 'direct',
                'Nobr.spaces_nobr_in_surname_abbr' : 'direct',
                'Nobr.dots_for_surname_abbr' : 'direct',                
                'Nobr.nbsp_celcius' : 'direct',		
                'Nobr.hyphen_nowrap_in_small_words' : 'direct',
                'Nobr.hyphen_nowrap' : 'direct',
                'Nobr.nowrap' : {'description' : 'Nobr (по умолчанию) & nowrap', 'disabled' : True, 'selector' : '*', 'setting' : 'nowrap' },
                
                'Symbol.tm_replace'     : 'direct',
                'Symbol.r_sign_replace' : 'direct',
                'Symbol.copy_replace' : 'direct',
                'Symbol.apostrophe' : 'direct',
                'Symbol.degree_f' : 'direct',
                'Symbol.arrows_symbols' : 'direct',
                'Symbol.no_inches' : { 'description' : 'Расстановка дюйма после числа', 'selector' : "Quote", 'setting' : 'no_inches', 'reversed' : True },
                
                'Punctmark.auto_comma' : 'direct',
                'Punctmark.hellip' : 'direct',
                'Punctmark.fix_pmarks' : 'direct',
                'Punctmark.fix_excl_quest_marks' : 'direct',
                'Punctmark.dot_on_end' : 'direct',
                
                'Number.minus_between_nums' : 'direct',
                'Number.minus_in_numbers_range' : 'direct',
                'Number.auto_times_x' : 'direct',
                'Number.simple_fraction' :'direct',
                'Number.math_chars' : 'direct',
                #'Number.split_number_to_triads' : 'direct',
                'Number.thinsp_between_number_triads' : 'direct',
                'Number.thinsp_between_no_and_number' : 'direct',
                'Number.thinsp_between_sect_and_number' : 'direct',
                
                'Date.years' : 'direct',
                'Date.mdash_month_interval' : 'direct',
                'Date.nbsp_and_dash_month_interval' : 'direct',
                'Date.nobr_year_in_date' : 'direct',
                
                'Space.many_spaces_to_one' : 'direct',	
                'Space.clear_percent' : 'direct',	
                'Space.clear_before_after_punct' : { 'description' : 'Удаление пробелов перед и после знаков препинания в предложении', 'selector' : 'Space.remove_space_before_punctuationmarks'},
                'Space.autospace_after' : { 'description' : 'Расстановка пробелов после знаков препинания', 'selector' : 'Space.autospace_after_*'},
                'Space.bracket_fix' : { 'description' : 'Удаление пробелов внутри скобок, а также расстановка пробела перед скобками', 
                                    'selector' : ['Space.nbsp_before_open_quote', 'Punctmark.fix_brackets']
                                },             

                'Abbr.nbsp_money_abbr' : { 'description' : 'Форматирование денежных сокращений (расстановка пробелов и привязка названия валюты к числу)', 
                                    'selector' : ['Abbr.nbsp_money_abbr', 'Abbr.nbsp_money_abbr_rev']
                                },    
                'Abbr.nobr_vtch_itd_itp' : 'direct',		
                'Abbr.nobr_sm_im' : 'direct',		
                'Abbr.nobr_acronym' : 'direct',		
                'Abbr.nobr_locations' : 'direct',		
                'Abbr.nobr_abbreviation' : 'direct',		
                'Abbr.ps_pps' : 'direct',		
                'Abbr.nbsp_org_abbr' : 'direct',		
                'Abbr.nobr_gost' : 'direct',		
                'Abbr.nobr_before_unit_volt' : 'direct',		
                'Abbr.nbsp_before_unit' : 'direct',		
                
                'OptAlign.all' : { 'description' : 'Inline стили или CSS', 'hide' : True, 'selector' : 'OptAlign.*'},
                'OptAlign.oa_oquote' : 'direct',	
                'OptAlign.oa_obracket_coma' : 'direct',	
                'OptAlign.layout' : { 'description' : 'Inline стили или CSS' },
                
                'Text.paragraphs' : 'direct',
                'Text.auto_links' : 'direct',
                'Text.email' : 'direct',
                'Text.breakline' : 'direct',
                'Text.no_repeat_words' : 'direct',
                
                
                #'Etc.no_nbsp_in_nobr' : 'direct',		
                'Etc.unicode_convert' : {'description' : 'Преобразовывать html-сущности в юникод', 'selector' : ['*', 'Etc.nobr_to_nbsp'], 'setting' : ['dounicode','active'], 'exact_selector' : True ,'disabled': True},
				'Etc.nobr_to_nbsp' : 'direct',
        }
        
        
    #/**
    # * Получить список имеющихся опций
    # *
    # * @return array
    # *     all    - полный список
    # *     group  - сгруппрованный по группам
    # */
    def get_options_list(self):
        arr = {}
        arr['all'] = []
        bygroup = {}
        for opt in self.all_options:
            arr['all'][opt] = self.get_option_info(opt)
            x = opt.split(".")
            bygroup[x[0]].append(opt)
            
        arr['group'] = []
        for group in self.group_list:
            ginfo = self.group_list[group]
            if isinstance(ginfo,bool) and ginfo:
                tret = self.get_tret(group)
                if tret:
                    info['title'] = self.title
                else:
                    info['title'] = "Не определено"
            else:
                info = ginfo
            info['name'] = group
            info['options'] = []
            if isinstance(bygroup[group] , (list, tuple)):
                for opt in bygroup[group]:
                    info['options'].append(opt)
            arr['group'].append(info)
        return arr
    
    
    #/**
    # * Получить информацию о настройке
    # *
    # * @param string $key
    # * @return array|False
    # */
    def get_option_info(self, key):
        if not self.all_options.has_key(key):
            return False
        if isinstance(self.all_options[key], (list,tuple,dict)):
            return self.all_options[key]
        
        if self.all_options[key] == "direct" or self.all_options[key] == "reverse":
            pa = key.split(".")
            tret_pattern = pa[0]
            tret = self.get_tret(tret_pattern)
            if not tret:
                return False		
            if not tret.rules.has_key(pa[1]):
                return False
            array = tret.rules[pa[1]]
            array['way'] = self.all_options[key]
            return array
        return False		
    
    
    # /**
    # * Установка одной метанастройки
    # *
    # * @param string $name
    # * @param mixed $value
    # */
    def do_setup(self, name, value):
        if not self.all_options.has_key(name):
            return
        
        # эта настрока связана с правилом ядра
        if isinstance(self.all_options[name], basestring):
                self.set(name, "active", value)
                return 
        if isinstance(self.all_options[name], dict):
            if self.all_options[name].has_key('selector'):
                settingname = "active"
                if self.all_options[name].has_key('setting'):
                    settingname = self.all_options[name]['setting']
                self.set(self.all_options[name]['selector'], settingname, value, self.all_options[name].get('exact_selector'))
        
        if name == "OptAlign.layout":
            if value == "style":
                self.set_tag_layout(LAYOUT_STYLE)
            if value == "class":
                self.set_tag_layout(LAYOUT_CLASS)
    
    #/**
    # * Запустить типограф со стандартными параметрами
    # *
    # * @param string $text
    # * @param array $options
    # * @return string
    # */
    def fast_apply(self, text, options = None):
        if isinstance(options, dict):
            self.setup(options)
        self.set_text(text)
        return self.apply()


#EMT = EMTypograph()
#EMT.debug_enabled = True
#EMT.logging = True
#print EMT.fast_apply("the (tm) x")
#print EMT.debug_info
#print EMT.logs
