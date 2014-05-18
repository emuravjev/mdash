#!/usr/bin/env python2.7
# -*- coding: utf-8 -*-

###################################################
##  Evgeny Muravjev Typograph, http://mdash.ru   ##
##  Version: 3.3-py (beta)                       ##
##  Release Date: May 4, 2014                    ##
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
        '"'     : {'html' : {'&laquo;', '&raquo;', '&ldquo;', '&lsquo;', '&bdquo;', '&ldquo;', '&quot;', '&#171;', '&#187;'},
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
            text = re.sub('(\</?)(.+?)(\>)', lambda m: m.group(1)+(u"%%___" if m.group(2).strip()[0:1] == u'a' else u"") + EMT_Lib.encrypt_tag(m.group(2).strip()) + m.group(3), text, 0, re.S |re.U)        
        else:
             #OK:
            text = re.sub('(\</?)(.+?)(\>)', lambda m: m.group(1)+(EMT_Lib.decrypt_tag(m.group(2).strip()[4:]) if m.group(2).strip()[0:3] == u'%%___' else EMT_Lib.decrypt_tag(m.group(2).strip())) + m.group(3), text, 0, re.S|re.U)        
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
        return unicode(base64.b64encode(text.encode('utf-8'))) #TODO


# Метод, осуществляющий декодирование информации
#
# @param     string $text
# @return     string
#/
    def decrypt_tag(self, text):
        return unicode(base64.b64decode(text).decode('utf-8')) #TODO


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
            return
        #pattern = preg_quote(pattern , '/') #TODO 
        pattern = pattern.replace("*", "[a-z0-9_\-]*") #TODO 
        return pattern

    def test_pattern(self, pattern, text):
        if(pattern == False):
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
        if(entity in html4_char_ents):
            return EMT_Lib.getUnicodeChar(html4_char_ents[entity])
        
        return False


# Сконвериторвать все html entity в соответсвующие юникод символы
#
# @param string $text
#/
    def convert_html_entities_to_unicode(self, text):  #TODO: &$text - '&' couldn't work
        text = re.sub("\&#([0-9]+)\;", 
                lambda m: EMT_Lib.getUnicodeChar(int(m.group(1)))
                , text) #TODO
        text = re.sub("\&#x([0-9A-F]+)\;", 
                lambda m: EMT_Lib.getUnicodeChar(int(m.group(1),16))
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
    
EMT_Lib = _EMT_Lib()

BASE64_PARAGRAPH_TAG = 'cA==' 
BASE64_BREAKLINE_TAG = 'YnIgLw==' 
BASE64_NOBR_OTAG = 'bm9icg==' 
BASE64_NOBR_CTAG = 'L25vYnI=' 

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





#######################################################
# EMT_Tret_Quote
#######################################################
class EMT_Tret_Quote(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Кавычки"

        self.rules = {
            u"quotes_outside_a": {
                u"description": u"Кавычки вне тэга <a>",
                u"pattern": u"/(\\<%%\\_\\_[^\\>]+\\>)\\\"(.+?)\\\"(\\<\\/%%\\_\\_[^\\>]+\\>)/s",
                u"replacement": u"\"\\1\\2\\3\""
            },
            u"open_quote": {
                u"description": u"Открывающая кавычка",
                u"pattern": u"/(^|\\(|\\s|\\>|-)(\\\"|\\\\\")(\\S+)/iue",
                u"replacement": u"m.group(1) + QUOTE_FIRS_OPEN + m.group(3)"
            },
            u"close_quote": {
                u"description": u"Закрывающая кавычка",
                u"pattern": u"/([a-zа-яё0-9]|\\.|\\&hellip\\;|\\!|\\?|\\>|\\)|\\:)((\\\"|\\\\\")+)(\\.|\\&hellip\\;|\\;|\\:|\\?|\\!|\\,|\\s|\\)|\\<\\/|$)/uie",
                u"replacement": u"m.group(1) + QUOTE_FIRS_CLOSE * ( m.group(2).count(u\"\\\"\") ) + m.group(4)"
            },
            u"close_quote_adv": {
                u"description": u"Закрывающая кавычка особые случаи",
                u"pattern": [
                    u"/([a-zа-яё0-9]|\\.|\\&hellip\\;|\\!|\\?|\\>|\\)|\\:)((\\\"|\\\\\"|\\&laquo\\;)+)(\\<[^\\>]+\\>)(\\.|\\&hellip\\;|\\;|\\:|\\?|\\!|\\,|\\)|\\<\\/|$| )/uie",
                    u"/([a-zа-яё0-9]|\\.|\\&hellip\\;|\\!|\\?|\\>|\\)|\\:)(\\s+)((\\\"|\\\\\")+)(\\s+)(\\.|\\&hellip\\;|\\;|\\:|\\?|\\!|\\,|\\)|\\<\\/|$| )/uie",
                    u"/\\>(\\&laquo\\;)\\.($|\\s|\\<)/ui",
                    u"/\\>(\\&laquo\\;),($|\\s|\\<|\\S)/ui"
                ],
                u"replacement": [
                    u"m.group(1) + QUOTE_FIRS_CLOSE * ( m.group(2).count(u\"\\\"\")+m.group(2).count(u\"&laquo;\") ) + m.group(4)+ m.group(5)",
                    u"m.group(1) +m.group(2)+ QUOTE_FIRS_CLOSE * ( m.group(3).count(u\"\\\"\")+m.group(3).count(u\"&laquo;\") ) + m.group(5)+ m.group(6)",
                    u">&raquo;.\\2",
                    u">&raquo;,\\2"
                ]
            },
            u"open_quote_adv": {
                u"description": u"Открывающая кавычка особые случаи",
                u"pattern": u"/(^|\\(|\\s|\\>)(\\\"|\\\\\")(\\s)(\\S+)/iue",
                u"replacement": u"m.group(1) + QUOTE_FIRS_OPEN +m.group(4)"
            },
            u"quotation": {
                u"description": u"Внутренние кавычки-лапки и дюймы",
                u"function": u"build_sub_quotations"
            }
        }
        self.rule_order = [
            u"quotes_outside_a",
            u"open_quote",
            u"close_quote",
            u"close_quote_adv",
            u"open_quote_adv",
            u"quotation"
        ]


    def inject_in(self, pos, text):
    	self._text = (self._text[0:pos] if pos>0 else u'') + text + self._text[pos+len(text):]
    	return
        i = 0
        
        while i < len(text):
            self._text[pos+i] = text[i]
            i += 1
    
    def build_sub_quotations(self):
        global __ax,__ay
        
        okposstack = [0]
        okpos = 0
        level = 0
        off = 0
        while True:
            p = EMT_Lib.strpos_ex(self._text, ["&laquo;", "&raquo;"], off)
            
            if isinstance(p, bool) and (p == False):
                break
            if (p['str'] == "&laquo;"):
                if (level>0) and (not self.is_on('no_bdquotes')):
                    self.inject_in(p['pos'], QUOTE_CRAWSE_OPEN) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
                level += 1;
                
            if (p['str'] == "&raquo;"):
                level -= 1    
                if (level>0) and (not self.is_on('no_bdquotes')):
                    self.inject_in(p['pos'], QUOTE_CRAWSE_CLOSE) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
            
            off = p['pos'] + len(p['str'])

            if(level == 0): 
                okpos = off
                okposstack.append(okpos)
                
            elif (level<0): # // уровень стал меньше нуля
                if(not self.is_on('no_inches')):

                    while (True):
                        lokpos = okposstack.pop(len(okposstack)-1)
                        k = EMT_Lib.substr(self._text,  lokpos, off - lokpos)
                        k = EMT_Lib.str_replace(QUOTE_CRAWSE_OPEN, QUOTE_FIRS_OPEN, k)
                        k = EMT_Lib.str_replace(QUOTE_CRAWSE_CLOSE, QUOTE_FIRS_CLOSE, k)
                        #//$k = preg_replace("/(^|[^0-9])([0-9]+)\&raquo\;/ui", '\1\2&Prime;', $k, 1, $amount);
                        
                        amount = 0
                        m = re.findall("(^|[^0-9])([0-9]+)\&raquo\;", k, re.I | re.U)
                        __ax = len(m)
                        __ay = 0
                        if(__ax):
                            def quote_extra_replace_function(m):
                                global __ax,__ay
                                __ay+=1
                                if __ay==__ax:
                                    return m.group(1)+m.group(2)+"&Prime;"
                                return m.group(0)
                            
                            k = re.sub("(^|[^0-9])([0-9]+)\&raquo\;",                                 
                                    quote_extra_replace_function,
                                k, 0, re.I | re.U);
                            amount = 1
                        
                        if not ((amount==0) and len(okposstack)):
                            break
                    
                    
                    #// успешно сделали замену
                    if (amount == 1):
                        #// заново просмотрим содержимое                                
                        self._text = EMT_Lib.substr(self._text, 0, lokpos) + k + EMT_Lib.substr(self._text, off)
                        off = lokpos
                        level = 0
                        continue
                    
                    #// иначе просто заменим последнюю явно на &quot; от отчаяния
                    if (amount == 0):
                        #// говорим, что всё в порядке
                        level = 0
                        self._text = EMT_Lib.substr(self._text, 0, p['pos']) + '&quot;' + EMT_Lib.substr(self._text, off)
                        off = p['pos'] + len('&quot;')
                        okposstack = [off]
                        continue
                    
        #// не совпало количество, отменяем все подкавычки
        if (level != 0 ):
            
            #// закрывающих меньше, чем надо
            if (level>0):
                k = EMT_Lib.substr(self._text, okpos)
                k = EMT_Lib.str_replace(QUOTE_CRAWSE_OPEN, QUOTE_FIRS_OPEN, k)
                k = EMT_Lib.str_replace(QUOTE_CRAWSE_CLOSE, QUOTE_FIRS_CLOSE, k)
                self._text = EMT_Lib.substr(self._text, 0, okpos) + k
         




#######################################################
# EMT_Tret_Dash
#######################################################
class EMT_Tret_Dash(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Дефисы и тире"

        self.rules = {
            u"mdash_symbol_to_html_mdash": {
                u"description": u"Замена символа тире на html конструкцию",
                u"pattern": u"/—|--/iu",
                u"replacement": u"&mdash;"
            },
            u"mdash": {
                u"description": u"Тире после кавычек, скобочек, пунктуации",
                u"pattern": [
                    u"/([a-zа-яё0-9]+|\\,|\\:|\\)|\\&(ra|ld)quo\\;|\\|\\\"|\\>)(\\040|\\t)(—|\\-|\\&mdash\\;)(\\s|$|\\<)/ui",
                    u"/(\\,|\\:|\\)|\\\")(—|\\-|\\&mdash\\;)(\\s|$|\\<)/ui"
                ],
                u"replacement": [
                    u"\\1&nbsp;&mdash;\\5",
                    u"\\1&nbsp;&mdash;\\3"
                ]
            },
            u"mdash_2": {
                u"description": u"Тире после переноса строки",
                u"pattern": u"/(\\n|\\r|^|\\>)(\\-|\\&mdash\\;)(\\t|\\040)/",
                u"replacement": u"\\1&mdash;&nbsp;"
            },
            u"mdash_3": {
                u"description": u"Тире после знаков восклицания, троеточия и прочее",
                u"pattern": u"/(\\.|\\!|\\?|\\&hellip\\;)(\\040|\\t|\\&nbsp\\;)(\\-|\\&mdash\\;)(\\040|\\t|\\&nbsp\\;)/",
                u"replacement": u"\\1 &mdash;&nbsp;"
            },
            u"iz_za_pod": {
                u"description": u"Расстановка дефисов между из-за, из-под",
                u"pattern": u"/(\\s|\\&nbsp\\;|\\>|^)(из)(\\040|\\t|\\&nbsp\\;)\\-?(за|под)([\\.\\,\\!\\?\\:\\;]|\\040|\\&nbsp\\;)/uie",
                u"replacement": u"(( u\" \"  if m.group(1) == u\"&nbsp;\"  else  m.group(1))) + m.group(2)+u\"-\"+m.group(4) + (( u\" \"  if m.group(5) == u\"&nbsp;\" else  m.group(5)))"
            },
            u"to_libo_nibud": {
                u"description": u"Автоматическая простановка дефисов в обезличенных местоимениях и междометиях",
                u"cycled": True,
                u"pattern": u"/(\\s|^|\\&nbsp\\;|\\>)(кто|кем|когда|зачем|почему|как|что|чем|где|чего|кого)\\-?(\\040|\\t|\\&nbsp\\;)\\-?(то|либо|нибудь)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie",
                u"replacement": u"(( u\" \"  if m.group(1) == u\"&nbsp;\"  else  m.group(1))) + m.group(2)+u\"-\"+m.group(4) + (( u\" \"  if m.group(5) == u\"&nbsp;\" else  m.group(5)))"
            },
            u"koe_kak": {
                u"description": u"Кое-как, кой-кого, все-таки",
                u"cycled": True,
                u"pattern": [
                    u"/(\\s|^|\\&nbsp\\;|\\>)(кое)\\-?(\\040|\\t|\\&nbsp\\;)\\-?(как)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie",
                    u"/(\\s|^|\\&nbsp\\;|\\>)(кой)\\-?(\\040|\\t|\\&nbsp\\;)\\-?(кого)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie",
                    u"/(\\s|^|\\&nbsp\\;|\\>)(вс[её])\\-?(\\040|\\t|\\&nbsp\\;)\\-?(таки)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie"
                ],
                u"replacement": u"(( u\" \"  if m.group(1) == u\"&nbsp;\"  else  m.group(1))) + m.group(2)+u\"-\"+m.group(4) + (( u\" \"  if m.group(5) == u\"&nbsp;\" else  m.group(5)))"
            },
            u"ka_de_kas": {
                u"description": u"Расстановка дефисов с частицами ка, де, кась",
                u"disabled": True,
                u"pattern": [
                    u"/(\\s|^|\\&nbsp\\;|\\>)([а-яё]+)(\\040|\\t|\\&nbsp\\;)(ка)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie",
                    u"/(\\s|^|\\&nbsp\\;|\\>)([а-яё]+)(\\040|\\t|\\&nbsp\\;)(де)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie",
                    u"/(\\s|^|\\&nbsp\\;|\\>)([а-яё]+)(\\040|\\t|\\&nbsp\\;)(кась)([\\.\\,\\!\\?\\;]|\\040|\\&nbsp\\;|$)/uie"
                ],
                u"replacement": u"(( u\" \"  if m.group(1) == u\"&nbsp;\"  else  m.group(1))) + m.group(2)+u\"-\"+m.group(4) + (( u\" \"  if m.group(5) == u\"&nbsp;\" else  m.group(5)))"
            }
        }
        self.rule_order = [
            u"mdash_symbol_to_html_mdash",
            u"mdash",
            u"mdash_2",
            u"mdash_3",
            u"iz_za_pod",
            u"to_libo_nibud",
            u"koe_kak",
            u"ka_de_kas"
        ]


#######################################################
# EMT_Tret_Symbol
#######################################################
class EMT_Tret_Symbol(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Специальные символы"

        self.rules = {
            u"tm_replace": {
                u"description": u"Замена (tm) на символ торговой марки",
                u"pattern": u"/([\\040\\t])?\\(tm\\)/i",
                u"replacement": u"&trade;"
            },
            u"r_sign_replace": {
                u"description": u"Замена (R) на символ зарегистрированной торговой марки",
                u"pattern": [
                    u"/(.|^)\\(r\\)(.|$)/ie"
                ],
                u"replacement": [
                    u"m.group(1)+u\"&reg;\"+m.group(2)"
                ]
            },
            u"copy_replace": {
                u"description": u"Замена (c) на символ копирайт",
                u"pattern": [
                    u"/\\((c|с)\\)\\s+/iu",
                    u"/\\((c|с)\\)($|\\.|,|!|\\?)/iu"
                ],
                u"replacement": [
                    u"&copy;&nbsp;",
                    u"&copy;\\2"
                ]
            },
            u"apostrophe": {
                u"description": u"Расстановка правильного апострофа в текстах",
                u"pattern": u"/(\\s|^|\\>|\\&rsquo\\;)([a-zа-яё]{1,})\'([a-zа-яё]+)/ui",
                u"replacement": u"\\1\\2&rsquo;\\3",
                u"cycled": True
            },
            u"degree_f": {
                u"description": u"Градусы по Фаренгейту",
                u"pattern": u"/([0-9]+)F($|\\s|\\.|\\,|\\;|\\:|\\&nbsp\\;|\\?|\\!)/eu",
                u"replacement": u"u\"\"+self.tag(m.group(1)+u\" &deg;F\",u\"span\", {u\"class\":u\"nowrap\"}) +m.group(2)"
            },
            u"euro_symbol": {
                u"description": u"Символ евро",
                u"simple_replace": True,
                u"pattern": u"€",
                u"replacement": u"&euro;"
            },
            u"arrows_symbols": {
                u"description": u"Замена стрелок вправо-влево на html коды",
                u"pattern": [
                    u"/(\\s|\\>|\\&nbsp\\;|^)\\-\\>($|\\s|\\&nbsp\\;|\\<)/",
                    u"/(\\s|\\>|\\&nbsp\\;|^|;)\\<\\-(\\s|\\&nbsp\\;|$)/",
                    u"/→/u",
                    u"/←/u"
                ],
                u"replacement": [
                    u"\\1&rarr;\\2",
                    u"\\1&larr;\\2",
                    u"&rarr;",
                    u"&larr;"
                ]
            }
        }
        self.rule_order = [
            u"tm_replace",
            u"r_sign_replace",
            u"copy_replace",
            u"apostrophe",
            u"degree_f",
            u"euro_symbol",
            u"arrows_symbols"
        ]
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


#######################################################
# EMT_Tret_Punctmark
#######################################################
class EMT_Tret_Punctmark(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Пунктуация и знаки препинания"

        self.rules = {
            u"auto_comma": {
                u"description": u"Расстановка запятых перед а, но",
                u"pattern": u"/([a-zа-яё])(\\s|&nbsp;)(но|а)(\\s|&nbsp;)/iu",
                u"replacement": u"\\1,\\2\\3\\4"
            },
            u"punctuation_marks_limit": {
                u"description": u"Лишние восклицательные, вопросительные знаки и точки",
                u"pattern": u"/([\\!\\.\\?]){4,}/",
                u"replacement": u"\\1\\1\\1"
            },
            u"punctuation_marks_base_limit": {
                u"description": u"Лишние запятые, двоеточия, точки с запятой",
                u"pattern": u"/([\\,]|[\\:]|[\\;]]){2,}/",
                u"replacement": u"\\1"
            },
            u"hellip": {
                u"description": u"Замена трех точек на знак многоточия",
                u"simple_replace": True,
                u"pattern": u"...",
                u"replacement": u"&hellip;"
            },
            u"fix_excl_quest_marks": {
                u"description": u"Замена восклицательного и вопросительного знаков местами",
                u"pattern": u"/([a-zа-яё0-9])\\!\\?(\\s|$|\\<)/ui",
                u"replacement": u"\\1?!\\2"
            },
            u"fix_pmarks": {
                u"description": u"Замена сдвоенных знаков препинания на одинарные",
                u"pattern": [
                    u"/([^\\!\\?])\\.\\./",
                    u"/([a-zа-яё0-9])(\\!|\\.)(\\!|\\.|\\?)(\\s|$|\\<)/ui",
                    u"/([a-zа-яё0-9])(\\?)(\\?)(\\s|$|\\<)/ui"
                ],
                u"replacement": [
                    u"\\1.",
                    u"\\1\\2\\4",
                    u"\\1\\2\\4"
                ]
            },
            u"fix_brackets": {
                u"description": u"Лишние пробелы после открывающей скобочки и перед закрывающей",
                u"pattern": [
                    u"/(\\()(\\040|\\t)+/",
                    u"/(\\040|\\t)+(\\))/"
                ],
                u"replacement": [
                    u"\\1",
                    u"\\2"
                ]
            },
            u"fix_brackets_space": {
                u"description": u"Пробел перед открывающей скобочкой",
                u"pattern": u"/([a-zа-яё0-9])(\\()/iu",
                u"replacement": u"\\1 \\2"
            },
            u"dot_on_end": {
                u"description": u"Точка в конце текста, если её там нет",
                u"disabled": True,
                u"pattern": u"/([a-zа-яё0-9])(\\040|\\t|\\&nbsp\\;)*$/ui",
                u"replacement": u"\\1."
            }
        }
        self.rule_order = [
            u"auto_comma",
            u"punctuation_marks_limit",
            u"punctuation_marks_base_limit",
            u"hellip",
            u"fix_excl_quest_marks",
            u"fix_pmarks",
            u"fix_brackets",
            u"fix_brackets_space",
            u"dot_on_end"
        ]


#######################################################
# EMT_Tret_Number
#######################################################
class EMT_Tret_Number(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Числа, дроби, математические знаки"

        self.rules = {
            u"minus_between_nums": {
                u"description": u"Расстановка знака минус между числами",
                u"pattern": u"/(\\d+)\\-(\\d)/i",
                u"replacement": u"\\1&minus;\\2"
            },
            u"minus_in_numbers_range": {
                u"description": u"Расстановка знака минус между диапозоном чисел",
                u"pattern": u"/(^|\\s|\\&nbsp\\;)(\\&minus\\;|\\-)(\\d+)(\\.\\.\\.|\\&hellip\\;)(\\s|\\&nbsp\\;)?(\\+|\\-|\\&minus\\;)?(\\d+)/ie",
                u"replacement": u"m.group(1) +u\"&minus;\"+m.group(3) + m.group(4)+m.group(5)+((m.group(6) if m.group(6)==u\"+\" else u\"&minus;\"))+m.group(7)"
            },
            u"auto_times_x": {
                u"description": u"Замена x на символ × в размерных единицах",
                u"cycled": True,
                u"pattern": u"/([^a-zA-Z><]|^)(\\&times\\;)?(\\d+)(\\040*)(x|х)(\\040*)(\\d+)([^a-zA-Z><]|$)/u",
                u"replacement": u"\\1\\2\\3&times;\\7\\8"
            },
            u"numeric_sub": {
                u"description": u"Нижний индекс",
                u"pattern": u"/([a-zа-яё0-9])\\_([\\d]{1,3})([^а-яёa-z0-9]|$)/ieu",
                u"replacement": u"m.group(1) + self.tag(self.tag(m.group(2),u\"small\"),u\"sub\") + m.group(3)"
            },
            u"numeric_sup": {
                u"description": u"Верхний индекс",
                u"pattern": u"/([a-zа-яё0-9])\\^([\\d]{1,3})([^а-яёa-z0-9]|$)/ieu",
                u"replacement": u"m.group(1) + self.tag(self.tag(m.group(2),u\"small\"),u\"sup\") + m.group(3)"
            },
            u"simple_fraction": {
                u"description": u"Замена дробей 1/2, 1/4, 3/4 на соответствующие символы",
                u"pattern": [
                    u"/(^|\\D)1\\/(2|4)(\\D)/",
                    u"/(^|\\D)3\\/4(\\D)/"
                ],
                u"replacement": [
                    u"\\1&frac1\\2;\\3",
                    u"\\1&frac34;\\2"
                ]
            },
            u"math_chars": {
                u"description": u"Математические знаки больше/меньше/плюс минус/неравно",
                u"simple_replace": True,
                u"pattern": [
                    u"!=",
                    u"<=",
                    u">=",
                    u"~=",
                    u"+-"
                ],
                u"replacement": [
                    u"&ne;",
                    u"&le;",
                    u"&ge;",
                    u"&cong;",
                    u"&plusmn;"
                ]
            },
            u"thinsp_between_number_triads": {
                u"description": u"Объединение триад чисел полупробелом",
                u"pattern": u"/([0-9]{1,3}( [0-9]{3}){1,})(.|$)/ue",
                u"replacement": u"(( m.group(0) if m.group(3)==u\"-\" else EMT_Lib.str_replace(u\" \",u\"&thinsp;\",m.group(1))+m.group(3)))"
            },
            u"thinsp_between_no_and_number": {
                u"description": u"Пробел между симоволом номера и числом",
                u"pattern": u"/(№|\\&#8470\\;)(\\s|&nbsp;)*(\\d)/iu",
                u"replacement": u"&#8470;&thinsp;\\3"
            },
            u"thinsp_between_sect_and_number": {
                u"description": u"Пробел между параграфом и числом",
                u"pattern": u"/(§|\\&sect\\;)(\\s|&nbsp;)*(\\d+|[IVX]+|[a-zа-яё]+)/ui",
                u"replacement": u"&sect;&thinsp;\\3"
            }
        }
        self.rule_order = [
            u"minus_between_nums",
            u"minus_in_numbers_range",
            u"auto_times_x",
            u"numeric_sub",
            u"numeric_sup",
            u"simple_fraction",
            u"math_chars",
            u"thinsp_between_number_triads",
            u"thinsp_between_no_and_number",
            u"thinsp_between_sect_and_number"
        ]


#######################################################
# EMT_Tret_Space
#######################################################
class EMT_Tret_Space(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Расстановка и удаление пробелов"

        self.rules = {
            u"nobr_twosym_abbr": {
                u"description": u"Неразрывный перед 2х символьной аббревиатурой",
                u"pattern": u"/([a-zA-Zа-яёА-ЯЁ])(\\040|\\t)+([A-ZА-ЯЁ]{2})([\\s\\;\\.\\?\\!\\:\\(\\\"]|\\&(ra|ld)quo\\;|$)/u",
                u"replacement": u"\\1&nbsp;\\3\\4"
            },
            u"remove_space_before_punctuationmarks": {
                u"description": u"Удаление пробела перед точкой, запятой, двоеточием, точкой с запятой",
                u"pattern": u"/((\\040|\\t|\\&nbsp\\;)+)([\\,\\:\\.\\;\\?])(\\s+|$)/",
                u"replacement": u"\\3\\4"
            },
            u"autospace_after_comma": {
                u"description": u"Пробел после запятой",
                u"pattern": [
                    u"/(\\040|\\t|\\&nbsp\\;)\\,([а-яёa-z0-9])/iu",
                    u"/([^0-9])\\,([а-яёa-z0-9])/iu"
                ],
                u"replacement": [
                    u", \\2",
                    u"\\1, \\2"
                ]
            },
            u"autospace_after_pmarks": {
                u"description": u"Пробел после знаков пунктуации, кроме точки",
                u"pattern": u"/(\\040|\\t|\\&nbsp\\;|^|\\n)([a-zа-яё0-9]+)(\\040|\\t|\\&nbsp\\;)?(\\:|\\)|\\,|\\&hellip\\;|(?:\\!|\\?)+)([а-яёa-z])/iu",
                u"replacement": u"\\1\\2\\4 \\5"
            },
            u"autospace_after_dot": {
                u"description": u"Пробел после точки",
                u"pattern": [
                    u"/(\\040|\\t|\\&nbsp\\;|^)([a-zа-яё0-9]+)(\\040|\\t|\\&nbsp\\;)?\\.([а-яёa-z]{4,})/iu",
                    u"/(\\040|\\t|\\&nbsp\\;|^)([a-zа-яё0-9]+)\\.([а-яёa-z]{1,3})/iue"
                ],
                u"replacement": [
                    u"\\1\\2. \\4",
                    u"m.group(1)+m.group(2)+u\".\" +(( u\"\" if EMT_Lib.strtolower(m.group(3)) in ( self.domain_zones) else u\" \"))+ m.group(3)"
                ]
            },
            u"autospace_after_hellips": {
                u"description": u"Пробел после знаков троеточий с вопросительным или восклицательными знаками",
                u"pattern": u"/([\\?\\!]\\.\\.)([а-яёa-z])/iu",
                u"replacement": u"\\1 \\2"
            },
            u"many_spaces_to_one": {
                u"description": u"Удаление лишних пробельных символов и табуляций",
                u"pattern": u"/(\\040|\\t)+/",
                u"replacement": u" "
            },
            u"clear_percent": {
                u"description": u"Удаление пробела перед символом процента",
                u"pattern": u"/(\\d+)([\\t\\040]+)\\%/",
                u"replacement": u"\\1%"
            },
            u"nbsp_before_open_quote": {
                u"description": u"Неразрывный пробел перед открывающей скобкой",
                u"pattern": u"/(^|\\040|\\t|>)([a-zа-яё]{1,2})\\040(\\&laquo\\;|\\&bdquo\\;)/u",
                u"replacement": u"\\1\\2&nbsp;\\3"
            },
            u"nbsp_before_month": {
                u"description": u"Неразрывный пробел в датах перед числом и месяцем",
                u"pattern": u"/(\\d)(\\s)+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)([^\\<]|$)/iu",
                u"replacement": u"\\1&nbsp;\\3\\4"
            },
            u"spaces_on_end": {
                u"description": u"Удаление пробелов в конце текста",
                u"pattern": u"/ +$/",
                u"replacement": u""
            },
            u"no_space_posle_hellip": {
                u"description": u"Отсутстввие пробела после троеточия после открывающей кавычки",
                u"pattern": u"/(\\&laquo\\;|\\&bdquo\\;)( |\\&nbsp\\;)?\\&hellip\\;( |\\&nbsp\\;)?([a-zа-яё])/ui",
                u"replacement": u"\\1&hellip;\\4"
            },
            u"space_posle_goda": {
                u"description": u"Пробел после года",
                u"pattern": u"/(^|\\040|\\&nbsp\\;)([0-9]{3,4})(год([ауе]|ом)?)([^a-zа-яё]|$)/ui",
                u"replacement": u"\\1\\2 \\3\\5"
            }
        }
        self.rule_order = [
            u"nobr_twosym_abbr",
            u"remove_space_before_punctuationmarks",
            u"autospace_after_comma",
            u"autospace_after_pmarks",
            u"autospace_after_dot",
            u"autospace_after_hellips",
            u"many_spaces_to_one",
            u"clear_percent",
            u"nbsp_before_open_quote",
            u"nbsp_before_month",
            u"spaces_on_end",
            u"no_space_posle_hellip",
            u"space_posle_goda"
        ]
        self.domain_zones = [
            u"ru",
            u"ру",
            u"com",
            u"ком",
            u"org",
            u"орг",
            u"уа",
            u"ua"
        ];
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


#######################################################
# EMT_Tret_Abbr
#######################################################
class EMT_Tret_Abbr(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Сокращения"

        self.rules = {
            u"nobr_abbreviation": {
                u"description": u"Расстановка пробелов перед сокращениями dpi, lpi",
                u"pattern": u"/(\\s+|^|\\>)(\\d+)(\\040|\\t)*(dpi|lpi)([\\s\\;\\.\\?\\!\\:\\(]|$)/i",
                u"replacement": u"\\1\\2&nbsp;\\4\\5"
            },
            u"nobr_acronym": {
                u"description": u"Расстановка пробелов перед сокращениями гл., стр., рис., илл., ст., п.",
                u"pattern": u"/(\\s|^|\\>|\\()(гл|стр|рис|илл?|ст|п|с)\\.(\\040|\\t)*(\\d+)(\\&nbsp\\;|\\s|\\.|\\,|\\?|\\!|$)/iu",
                u"replacement": u"\\1\\2.&nbsp;\\4\\5"
            },
            u"nobr_sm_im": {
                u"description": u"Расстановка пробелов перед сокращениями см., им.",
                u"pattern": u"/(\\s|^|\\>|\\()(см|им)\\.(\\040|\\t)*([а-яё0-9a-z]+)(\\s|\\.|\\,|\\?|\\!|$)/iu",
                u"replacement": u"\\1\\2.&nbsp;\\4\\5"
            },
            u"nobr_locations": {
                u"description": u"Расстановка пробелов в сокращениях г., ул., пер., д.",
                u"pattern": [
                    u"/(\\s|^|\\>)(г|ул|пер|просп|пл|бул|наб|пр|ш|туп)\\.(\\040|\\t)*([а-яё0-9a-z]+)(\\s|\\.|\\,|\\?|\\!|$)/iu",
                    u"/(\\s|^|\\>)(б\\-р|пр\\-кт)(\\040|\\t)*([а-яё0-9a-z]+)(\\s|\\.|\\,|\\?|\\!|$)/iu",
                    u"/(\\s|^|\\>)(д|кв|эт)\\.(\\040|\\t)*(\\d+)(\\s|\\.|\\,|\\?|\\!|$)/iu"
                ],
                u"replacement": [
                    u"\\1\\2.&nbsp;\\4\\5",
                    u"\\1\\2&nbsp;\\4\\5",
                    u"\\1\\2.&nbsp;\\4\\5"
                ]
            },
            u"nbsp_before_unit": {
                u"description": u"Замена символов и привязка сокращений в размерных величинах: м, см, м2…",
                u"pattern": [
                    u"/(\\s|^|\\>|\\&nbsp\\;|\\,)(\\d+)( |\\&nbsp\\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)(\\s|\\.|\\!|\\?|\\,|$|\\&plusmn\\;|\\;)/iu",
                    u"/(\\s|^|\\>|\\&nbsp\\;|\\,)(\\d+)( |\\&nbsp\\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)([32]|&sup3;|&sup2;)(\\s|\\.|\\!|\\?|\\,|$|\\&plusmn\\;|\\;)/iue"
                ],
                u"replacement": [
                    u"\\1\\2&nbsp;\\4\\5",
                    u"m.group(1)+m.group(2)+u\"&nbsp;\"+m.group(4)+(( u\"&sup\"+m.group(5)+u\";\"  if m.group(5)==u\"3\" or m.group(5)==u\"2\" else  m.group(5) ))+m.group(6)"
                ]
            },
            u"nbsp_before_weight_unit": {
                u"description": u"Замена символов и привязка сокращений в весовых величинах: г, кг, мг…",
                u"pattern": u"/(\\s|^|\\>|\\&nbsp\\;|\\,)(\\d+)( |\\&nbsp\\;)?(г|кг|мг|т)(\\s|\\.|\\!|\\?|\\,|$|\\&nbsp\\;|\\;)/iu",
                u"replacement": u"\\1\\2&nbsp;\\4\\5"
            },
            u"nobr_before_unit_volt": {
                u"description": u"Установка пробельных символов в сокращении вольт",
                u"pattern": u"/(\\d+)([вВ]| В)(\\s|\\.|\\!|\\?|\\,|$)/u",
                u"replacement": u"\\1&nbsp;В\\3"
            },
            u"ps_pps": {
                u"description": u"Объединение сокращений P.S., P.P.S.",
                u"pattern": u"/(^|\\040|\\t|\\>|\\r|\\n)(p\\.\\040?)(p\\.\\040?)?(s\\.)([^\\<])/ie",
                u"replacement": u"m.group(1) + self.tag(m.group(2).strip() + u\" \" + (( m.group(3).strip() + u\" \"  if m.group(3)  else  u\"\"))+ m.group(4), u\"span\",  {u\"class\" : u\"nowrap\"} )+m.group(5) "
            },
            u"nobr_vtch_itd_itp": {
                u"description": u"Объединение сокращений и т.д., и т.п., в т.ч.",
                u"pattern": [
                    u"/(\\s|\\&nbsp\\;)и( |\\&nbsp\\;)т\\.?[ ]?д\\./ue",
                    u"/(\\s|\\&nbsp\\;)и( |\\&nbsp\\;)т\\.?[ ]?п\\./ue",
                    u"/(\\s|\\&nbsp\\;)в( |\\&nbsp\\;)т\\.?[ ]?ч\\./ue"
                ],
                u"replacement": [
                    u"m.group(1)+self.tag(u\"и т. д.\", u\"span\",  {u\"class\" : u\"nowrap\"})",
                    u"m.group(1)+self.tag(u\"и т. п.\", u\"span\",  {u\"class\" : u\"nowrap\"})",
                    u"m.group(1)+self.tag(u\"в т. ч.\", u\"span\",  {u\"class\" : u\"nowrap\"})"
                ]
            },
            u"nbsp_te": {
                u"description": u"Обработка т.е.",
                u"pattern": u"/(^|\\s|\\&nbsp\\;)([тТ])\\.?[ ]?е\\./ue",
                u"replacement": u"m.group(1)+self.tag(m.group(2)+u\". е.\", u\"span\",  {u\"class\" : u\"nowrap\"})"
            },
            u"nbsp_money_abbr": {
                u"description": u"Форматирование денежных сокращений (расстановка пробелов и привязка названия валюты к числу)",
                u"pattern": u"/(\\d)((\\040|\\&nbsp\\;)?(тыс|млн|млрд)\\.?(\\040|\\&nbsp\\;)?)?(\\040|\\&nbsp\\;)?(руб\\.|долл\\.|евро|€|&euro;|\\$|у[\\.]? ?е[\\.]?)/ieu",
                u"replacement": u"m.group(1)+(u\"&nbsp;\"+m.group(4)+(u\".\" if m.group(4)==u\"тыс\" else u\"\") if m.group(4) else u\"\")+u\"&nbsp;\"+(m.group(7) if not re.match(u\"у[\\\\.]? ?е[\\\\.]?\",m.group(7),re.I | re.U) else u\"у.е.\")",
                u"replacement_python": u"m.group(1)+(u\"&nbsp;\"+m.group(4)+(u\".\" if m.group(4)==u\"тыс\" else u\"\") if m.group(4) else u\"\")+u\"&nbsp;\"+(m.group(7) if not re.match(u\"у[\\\\.]? ?е[\\\\.]?\",m.group(7),re.I | re.U) else u\"у.е.\")"
            },
            u"nbsp_org_abbr": {
                u"description": u"Привязка сокращений форм собственности к названиям организаций",
                u"pattern": u"/([^a-zA-Zа-яёА-ЯЁ]|^)(ООО|ЗАО|ОАО|НИИ|ПБОЮЛ) ([a-zA-Zа-яёА-ЯЁ]|\\\"|\\&laquo\\;|\\&bdquo\\;|<)/u",
                u"replacement": u"\\1\\2&nbsp;\\3"
            },
            u"nobr_gost": {
                u"description": u"Привязка сокращения ГОСТ к номеру",
                u"pattern": [
                    u"/(\\040|\\t|\\&nbsp\\;|^)ГОСТ( |\\&nbsp\\;)?(\\d+)((\\-|\\&minus\\;|\\&mdash\\;)(\\d+))?(( |\\&nbsp\\;)(\\-|\\&mdash\\;))?/ieu",
                    u"/(\\040|\\t|\\&nbsp\\;|^|\\>)ГОСТ( |\\&nbsp\\;)?(\\d+)(\\-|\\&minus\\;|\\&mdash\\;)(\\d+)/ieu"
                ],
                u"replacement": [
                    u"m.group(1)+self.tag(u\"ГОСТ \"+m.group(3)+((u\"&ndash;\"+m.group(6) if (m.group(6)) else u\"\"))+((u\" &mdash;\" if (m.group(7)) else u\"\")),u\"span\", {u\"class\":u\"nowrap\"})",
                    u"m.group(1)+u\"ГОСТ \"+m.group(3)+u\"&ndash;\"+m.group(5)"
                ]
            }
        }
        self.rule_order = [
            u"nobr_abbreviation",
            u"nobr_acronym",
            u"nobr_sm_im",
            u"nobr_locations",
            u"nbsp_before_unit",
            u"nbsp_before_weight_unit",
            u"nobr_before_unit_volt",
            u"ps_pps",
            u"nobr_vtch_itd_itp",
            u"nbsp_te",
            u"nbsp_money_abbr",
            u"nbsp_org_abbr",
            u"nobr_gost"
        ]
        self.domain_zones = [
            u"ru",
            u"ру",
            u"com",
            u"ком",
            u"org",
            u"орг",
            u"уа",
            u"ua"
        ];
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


#######################################################
# EMT_Tret_Nobr
#######################################################
class EMT_Tret_Nobr(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Неразрывные конструкции"

        self.rules = {
            u"super_nbsp": {
                u"description": u"Привязка союзов и предлогов к написанным после словам",
                u"pattern": u"/(\\s|^|\\&(la|bd)quo\\;|\\>|\\(|\\&mdash\\;\\&nbsp\\;)([a-zа-яё]{1,2}\\s+)([a-zа-яё]{1,2}\\s+)?([a-zа-яё0-9\\-]{2,}|[0-9])/ieu",
                u"replacement": u"m.group(1) + m.group(3).strip() + u\"&nbsp;\" + (( m.group(4).strip() + u\"&nbsp;\"  if m.group(4)  else  u\"\")) + m.group(5)"
            },
            u"nbsp_in_the_end": {
                u"description": u"Привязка союзов и предлогов к предыдущим словам в случае конца предложения",
                u"pattern": u"/([a-zа-яё0-9\\-]{3,}) ([a-zа-яё]{1,2})\\.( [A-ZА-ЯЁ]|$)/u",
                u"replacement": u"\\1&nbsp;\\2.\\3"
            },
            u"phone_builder": {
                u"description": u"Объединение в неразрывные конструкции номеров телефонов",
                u"pattern": [
                    u"/([^\\d\\+]|^)([\\+]?[0-9]{1,3})( |\\&nbsp\\;|\\&thinsp\\;)([0-9]{3,4}|\\([0-9]{3,4}\\))( |\\&nbsp\\;|\\&thinsp\\;)([0-9]{2,3})(-|\\&minus\\;)([0-9]{2})(-|\\&minus\\;)([0-9]{2})([^\\d]|$)/e",
                    u"/([^\\d\\+]|^)([\\+]?[0-9]{1,3})( |\\&nbsp\\;|\\&thinsp\\;)([0-9]{3,4}|[0-9]{3,4})( |\\&nbsp\\;|\\&thinsp\\;)([0-9]{2,3})(-|\\&minus\\;)([0-9]{2})(-|\\&minus\\;)([0-9]{2})([^\\d]|$)/e"
                ],
                u"replacement": [
                    u"m.group(1)  +(( m.group(2)+u\" \"+m.group(4)+u\" \"+m.group(6)+u\"-\"+m.group(8)+u\"-\"+m.group(10)  if (m.group(1) == u\">\"  or  m.group(11) == u\"<\")  else self.tag(m.group(2)+u\" \"+m.group(4)+u\" \"+m.group(6)+u\"-\"+m.group(8)+u\"-\"+m.group(10), u\"span\", {u\"class\":u\"nowrap\"})  ))+m.group(11)",
                    u"m.group(1)  +(( m.group(2)+u\" \"+m.group(4)+u\" \"+m.group(6)+u\"-\"+m.group(8)+u\"-\"+m.group(10)  if (m.group(1) == u\">\"  or  m.group(11) == u\"<\")  else self.tag(m.group(2)+u\" \"+m.group(4)+u\" \"+m.group(6)+u\"-\"+m.group(8)+u\"-\"+m.group(10), u\"span\", {u\"class\":u\"nowrap\"})  ))+m.group(11)"
                ]
            },
            u"ip_address": {
                u"description": u"Объединение IP-адресов",
                u"pattern": u"/(\\s|\\&nbsp\\;|^)(\\d{0,3}\\.\\d{0,3}\\.\\d{0,3}\\.\\d{0,3})/ie",
                u"replacement": u"m.group(1) + self.nowrap_ip_address(m.group(2))"
            },
            u"spaces_nobr_in_surname_abbr": {
                u"description": u"Привязка инициалов к фамилиям",
                u"pattern": [
                    u"/(\\s|^|\\.|\\,|\\;|\\:|\\?|\\!|\\&nbsp\\;)([A-ZА-ЯЁ])\\.?(\\s|\\&nbsp\\;)?([A-ZА-ЯЁ])(\\.(\\s|\\&nbsp\\;)?|(\\s|\\&nbsp\\;))([A-ZА-ЯЁ][a-zа-яё]+)(\\s|$|\\.|\\,|\\;|\\:|\\?|\\!|\\&nbsp\\;)/ue",
                    u"/(\\s|^|\\.|\\,|\\;|\\:|\\?|\\!|\\&nbsp\\;)([A-ZА-ЯЁ][a-zа-яё]+)(\\s|\\&nbsp\\;)([A-ZА-ЯЁ])\\.?(\\s|\\&nbsp\\;)?([A-ZА-ЯЁ])\\.?(\\s|$|\\.|\\,|\\;|\\:|\\?|\\!|\\&nbsp\\;)/ue"
                ],
                u"replacement": [
                    u"m.group(1)+self.tag(m.group(2)+u\". \"+m.group(4)+u\". \"+m.group(8), u\"span\",  {u\"class\" : u\"nowrap\"})+m.group(9)",
                    u"m.group(1)+self.tag(m.group(2)+u\" \"+m.group(4)+u\". \"+m.group(6)+u\".\", u\"span\",  {u\"class\" : u\"nowrap\"})+m.group(7)"
                ]
            },
            u"nbsp_before_particle": {
                u"description": u"Неразрывный пробел перед частицей",
                u"pattern": u"/(\\040|\\t)+(ли|бы|б|же|ж)(\\&nbsp\\;|\\.|\\,|\\:|\\;|\\&hellip\\;|\\?|\\s)/iue",
                u"replacement": u"u\"&nbsp;\"+m.group(2) + (( u\" \"  if m.group(3) == u\"&nbsp;\"  else  m.group(3)))"
            },
            u"nbsp_v_kak_to": {
                u"description": u"Неразрывный пробел в как то",
                u"pattern": u"/как то\\:/ui",
                u"replacement": u"как&nbsp;то:"
            },
            u"nbsp_celcius": {
                u"description": u"Привязка градусов к числу",
                u"pattern": u"/(\\s|^|\\>|\\&nbsp\\;)(\\d+)( |\\&nbsp\\;)?(°|\\&deg\\;)(C|С)(\\s|\\.|\\!|\\?|\\,|$|\\&nbsp\\;|\\;)/iu",
                u"replacement": u"\\1\\2&nbsp;\\4C\\6"
            },
            u"hyphen_nowrap_in_small_words": {
                u"description": u"Обрамление пятисимвольных слов разделенных дефисом в неразрывные блоки",
                u"disabled": True,
                u"cycled": True,
                u"pattern": u"/(\\&nbsp\\;|\\s|\\>|^)([a-zа-яё]{1}\\-[a-zа-яё]{4}|[a-zа-яё]{2}\\-[a-zа-яё]{3}|[a-zа-яё]{3}\\-[a-zа-яё]{2}|[a-zа-яё]{4}\\-[a-zа-яё]{1}|когда\\-то|кое\\-как|кой\\-кого|вс[её]\\-таки|[а-яё]+\\-(кась|ка|де))(\\s|\\.|\\,|\\!|\\?|\\&nbsp\\;|\\&hellip\\;|$)/uie",
                u"replacement": u"m.group(1) + self.tag(m.group(2), u\"span\", {u\"class\":u\"nowrap\"}) + m.group(4)"
            },
            u"hyphen_nowrap": {
                u"description": u"Отмена переноса слова с дефисом",
                u"disabled": True,
                u"cycled": True,
                u"pattern": u"/(\\&nbsp\\;|\\s|\\>|^)([a-zа-яё]+)((\\-([a-zа-яё]+)){1,2})(\\s|\\.|\\,|\\!|\\?|\\&nbsp\\;|\\&hellip\\;|$)/uie",
                u"replacement": u"m.group(1) + self.tag(m.group(2)+m.group(3), u\"span\", {u\"class\":u\"nowrap\"}) + m.group(6)"
            }
        }
        self.rule_order = [
            u"super_nbsp",
            u"nbsp_in_the_end",
            u"phone_builder",
            u"ip_address",
            u"spaces_nobr_in_surname_abbr",
            u"nbsp_before_particle",
            u"nbsp_v_kak_to",
            u"nbsp_celcius",
            u"hyphen_nowrap_in_small_words",
            u"hyphen_nowrap"
        ]
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


    # * Объединение IP-адрессов в неразрывные конструкции (IPv4 only)
    # *
    # * @param unknown_type $triads
    # * @return unknown
    def nowrap_ip_address(self, triads):        
        triad = triads.split('.')
        addTag = True
        
        for value in triad:
            value = int(value)
            if (value > 255):
                addTag = false
                break
        
        if (addTag == True):
            triads = self.tag(triads, 'span', {'class': "nowrap"})
        
        return triads


#######################################################
# EMT_Tret_Date
#######################################################
class EMT_Tret_Date(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Даты и дни"

        self.rules = {
            u"years": {
                u"description": u"Установка тире и пробельных символов в периодах дат",
                u"pattern": u"/(с|по|период|середины|начала|начало|конца|конец|половины|в|между|\\([cс]\\)|\\&copy\\;)(\\s+|\\&nbsp\\;)([\\d]{4})(-|\\&mdash\\;|\\&minus\\;)([\\d]{4})(( |\\&nbsp\\;)?(г\\.г\\.|гг\\.|гг|г\\.|г)([^а-яёa-z]))?/eui",
                u"replacement": u"m.group(1)+m.group(2)+  (( m.group(3)+m.group(4)+m.group(5)  if int(m.group(3))>=int(m.group(5)) else  m.group(3)+u\"&mdash;\"+m.group(5))) + (( u\"&nbsp;гг.\" if (m.group(6)) else u\"\"))+((m.group(9) if (m.group(9)) else u\"\"))"
            },
            u"mdash_month_interval": {
                u"description": u"Расстановка тире и объединение в неразрывные периоды месяцев",
                u"disabled": True,
                u"pattern": u"/((январ|феврал|сентябр|октябр|ноябр|декабр)([ьяюе]|[её]м)|(апрел|июн|июл)([ьяюе]|ем)|(март|август)([ауе]|ом)?|ма[йяюе]|маем)\\-((январ|феврал|сентябр|октябр|ноябр|декабр)([ьяюе]|[её]м)|(апрел|июн|июл)([ьяюе]|ем)|(март|август)([ауе]|ом)?|ма[йяюе]|маем)/iu",
                u"replacement": u"\\1&mdash;\\8"
            },
            u"nbsp_and_dash_month_interval": {
                u"description": u"Расстановка тире и объединение в неразрывные периоды дней",
                u"disabled": True,
                u"pattern": u"/([^\\>]|^)(\\d+)(\\-|\\&minus\\;|\\&mdash\\;)(\\d+)( |\\&nbsp\\;)(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)([^\\<]|$)/ieu",
                u"replacement": u"m.group(1)+self.tag(m.group(2)+u\"&mdash;\"+m.group(4)+u\" \"+m.group(6),u\"span\", {u\"class\":u\"nowrap\"})+m.group(7)"
            },
            u"nobr_year_in_date": {
                u"description": u"Привязка года к дате",
                u"pattern": [
                    u"/(\\s|\\&nbsp\\;)([0-9]{2}\\.[0-9]{2}\\.([0-9]{2})?[0-9]{2})(\\s|\\&nbsp\\;)?г(\\.|\\s|\\&nbsp\\;)/eiu",
                    u"/(\\s|\\&nbsp\\;)([0-9]{2}\\.[0-9]{2}\\.([0-9]{2})?[0-9]{2})(\\s|\\&nbsp\\;|\\.(\\s|\\&nbsp\\;|$)|$)/eiu"
                ],
                u"replacement": [
                    u"m.group(1)+self.tag(m.group(2)+u\" г.\",u\"span\", {u\"class\":u\"nowrap\"})+((u\"\" if m.group(5)==u\".\" else u\" \"))",
                    u"m.group(1)+self.tag(m.group(2),u\"span\", {u\"class\":u\"nowrap\"})+m.group(4)"
                ]
            },
            u"space_posle_goda": {
                u"description": u"Пробел после года",
                u"pattern": u"/(^|\\040|\\&nbsp\\;)([0-9]{3,4})(год([ауе]|ом)?)([^a-zа-яё]|$)/ui",
                u"replacement": u"\\1\\2 \\3\\5"
            },
            u"nbsp_posle_goda_abbr": {
                u"description": u"Пробел после года",
                u"pattern": u"/(^|\\040|\\&nbsp\\;|\\\"|\\&laquo\\;)([0-9]{3,4})[ ]?(г\\.)([^a-zа-яё]|$)/ui",
                u"replacement": u"\\1\\2&nbsp;\\3\\4"
            }
        }
        self.rule_order = [
            u"years",
            u"mdash_month_interval",
            u"nbsp_and_dash_month_interval",
            u"nobr_year_in_date",
            u"space_posle_goda",
            u"nbsp_posle_goda_abbr"
        ]
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


#######################################################
# EMT_Tret_OptAlign
#######################################################
class EMT_Tret_OptAlign(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Оптическое выравнивание"

        self.rules = {
            u"oa_oquote": {
                u"description": u"Оптическое выравнивание открывающей кавычки",
                u"pattern": [
                    u"/([a-zа-яё\\-]{3,})(\\040|\\&nbsp\\;|\\t)(\\&laquo\\;)/uie",
                    u"/(\\n|\\r|^)(\\&laquo\\;)/ei"
                ],
                u"replacement": [
                    u"m.group(1) + self.tag(m.group(2), u\"span\", {u\"class\":u\"oa_oqoute_sp_s\"}) + self.tag(m.group(3), u\"span\", {u\"class\":u\"oa_oqoute_sp_q\"})",
                    u"m.group(1) + self.tag(m.group(2), u\"span\", {u\"class\":u\"oa_oquote_nl\"})"
                ]
            },
            u"oa_oquote_extra": {
                u"description": u"Оптическое выравнивание кавычки",
                u"function": u"oaquote_extra"
            },
            u"oa_obracket_coma": {
                u"description": u"Оптическое выравнивание для пунктуации (скобка и запятая)",
                u"pattern": [
                    u"/(\\040|\\&nbsp\\;|\\t)\\(/ei",
                    u"/(\\n|\\r|^)\\(/ei",
                    u"/([а-яёa-z0-9]+)\\,(\\040+)/iue"
                ],
                u"replacement": [
                    u"self.tag(m.group(1), u\"span\", {u\"class\":u\"oa_obracket_sp_s\"}) + self.tag(u\"(\", u\"span\", {u\"class\":u\"oa_obracket_sp_b\"})",
                    u"m.group(1) + self.tag(u\"(\", u\"span\", {u\"class\":u\"oa_obracket_nl_b\"})",
                    u"m.group(1) + self.tag(u\",\", u\"span\", {u\"class\":u\"oa_comma_b\"}) + self.tag(u\" \", u\"span\", {u\"class\":u\"oa_comma_e\"})"
                ]
            }
        }
        self.rule_order = [
            u"oa_oquote",
            u"oa_oquote_extra",
            u"oa_obracket_coma"
        ]
        self.classes = {
            u"oa_obracket_sp_s": u"margin-right:0.3em;",
            u"oa_obracket_sp_b": u"margin-left:-0.3em;",
            u"oa_obracket_nl_b": u"margin-left:-0.3em;",
            u"oa_comma_b": u"margin-right:-0.2em;",
            u"oa_comma_e": u"margin-left:0.2em;",
            u"oa_oquote_nl": u"margin-left:-0.44em;",
            u"oa_oqoute_sp_s": u"margin-right:0.44em;",
            u"oa_oqoute_sp_q": u"margin-left:-0.44em;"
        };


#######################################################
# EMT_Tret_Etc
#######################################################
class EMT_Tret_Etc(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Прочее"

        self.rules = {
            u"acute_accent": {
                u"description": u"Акцент",
                u"pattern": u"/(у|е|ы|а|о|э|я|и|ю|ё)\\`(\\w)/i",
                u"replacement": u"\\1&#769;\\2"
            },
            u"word_sup": {
                u"description": u"Надстрочный текст после символа ^",
                u"pattern": u"/((\\s|\\&nbsp\\;|^)+)\\^([a-zа-яё0-9\\.\\:\\,\\-]+)(\\s|\\&nbsp\\;|$|\\.$)/ieu",
                u"replacement": u"u\"\" + self.tag(self.tag(m.group(3),u\"small\"),u\"sup\") + m.group(4)"
            },
            u"century_period": {
                u"description": u"Тире между диапозоном веков",
                u"pattern": u"/(\\040|\\t|\\&nbsp\\;|^)([XIV]{1,5})(-|\\&mdash\\;)([XIV]{1,5})(( |\\&nbsp\\;)?(в\\.в\\.|вв\\.|вв|в\\.|в))/eu",
                u"replacement": u"m.group(1) +self.tag(m.group(2)+u\"&mdash;\"+m.group(4)+u\" вв.\",u\"span\", {u\"class\":u\"nowrap\"})"
            },
            u"time_interval": {
                u"description": u"Тире и отмена переноса между диапозоном времени",
                u"pattern": u"/([^\\d\\>]|^)([\\d]{1,2}\\:[\\d]{2})(-|\\&mdash\\;|\\&minus\\;)([\\d]{1,2}\\:[\\d]{2})([^\\d\\<]|$)/eui",
                u"replacement": u"m.group(1) + self.tag(m.group(2)+u\"&mdash;\"+m.group(4),u\"span\", {u\"class\":u\"nowrap\"})+m.group(5)"
            },
            u"expand_no_nbsp_in_nobr": {
                u"description": u"Удаление nbsp в nobr/nowrap тэгах",
                u"function": u"remove_nbsp"
            }
        }
        self.rule_order = [
            u"acute_accent",
            u"word_sup",
            u"century_period",
            u"time_interval",
            u"expand_no_nbsp_in_nobr"
        ]
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };


    def remove_nbsp(self):
        thetag = self.tag(u"###", u'span', {u'class': u"nowrap"})
        arr = thetag.split(u"###")
        b = re.escape(arr[0])
        e = re.escape(arr[1])
        
        match = u'/(^|[^a-zа-яё])([a-zа-яё]+)\&nbsp\;(' + b + u')/iu'
        p = EMT_Lib.parse_preg_pattern(match)
        while (True):
            self._text = EMT_Lib.preg_replace(match, u"\\1\\3\\2 ", self._text)
            if not (re.match(p['pattern'], self._text, p['flags'])):
                break

        match = u'/(' + e + u')\&nbsp\;([a-zа-яё]+)($|[^a-zа-яё])/iu'
        p = EMT_Lib.parse_preg_pattern(match)
        while (True):
            self._text = EMT_Lib.preg_replace(match, u" \\2\\1\\3", self._text)
            if not (re.match(p['pattern'], self._text, p['flags'])):
                break
        
        self._text = EMT_Lib.preg_replace(u'/' + b + u'.*?' + e + u'/iue', u'EMT_Lib.str_replace("&nbsp;"," ",m.group(0))' , self._text )


#######################################################
# EMT_Tret_Text
#######################################################
class EMT_Tret_Text(EMT_Tret):

    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "Текст и абзацы"

        self.rules = {
            u"auto_links": {
                u"description": u"Выделение ссылок из текста",
                u"pattern": u"/(\\s|^)(http|ftp|mailto|https)(:\\/\\/)([^\\s\\,\\!\\<]{4,})(\\s|\\.|\\,|\\!|\\?|\\<|$)/ieu",
                u"replacement": u"m.group(1) + self.tag(((EMT_Lib.substr(m.group(4),0,-1) if EMT_Lib.substr(m.group(4),-1)==u\".\" else m.group(4))), u\"a\", {u\"href\" : m.group(2)+m.group(3)+((EMT_Lib.substr(m.group(4),0,-1) if EMT_Lib.substr(m.group(4),-1)==u\".\" else m.group(4)))}) + ((u\".\" if EMT_Lib.substr(m.group(4),-1)==u\".\" else u\"\")) +m.group(5)"
            },
            u"email": {
                u"description": u"Выделение эл. почты из текста",
                u"pattern": u"/(\\s|^|\\&nbsp\\;|\\()([a-z0-9\\-\\_\\.]{2,})\\@([a-z0-9\\-\\.]{2,})\\.([a-z]{2,6})(\\)|\\s|\\.|\\,|\\!|\\?|$|\\<)/e",
                u"replacement": u"m.group(1) + self.tag(m.group(2)+u\"@\"+m.group(3)+u\".\"+m.group(4), u\"a\", {u\"href\" : u\"mailto:\"+m.group(2)+u\"@\"+m.group(3)+u\".\"+m.group(4)}) + m.group(5)"
            },
            u"no_repeat_words": {
                u"description": u"Удаление повторяющихся слов",
                u"disabled": True,
                u"pattern": [
                    u"/([а-яё]{3,})( |\\t|\\&nbsp\\;)\\1/iu",
                    u"/(\\s|\\&nbsp\\;|^|\\.|\\!|\\?)(([А-ЯЁ])([а-яё]{2,}))( |\\t|\\&nbsp\\;)(([а-яё])\\4)/eu"
                ],
                u"replacement": [
                    u"\\1",
                    u"m.group(1)+(( m.group(2)  if m.group(7) == EMT_Lib.strtolower(m.group(3))  else  m.group(2)+m.group(5)+m.group(6) ))"
                ]
            },
            u"paragraphs": {
                u"description": u"Простановка параграфов",
                u"function": u"build_paragraphs"
            },
            u"breakline": {
                u"description": u"Простановка переносов строк",
                u"function": u"build_brs"
            }
        }
        self.rule_order = [
            u"auto_links",
            u"email",
            u"no_repeat_words",
            u"paragraphs",
            u"breakline"
        ]
        self.classes = {
            u"nowrap": u"word-spacing:nowrap;"
        };



    def do_paragraphs(self, text):
        text = EMT_Lib.str_replace(u"\r\n",u"\n",text)
        text = EMT_Lib.str_replace(u"\r",u"\n",text)
        text = u'<' + BASE64_PARAGRAPH_TAG + u'>' + text.strip() + u'</' + BASE64_PARAGRAPH_TAG + u'>'
        text = self.preg_replace('/([\040\t]+)?(\n)+([\040\t]*)(\n)+/e', '(u"" if m.group(1) is None else m.group(1))+u"</" + BASE64_PARAGRAPH_TAG + u">"+EMT_Lib.iblock(m.group(2)+m.group(3))+u"<" +BASE64_PARAGRAPH_TAG + u">"', text)
        text = self.preg_replace('/\<' + BASE64_PARAGRAPH_TAG + '\>(' + INTERNAL_BLOCK_OPEN + '[a-zA-Z0-9\/=]+?' + INTERNAL_BLOCK_CLOSE + ')?\<\/' + BASE64_PARAGRAPH_TAG + '\>/s', "", text)
        return text
        
        
    def build_paragraphs(self):
        r = self._text.find( u'<' + BASE64_PARAGRAPH_TAG + u'>' )
        p = self._text.rfind( u'</'+  BASE64_PARAGRAPH_TAG + u'>' )    
        if(( r != -1) and (p != -1)):
            
            beg = EMT_Lib.substr(self._text,0,r);
            end = EMT_Lib.substr(self._text,p+len(u'</' + BASE64_PARAGRAPH_TAG + u'>'))           
            self._text = (self.do_paragraphs(beg)+ u"\n" if beg.strip() else u"") +u'<' + BASE64_PARAGRAPH_TAG + u'>'+EMT_Lib.substr(self._text,r + len(u'<' + BASE64_PARAGRAPH_TAG + u'>'),p -(r + len(u'<' + BASE64_PARAGRAPH_TAG + u'>')) )+u'</' + BASE64_PARAGRAPH_TAG + u'>'+( u"\n"+self.do_paragraphs(end) if end.strip() else u"") 
        else:
            self._text = self.do_paragraphs(self._text)
            
    def build_brs(self):
        self._text = self.preg_replace('/(\<\/' + BASE64_PARAGRAPH_TAG + '\>)([\r\n \t]+)(\<' + BASE64_PARAGRAPH_TAG + '\>)/mse', 'm.group(1)+EMT_Lib.iblock(m.group(2))+m.group(3)', self._text);
        
        if (not re.match('\<' + BASE64_BREAKLINE_TAG + '\>', self._text)):
            self._text = EMT_Lib.str_replace("\r\n","\n",self._text)
            self._text = EMT_Lib.str_replace("\r","\n",self._text)
            self._text = self.preg_replace('/(\n)/e', '"<" + BASE64_BREAKLINE_TAG + ">\\n"', self._text)
       











# /**
# * Evgeny Muravjev Typograph, http://mdash.ru
# * Version: 3.0 Gold Master
# * Release Date: September 28, 2013
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
        self._safe_blocks = {}	

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
        self._safe_blocks[xid] = {
                    'id' : xid,
                    'tag' : tag,
                    'open' :  xopen,
                    'close' :  close
            }
    
    # /**
    # * Список защищенных блоков
    # *
    # * @return 	array
    # */
    def get_all_safe_blocks(self):
       return self._safe_blocks
    
    # /**
    # * Удаленного блока по его номеру ключа
    # *
    # * @param 	string $id идентифиактор защищённого блока 
    # * @return  void
    # */
    def remove_safe_block(self, xid):
       del self._safe_blocks[xid]
    
    
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
            def safereplace(m):
                return m.group(1)+(EMT_Lib.encrypt_tag(m.group(2)) if True == way else EMT_Lib.decrypt_tag(m.group(2)).replace("\\n","\n").replace("\\r","\n").replace("\\",""))+m.group(3)
            for idx in self._safe_blocks:
                block = self._safe_blocks[idx]
                #text = EMT_Lib.preg_replace(u"/("+block['open']+u")(.+?)("+block['close']+u")/ue", 'm.group(1)+' + safeType + '+m.group(3)', text)
                text = re.sub(u"("+block['open']+u")(.+?)("+block['close']+u")", safereplace, text, 0, re.U)
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
    # *
    # * @param mixed $selector
    # * @param mixed $key
    # * @param mixed $value
    # */
    def set(self, selector, key , value = False):
        if isinstance(selector, (list,tuple)):
            for val in selector:
                self.set(val, key, value)
            return
        if isinstance(selector, dict):
            for x in key:
                y = key[x]
                if isinstance(value, dict):
                    kk = y
                    vv = value[x]
                else:
                    kk = x
                    vv = y
                self.set(selector, kk, vv)
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
                'Nobr.ip_address' : 'direct',
                'Nobr.spaces_nobr_in_surname_abbr' : 'direct',
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
                                    'selector' : {'Space.nbsp_before_open_quote', 'Punctmark.fix_brackets'}
                                },                            
                'Abbr.nbsp_money_abbr' : 'direct',		
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
                'Etc.unicode_convert' : {'description' : 'Преобразовывать html-сущности в юникод', 'selector' : '*', 'setting' : 'dounicode' , 'disabled' : True},
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
                self.set(self.all_options[name]['selector'], settingname, value)
        
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
