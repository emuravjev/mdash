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

tests = json.loads(open('../tests/tests.json').read(100000000))

#main
def main():
    
    total = 0
    passed = 0
    for test in tests:
        EMT = EMTypograph()
        if test['title'] == u'Минус в диапозоне чисел':
            x = 1
        if test.has_key('params'):
            EMT.setup(test['params'])
        if test.get('safetags'):
            x = test.get('safetags')
            if isinstance(x, basestring):
                x = [x]
            for s in x:
                EMT.add_safe_tag(s)
        EMT.set_text(test['text'])
        
        passx = 0
        result = EMT.apply()
        if result == test['result']:
            r = u"OK     "            
            passx += 1
        else:
            r = u"FAIL   "
        print (r + test['title'] + u'' )
        if result != test['result']:
            print(u"    GOT:   "+result + u'')
            print(u"    NEED:  "+test['result'] + u'')
        
        del EMT
                
        total +=1
        if passx>=1:
            passed += 1
        
    print u'Total tests : '+str(total)
    print u'Passed tests: '+str(passed)
    print u'Failed tests: '+str(total-passed)
    return

if __name__=="__main__":
    main()