# vericsv.py   Verificación de archivo .csv
#
#                                                 2023jun14-1434
#

import csv
import os
import sys
import http.cookiejar as cookiejar
import psycopg2
import yaml
from datetime import date, time, datetime
from decimal import Decimal

path_file =  sys.argv[1]

cookie_jar = cookiejar.CookieJar()
cookie = cookiejar.Cookie( version =0, name='fchsube', value='20230615-193302', port=None, port_specified=False, domain='.Armagestion', domain_specified=True, domain_initial_dot=False, path='/', path_specified=True, secure=False, expires=None, discard=True, comment=None, comment_url=None, rest=None)
def validar_archivo_csv22( entidad , archivo , totcampo ) :

   with open("/var/www/html/Armagestion/public/config.yaml" , "r") as f:
      config = yaml.safe_load( f )
 
   dbname = config["database"]["dbname"]
   dbhost = config["database"]["dbhost"]
   dbport = config["database"]["dbport"]
   dbuser = config["database"]["dbuser"]
   dbpasw = config["database"]["dbpasw"]
 
 
   conn = psycopg2.connect(database=dbname,
      user=dbuser, password=dbpasw,
      host=dbhost, port=dbport
   )
 
 
   conn.autocommit = True
   cursor = conn.cursor()
   print( entidad , " : " , archivo )
   if not os.path.isfile( archivo )  :
      print( "No existe!" )
      return False
  
   with open( archivo , newline = '' , encoding='utf-8' ) as file:
      reader = csv.reader( file )
      arre = [ row for row in reader ]
      stu = arre[0][0]
      stu = stu.replace(',', '').replace("'","")
      deli = stu.count(';')
      encabezado = stu.split(';')
      subidos = 0
      fallidos = 0
      error = ''
      tipos_validos = [
         str,         # account 
         str,         # value
         str          # date
      ]     
      if deli == 0 and totcampo > 1 :
         print( "EL DELIMITADOR DE CAMPO DEBE SER PUNTO Y COMA (;)" )
         return False
      else :
         campo = stu.split(';')
         if len(campo)!=totcampo :
            print( 'NUMERO DE COLUMNAS ERRONEO. SE REQUIEREN: '+str(totcampo)+', SE REPORTAN: '+str(len(campo))+'.' )
            return False
         else:
            for row in range(1,len(arre)):
               if len(arre[row]) == 0:
                  error += 'FILA: '+str(row+1)+' | ERROR: NO HAY DATOS. \n\n'
                  fallidos+=1
               else:
                  if len(arre[row]) > 0:
                     arre[row] = arre[row][0].replace("; ;",";;").replace(";;","; ;").split(';')                 
                  if len(arre[row]) < totcampo or len(arre[row]) > totcampo:  
                     error += 'FILA: '+str(row+1)+' | ERROR: SE REQUIEREN '+str(totcampo)+' COLUMNAS, SE REPORTAN: '+str(len(arre[row]))+' COLUMNAS. \n\n'
                     fallidos+=1
                  else:
                     insert = 'INSERT INTO pagos ( "account", "value", "date") values ('
                     bol = True
                     for col in range(len(arre[row])):
                        # print(type(arre[row][col]) is tipos_validos[col])
                        # return False

                        arre[row][col] = arre[row][col].strip().replace('ñ','n').replace('á','a').replace('é','e').replace('í','i').replace('ó','o').replace('ú','u')
                        if arre[row][col] == '' or arre[row][col] is None:
                           bol = False
                           error += 'FILA: '+str(row+1)+' | COLUMNA: '+str(col+1)+' | ERROR: DATO NULL. \n'
                        elif type(arre[row][col]) is tipos_validos[col]:
                           if tipos_validos[col] is Decimal or tipos_validos[col] is int:
                              insert+= arre[row][col]+','
                           else:
                              insert+= "'"+arre[row][col]+"',"
                        else:
                           bol = False
                           error += 'FILA: '+str(row+1)+' | COLUMNA: '+str(col+1)+' | ERROR: TIPO DE DATO INVALIDO. \n'
                     if bol:
                        subidos+=1
                        insert+=");"
                        insert = insert.replace(",);",");")
                        # print(insert)
                        # return False
                        sql1 = insert
                        cursor.execute( sql1 )
                        error+= 'FILA: '+str(row+1)+' SUBIDA CON EXITO. \n\n'
                     else:
                        error+= '\n'
                        fallidos+=1

            ruta_errores = "/var/www/html/Armagestion/storage/app/errores_pago.txt"
            with open(ruta_errores, "w", encoding="utf-8") as file:
             file.write(error)

      print( "Registros :" , subidos )
      print( "Fallidos :" , fallidos )
      print(error)

   conn.commit()
   conn.close()

if __name__ == '__main__' :
    validar_archivo_csv22('Prueba','/var/www/html/Armagestion/storage/app/'+path_file , 3 )
