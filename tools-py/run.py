#!/usr/bin/env python2.7
# -*- coding: utf-8 -*-

import sys
import re
import base64
import types
import os.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), os.path.pardir)))
from EMT import EMTypograph
import json

def main():   
    EMT = EMTypograph()
    #EMT.set_text(u'"Эдиториум.ру" - сайт, созданный по материалам сборника "О редактировании и редакторах" Аркадия Эммануиловича Мильчина, который с 1944 года коллекционировал выдержки из статей, рассказов, фельетонов, пародий, писем и книг, где так или иначе затрагивается тема редакторской работы. Эта коллекция легла в основу обширной антологии, представляющей историю и природу редактирования в первоисточниках. ')    
    #EMT.set_text(u'<a href="https://www.lendingclub.com/">Lending Club</a>')    
    EMT.set_text(u'pr@example.ru')    
    

    result = EMT.apply()
    print result    

if __name__=="__main__":
    main()