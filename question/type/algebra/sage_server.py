#!/usr/bin/env sage -python

from SimpleXMLRPCServer import SimpleXMLRPCServer

from sage.all import Sage

s=Sage()

server=SimpleXMLRPCServer(("localhost",7777))
server.register_introspection_functions()

def full_symbolic_compare(expr1,expr2,vars):
    varstr=",".join(vars)
    print "Comparing %s to %s with variables %s" % (expr1,expr2,varstr)
    s.eval('%s=var("%s")' % (varstr,varstr))
    s.eval("_func=(%s)-(%s)" % (expr1,expr2))
    result=s.eval("_func.simplify_full()")
    if result=='0':
        print "Equal"
        return 0
    else:
        print "Not equal"
        return 1

server.register_function(full_symbolic_compare)

print full_symbolic_compare('x^2+2*x+1', 'x^2+2*x+1', ['x'])

server.serve_forever()
